<?php

namespace Ganlv\MfencDecompiler;

use RuntimeException;

class DirectedGraph
{
    protected $vertices = [];
    protected $edges = [];
    protected $edgesTo = [];

    public function getVertices()
    {
        ksort($this->vertices);
        return $this->vertices;
    }

    public function getVerticesId()
    {
        return array_keys($this->vertices);
    }

    public function setVertex($id, $content)
    {
        $this->vertices[$id] = $content;
    }

    public function hasVertex($id)
    {
        return isset($this->vertices[$id]);
    }

    public function getVertex($id, $default = [])
    {
        return $this->hasVertex($id) ? $this->vertices[$id] : $default;
    }

    public function removeVertex($id)
    {
        unset($this->vertices[$id]);
        $this->removeEdgeFrom($id);
        $this->removeEdgeTo($id);
    }

    public function createEdge($from, $to)
    {
        if (!$this->hasVertex($from)) {
            throw new RuntimeException("Vertex not exists: $from.");
        }
        if (!$this->hasVertex($to)) {
            throw new RuntimeException("Vertex not exists: $to.");
        }
        if ($this->hasEdge($from, $to)) {
            throw new RuntimeException("Edge already exists: $from -> $to.");
        }
        $this->edges[$from][] = $to;
        $this->edgesTo[$to][] = $from;
    }

    public function removeEdge($from, $to)
    {
        if ($this->hasEdge($from, $to)) {
            $index = array_search($to, $this->edges[$from]);
            unset($this->edges[$from][$index]);
            $this->edges[$from] = array_values($this->edges[$from]);
            if (empty($this->edges[$from])) {
                unset($this->edges[$from]);
            }

            $index = array_search($from, $this->edgesTo[$to]);
            unset($this->edgesTo[$to][$index]);
            $this->edgesTo[$to] = array_values($this->edgesTo[$to]);
            if (empty($this->edgesTo[$to])) {
                unset($this->edgesTo[$to]);
            }
        }
    }

    public function removeEdgeFrom($from)
    {
        foreach ($this->getEdgeFrom($from) as $to) {
            $this->removeEdge($from, $to);
        }
    }

    public function removeEdgeTo($to)
    {
        foreach ($this->getEdgeTo($to) as $from) {
            $this->removeEdge($from, $to);
        }
    }

    public function hasEdge($from, $to)
    {
        return isset($this->edges[$from]) && in_array($to, $this->edges[$from]);
    }

    public function getEdgeFrom($from)
    {
        return isset($this->edges[$from]) ? $this->edges[$from] : [];
    }

    public function getEdgeTo($to)
    {
        return isset($this->edgesTo[$to]) ? $this->edgesTo[$to] : [];
    }

    public function getEdges()
    {
        ksort($this->edges);
        return $this->edges;
    }

    public function simplify()
    {
        $simplifier = new DirectedGraphSimpleSimplifier($this);
        return $simplifier->simplify();
    }
}