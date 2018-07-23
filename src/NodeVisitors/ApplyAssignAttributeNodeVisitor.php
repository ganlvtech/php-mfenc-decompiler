<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use Ganlv\MfencDecompiler\BaseDecompiler;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\NodeVisitorAbstract;

class ApplyAssignAttributeNodeVisitor extends NodeVisitorAbstract
{
    public $largeStringData = [];

    public function leaveNode(Node $node)
    {
        if ($node instanceof Expr) {
            // $assign = BaseDecompiler::getAssign($node);
            // if ($assign) {
            //     BaseDecompiler::setAssign($node, null);
            //     assert($assign->var === $node);
            //     return $assign;
            // }
            $assign = BaseDecompiler::getAssignTo($node);
            if ($assign) {
                BaseDecompiler::setAssignTo($node, null);
                assert($assign->expr === $node);
                return $assign;
            }
            $list = BaseDecompiler::getAssignToList($node);
            if ($list) {
                BaseDecompiler::setAssignToList($node, null);
                BaseDecompiler::used($list);
                $assign = new Assign($list, $node);
                return $assign;
            }
        }
        return $node;
    }
}
