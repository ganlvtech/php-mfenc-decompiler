<?php

namespace Ganlv\MfencDecompiler;


use Ganlv\MfencDecompiler\Exceptions\DecompileFunctionBreakException;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\TryCatch;

class VmDecompiler
{
    public $ast;
    public $vmStart;
    public $vmVariables;
    public $vmMemoryData;
    public $instructions;
    /** @var \Ganlv\MfencDecompiler\DirectedGraph */
    public $graph;
    public $structuredInstructions;
    public $decompiler;

    public function __construct($ast)
    {
        $this->ast = $ast;
    }

    public static function isVmStart($ast, $i)
    {
        if ($ast[$i] instanceof Static_
            && $ast[$i + 1] instanceof If_
            && $ast[$i + 2] instanceof Expression
            && $ast[$i + 3] instanceof Expression
            && $ast[$i + 4] instanceof Expression
            && $ast[$i + 5] instanceof Expression
            && $ast[$i + 6] instanceof TryCatch) {
            return true;
        }
        return false;
    }

    public static function findVmStart($ast)
    {
        for ($i = 0; $i < count($ast) - 6; ++$i) {
            if (self::isVmStart($ast, $i)) {
                return $i;
            }
        }
        return false;
    }

    public function getVmStart()
    {
        $this->vmStart = self::findVmStart($this->ast);
        return $this->vmStart;
    }

    public static function findVmVariables($ast, $vmStart)
    {
        return [
            'stack' => $ast[$vmStart + 2]->expr->var->name,
            'error_level_stack' => $ast[$vmStart + 3]->expr->var->name,
            'esp' => $ast[$vmStart + 4]->expr->var->name,
            'error_level_stack_pointer' => $ast[$vmStart + 4]->expr->expr->var->name,
            'eip' => $ast[$vmStart + 4]->expr->expr->expr->var->name,
            'memory' => $ast[$vmStart]->vars[0]->var->name,
            'temp1' => $ast[$vmStart + 5]->expr->var->name,
            'temp' => $ast[$vmStart + 5]->expr->expr->var->name,
        ];
    }

    public function getVmVariables()
    {
        $this->vmVariables = self::findVmVariables($this->ast, $this->vmStart);
        return $this->vmVariables;
    }

    public static function findVmMemoryData($ast, $vmStart)
    {
        $memory_data_node = $ast[$vmStart + 1]->stmts[0]->expr->expr;
        $memory_data_nodes = Helper::astConcatString([$memory_data_node]);
        $memory_data = null;
        if ($memory_data_nodes[0] instanceof String_) {
            $memory_data = $memory_data_nodes[0]->value;
        }
        return compact('memory_data_node', 'memory_data');
    }

    public function getVmMemoryData()
    {
        $this->vmMemoryData = self::findVmMemoryData($this->ast, $this->vmStart);
        return $this->vmMemoryData;
    }

    public function disassemble()
    {
        $disassembler = new Disassembler1($this->vmMemoryData['memory_data']);
        $disassembler2 = new Disassembler2($this->vmVariables);
        $dfsDisassembler = new DfsDisassembler($disassembler, $disassembler2);
        $dfsDisassembler->disassemble();
        $this->instructions = $dfsDisassembler->getInstructions();
        return $this->instructions;
    }

    public function toGraph()
    {
        $instructions = $this->instructions;
        $graph = GraphViewer::toDirectedGraph($instructions);
        $graph->simplify();
        $this->graph = $graph;
        return $this->graph;
    }

    public function simplifyGraphStructure()
    {
        $simplifier = new DirectedGraphStructureSimplifier($this->graph);
        $simplifier->simplify();
    }

    public function getStructuredInstructions()
    {
        $graph = $this->graph;
        assert(count($graph->getVerticesId()) === 1);
        $instructions = $graph->getVertex(0);
        $this->structuredInstructions = $instructions;
        return $this->structuredInstructions;
    }

    public function decompile()
    {
        $decompiler = new Decompiler($this->structuredInstructions);
        try {
            $decompiler->decompile();
        } catch (DecompileFunctionBreakException $e) {
        }
        $this->decompiler = $decompiler;
        return $this->decompiler;
    }

    public function autoDecompile()
    {
        $this->getVmStart();
        $this->getVmVariables();
        $this->getVmMemoryData();
        $this->disassemble();
        $this->toGraph();
        $this->simplifyGraphStructure();
        $this->getStructuredInstructions();
        return $this->decompile();
    }
}