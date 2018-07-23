<?php

use Ganlv\MfencDecompiler\Helper;

if (!function_exists('is_stack_at')) {
    function is_stack_at($expr, $offset)
    {
        if ($offset > 0) {
            return ($expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
                && $expr->var instanceof \PhpParser\Node\Expr\Variable
                && $expr->var->name === 'stack'
                && $expr->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
                && $expr->dim->left instanceof \PhpParser\Node\Expr\Variable
                && $expr->dim->left->name === 'esp'
                && $expr->dim->right instanceof \PhpParser\Node\Scalar\LNumber
                && $expr->dim->right->value === $offset);
        } else {
            return ($expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
                && $expr->var instanceof \PhpParser\Node\Expr\Variable
                && $expr->var->name === 'stack'
                && $expr->dim instanceof \PhpParser\Node\Expr\Variable
                && $expr->dim->name === 'esp');
        }
    }
}

return [
    // ==================== 栈操作 ====================
    // $stack[$esp] = SOME_CONST;
    'load_const' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ConstFetch
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name) {
            return [$ast[0]->expr->expr->name->parts[0]];
        }
        return false;
    },
    // $stack[++$esp] = false;
    'push_const' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ConstFetch
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name) {
            return [$ast[0]->expr->expr->name->parts[0]];
        }
        return false;
    },
    // $stack[++$esp] = 'some string';
    'push_string' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Scalar\String_) {
            return [$ast[0]->expr->expr->value];
        }
        return false;
    },
    // $stack[$esp] = array();
    'push_array' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Array_) {
            return [];
        }
        return false;
    },
    // $stack[$esp] = 123456;
    // $stack[++$esp] = -1;
    'push_number' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Scalar\LNumber) {
            return [$ast[0]->expr->expr->value];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\UnaryMinus
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Scalar\LNumber) {
            return [-$ast[0]->expr->expr->expr->value];
        }
        return false;
    },
    // $stack[++$esp] = $stack[0];
    'push_magic_const_file' => function ($ast) {
        if (count($ast) === 1
            && $ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->dim->value === 0) {
            return [];
        }
        return false;
    },
    // $stack[++$esp] = dirname($stack[0]);
    'push_magic_const_dir' => function ($ast) {
        if (count($ast) === 1
            && $ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name
            && count($ast[0]->expr->expr->name->parts) === 1
            && $ast[0]->expr->expr->name->parts[0] === 'dirname'
            && count($ast[0]->expr->expr->args) === 1
            && $ast[0]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->args[0]->value->var->name === 'stack'
            && $ast[0]->expr->expr->args[0]->value->dim instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->args[0]->value->dim->value === 0) {
            return [];
        }
        return false;
    },
    // unset($stack[$esp--]);
    // unset($stack[$esp - 0]); $esp -= 1;
    // unset($stack[$esp - 0]); $esp -= 1;
    'pop' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[0]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[0]->var->name === 'stack'
            && $ast[0]->vars[0]->dim instanceof \PhpParser\Node\Expr\PostDec
            && $ast[0]->vars[0]->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[0]->dim->var->name === 'esp') {
            return [1];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[0]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[0]->var->name === 'stack'
            && $ast[0]->vars[0]->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->vars[0]->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[0]->dim->left->name === 'esp'
            && $ast[0]->vars[0]->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->vars[0]->dim->right->value === 0
            && $ast[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[1]->expr instanceof \PhpParser\Node\Expr\AssignOp\Minus
            && $ast[1]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->name === 'esp'
            && $ast[1]->expr->expr instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->expr->expr->value === 1) {
            return [1];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[0]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[0]->var->name === 'stack'
            && $ast[0]->vars[0]->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->vars[0]->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[0]->dim->left->name === 'esp'
            && $ast[0]->vars[0]->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->vars[0]->dim->right->value === 0
            && $ast[0]->vars[1] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->vars[1]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[1]->var->name === 'stack'
            && $ast[0]->vars[1]->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->vars[1]->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->vars[1]->dim->left->name === 'esp'
            && $ast[0]->vars[1]->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->vars[1]->dim->right->value === 1
            && $ast[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[1]->expr instanceof \PhpParser\Node\Expr\AssignOp\Minus
            && $ast[1]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->name === 'esp'
            && $ast[1]->expr->expr instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->expr->expr->value === 2) {
            return [2];
        }
        return false;
    },
    // $temp = $stack[$esp]; unset($stack[$esp]); $stack[$esp] = $temp; $temp = null;
    'dereference' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->name === 'temp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->name === 'esp'
            && $ast[1] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[1]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->vars[0]->var->name === 'stack'
            && $ast[1]->vars[0]->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->vars[0]->dim->name === 'esp'
            && $ast[2] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[2]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[2]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->var->name === 'stack'
            && $ast[2]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->dim->name === 'esp'
            && $ast[2]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->name === 'temp'
            && $ast[3] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[3]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[3]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[3]->expr->var->name === 'temp'
            && $ast[3]->expr->expr instanceof \PhpParser\Node\Expr\ConstFetch
            && $ast[3]->expr->expr->name instanceof \PhpParser\Node\Name
            && $ast[3]->expr->expr->name->parts[0] === 'null') {
            return [];
        }
        return false;
    },
    // switch ($stack[$esp]) { case 'this': $stack[$esp] =& $this; break; ... }
    'reference' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Switch_
            && $ast[0]->cond instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cond->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->var->name === 'stack'
            && $ast[0]->cond->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->dim->name === 'esp'
            && $ast[0]->cases[0] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[0]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[0]->cond->value === 'this'
            && $ast[0]->cases[0]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[0]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[0]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[0]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[0]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[0]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[0]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[0]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[0]->stmts[0]->expr->expr->name === 'this'
            && $ast[0]->cases[0]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[0]->stmts[1]->num === null
            && $ast[0]->cases[1] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[1]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[1]->cond->value === 'GLOBALS'
            && $ast[0]->cases[1]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[1]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[1]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[1]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[1]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[1]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[1]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[1]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[1]->stmts[0]->expr->expr->name === 'GLOBALS'
            && $ast[0]->cases[1]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[1]->stmts[1]->num === null
            && $ast[0]->cases[2] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[2]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[2]->cond->value === '_SERVER'
            && $ast[0]->cases[2]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[2]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[2]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[2]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[2]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[2]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[2]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[2]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[2]->stmts[0]->expr->expr->name === '_SERVER'
            && $ast[0]->cases[2]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[2]->stmts[1]->num === null
            && $ast[0]->cases[3] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[3]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[3]->cond->value === '_GET'
            && $ast[0]->cases[3]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[3]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[3]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[3]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[3]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[3]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[3]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[3]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[3]->stmts[0]->expr->expr->name === '_GET'
            && $ast[0]->cases[3]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[3]->stmts[1]->num === null
            && $ast[0]->cases[4] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[4]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[4]->cond->value === '_POST'
            && $ast[0]->cases[4]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[4]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[4]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[4]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[4]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[4]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[4]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[4]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[4]->stmts[0]->expr->expr->name === '_POST'
            && $ast[0]->cases[4]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[4]->stmts[1]->num === null
            && $ast[0]->cases[5] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[5]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[5]->cond->value === '_FILES'
            && $ast[0]->cases[5]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[5]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[5]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[5]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[5]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[5]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[5]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[5]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[5]->stmts[0]->expr->expr->name === '_FILES'
            && $ast[0]->cases[5]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[5]->stmts[1]->num === null
            && $ast[0]->cases[6] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[6]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[6]->cond->value === '_COOKIE'
            && $ast[0]->cases[6]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[6]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[6]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[6]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[6]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[6]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[6]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[6]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[6]->stmts[0]->expr->expr->name === '_COOKIE'
            && $ast[0]->cases[6]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[6]->stmts[1]->num === null
            && $ast[0]->cases[7] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[7]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[7]->cond->value === '_SESSION'
            && $ast[0]->cases[7]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[7]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[7]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[7]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[7]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[7]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[7]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[7]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[7]->stmts[0]->expr->expr->name === '_SESSION'
            && $ast[0]->cases[7]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[7]->stmts[1]->num === null
            && $ast[0]->cases[8] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[8]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[8]->cond->value === '_REQUEST'
            && $ast[0]->cases[8]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[8]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[8]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[8]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[8]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[8]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[8]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[8]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[8]->stmts[0]->expr->expr->name === '_REQUEST'
            && $ast[0]->cases[8]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[8]->stmts[1]->num === null
            && $ast[0]->cases[9] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[9]->cond instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->cases[9]->cond->value === '_ENV'
            && $ast[0]->cases[9]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[9]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[9]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[9]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[9]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[9]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[9]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[9]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[9]->stmts[0]->expr->expr->name === '_ENV'
            && $ast[0]->cases[9]->stmts[1] instanceof \PhpParser\Node\Stmt\Break_
            && $ast[0]->cases[9]->stmts[1]->num === null
            && $ast[0]->cases[10] instanceof \PhpParser\Node\Stmt\Case_
            && $ast[0]->cases[10]->cond === null
            && $ast[0]->cases[10]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->cases[10]->stmts[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->cases[10]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[10]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[10]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->cases[10]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[10]->stmts[0]->expr->var->dim->name === 'esp'
            && $ast[0]->cases[10]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[10]->stmts[0]->expr->expr->name instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cases[10]->stmts[0]->expr->expr->name->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[10]->stmts[0]->expr->expr->name->var->name === 'stack'
            && $ast[0]->cases[10]->stmts[0]->expr->expr->name->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cases[10]->stmts[0]->expr->expr->name->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp] = $stack[$esp - 1];
    // $stack[$esp - 1] = $stack[$esp];
    // $temp = $stack[$esp - 2]; $stack[$esp - 1] = $temp;
    'assign' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->left->name === 'esp'
            && $ast[0]->expr->expr->dim->right instanceof \PhpParser\Node\Scalar\LNumber) {
            return [0, $ast[0]->expr->expr->dim->right->value];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->name === 'esp') {
            return [$ast[0]->expr->var->dim->right->value, 0];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->name === 'temp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->left->name === 'esp'
            && $ast[0]->expr->expr->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[1]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[1]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->var->name === 'stack'
            && $ast[1]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[1]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->dim->left->name === 'esp'
            && $ast[1]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->name === 'temp') {
            return [$ast[1]->expr->var->dim->right->value, $ast[0]->expr->expr->dim->right->value];
        }
        return false;
    },

    // ==================== 跳转 ====================
    // if ($stack[$esp]) $eip = 0x12345678;
    'jnz' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\If_
            && $ast[0]->cond instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cond->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->var->name === 'stack'
            && $ast[0]->cond->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->dim->name === 'esp'
            && $ast[0]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->stmts[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->var->name === 'eip'
            && $ast[0]->stmts[0]->expr->expr instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->else === null) {
            return [$ast[0]->stmts[0]->expr->expr->value];
        }
        return false;
    },
    // $eip = 0x12345678;
    // $eip = -1;
    'jmp' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->name === 'eip'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Scalar\LNumber) {
            return [$ast[0]->expr->expr->value];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->name === 'eip'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\UnaryMinus
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Scalar\LNumber) {
            return [-$ast[0]->expr->expr->expr->value];
        }
        return false;
    },

    // ==================== 语言结构 ====================
    // global $_G;
    'global' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Global_
            && $ast[0]->vars[0] instanceof \PhpParser\Node\Expr\Variable) {
            return [$ast[0]->vars[0]->name];
        }
        return false;
    },
    // $stack[$esp] = empty($xxx);
    'empty' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Empty_) {
            return [(new \PhpParser\PrettyPrinter\Standard())->prettyPrintExpr($ast[0]->expr->expr->expr)];
        }
        return false;
    },
    // $stack[$esp] = isset($xxx);
    'isset' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Isset_) {
            return [Helper::prettyPrintExpr($ast[0]->expr->expr->vars[0])];
        }
        return false;
    },
    // exit($stack[$esp]);
    'exit' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Exit_
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // echo $stack[$esp];
    'echo' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Echo_
            && $ast[0]->exprs[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->exprs[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->exprs[0]->var->name === 'stack'
            && $ast[0]->exprs[0]->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->exprs[0]->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp] = new $stack[$esp];
    'new' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\New_
            && $ast[0]->expr->expr->class instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->class->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->class->var->name === 'stack'
            && $ast[0]->expr->expr->class->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->class->dim->name === 'esp') {
            return [];
        }
        return false;
    },

    // ==================== 数组操作 ====================
    // $stack[$esp - 1][] = $stack[$esp];
    // $stack[$esp - 2][$stack[$esp - 1]] = $stack[$esp];
    'array_item' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->var->name === 'stack'
            && $ast[0]->expr->var->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->var->dim->right->value === 1
            && $ast[0]->expr->var->dim === null
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->name === 'esp') {
            return [null];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->var->name === 'stack'
            && $ast[0]->expr->var->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->var->dim->right->value === 2
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'stack'
            && $ast[0]->expr->var->dim->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->name === 'esp') {
            return [true];
        }
        return false;
    },
    // $temp =& $stack[$esp][]; unset($stack[$esp]); $stack[$esp] =& $temp; unset($temp);
    // if (is_scalar($stack[$esp - 1])) { $temp = $stack[$esp - 1]; unset($stack[$esp - 1]); $stack[$esp - 1] = $temp[$stack[$esp]]; } else { if (!is_array($stack[$esp - 1])) { $stack[$esp - 1] = []; }$temp =& $stack[$esp - 1][$stack[$esp]]; unset($stack[$esp - 1]); $stack[$esp - 1] =& $temp; unset($temp); }
    'array_dim_fetch' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->name === 'temp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->var->name === 'stack'
            && $ast[0]->expr->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr->dim === null
            && $ast[1] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[1]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->vars[0]->var->name === 'stack'
            && $ast[1]->vars[0]->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->vars[0]->dim->name === 'esp'
            && $ast[2] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[2]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[2]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->var->name === 'stack'
            && $ast[2]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->dim->name === 'esp'
            && $ast[2]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->name === 'temp'
            && $ast[3] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[3]->vars[0] instanceof \PhpParser\Node\Expr\Variable
            && $ast[3]->vars[0]->name === 'temp') {
            return [0, null];
        } elseif ($ast[0] instanceof \PhpParser\Node\Stmt\If_
            && $ast[0]->cond instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->cond->name instanceof \PhpParser\Node\Name
            && $ast[0]->cond->name->parts[0] === 'is_scalar'
            && $ast[0]->cond->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->cond->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cond->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->args[0]->value->var->name === 'stack'
            && $ast[0]->cond->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->cond->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->args[0]->value->dim->left->name === 'esp'
            && $ast[0]->cond->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->cond->args[0]->value->dim->right->value === 1
            && $ast[0]->cond->args[0]->byRef === false
            && $ast[0]->cond->args[0]->unpack === false
            && $ast[0]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->stmts[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->var->name === 'temp'
            && $ast[0]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->expr->var->name === 'stack'
            && $ast[0]->stmts[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->stmts[0]->expr->expr->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->expr->dim->left->name === 'esp'
            && $ast[0]->stmts[0]->expr->expr->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->stmts[0]->expr->expr->dim->right->value === 1
            && $ast[0]->stmts[1] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[0]->stmts[1]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[1]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[1]->vars[0]->var->name === 'stack'
            && $ast[0]->stmts[1]->vars[0]->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->stmts[1]->vars[0]->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[1]->vars[0]->dim->left->name === 'esp'
            && $ast[0]->stmts[1]->vars[0]->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->stmts[1]->vars[0]->dim->right->value === 1
            && $ast[0]->stmts[2] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->stmts[2]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->stmts[2]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[2]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[2]->expr->var->var->name === 'stack'
            && $ast[0]->stmts[2]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->stmts[2]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[2]->expr->var->dim->left->name === 'esp'
            && $ast[0]->stmts[2]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->stmts[2]->expr->var->dim->right->value === 1
            && $ast[0]->stmts[2]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[2]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[2]->expr->expr->var->name === 'temp'
            && $ast[0]->stmts[2]->expr->expr->dim instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[2]->expr->expr->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[2]->expr->expr->dim->var->name === 'stack'
            && $ast[0]->stmts[2]->expr->expr->dim->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[2]->expr->expr->dim->dim->name === 'esp'
            && $ast[0]->else instanceof \PhpParser\Node\Stmt\Else_
            && $ast[0]->else->stmts[0] instanceof \PhpParser\Node\Stmt\If_
            && $ast[0]->else->stmts[0]->cond instanceof \PhpParser\Node\Expr\BooleanNot
            && $ast[0]->else->stmts[0]->cond->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->else->stmts[0]->cond->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->else->stmts[0]->cond->expr->name->parts[0] === 'is_array'
            && $ast[0]->else->stmts[0]->cond->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->var->name === 'stack'
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->dim->left->name === 'esp'
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->value->dim->right->value === 1
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->byRef === false
            && $ast[0]->else->stmts[0]->cond->expr->args[0]->unpack === false
            && $ast[0]->else->stmts[0]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->else->stmts[0]->stmts[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->else->stmts[0]->stmts[0]->expr->var->dim->right->value === 1
            && $ast[0]->else->stmts[0]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\Array_
            && $ast[0]->else->stmts[0]->else === null
            && $ast[0]->else->stmts[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->else->stmts[1]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->else->stmts[1]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[1]->expr->var->name === 'temp'
            && $ast[0]->else->stmts[1]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[1]->expr->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[1]->expr->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[1]->expr->expr->var->var->name === 'stack'
            && $ast[0]->else->stmts[1]->expr->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->else->stmts[1]->expr->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[1]->expr->expr->var->dim->left->name === 'esp'
            && $ast[0]->else->stmts[1]->expr->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->else->stmts[1]->expr->expr->var->dim->right->value === 1
            && $ast[0]->else->stmts[1]->expr->expr->dim instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[1]->expr->expr->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[1]->expr->expr->dim->var->name === 'stack'
            && $ast[0]->else->stmts[1]->expr->expr->dim->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[1]->expr->expr->dim->dim->name === 'esp'
            && $ast[0]->else->stmts[2] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[0]->else->stmts[2]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[2]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[2]->vars[0]->var->name === 'stack'
            && $ast[0]->else->stmts[2]->vars[0]->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->else->stmts[2]->vars[0]->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[2]->vars[0]->dim->left->name === 'esp'
            && $ast[0]->else->stmts[2]->vars[0]->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->else->stmts[2]->vars[0]->dim->right->value === 1
            && $ast[0]->else->stmts[3] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->else->stmts[3]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->else->stmts[3]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->else->stmts[3]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[3]->expr->var->var->name === 'stack'
            && $ast[0]->else->stmts[3]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->else->stmts[3]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[3]->expr->var->dim->left->name === 'esp'
            && $ast[0]->else->stmts[3]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->else->stmts[3]->expr->var->dim->right->value === 1
            && $ast[0]->else->stmts[3]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[3]->expr->expr->name === 'temp'
            && $ast[0]->else->stmts[4] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[0]->else->stmts[4]->vars[0] instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->else->stmts[4]->vars[0]->name === 'temp') {
            return [1, 0];
        }
        return false;
    },
    // $stack[$esp - 0] = $stack[$esp - 1][0];
    'list_assign' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->var->name === 'stack'
            && $ast[0]->expr->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Scalar\LNumber) {
            return [$ast[0]->expr->var->dim->right->value, $ast[0]->expr->expr->var->dim->right->value, $ast[0]->expr->expr->dim->value];
        }
        return false;
    },

    // ==================== 运算符 ====================
    // $stack[$esp] = !$stack[$esp];
    'boolean_not' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BooleanNot
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] + $stack[$esp];
    'plus' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Plus
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] - $stack[$esp];
    'minus' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] * $stack[$esp];
    'mul' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Mul
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] / $stack[$esp];
    'div' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Div
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] % $stack[$esp];
    'mod' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Mod
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] . $stack[$esp];
    'concat' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] >= $stack[$esp];
    'greater_or_equal' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] > $stack[$esp];
    'greater' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Greater
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] == $stack[$esp];
    'equal' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Equal
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = $stack[$esp - 1] === $stack[$esp];
    'identical' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $ast[0]->expr->expr->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->var->name === 'stack'
            && $ast[0]->expr->expr->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->left->dim->left->name === 'esp'
            && $ast[0]->expr->expr->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->left->dim->right->value === 1
            && $ast[0]->expr->expr->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->var->name === 'stack'
            && $ast[0]->expr->expr->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->right->dim->name === 'esp') {
            return [];
        }
        return false;
    },

    // ==================== 其他运算 ====================
    // $stack[$esp - 3] = call_user_func(array($stack[$esp - 2], $stack[$esp - 1]), $stack[$esp]);
    'method_call' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->expr->name->parts[0] === 'call_user_func'
            && $ast[0]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->expr->args[0]->value instanceof \PhpParser\Node\Expr\Array_
            && $ast[0]->expr->expr->args[0]->value->items[0] instanceof \PhpParser\Node\Expr\ArrayItem
            && $ast[0]->expr->expr->args[0]->value->items[0]->key === null
            && is_stack_at($ast[0]->expr->expr->args[0]->value->items[0]->value, $ast[0]->expr->var->dim->right->value - 1)
            && $ast[0]->expr->expr->args[0]->value->items[1] instanceof \PhpParser\Node\Expr\ArrayItem
            && $ast[0]->expr->expr->args[0]->value->items[1]->key === null
            && is_stack_at($ast[0]->expr->expr->args[0]->value->items[1]->value, $ast[0]->expr->var->dim->right->value - 2)) {
            return [$ast[0]->expr->var->dim->right->value - 2];
        }
        return false;
    },
    // $stack[$esp - 3] = call_user_func($stack[$esp - 2] . '::' . $stack[$esp - 1], $stack[$esp]);
    'static_call' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->expr->name->parts[0] === 'call_user_func'
            && $ast[0]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->expr->args[0]->value instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && $ast[0]->expr->expr->args[0]->value->left instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && is_stack_at($ast[0]->expr->expr->args[0]->value->left->left, $ast[0]->expr->var->dim->right->value - 1)
            && $ast[0]->expr->expr->args[0]->value->left->right instanceof \PhpParser\Node\Scalar\String_
            && $ast[0]->expr->expr->args[0]->value->left->right->value === '::'
            && is_stack_at($ast[0]->expr->expr->args[0]->value->right, $ast[0]->expr->var->dim->right->value - 2)) {
            return [$ast[0]->expr->var->dim->right->value - 2];
        }
        return false;
    },
    // $stack[$esp - 3] = $stack[$esp - 2]($stack[$esp - 1], $stack[$esp]);
    // $stack[$esp - 2] = $stack[$esp - 1]($stack[$esp]);
    // $stack[$esp - 1] = $stack[$esp]();
    'func_call' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && is_stack_at($ast[0]->expr->expr->name, $ast[0]->expr->var->dim->right->value - 1)) {
            return [$ast[0]->expr->var->dim->right->value - 1];
        }
        return false;
    },
    // $stack[$esp] = include($stack[$esp]);
    'include' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Include_
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->dim->name === 'esp') {
            return [$ast[0]->expr->expr->type];
        }
        return false;
    },
    // $stack[$esp] = (bool)$stack[$esp];
    'cast_bool' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Cast\Bool_
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp] = (array)$stack[$esp];
    'cast_array' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Cast\Array_
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $stack[$esp] = (double)$stack[$esp];
    'cast_double' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->name === 'esp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Cast\Double
            && $ast[0]->expr->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->expr->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->expr->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // $temp =& $stack[$esp - 1]; unset($stack[$esp - 1]); $stack[$esp - 1] =& $temp->{$stack[$esp]}; unset($temp);
    'property_fetch' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->name === 'temp'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->var->name === 'stack'
            && $ast[0]->expr->expr->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->dim->left->name === 'esp'
            && $ast[0]->expr->expr->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->dim->right->value === 1
            && $ast[1] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[1]->vars[0] instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->vars[0]->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->vars[0]->var->name === 'stack'
            && $ast[1]->vars[0]->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[1]->vars[0]->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->vars[0]->dim->left->name === 'esp'
            && $ast[1]->vars[0]->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->vars[0]->dim->right->value === 1
            && $ast[2] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[2]->expr instanceof \PhpParser\Node\Expr\AssignRef
            && $ast[2]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->var->name === 'stack'
            && $ast[2]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[2]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->dim->left->name === 'esp'
            && $ast[2]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[2]->expr->var->dim->right->value === 1
            && $ast[2]->expr->expr instanceof \PhpParser\Node\Expr\PropertyFetch
            && $ast[2]->expr->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->var->name === 'temp'
            && $ast[2]->expr->expr->name instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->expr->name->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->name->var->name === 'stack'
            && $ast[2]->expr->expr->name->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->name->dim->name === 'esp'
            && $ast[3] instanceof \PhpParser\Node\Stmt\Unset_
            && $ast[3]->vars[0] instanceof \PhpParser\Node\Expr\Variable
            && $ast[3]->vars[0]->name === 'temp') {
            return [];
        }
        return false;
    },
    // if (is_object($stack[$esp - 1])) { $stack[$esp - 1] = get_class($stack[$esp - 1]); } $error_level_stack[++$error_level_stack_pointer] = '$stack[$esp-1]=&' . $stack[$esp - 1] . '::$' . $stack[$esp] . ';';
    'static_property_fetch' => function ($ast) {
        if (count($ast) === 2
            && $ast[0] instanceof \PhpParser\Node\Stmt\If_
            && $ast[0]->cond instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->cond->name instanceof \PhpParser\Node\Name
            && count($ast[0]->cond->name->parts) === 1
            && $ast[0]->cond->name->parts[0] === 'is_object'
            && count($ast[0]->cond->args) === 1
            && $ast[0]->cond->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->cond->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->cond->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->args[0]->value->var->name === 'stack'
            && $ast[0]->cond->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->cond->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->cond->args[0]->value->dim->left->name === 'esp'
            && $ast[0]->cond->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->cond->args[0]->value->dim->right->value === 1
            && $ast[0]->cond->args[0]->byRef === false
            && $ast[0]->cond->args[0]->unpack === false
            && count($ast[0]->stmts) === 1
            && $ast[0]->stmts[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->stmts[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->stmts[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->var->var->name === 'stack'
            && $ast[0]->stmts[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->stmts[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->stmts[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->stmts[0]->expr->var->dim->right->value === 1
            && $ast[0]->stmts[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->stmts[0]->expr->expr->name instanceof \PhpParser\Node\Name
            && count($ast[0]->stmts[0]->expr->expr->name->parts) === 1
            && $ast[0]->stmts[0]->expr->expr->name->parts[0] === 'get_class'
            && count($ast[0]->stmts[0]->expr->expr->args) === 1
            && $ast[0]->stmts[0]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->stmts[0]->expr->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->var->name === 'stack'
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->dim->left->name === 'esp'
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->stmts[0]->expr->expr->args[0]->value->dim->right->value === 1
            && $ast[0]->stmts[0]->expr->expr->args[0]->byRef === false
            && $ast[0]->stmts[0]->expr->expr->args[0]->unpack === false
            && count($ast[0]->elseifs) === 0
            && $ast[0]->else === null
            && $ast[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[1]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[1]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->var->name === 'error_level_stack'
            && $ast[1]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[1]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->dim->var->name === 'error_level_stack_pointer'
            && $ast[1]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && $ast[1]->expr->expr->left instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && $ast[1]->expr->expr->left->left instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && $ast[1]->expr->expr->left->left->left instanceof \PhpParser\Node\Expr\BinaryOp\Concat
            && $ast[1]->expr->expr->left->left->left->left instanceof \PhpParser\Node\Scalar\String_
            && $ast[1]->expr->expr->left->left->left->left->value === '$stack[$esp-1]=&'
            && $ast[1]->expr->expr->left->left->left->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->expr->expr->left->left->left->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->left->left->left->right->var->name === 'stack'
            && $ast[1]->expr->expr->left->left->left->right->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[1]->expr->expr->left->left->left->right->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->left->left->left->right->dim->left->name === 'esp'
            && $ast[1]->expr->expr->left->left->left->right->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->expr->expr->left->left->left->right->dim->right->value === 1
            && $ast[1]->expr->expr->left->left->right instanceof \PhpParser\Node\Scalar\String_
            && $ast[1]->expr->expr->left->left->right->value === '::$'
            && $ast[1]->expr->expr->left->right instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->expr->expr->left->right->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->left->right->var->name === 'stack'
            && $ast[1]->expr->expr->left->right->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->left->right->dim->name === 'esp'
            && $ast[1]->expr->expr->right instanceof \PhpParser\Node\Scalar\String_
            && $ast[1]->expr->expr->right->value === ';') {
            return [];
        }
        return false;
    },


    // ==================== 循环 ====================
    // reset($stack[$esp]);
    'reset' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->name->parts[0] === 'reset'
            && $ast[0]->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->args[0]->value->var->name === 'stack'
            && $ast[0]->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->args[0]->value->dim->name === 'esp') {
            return [];
        }
        return false;
    },
    // next($stack[$esp - 2]);
    'next' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->name->parts[0] === 'next'
            && $ast[0]->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->args[0]->value->var->name === 'stack'
            && $ast[0]->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->args[0]->value->dim->left->name === 'esp'
            && $ast[0]->expr->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->args[0]->value->dim->right->value === 2) {
            return [];
        }
        return false;
    },
    // $stack[$esp - 1] = key($stack[$esp - 2]); $stack[$esp] = current($stack[$esp - 2]); $stack[++$esp] = ($stack[$esp - 2] !== null && $stack[$esp - 2] !== false);
    'foreach' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->var->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->left->name === 'esp'
            && $ast[0]->expr->var->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->var->dim->right->value === 1
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->expr->name->parts[0] === 'key'
            && $ast[0]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->args[0]->value->var->name === 'stack'
            && $ast[0]->expr->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[0]->expr->expr->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->args[0]->value->dim->left->name === 'esp'
            && $ast[0]->expr->expr->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->args[0]->value->dim->right->value === 2
            && $ast[0]->expr->expr->args[0]->byRef === false
            && $ast[0]->expr->expr->args[0]->unpack === false
            && $ast[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[1]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[1]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->var->name === 'stack'
            && $ast[1]->expr->var->dim instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->dim->name === 'esp'
            && $ast[1]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[1]->expr->expr->name instanceof \PhpParser\Node\Name
            && $ast[1]->expr->expr->name->parts[0] === 'current'
            && $ast[1]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[1]->expr->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[1]->expr->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->args[0]->value->var->name === 'stack'
            && $ast[1]->expr->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[1]->expr->expr->args[0]->value->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->expr->args[0]->value->dim->left->name === 'esp'
            && $ast[1]->expr->expr->args[0]->value->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->expr->expr->args[0]->value->dim->right->value === 2
            && $ast[1]->expr->expr->args[0]->byRef === false
            && $ast[1]->expr->expr->args[0]->unpack === false
            && $ast[2] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[2]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[2]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->var->name === 'stack'
            && $ast[2]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[2]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->var->dim->var->name === 'esp'
            && $ast[2]->expr->expr instanceof \PhpParser\Node\Expr\BinaryOp\BooleanAnd
            && $ast[2]->expr->expr->left instanceof \PhpParser\Node\Expr\BinaryOp\NotIdentical
            && $ast[2]->expr->expr->left->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->expr->left->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->left->left->var->name === 'stack'
            && $ast[2]->expr->expr->left->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[2]->expr->expr->left->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->left->left->dim->left->name === 'esp'
            && $ast[2]->expr->expr->left->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[2]->expr->expr->left->left->dim->right->value === 2
            && $ast[2]->expr->expr->left->right instanceof \PhpParser\Node\Expr\ConstFetch
            && $ast[2]->expr->expr->left->right->name instanceof \PhpParser\Node\Name
            && $ast[2]->expr->expr->left->right->name->parts[0] === 'null'
            && $ast[2]->expr->expr->right instanceof \PhpParser\Node\Expr\BinaryOp\NotIdentical
            && $ast[2]->expr->expr->right->left instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[2]->expr->expr->right->left->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->right->left->var->name === 'stack'
            && $ast[2]->expr->expr->right->left->dim instanceof \PhpParser\Node\Expr\BinaryOp\Minus
            && $ast[2]->expr->expr->right->left->dim->left instanceof \PhpParser\Node\Expr\Variable
            && $ast[2]->expr->expr->right->left->dim->left->name === 'esp'
            && $ast[2]->expr->expr->right->left->dim->right instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[2]->expr->expr->right->left->dim->right->value === 2
            && $ast[2]->expr->expr->right->right instanceof \PhpParser\Node\Expr\ConstFetch
            && $ast[2]->expr->expr->right->right->name instanceof \PhpParser\Node\Name
            && $ast[2]->expr->expr->right->right->name->parts[0] === 'false') {
            return [];
        }
        return false;
    },

    // ==================== 报错等级 ====================
    // $error_level_stack[$error_level_stack_pointer] = error_reporting($stack[$esp]);
    'push_error_level' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'error_level_stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'error_level_stack_pointer'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->expr->name->parts[0] === 'error_reporting'
            && $ast[0]->expr->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->expr->args[0]->value instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[0]->expr->expr->args[0]->value->value === 0) {
            return [];
        }
        return false;
    },
    // error_reporting($error_level_stack[$error_level_stack_pointer]);
    'pop_error_level' => function ($ast) {
        if ($ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $ast[0]->expr->name instanceof \PhpParser\Node\Name
            && $ast[0]->expr->name->parts[0] === 'error_reporting'
            && $ast[0]->expr->args[0] instanceof \PhpParser\Node\Arg
            && $ast[0]->expr->args[0]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->args[0]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->args[0]->value->var->name === 'error_level_stack'
            && $ast[0]->expr->args[0]->value->dim instanceof \PhpParser\Node\Expr\PostDec
            && $ast[0]->expr->args[0]->value->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->args[0]->value->dim->var->name === 'error_level_stack_pointer') {
            return [];
        }
        return false;
    },
    // $error_level_stack[++$error_level_stack_pointer] = $eip; $eip = -2;
    'eval_3' => function ($ast) {
        if (count($ast) === 2
            && $ast[0] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[0]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[0]->expr->var instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $ast[0]->expr->var->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->var->name === 'error_level_stack'
            && $ast[0]->expr->var->dim instanceof \PhpParser\Node\Expr\PreInc
            && $ast[0]->expr->var->dim->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->var->dim->var->name === 'error_level_stack_pointer'
            && $ast[0]->expr->expr instanceof \PhpParser\Node\Expr\Variable
            && $ast[0]->expr->expr->name === 'eip'
            && $ast[1] instanceof \PhpParser\Node\Stmt\Expression
            && $ast[1]->expr instanceof \PhpParser\Node\Expr\Assign
            && $ast[1]->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $ast[1]->expr->var->name === 'eip'
            && $ast[1]->expr->expr instanceof \PhpParser\Node\Expr\UnaryMinus
            && $ast[1]->expr->expr->expr instanceof \PhpParser\Node\Scalar\LNumber
            && $ast[1]->expr->expr->expr->value === 2) {
            return [];
        }
        return false;
    },

    'UNKNOWN' => function ($ast) {
        if (false) {
            return [];
        }
        return false;
    },
];