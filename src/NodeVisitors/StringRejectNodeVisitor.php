<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class StringRejectNodeVisitor extends NodeVisitorAbstract
{
    public $largeStringData = [];

    public function leaveNode(Node $node)
    {
        if ($node instanceof String_) {
            if (strlen($node->value) > 150) {
                $this->largeStringData[] = $node->value;
                return new ArrayDimFetch(
                    new ArrayDimFetch(new Variable('GLOBALS'), new String_('LARGE_STRING_DATA')),
                    new LNumber(count($this->largeStringData) - 1)
                );
            }
        }
        return parent::leaveNode($node);
    }
}
