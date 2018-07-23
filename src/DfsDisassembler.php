<?php

namespace Ganlv\MfencDecompiler;

use Ganlv\MfencDecompiler\Exceptions\MemoryAddressOutOfBoundException;

class DfsDisassembler
{
    protected $instructions = [];
    protected $disassembler1;
    protected $disassembler2;

    public function __construct(Disassembler1 $disassembler1, Disassembler2 $disassembler2)
    {
        $this->disassembler1 = $disassembler1;
        $this->disassembler2 = $disassembler2;
    }

    public function disassemble()
    {
        try {
            for (; ;) {
                $this->disassembleNextInstruction();
            }
        } catch (MemoryAddressOutOfBoundException $e) {
        }
    }

    public function disassembleNextInstruction()
    {
        $start = $this->disassembler1->getEip();
        if (isset($this->instructions[$start])) {
            $this->disassembler1->setEip(-1);
        }
        $instruction = $this->disassembler1->getNextInstruction();
        $end = $this->disassembler1->getEip();
        if ($instruction['operation'] === 'eval') {
            $instruction = $this->disassembler2->parseEvalCode($instruction['operands'][0]);
        }
        $this->instructions[$start] = Helper::buildInstruction($instruction['operation'], $instruction['operands'], $start, $end);

        if ($instruction['operation'] === 'jmp') {
            $targetEip = $instruction['operands'][0];
            $this->disassembler1->setEip($targetEip);
        } elseif ($instruction['operation'] === 'jnz') {
            $elseEip = $end;
            $targetEip = $instruction['operands'][0];
            $this->disassembler1->setEip($targetEip);
            // DFS
            $this->disassemble();
            $this->disassembler1->setEip($elseEip);
        }
    }

    public function getInstructions()
    {
        ksort($this->instructions);
        return $this->instructions;
    }
}