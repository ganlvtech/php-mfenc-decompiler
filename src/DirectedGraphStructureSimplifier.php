<?php

namespace Ganlv\MfencDecompiler;

class DirectedGraphStructureSimplifier extends DirectedGraphSimplifier
{
    /**
     * 尝试简化简单条件分支结构
     *
     * 如果节点有两个分支，这两个分支分 3 种情况
     * 1. yes 分支的出口与 no 分支的入口相同，则 if (cond) { statements; } else {}
     * 2. yes 分支的入口与 no 分支的出口相同，则 if (cond) {} else { statements; }
     * 3. yes 分支的出口与 no 分支的出口相同，则 if (cond) { statements; } else { statements; }
     * 3.1. 均指向下一个节点的入口位置
     * 3.2. 均没有下一个节点（函数返回）
     *
     * @param $id
     *
     * @return bool
     */
    public function trySimplifyCondition($id)
    {
        if (!$this->graph->hasVertex($id)) {
            return false;
        }
        $tos = $this->graph->getEdgeFrom($id);
        if (count($tos) === 2) {
            $yesTos = $this->graph->getEdgeFrom($tos[0]);
            $noTos = $this->graph->getEdgeFrom($tos[1]);
            $yesFroms = $this->graph->getEdgeTo($tos[0]);
            $noFroms = $this->graph->getEdgeTo($tos[1]);
            if ((count($yesTos) === 1 && $yesTos[0] === $tos[1] || count($yesTos) === 0) && count($yesFroms) === 1 && $yesFroms[0] === $id) {
                // if (cond) { statements; } else {}
                $instructions = $this->graph->getVertex($id);
                $instructions[] = Helper::buildInstruction('if', [
                    $this->graph->getVertex($tos[0]),
                    [],
                ]);
                $this->graph->setVertex($id, $instructions);
                $this->graph->removeVertex($tos[0]);
                return true;
            } elseif ((count($noTos) === 1 && $noTos[0] === $tos[0] || count($noTos) === 0) && count($noFroms) === 1 && $noFroms[0] === $id) {
                // if (cond) {} else { statements; }
                $instructions = $this->graph->getVertex($id);
                $instructions[] = Helper::buildInstruction('if', [
                    [],
                    $this->graph->getVertex($tos[1]),
                ]);
                $this->graph->setVertex($id, $instructions);
                $this->graph->removeVertex($tos[1]);
                return true;
            } elseif (count($yesTos) === 1 && count($noTos) === 1 && $yesTos[0] === $noTos[0]
                && count($yesFroms) === 1 && $yesFroms[0] === $id && count($noFroms) === 1 && $noFroms[0] === $id) {
                // if (cond) { statements; } else { statements; }
                $instructions = $this->graph->getVertex($id);
                $instructions[] = Helper::buildInstruction('if', [
                    $this->graph->getVertex($tos[0]),
                    $this->graph->getVertex($tos[1]),
                ]);
                $this->graph->setVertex($id, $instructions);
                $this->graph->removeVertex($tos[0]);
                $this->graph->removeVertex($tos[1]);
                $this->graph->createEdge($id, $yesTos[0]);
                return true;
            } elseif (count($yesTos) === 0 && count($noTos) === 0
                && count($yesFroms) === 1 && $yesFroms[0] === $id && count($noFroms) === 1 && $yesFroms[0] === $id) {
                // if (cond) { statements; return; } else { statements; return; }
                $instructions = $this->graph->getVertex($id);
                $instructions[] = Helper::buildInstruction('if', [
                    $this->graph->getVertex($tos[0]),
                    $this->graph->getVertex($tos[1]),
                ]);
                $this->graph->setVertex($id, $instructions);
                $this->graph->removeVertex($tos[0]);
                $this->graph->removeVertex($tos[1]);
                return true;
            }
        }
        return false;
    }

    /**
     * 尝试简化死循环
     *
     * 如果节点没有分支，唯一一个出口指向自己，则 while(true) { statements; }
     *
     * @param $id
     *
     * @return bool
     */
    public function trySimplifyLoopWithNoBreak($id)
    {
        if (!$this->graph->hasVertex($id)) {
            return false;
        }
        $tos = $this->graph->getEdgeFrom($id);
        if (count($tos) === 1 && $tos[0] === $id) {
            $vertex = $this->graph->getVertex($id);
            $vertex = [
                Helper::buildInstruction('loop', [$vertex]),
            ];
            $this->graph->setVertex($id, $vertex);
            $this->graph->removeEdge($id, $id);
            return true;
        }
        return false;
    }

