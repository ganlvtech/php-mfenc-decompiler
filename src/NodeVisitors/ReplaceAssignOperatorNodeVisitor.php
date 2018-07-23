<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeVisitorAbstract;

class ReplaceAssignOperatorNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Assign
            && $node->expr instanceof BinaryOp
            && $node->expr->left instanceof Variable
            && $node->expr->left->name === $node->var->name) {
            $type = $node->expr->getType(); // Expr_BinaryOp_BooleanOr
            if (0 === strpos($type, 'Expr_BinaryOp_')) {
                $class = '\PhpParser\Node\Expr\AssignOp\\' . substr($type,strlen('Expr_BinaryOp_'));
                $new = new $class($node->var, $node->expr->right);
                return $new;
            }
        }
        return parent::leaveNode($node);
    }
}
