<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
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

class ReplaceNameLiteralStringNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof MethodCall && $node->name instanceof String_) {
            $node->name = new Identifier($node->name->value);
        }
        if ($node instanceof StaticCall && $node->class instanceof String_ && $node->name instanceof String_) {
            $node->class = new Name($node->class->value);
            $node->name = new Identifier($node->name->value);
        }
        if ($node instanceof FuncCall && $node->name instanceof String_) {
            $node->name = new Name($node->name->value);
        }
        if ($node instanceof PropertyFetch && $node->name instanceof String_) {
            $node->name = new Identifier($node->name->value);
        }
        if ($node instanceof StaticPropertyFetch && $node->class instanceof String_ && $node->name instanceof String_) {
            $node->class = new Name($node->class->value);
            $node->name = new VarLikeIdentifier($node->name->value);
        }
        if ($node instanceof Variable && $node->name instanceof String_) {
            $node->name = $node->name->value;
        }
        if ($node instanceof New_ && $node->class instanceof String_) {
            $node->class = new Name($node->class->value);
        }
        return parent::leaveNode($node);
    }
}
