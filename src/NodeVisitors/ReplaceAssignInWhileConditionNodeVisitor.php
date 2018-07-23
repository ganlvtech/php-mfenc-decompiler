<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeVisitorAbstract;

class ReplaceAssignInWhileConditionNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof While_
            && $node->cond instanceof ConstFetch
            && $node->cond->name instanceof Name
            && count($node->cond->name->parts) === 1
            && $node->cond->name->parts[0] = 'true'
        ) {
            if (count($node->stmts) >= 2
                && $node->stmts[0] instanceof Expression
                && $node->stmts[0]->expr instanceof Assign
                && $node->stmts[1] instanceof If_
                && count($node->stmts[1]->stmts) === 0
                && $node->stmts[1]->else instanceof Else_
                && count($node->stmts[1]->else->stmts) === 1
                && $node->stmts[1]->else->stmts[count($node->stmts[1]->else->stmts) - 1] instanceof Break_
                && $node->stmts[0]->expr->expr === $node->stmts[1]->cond) {
                // while (true) { $foo = bar(); if (bar()) { } else { break; } statements; }
                $node->cond = $node->stmts[0]->expr;
                $node->stmts = array_slice($node->stmts, 2);
            } elseif (count($node->stmts) >= 1
                && $node->stmts[0] instanceof If_
                && count($node->stmts[0]->stmts) === 0
                && $node->stmts[0]->else instanceof Else_
                && count($node->stmts[0]->else->stmts) === 1
                && $node->stmts[0]->else->stmts[count($node->stmts[0]->else->stmts) - 1] instanceof Break_) {
                // while (true) { if ($foo = bar()) { } else { break; } statements; }
                $node->cond = $node->stmts[0]->cond;
                $node->stmts = array_slice($node->stmts, 1);
            } elseif (count($node->stmts) >= 1
                && $node->stmts[count($node->stmts) - 1] instanceof If_
                && count($node->stmts[count($node->stmts) - 1]->stmts) === 0
                && $node->stmts[count($node->stmts) - 1]->else instanceof Else_
                && count($node->stmts[count($node->stmts) - 1]->else->stmts) === 1
                && $node->stmts[count($node->stmts) - 1]->else->stmts[count($node->stmts[count($node->stmts) - 1]->else->stmts) - 1] instanceof Break_) {
                // while (true) { statements; if ($foo = bar()) { } else { break; } }
                $stmts = array_slice($node->stmts, 0, count($node->stmts) - 1);
                $do = new Do_($node->stmts[count($node->stmts) - 1]->cond, $stmts);
                $do->setAttribute('instruction', $node->getAttribute('instruction'));
                return $do;
            }
        }
        return parent::leaveNode($node);
    }
}
