<?php

namespace Ganlv\MfencDecompiler;

use Ganlv\MfencDecompiler\NodeVisitors\ReplaceAssignInWhileConditionNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\ReplaceAssignOperatorNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\ReplaceBooleanOperatorNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\ReplaceElseIfNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\ReplaceListAssignNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\ReplaceNameLiteralStringNodeVisitor;

class Beautifier
{
    /**
     * Must before ReplaceNameLiteralString
     *
     * @param $ast
     *
     * @return array|\PhpParser\Node[]
     */
    public static function astReplaceAssignInWhileCondition($ast)
    {
        $nodeVisitor = new ReplaceAssignInWhileConditionNodeVisitor();
        return Helper::traverseAst($nodeVisitor, $ast);
    }

    public static function astReplaceBooleanOperator($ast)
    {
        $nodeVisitor = new ReplaceBooleanOperatorNodeVisitor();
        return Helper::traverseAst($nodeVisitor, $ast);
    }

    public static function astReplaceElseIf($ast)
    {
        $nodeVisitor = new ReplaceElseIfNodeVisitor();
        return Helper::traverseAst($nodeVisitor, $ast);
    }

    public static function astReplaceNameLiteralString($ast)
    {
        $nodeVisitor = new ReplaceNameLiteralStringNodeVisitor();
        return Helper::traverseAst($nodeVisitor, $ast);
    }

    public static function astReplaceListAssign($ast)
    {
        $nodeVisitor = new ReplaceListAssignNodeVisitor();
        return Helper::traverseAst($nodeVisitor, $ast);
    }

    /**
     * Must after ReplaceNameLiteralString
     *
     * @param $ast
     *
     * @return array|\PhpParser\Node[]
     */
    public static function astReplaceAssignOperator($ast)
    {
        $nodeVisitor = new ReplaceAssignOperatorNodeVisitor();
        return Helper::traverseAst($nodeVisitor, $ast);
    }

    public static function beautify($ast)
    {
        $ast = self::astReplaceAssignInWhileCondition($ast);
        $ast = self::astReplaceBooleanOperator($ast);
        $ast = self::astReplaceElseIf($ast);
        $ast = self::astReplaceNameLiteralString($ast);
        $ast = self::astReplaceListAssign($ast);
        $ast = self::astReplaceAssignOperator($ast);
        return $ast;
    }
}