<?php

namespace Ganlv\MfencDecompiler;

abstract class DirectedGraphSimplifier
{
    /**
     * @var \Ganlv\MfencDecompiler\DirectedGraph
     */
    protected $graph;

    public function __construct($graph)
    {
        $this->graph = $graph;
    }

    /**
     * @return \Ganlv\MfencDecompiler\DirectedGraph
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * @return \Ganlv\MfencDecompiler\DirectedGraph
     */
    public abstract function simplify();
}