<?php

namespace Ganlv\MfencDecompiler;

class Disassembler2
{
    public $vmVariables = [
        'stack' => 'v1',
        'error_level_stack' => 'v2',
        'esp' => 'v3',
        'error_level_stack_pointer' => 'v4',
        'eip' => 'v5',
        'memory' => 'v6',
        'temp_str' => 'v7',
        'temp' => 'v8',
    ];
    public $vmVariablesSearch = [];
    public $vmVariablesReplace = [];
    public $instructions = [];

    public function __construct($vmVariables)
    {
        $this->instructions = include __DIR__ . '/instructions.php';
        $this->prepareReplace($vmVariables);
    }

    protected function prepareReplace($vmVariables)
    {
        $this->vmVariables = $vmVariables;
        uasort($vmVariables, function ($a, $b) {
            // 长的字符串先匹配
            return strlen($b) - strlen($a);
        });
        $this->vmVariablesSearch = array_map(function ($value) {
            return '$' . $value;
        }, array_values($vmVariables));
        $this->vmVariablesReplace = array_map(function ($value) {
            return '$' . $value;
        }, array_keys($vmVariables));
    }

    protected function doReplace($code)
    {
        return str_replace($this->vmVariablesSearch, $this->vmVariablesReplace, $code);
    }

    public function parseEvalCode($code)
    {
        $readableCode = $this->doReplace($code);
        $ast = Helper::parseExprCode($readableCode);
        foreach ($this->instructions as $operation => &$func) {
            $result = $func($ast, $this->vmVariables);
            if (is_array($result)) {
                return Helper::buildInstruction($operation, $result);
            }
        }
        return Helper::buildInstruction('eval_2', [$readableCode]);
    }
}