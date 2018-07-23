<?php

namespace Ganlv\MfencDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class VariableRenameNodeVisitor extends NodeVisitorAbstract
{
    protected $variablesMap = [];

    public function __construct($variablesMap)
    {
        $this->variablesMap = $variablesMap;
    }

    protected function generateNewVariableName()
    {
        $values = array_values($this->variablesMap);
        $i = 0;
        while (in_array('v' . $i, $values)) {
            ++$i;
        }
        return 'v' . $i;
    }

    protected static function isUnreadable($name)
    {
        return 1 !== preg_match('/^\\w+$/', $name);
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\Variable) {
            if (is_string($node->name)) {
                if (self::isUnreadable($node->name)) {
                    if (!isset($this->variablesMap[$node->name])) {
                        $this->variablesMap[$node->name] = $this->generateNewVariableName();
                    }
                    $node->name = $this->variablesMap[$node->name];
                }
            }
        }
        return parent::leaveNode($node);
    }

    public function getVariablesMap()
    {
        return $this->variablesMap;
    }
}
