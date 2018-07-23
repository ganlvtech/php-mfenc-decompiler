<?php

namespace Ganlv\MfencDecompiler;

class DirectedGraphSimpleSimplifier extends DirectedGraphSimplifier
{
    /**
     * 如果1进的节点的来源节点只有1出，则二者合并。
     *
     * @param int $id
     *
     * @return int
     */
    public function tryMergeVertex($id)
    {
        if (!$this->graph->hasVertex($id)) {
            return false;
        }
        $froms = $this->graph->getEdgeTo($id);
        if (count($froms) === 1 && count($this->graph->getEdgeFrom($froms[0])) === 1) {
            $from = $froms[0];
            $this->graph->setVertex($from, array_merge(
                $this->graph->getVertex($from),
                $this->graph->getVertex($id)
            ));
            foreach ($this->graph->getEdgeFrom($id) as $to) {
                $this->graph->createEdge($from, $to);
            }
            $this->graph->removeVertex($id);
            return true;
        }
        return false;
    }

    /**
     * 如果节点中没有指令，并且只有1出，修改来源节点的出口目标。
     *
     * @param int $id
     *
     * @return int
     */
    public function tryMergeEmptyVertex($id)
    {
        if (!$this->graph->hasVertex($id)) {
            return false;
        }
        $tos = $this->graph->getEdgeFrom($id);
        if (empty($this->graph->getVertex($id)) && count($tos) === 1) {
            $to = $tos[0];
            foreach ($this->graph->getEdgeTo($id) as $from) {
                $this->graph->removeEdge($from, $id);
                $this->graph->createEdge($from, $to);
            }
            $this->graph->removeVertex($id);
            return true;
        }
        return false;
    }

    /**
     * 简化图
     *
     * 图由节点和有向线段组成。
     * 简化之前的图，每个节点可能会有：1进1出、1进n出、n进1出、n进n出。
     * n出的类型为 Condition，1出的类型为 Operation。
     * 如果1进的节点的来源节点只有1出，则二者合并。
     * 如果节点中没有指令，并且只有1出，修改来源节点的出口目标。
     * 简化之后的图，每个节点可能会有：1进n出、n进1出、n进n出。
     */
    public function simplifyOneRound()
    {
        $count = 0;
        // 如果1进的节点的来源节点只有1出，则二者合并
        foreach ($this->graph->getVerticesId() as $id) {
            $count += (int)$this->tryMergeVertex($id);
        }
        // 如果块中没有指令，并且只有一个出口，修改来源指令的跳转目标
        foreach ($this->graph->getVerticesId() as $id) {
            $count += (int)$this->tryMergeEmptyVertex($id);
        }
        return $count;
    }

    public function simplify()
    {
        while ($this->simplifyOneRound() > 0) {
            // no body
        }
        return $this->graph;
    }
}