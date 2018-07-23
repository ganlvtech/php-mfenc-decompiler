<?php

namespace Ganlv\MfencDecompiler;

use Ganlv\MfencDecompiler\NodeVisitors\StringConcatNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\StringRejectNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\VariableRenameNodeVisitor;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Formatter
{
    protected $variablesMap = [];
    protected $largeStringData = [];

    public function renameVariable($ast)
    {
        $traverser = new NodeTraverser();
        $variableRenameNodeVisitor = new VariableRenameNodeVisitor($this->variablesMap);
        $traverser->addVisitor($variableRenameNodeVisitor);
        $ast = $traverser->traverse($ast);
        $this->variablesMap = $variableRenameNodeVisitor->getVariablesMap();
        return $ast;
    }

    public function rejectLargeString($ast)
    {
        $traverser = new NodeTraverser();
        $stringRejectNodeVisitor = new StringRejectNodeVisitor();
        $traverser->addVisitor($stringRejectNodeVisitor);
        $ast = $traverser->traverse($ast);
        $this->largeStringData = $stringRejectNodeVisitor->largeStringData;
        return $ast;
    }

    public function includeLargeString($ast, $path)
    {
        array_unshift($ast, new Expression(new Assign(
            new ArrayDimFetch(new Variable('GLOBALS'), new String_('LARGE_STRING_DATA')),
            new Include_(new String_($path), Include_::TYPE_INCLUDE)
        )));
        return $ast;
    }

    public function format($code, $largeStringDataRelPath)
    {
        $ast = Helper::parseCode($code);
        $ast = $this->renameVariable($ast);
        $ast = Helper::astConcatString($ast);
        $ast = $this->rejectLargeString($ast);
        $ast = $this->includeLargeString($ast, $largeStringDataRelPath);
        return $ast;
    }

    public function getVariablesMap()
    {
        return $this->variablesMap;
    }

    public function getLargeStringData()
    {
        return $this->largeStringData;
    }
}