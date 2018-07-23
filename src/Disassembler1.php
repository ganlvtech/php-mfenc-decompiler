<?php

namespace Ganlv\MfencDecompiler;

use Ganlv\MfencDecompiler\Exceptions\MemoryAddressOutOfBoundException;

class Disassembler1
{
    protected $memory;
    protected $eip;

    public function __construct($memory)
    {
        $this->memory = $memory;
        $this->eip = 0;
    }

    /**
     * 获取下一条指令
     *
     * @return array
     */
    public function getNextInstruction()
    {
        $start = $this->eip;
        $key = $this->getKey();
        $operation = $this->getOperation($key);
        switch ($operation) {
            case '1':
                $instruction = Helper::buildInstruction('eval', [$this->getString($key, 2)]);
                break;
            case '2':
                $instruction = Helper::buildInstruction('eval', [$this->getString($key, 4)]);
                break;
            case '3':
                $instruction = Helper::buildInstruction('eval', [$this->getString($key, 10)]);
                break;
            case 'a':
                $instruction = Helper::buildInstruction('pop', [1]);
                break;
            case 'b':
                $instruction = Helper::buildInstruction('dereference', []);
                break;
            case 'c':
                $instruction = Helper::buildInstruction('push_const', ['null']);
                break;
            case 'd':
                $instruction = Helper::buildInstruction('array_dim_fetch', [1, 0]);
                break;
            case 'e':
                $instruction = Helper::buildInstruction('reference', []);
                break;
            case 'fd':
                $instruction = Helper::buildInstruction('push_string', [$this->getString($key, 2)]);
                break;
            case 'fq':
                $instruction = Helper::buildInstruction('push_string', [$this->getString($key, 4)]);
                break;
            case 'fx':
                $instruction = Helper::buildInstruction('push_string', [$this->getString($key, 10)]);
                break;
            default:
                // TODO raise exception
                $instruction = Helper::buildInstruction('UNKNOWN', [$this->getEip(), $operation]);
        }
        $end = $this->eip;
        $instruction['start'] = $start;
        $instruction['end'] = $end;
        return $instruction;
    }

    public function getKey()
    {
        $key = $this->getByteNoCrypt();
        return $key;
    }

    /**
     * 获取指令操作
     *
     * @param $key
     *
     * @return int|string
     */
    public function getOperation($key)
    {
        $instruction1 = $this->getByte($key);
        if ($instruction1 == 'f') {
            $instruction1 .= $this->getByte($key);
        }
        return $instruction1;
    }

    /**
     * @param string $key
     * @param int $len 2, 4 or 10
     *
     * @return string
     */
    public function getString($key, $len)
    {
        $len2 = $this->getInteger($key, $len);
        $string = $this->getBytes($key, $len2);
        return $string;
    }

    public function getByteNoCrypt()
    {
        if (!isset($this->memory[$this->eip]) || $this->eip < 0) {
            throw new MemoryAddressOutOfBoundException('内存越界');
        }
        return $this->memory[$this->eip++];
    }

    public function getByte($key)
    {
        $byte = $key ^ $this->getByteNoCrypt();
        return $byte;
    }

    public function getInteger($key, $len)
    {
        return (int)$this->getBytes($key, $len);
    }

    public function getBytes($key, $len)
    {
        $bytes = '';
        for ($i = 0; $i < $len; ++$i) {
            $bytes .= $this->getByte($key);
        }
        return $bytes;
    }

    public function getEip()
    {
        return $this->eip;
    }

    public function setEip($eip)
    {
        $this->eip = $eip;
    }
}