<?php

namespace Ganlv\MfencDecompiler\Exceptions;

use Exception;

/**
 * 堆栈不平衡
 *
 * @package Ganlv\MfencDecompiler\Exceptions
 */
class StackNotCleanException extends Exception
{
    public $before;
    public $after;

    public function __construct($before, $after)
    {
        $this->before = $before;
        $this->after = $after;
        parent::__construct('堆栈不平衡');
    }
}