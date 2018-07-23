<?php

namespace Ganlv\MfencDecompiler;


class GraphViewer
{
    public static function getJumpList($instructions)
    {
        $result = [];
        foreach ($instructions as $address => $instruction) {
            if (Helper::isJumpInstruction($instruction)) {
                $result[$address] = $instruction['operands'][0];
            }
        }
        return $result;
    }

    public static function getInstructionSeparators($instructions)
    {
        $result = [0];
        foreach ($instructions as $address => $instruction) {
            if (Helper::isJumpInstruction($instruction)) {
                $result[] = $instruction['end'];
                $result[] = $instruction['operands'][0];
            }
        }
        $result = array_unique($result);
        sort($result);
        return $result;
    }

    public static function getInstructionSections($instructions)
    {
        $separators = self::getInstructionSeparators($instructions);
        $index = 0;
        $sections = [];
        foreach ($instructions as $eip => $instruction) {
            while (isset($separators[$index + 1]) && $eip >= $separators[$index + 1]) {
                ++$index;
            }
            if (!isset($sections[$separators[$index]])) {
                $sections[$separators[$index]] = [];
            }
            $sections[$separators[$index]][$eip] = $instruction;
        }
        $result = [];
        foreach ($sections as $key => $section) {
            if (!empty($section)) {
                $result[array_keys($section)[0]] = $section;
            }
        }
        return $result;
    }

    public static function toDirectedGraph($instructions)
    {
        $sections = self::getInstructionSections($instructions);
        $graph = new DirectedGraph();
        foreach ($sections as $eip => $instructions) {
            $graph->setVertex($eip, array_values($instructions));
        }
        foreach ($graph->getVertices() as $eip => $instructions) {
            $lastInstruction = array_pop($instructions);
            if ($lastInstruction['operation'] === 'jnz') {
                assert($lastInstruction['operands'][0] >= 0);
                assert($lastInstruction['end'] >= 0);
                $graph->createEdge($eip, $lastInstruction['operands'][0]);
                $graph->createEdge($eip, $lastInstruction['end']);
                $graph->setVertex($eip, $instructions);
            } elseif ($lastInstruction['operation'] === 'jmp') {
                if ($lastInstruction['operands'][0] < 0) {
                    $instructions[] = Helper::buildInstruction('return', [], $lastInstruction['start'], $lastInstruction['end']);
                } else {
                    $graph->createEdge($eip, $lastInstruction['operands'][0]);
                }
                $graph->setVertex($eip, $instructions);
            } else {
                $graph->createEdge($eip, $lastInstruction['end']);
            }
        }
        return $graph;
    }
}