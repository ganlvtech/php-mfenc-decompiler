<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\NodeVisitorAbstract;

class ReplaceListAssignNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Assign && $node->var instanceof List_) {
            $items = [];
            /** @var \PhpParser\Node\Expr\ArrayItem $item */
            foreach ($node->var->items as $item) {
                $items[$item->key->value] = $item;
            }
            ksort($items);
            $node->var->items = [];
            $i = 0;
            foreach ($items as $key => $item) {
                assert($key === $i);
                $item->key = null;
                $node->var->items[] = $item;
                ++$i;
            }
        }
        return parent::leaveNode($node);
    }
}
