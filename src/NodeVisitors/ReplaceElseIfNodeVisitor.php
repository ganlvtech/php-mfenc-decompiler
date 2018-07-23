<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitorAbstract;

class ReplaceElseIfNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        // if (cond) { } else { statements; }
        // => if (!cond) { statements; }
        if ($node instanceof If_
            && count($node->stmts) === 0
            && !is_null($node->else)) {
            if ($node->cond instanceof BooleanNot) {
                $node->cond = $node->cond->expr;
            } else {
                $node->cond = new BooleanNot($node->cond);
            }
            $node->stmts = $node->else->stmts;
            $node->else = null;
        }
        // if (cond) { statements; } else { }
        // => if (cond) { statements; }
        if ($node instanceof If_
            && $node->else instanceof Else_
            && count($node->else->stmts) === 0) {
            $node->else = null;
        }
        // if (cond) { statements; } else { if (cond) { statements; } }
        // => if (cond) { statements; } elseif (cond) { statements; }
        if ($node instanceof If_
            && !is_null($node->else)
            && count($node->else->stmts) === 1
            && $node->else->stmts[0] instanceof If_) {
            $node->elseifs = array_merge(
                [new ElseIf_($node->else->stmts[0]->cond, $node->else->stmts[0]->stmts)],
                $node->else->stmts[0]->elseifs
            );
            $node->else = $node->else->stmts[0]->else;
        }
        return parent::leaveNode($node);
    }
}
