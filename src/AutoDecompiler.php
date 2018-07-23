<?php

namespace Ganlv\MfencDecompiler;

use Exception;
use Ganlv\MfencDecompiler\NodeVisitors\AutoRebuildNodeVisitor;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;

class AutoDecompiler
{
    public static function decompileMain($nodes)
    {
        $vmDecompiler = new VmDecompiler($nodes);
        try {
            $decompiler = $vmDecompiler->autoDecompile();
            $vmStart = $vmDecompiler->vmStart;
            $stack = $decompiler->stack;
            $ast = $decompiler->ast;
            if (!empty($stack)) {
                $ast[] = new \PhpParser\Node\Stmt\Return_(array_pop($stack));
            }
            $ast = Beautifier::beautify($ast);
            if ($ast[count($ast) - 1] instanceof \PhpParser\Node\Stmt\Return_
                && $ast[count($ast) - 1]->expr instanceof \PhpParser\Node\Expr\ConstFetch
                && $ast[count($ast) - 1]->expr->name instanceof \PhpParser\Node\Name
                && count($ast[count($ast) - 1]->expr->name->parts) === 1
                && $ast[count($ast) - 1]->expr->name->parts[0] === 'null') {
                array_pop($ast);
            }
        } catch (Exception $e) {
            echo $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString(), PHP_EOL;
            $str = Helper::printStructuredInstructions($vmDecompiler->structuredInstructions, true);
            $str = new String_($str);
            $str->setAttribute('kind', String_::KIND_NOWDOC);
            $str->setAttribute('docLabel', 'EOD');
            $ast = [
                new Expression($str),
            ];
        }
        return array_merge(
            array_slice($nodes, 0, $vmStart - 1),
            $ast,
            array_slice($nodes, $vmStart + 8)
        );
    }

    public static function decompileFunctionLike($node)
    {
        $vmDecompiler = new VmDecompiler($node->stmts);
        try {
            $decompiler = $vmDecompiler->autoDecompile();
            $stack = $decompiler->stack;
            $ast = $decompiler->ast;
            if (!empty($stack)) {
                $ast[] = new \PhpParser\Node\Stmt\Return_(array_pop($stack));
            }
            $ast = Beautifier::beautify($ast);
        } catch (Exception $e) {
            echo $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString(), PHP_EOL;
            $str = Helper::printStructuredInstructions($vmDecompiler->structuredInstructions, true);
            $str = new String_($str);
            $str->setAttribute('kind', String_::KIND_NOWDOC);
            $str->setAttribute('docLabel', 'EOD');
            $ast = [
                new Expression($str),
            ];
        }
        $node->stmts = $ast;
        return $node;
    }

    public static function autoDecompileCode($code)
    {
        return Helper::prettyPrintFile(self::autoDecompileAst(Helper::parseCode($code)));
    }

    public static function autoDecompileAst($ast)
    {
        $autoRebuildCodeNodeVisitor = new AutoRebuildNodeVisitor();
        return Helper::traverseAst($autoRebuildCodeNodeVisitor, $ast);
    }
}