    /**
     * 尝试简化单出口循环
     *
     * 单出口循环，循环体内只包含一个条件分支节点，分两种情况
     * 1. 入口位置 -> condition(->break) -> 入口位置
     * 2. 入口位置 -> condition(->break) -> operation -> 入口位置
     * 这两种情况，分别要考虑 break 位于 condition 的 yes 和 no 分支的情况
     *
     * @param $id
     *
     * @return bool
     */
    public function trySimplifyLoopWithSingleBreak($id)
    {
        if (!$this->graph->hasVertex($id)) {
            return false;
        }
        $tos = $this->graph->getEdgeFrom($id);
        if (count($tos) === 2) {
            if ($tos[0] === $id) {
                // while (true) { statements; if (cond) { } else { break; } }
                $vertex = $this->graph->getVertex($id);
                $vertex[] = Helper::buildInstruction('if', [
                    [],
                    [
                        Helper::buildInstruction('break'),
                    ],
                ]);
                $vertex = [
                    Helper::buildInstruction('loop', [$vertex]),
                ];
                $this->graph->setVertex($id, $vertex);
                $this->graph->removeEdge($tos[0], $id);
                return true;
            } elseif ($tos[1] === $id) {
                // while (true) { statements; if (cond) { break; } else { } }
                $vertex = $this->graph->getVertex($id);
                $vertex[] = Helper::buildInstruction('if', [
                    [
                        Helper::buildInstruction('break'),
                    ],
                    [],
                ]);
                $vertex = [
                    Helper::buildInstruction('loop', [$vertex]),
                ];
                $this->graph->setVertex($id, $vertex);
                $this->graph->removeEdge($tos[1], $id);
                return true;
            } else {
                $yesTos = $this->graph->getEdgeFrom($tos[0]);
                $noTos = $this->graph->getEdgeFrom($tos[1]);
                if (count($yesTos) === 1 && $yesTos[0] === $id) {
                    // while (true) { statements; if (cond) { } else { break; } statements; }
                    $vertex = $this->graph->getVertex($id);
                    $vertex[] = Helper::buildInstruction('if', [
                        [],
                        [
                            Helper::buildInstruction('break'),
                        ],
                    ]);
                    $vertex = array_merge($vertex, $this->graph->getVertex($tos[0]));
                    $vertex = [
                        Helper::buildInstruction('loop', [$vertex]),
                    ];
                    $this->graph->setVertex($id, $vertex);
                    $this->graph->removeVertex($tos[0]);
                    return true;
                } elseif (count($noTos) === 1 && $noTos[0] === $id) {
                    // while (true) { statements; if (cond) { break; } else { } statements; }
                    $vertex = $this->graph->getVertex($id);
                    $vertex[] = Helper::buildInstruction('if', [
                        [
                            Helper::buildInstruction('break'),
                        ],
                        [],
                    ]);
                    $vertex = array_merge($vertex, $this->graph->getVertex($tos[1]));
                    $vertex = [
                        Helper::buildInstruction('loop', [$vertex]),
                    ];
                    $this->graph->setVertex($id, $vertex);
                    $this->graph->removeVertex($tos[1]);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 尝试简化多出口循环
     *
     * @param $id
     *
     * @return bool
     */
    public function trySimplifyLoopWithMultipleBreak($circle)
    {
        // 找出这个环中的所有出口
        $breakTos = [];
        foreach ($circle as $id) {
            if (!$this->graph->hasVertex($id)) {
                return false;
            }
            $tos = $this->graph->getEdgeFrom($id);
            if (count($tos) === 2) {
                if (in_array($tos[0], $circle)) {
                    $breakTos[] = $tos[1];
                } else {
                    $breakTos[] = $tos[0];
                }
            }
        }

        // 获取每个出口接下来的 operation 的地址
        $breakTos2 = [];
        foreach ($breakTos as $id) {
            $tos = $this->graph->getEdgeFrom($id);
            if (count($tos) !== 1) {
                return false;
            }
            $breakTos2[] = $tos[0];
        }

        // 找出每个出口合并到一起的地址
        $breakEnd = null;
        foreach ($breakTos2 as $id) {
            if (in_array($id, $breakTos) || count(array_keys($breakTos2, $id)) >= 2) {
                $breakEnd = $id;
                break;
            }
        }
        if (is_null($breakEnd)) {
            return false;
        }

        // 转换每个 condition
        foreach ($circle as $id) {
            if (!$this->graph->hasVertex($id)) {
                return false;
            }
            $tos = $this->graph->getEdgeFrom($id);
            if (count($tos) === 2) {
                $vertex = $this->graph->getVertex($id);
                if (in_array($tos[0], $circle)) {
                    if ($tos[1] === $breakEnd) {
                        // if (cond) { } else { break; }
                        $vertex[] = Helper::buildInstruction('if', [
                            [],
                            [
                                Helper::buildInstruction('break'),
                            ],
                        ]);
                    } else {
                        // if (cond) { } else { statements; break; }
                        $vertex[] = Helper::buildInstruction('if', [
                            [],
                            array_merge($this->graph->getVertex($tos[1]), [
                                Helper::buildInstruction('break'),
                            ]),
                        ]);
                        $this->graph->removeVertex($tos[1]);
                    }
                } else {
                    if ($tos[0] === $breakEnd) {
                        // if (cond) { break; } else { }
                        $vertex[] = Helper::buildInstruction('if', [
                            [
                                Helper::buildInstruction('break'),
                            ],
                            [],
                        ]);
                    } else {
                        // if (cond) { statements; break; } else { }
                        $vertex[] = Helper::buildInstruction('if', [
                            array_merge($this->graph->getVertex($tos[0]), [
                                Helper::buildInstruction('break'),
                            ]),
                            [],
                        ]);
                        $this->graph->removeVertex($tos[0]);
                    }
                }
                $this->graph->setVertex($id, $vertex);
            }
        }

        // 合并环中的每个 operation
        $vertex = [];
        foreach ($circle as $id) {
            $vertex = array_merge($vertex, $this->graph->getVertex($id));
        }
        foreach ($circle as $index => $id) {
            if ($index === 0) {
                $this->graph->setVertex($id, [
                    Helper::buildInstruction('loop', [
                        $vertex,
                    ]),
                ]);
                $this->graph->createEdge($id, $breakEnd);
            } else {
                $this->graph->removeVertex($id);
            }
        }
        return true;
    }

    /**
     * 查找有向图中的环
     *
     * @param $id
     * @param array $path
     * @param array $circles
     *
     * @return array
     */
    public function findCircles($id, $path = [], $circles = [])
    {
        if (in_array($id, $path)) {
            $circleStart = array_search($id, $path);
            $circle = array_slice($path, $circleStart);
            if (!in_array($circle, $circles)) {
                $circles[] = $circle;
            }
            return $circles;
        }
        $path[] = $id;
        $tos = $this->graph->getEdgeFrom($id);
        foreach ($tos as $to) {
            $circles = $this->findCircles($to, $path, $circles);
        }
        return $circles;
    }

    public function simplifyOneRound()
    {
        $count = 0;
        // 尝试简化简单条件分支结构
        foreach ($this->graph->getVerticesId() as $id) {
            $count += (int)$this->trySimplifyCondition($id);
        }
        // 尝试简化死循环
        if ($count <= 0) {
            foreach ($this->graph->getVerticesId() as $id) {
                $count += (int)$this->trySimplifyLoopWithNoBreak($id);
            }
        }
        // 尝试简化单出口循环
        if ($count <= 0) {
            foreach ($this->graph->getVerticesId() as $id) {
                $count += (int)$this->trySimplifyLoopWithSingleBreak($id);
            }
        }
        // 尝试简化多出口循环
        if ($count <= 0) {
            foreach ($this->findCircles(0) as $circle) {
                if ($this->trySimplifyLoopWithMultipleBreak($circle)) {
                    ++$count;
                    break;
                }
            }
        }
        return $count;
    }

    public function simplify()
    {
        while ($this->simplifyOneRound() > 0) {
            $this->graph->simplify();
        }
        $this->graph->simplify();
        return $this->graph;
    }
}