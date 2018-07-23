<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\NodeVisitorAbstract;

class ReplaceBooleanOperatorNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof LogicalOr) {
            if ($node->left instanceof Bool_ && $node->right instanceof Bool_) {
                return new BooleanOr($node->left->expr, $node->right->expr);
            }
        } elseif ($node instanceof LogicalAnd) {
            if ($node->left instanceof Bool_ && $node->right instanceof Bool_) {
                return new BooleanAnd($node->left->expr, $node->right->expr);
            }
        } elseif ($node instanceof BooleanNot) {
            if ($node->expr instanceof Equal) {
                return new NotEqual($node->expr->left, $node->expr->right);
            } elseif ($node->expr instanceof Identical) {
                return new NotIdentical($node->expr->left, $node->expr->right);
            }
        }
        return parent::leaveNode($node);
    }
}
