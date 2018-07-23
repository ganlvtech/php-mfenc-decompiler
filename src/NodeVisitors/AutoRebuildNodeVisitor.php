<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use Ganlv\MfencDecompiler\AutoDecompiler;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeVisitorAbstract;

class AutoRebuildNodeVisitor extends NodeVisitorAbstract
{
    public $largeStringData = [];
    public $variablesMap = [];

    public function beforeTraverse(array $nodes)
    {
        return AutoDecompiler::decompileMain($nodes);
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof FunctionLike) {
            return AutoDecompiler::decompileFunctionLike($node);
        }
        return parent::enterNode($node);
    }
}