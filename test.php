<?php

use Ganlv\MfencDecompiler\Decompiler;
use Ganlv\MfencDecompiler\DfsDisassembler;
use Ganlv\MfencDecompiler\DirectedGraphStructureSimplifier;
use Ganlv\MfencDecompiler\Disassembler1;
use Ganlv\MfencDecompiler\Disassembler2;
use Ganlv\MfencDecompiler\GraphViewer;
use Ganlv\MfencDecompiler\Helper;
use Ganlv\MfencDecompiler\VmDecompiler;

require 'vendor/autoload.php';

ini_set('xdebug.max_nesting_level', 1000000);

$functionIndex = 10;
if (isset($_GET['flowchart'])) {
    $functionIndex = $_GET['flowchart'];
    $graph = getGraph($functionIndex);
    $simplifier = new DirectedGraphStructureSimplifier($graph);
    $graph = $simplifier->simplify();
    header('Content-Type: text/plain; charset=UTF-8');
    echo Helper::graphToFlowchart($graph);
    return;
}
if (isset($_GET['id'])) {
    $functionIndex = $_GET['id'];
}

$dissectInstructions = getStructuredInstructions($functionIndex);
$decompiler = new Decompiler($dissectInstructions);
try {
    $decompiler->decompile();
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL, PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL, PHP_EOL;
}
$ast = $decompiler->ast;
echo Helper::prettyPrintFile($ast);

$nodeVisitor = new \Ganlv\MfencDecompiler\NodeVisitors\GetAllEipsNodeVisitor();
Helper::traverseAst($nodeVisitor, $ast);
$eips = $nodeVisitor->eips;
file_put_contents("runtime/$functionIndex.structure.summary.txt", Helper::printStructuredInstructionsIsUsed(getStructuredInstructions($functionIndex), $eips, true));
$stack = $decompiler->stack;
$ast = \Ganlv\MfencDecompiler\Beautifier::beautify($ast);
file_put_contents("runtime/$functionIndex.decompiled.1.php", Helper::prettyPrintFile($ast));

function getAst()
{
    if (!file_exists("runtime/ast.serialize.txt")) {
        $original = file_get_contents('tests/keke_xzhseo.class.php');
        $ast = Helper::parseCode($original);
        file_put_contents("runtime/ast.serialize.txt", serialize($ast));
    } else {
        $ast = unserialize(file_get_contents("runtime/ast.serialize.txt"));
    }
    return $ast;
}

function getInstructions($functionIndex)
{
    if (!file_exists("runtime/$functionIndex.instructions.serialize.txt")) {
        $ast = getAst();
        $ast = $ast[11]->stmts[$functionIndex]->stmts;
        $vmStart = VmDecompiler::findVmStart($ast);
        $vmVariables = VmDecompiler::findVmVariables($ast, $vmStart);
        $vmMemoryData = VmDecompiler::findVmMemoryData($ast, $vmStart);

        $disassembler = new Disassembler1($vmMemoryData['memory_data']);
        $disassembler2 = new Disassembler2($vmVariables);
        $dfsDisassembler = new DfsDisassembler($disassembler, $disassembler2);
        $dfsDisassembler->disassemble();
        $instructions = $dfsDisassembler->getInstructions();
        file_put_contents("runtime/$functionIndex.instructions.serialize.txt", serialize($instructions));
        file_put_contents("runtime/$functionIndex.instructions.txt", Helper::printInstructions($instructions, true, true));
    } else {
        $instructions = unserialize(file_get_contents("runtime/$functionIndex.instructions.serialize.txt"));
    }
    return $instructions;
}

function getGraph($functionIndex)
{
    if (!file_exists("runtime/$functionIndex.graph.serialize.txt")) {
        $instructions = getInstructions($functionIndex);
        $graph = GraphViewer::toDirectedGraph($instructions);
        $graph->simplify();
        file_put_contents("runtime/$functionIndex.graph.serialize.txt", serialize($graph));
        file_put_contents("runtime/$functionIndex.graph.txt", Helper::printDirectedGraph($graph, true));
    } else {
        $graph = unserialize(file_get_contents("runtime/$functionIndex.graph.serialize.txt"));
    }
    return $graph;
}

function getStructuredInstructions($functionIndex)
{
    if (!file_exists("runtime/$functionIndex.structure.serialize.txt")) {
        $graph = getGraph($functionIndex);
        $simplifier = new DirectedGraphStructureSimplifier($graph);
        $graph = $simplifier->simplify();
        assert(count($graph->getVerticesId()) === 1);
        $instructions = $graph->getVertex(0);
        file_put_contents("runtime/$functionIndex.structure.serialize.txt", serialize($instructions));
        file_put_contents("runtime/$functionIndex.structure.txt", Helper::printStructuredInstructions($instructions, true));
    } else {
        $instructions = unserialize(file_get_contents("runtime/$functionIndex.structure.serialize.txt"));
    }
    return $instructions;
}
