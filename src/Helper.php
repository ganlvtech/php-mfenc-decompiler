<?php

namespace Ganlv\MfencDecompiler;

use Ganlv\MfencDecompiler\NodeDumpers\ConditionExpressionNodeDumper;
use Ganlv\MfencDecompiler\NodeVisitors\StringConcatNodeVisitor;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Helper
{
    public static function needDoubleQuoted($string)
    {
        return preg_match('/(\n|\r|\t)/', $string) === 1;
    }

    public static function tryDoubleQuoted(String_ $string)
    {
        if (self::needDoubleQuoted($string->value)) {
            $string->setAttribute('kind', \PhpParser\Node\Scalar\String_::KIND_DOUBLE_QUOTED);
        }
        return $string;
    }

    public static function var_export($value)
    {
        if (is_string($value)) {
            return self::prettyPrintExpr(self::tryDoubleQuoted(new String_($value)));
        }
        return var_export($value, true);
    }

    public static function parseCode($code)
    {
        static $parser = null;
        if (is_null($parser)) {
            $parserFactory = new ParserFactory();
            $parser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        }
        return $parser->parse($code);
    }

    public static function parseExprCode($exprCode)
    {
        return self::parseCode('<?php ' . $exprCode . ';');
    }

    public static function standardPrettyPrinter()
    {
        static $prettyPrinter = null;
        if (is_null($prettyPrinter)) {
            $prettyPrinter = new Standard();
        }
        return $prettyPrinter;
    }

    public static function prettyPrint($ast)
    {
        return self::standardPrettyPrinter()->prettyPrint($ast);
    }

    public static function prettyPrintFile($ast)
    {
        return self::standardPrettyPrinter()->prettyPrintFile($ast);
    }

    public static function prettyPrintExpr(Expr $expr)
    {
        return self::standardPrettyPrinter()->prettyPrintExpr($expr);
    }

    public static function traverseAst($nodeVisitor, $ast)
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);
        return $traverser->traverse($ast);
    }

    public static function astConcatString($ast)
    {
        $nodeVisitor = new StringConcatNodeVisitor();
        return self::traverseAst($nodeVisitor, $ast);
    }

    public static function toConditionExpression($ast)
    {
        static $dumper = null;
        if (is_null($dumper)) {
            $dumper = new ConditionExpressionNodeDumper;
        }
        return $dumper->dump($ast);
    }

    public static function isJumpInstruction($instruction)
    {
        return $instruction['operation'] === 'jnz' || $instruction['operation'] === 'jmp';
    }

    public static function buildInstruction($operation, $operands = [], $start = null, $end = null)
    {
        return compact('operation', 'operands', 'start', 'end');
    }

    public static function formatInstructionAddress($address)
    {
        return sprintf('%08X', $address);
    }

    public static function printInstruction($instruction, $printAddress = false, $return = false)
    {
        static $instructionsDisplayFormat = null;
        if (is_null($instructionsDisplayFormat)) {
            // $instructionsDisplayFormat = include __DIR__ . '/instructions_display_format.php';
            $instructionsDisplayFormat = [];
        }
        if (self::isJumpInstruction($instruction) && $instruction['operands'][0] > 0) {
            $operands = '  ' . self::formatInstructionAddress($instruction['operands'][0]);
        } elseif ($instruction['operands']) {
            $operands = '  ' . implode('  ', array_map(function ($item) {
                    return self::var_export($item, true);
                }, $instruction['operands']));
        } else {
            $operands = '';
        }

        $address = '';
        if ($printAddress) {
            if (!is_null($instruction['start'])) {
                $address .= self::formatInstructionAddress($instruction['start']) . ' ';
            }
            if (!is_null($instruction['end'])) {
                $address .= '- ' . self::formatInstructionAddress($instruction['end'] - 1) . ' ';
            }
        }

        $operation = $instruction['operation'];
        if (isset($instructionsDisplayFormat[$operation])) {
            $operation = $instructionsDisplayFormat[$operation];
        }

        $result = $address . $operation . $operands;
        if ($return) {
            return $result;
        } else {
            echo $result, PHP_EOL;
        }
    }

    public static function printInstructions($instructions, $address = true, $return = false)
    {
        $lines = [];
        foreach ($instructions as $start => $instruction) {
            $lines[] = self::printInstruction($instruction, $address, true);
        }
        if ($return === 'array') {
            return $lines;
        } else {
            $result = implode("\n", $lines);
            if ($return === false) {
                echo $result, PHP_EOL;
            } else {
                return $result;
            }
        }
    }

    /**
     * @param \Ganlv\MfencDecompiler\DirectedGraph $graph
     * @param bool $return
     *
     * @return array|string
     */
    public static function printDirectedGraph($graph, $return = false)
    {
        $lines = [];
        foreach ($graph->getVerticesId() as $eip) {
            $lines[] = self::formatInstructionAddress($eip) . ':';
            $lines[] = '<-- ' . implode(', ', array_map(function ($value) {
                    return self::formatInstructionAddress($value);
                }, $graph->getEdgeTo($eip)));
            $lines = array_merge($lines, array_map(function ($value) {
                return '    ' . $value;
            }, self::printStructuredInstructions($graph->getVertex($eip), 'array')));
            $lines[] = '--> ' . implode(', ', array_map(function ($value) {
                    return self::formatInstructionAddress($value);
                }, $graph->getEdgeFrom($eip)));
            $lines[] = '';
        }
        if ($return === 'array') {
            return $lines;
        } else {
            $result = implode("\n", $lines);
            if ($return === false) {
                echo $result, PHP_EOL;
            } else {
                return $result;
            }
        }
    }

    public static function printStructuredInstructions($instructions, $return = false)
    {
        $lines = [];
        foreach ($instructions as $instruction) {
            if (($instruction['operation'] === 'if')) {
                $lines[] = 'if';
                $lines = array_merge($lines, array_map(function ($value) {
                    return '    ' . $value;
                }, self::printStructuredInstructions($instruction['operands'][0], 'array')));
                if (!empty($instruction['operands'][1])) {
                    $lines[] = 'else';
                    $lines = array_merge($lines, array_map(function ($value) {
                        return '    ' . $value;
                    }, self::printStructuredInstructions($instruction['operands'][1], 'array')));
                }
            } elseif (($instruction['operation'] === 'while')) {
                $lines[] = 'while';
                $lines = array_merge($lines, array_map(function ($value) {
                    return '    ' . $value;
                }, self::printStructuredInstructions($instruction['operands'][0], 'array')));
                if (isset($instruction['operands'][1])) {
                    $lines[] = 'while_body';
                    $lines = array_merge($lines, array_map(function ($value) {
                        return '    ' . $value;
                    }, self::printStructuredInstructions($instruction['operands'][1], 'array')));
                }
            } elseif ($instruction['operation'] === 'loop') {
                $lines[] = 'loop';
                $lines = array_merge($lines, array_map(function ($value) {
                    return '    ' . $value;
                }, self::printStructuredInstructions($instruction['operands'][0], 'array')));
            } else {
                $lines[] = self::printInstruction($instruction, false, true);
            }
        }
        if ($return === 'array') {
            return $lines;
        } else {
            $result = implode("\n", $lines);
            if ($return === false) {
                echo $result, PHP_EOL;
            } else {
                return $result;
            }
        }
    }

    public static function printStructuredInstructionsIsUsed($instructions, $eips, $return = false)
    {
        $lines = [];
        foreach ($instructions as $instruction) {
            $used = (!is_null($instruction['start']) && in_array($instruction['start'], $eips)) || in_array($instruction['operation'], [
                'pop',
                'dereference',
                'jnz',
                'jmp',
                'push_const',
                'push_error_level',
                'if',
                'loop',
                'reset',
            ]) ? '  ' : '# ';
            if (($instruction['operation'] === 'if')) {
                $lines[] = $used . 'if';
                $lines = array_merge($lines, array_map(function ($value) use ($used) {
                    return $used . '    ' . $value;
                }, self::printStructuredInstructionsIsUsed($instruction['operands'][0], $eips, 'array')));
                if (!empty($instruction['operands'][1])) {
                    $lines[] = $used . 'else';
                    $lines = array_merge($lines, array_map(function ($value) use ($used) {
                        return $used . '    ' . $value;
                    }, self::printStructuredInstructionsIsUsed($instruction['operands'][1], $eips, 'array')));
                }
            } elseif ($instruction['operation'] === 'loop') {
                $lines[] = $used . 'loop';
                $lines = array_merge($lines, array_map(function ($value) use ($used) {
                    return $used . '    ' . $value;
                }, self::printStructuredInstructionsIsUsed($instruction['operands'][0], $eips, 'array')));
            } else {
                $lines[] = $used . self::printInstruction($instruction, false, true);
            }
        }
        if ($return === 'array') {
            return $lines;
        } else {
            $result = implode("\n", $lines);
            if ($return === false) {
                echo $result, PHP_EOL;
            } else {
                return $result;
            }
        }
    }

    public static function formatFlowchartBlockText($instructions)
    {
        $lines = self::printStructuredInstructions($instructions, 'array');
        $result = [];
        $count = 0;
        foreach ($lines as $line) {
            if ($count < 5) {
                $result[] = $line;
            } else {
                $result[] = '......';
                break;
            }
            ++$count;
        }
        if (empty($result)) {
            $result[] = '(Empty)';
        }
        return implode("\n", $result);
    }

    /**
     * @param \Ganlv\MfencDecompiler\DirectedGraph $graph
     *
     * @return string
     */
    public static function graphToFlowchart($graph)
    {
        $blocks = [
            'st=>start: Start',
        ];
        $flow = [];
        foreach ($graph->getVertices() as $eip => $instructions) {
            $formattedAddress = self::formatInstructionAddress($eip);
            $to = $graph->getEdgeFrom($eip);
            $addr = 'addr' . $formattedAddress;
            if (count($to) === 0) {
                $blocks[] = $addr . '=>operation: ' . "$formattedAddress($eip):\n" . self::formatFlowchartBlockText($instructions);
                $flow[] = 'st->' . $addr;
            } elseif (count($to) <= 1) {
                $formattedAddressTo = self::formatInstructionAddress($to[0]);
                $blocks[] = $addr . '=>operation: ' . "$formattedAddress($eip):\n" . self::formatFlowchartBlockText($instructions) . "\nTo:$formattedAddressTo({$to[0]})";
                $flow[] = $addr . '->' . 'addr' . self::formatInstructionAddress($to[0]);
            } else {
                $formattedAddressYes = self::formatInstructionAddress($to[0]);
                $formattedAddressNo = self::formatInstructionAddress($to[1]);
                $blocks[] = $addr . '=>condition: ' . "$formattedAddress($eip):\n" . self::formatFlowchartBlockText($instructions) . "\nYes:$formattedAddressYes({$to[0]})\nNo:$formattedAddressNo({$to[1]})";
                $flow[] = $addr . '(yes, bottom)->' . 'addr' . self::formatInstructionAddress($to[0]);
                $flow[] = $addr . '(no)->' . 'addr' . self::formatInstructionAddress($to[1]);
            }
        }
        return implode("\n", $blocks) . "\n\n" . implode("\n", $flow);
    }

    public static function exportArray($path, $array, $return = false)
    {
        $data = '<?php return ' . var_export($array, true) . ';';
        if ($return) {
            return $data;
        } else {
            return file_put_contents($path, $data);
        }
    }
}