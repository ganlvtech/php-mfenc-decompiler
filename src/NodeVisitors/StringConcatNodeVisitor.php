<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class StringConcatNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Concat) {
            if ($node->left instanceof String_ && $node->right instanceof String_) {
                return new String_($node->left->value . $node->right->value);
            }
        }
        return parent::leaveNode($node);
    }
}
