<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use Ganlv\MfencDecompiler\BaseDecompiler;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\NodeVisitorAbstract;

class SetAttributePopedNodeVisitor extends NodeVisitorAbstract
{
    public $largeStringData = [];

    public function leaveNode(Node $node)
    {
        BaseDecompiler::used($node);
        return $node;
    }
}
