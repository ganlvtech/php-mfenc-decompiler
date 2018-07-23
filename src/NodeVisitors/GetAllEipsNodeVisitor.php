<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class GetAllEipsNodeVisitor extends NodeVisitorAbstract
{
    public $eips = [];

    public function leaveNode(Node $node)
    {
        $instruction = $node->getAttribute('instruction');
        if ($instruction) {
            $this->eips[] = $instruction['start'];
        }
        return parent::leaveNode($node);
    }
}
