<?php

namespace Ganlv\MfencDecompiler\Exceptions;

use Exception;

class DecompileFunctionBreakException extends Exception
{
    public $type;

    public function __construct($type = 'break')
    {
        $this->type = $type;
        parent::__construct($type);
    }
}