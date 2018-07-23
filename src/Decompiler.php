<?php

namespace Ganlv\MfencDecompiler;

use Exception;
use Ganlv\MfencDecompiler\Exceptions\DecompileFunctionBreakException;
use Ganlv\MfencDecompiler\Exceptions\StackNotCleanException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Array_ as CastArray;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\While_;

class Decompiler extends BaseDecompiler
{
    // ==================== 通用 ====================

    protected function unaryOp($name)
    {
        $operand = $this->getStackItemByOffset(0);
        self::used($operand);
        $expr = new $name($operand);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset(0, $expr);
        return $expr;
    }

    protected function binaryOp($name)
    {
        $left = $this->getStackItemByOffset(1);
        $right = $this->getStackItemByOffset(0);
        self::used($left);
        self::used($right);
        $expr = new $name($left, $right);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset(1, $expr);
        return $expr;
    }

    // ==================== 栈操作 ====================

    protected function _load_const($name)
    {
        $expr = new ConstFetch(new Name($name));
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset(0, $expr);
        return $expr;
    }

    protected function _push_const($name)
    {
        $expr = new ConstFetch(new Name($name));
        $this->setInstructionAttribute($expr);
        $this->push($expr);
        return $expr;
    }

    protected function _push_string($value)
    {
        $expr = Helper::tryDoubleQuoted(new String_($value));
        $this->setInstructionAttribute($expr);
        $this->push($expr);
        return $expr;
    }

    protected function _push_number($value)
    {
        if (is_int($value)) {
            $expr = new LNumber($value);
        } elseif (is_float($value)) {
            $expr = new DNumber($value);
        } else {
            throw new Exception('_push_number error');
        }
        $this->setInstructionAttribute($expr);
        $this->push($expr);
        return $expr;
    }

    protected function _push_array()
    {
        $expr = new Array_([]);
        $this->setInstructionAttribute($expr);
        $this->push($expr);
        return $expr;
    }

    protected function _push_magic_const_file()
    {
        $expr = new File();
        $this->setInstructionAttribute($expr);
        $this->push($expr);
        return $expr;
    }

    protected function _push_magic_const_dir()
    {
        $expr = new Dir();
        $this->setInstructionAttribute($expr);
        $this->push($expr);
        return $expr;
    }

    protected function _pop($count = 1)
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->popOrToAst();
        }
        return null;
    }

    protected function _reference()
    {
        $name = $this->getStackItemByOffset();
        assert($name instanceof String_);
        $var = new Variable($name);
        self::byRef($var);
        $this->setInstructionAttribute($var);
        $this->replaceOrAssignByOffset(0, $var);
        return $var;
    }

    protected function _dereference()
    {
        $var = $this->getStackItemByOffset();
        if (self::isByRef($var)) {
            self::byVal($var);
        }
        return $var;
    }

    protected function _assign($varOffset, $exprOffset)
    {
        // TODO
        $expr = $this->getStackItemByOffset($exprOffset);
        self::used($expr);
        if (self::isByRef($expr)) {
            $expr = clone($expr);
            self::byVal($expr);
        }
        $assign = $this->replaceOrAssignByOffset($varOffset, $expr);
        return $assign;
    }

    // ==================== 跳转 ====================

    protected function _if($stmtsInstructions, $elseInstructions)
    {
        $cond = $this->getStackItemByOffset();
        assert(!self::isByRef($cond));
        self::used($cond);

        $decompiler = new self($stmtsInstructions, $this->stack);
        try {
            $decompiler->decompile();
            $stmtsIsBreak = false;
        } catch (DecompileFunctionBreakException $e) {
            $stmtsIsBreak = true;
            $this->breaks[] = [
                'exception' => $e,
                'stack' => $decompiler->stack,
            ];
        }
        $stmtsAst = $decompiler->ast;
        $stmtsStack = $decompiler->stack;

        $decompiler = new self($elseInstructions, $this->stack);
        try {
            $decompiler->decompile();
            $elseIsBreak = false;
        } catch (DecompileFunctionBreakException $e) {
            $elseIsBreak = true;
            $this->breaks[] = [
                'exception' => $e,
                'stack' => $decompiler->stack,
            ];
        }
        $elseAst = $decompiler->ast;
        $elseStack = $decompiler->stack;

        if (count($stmtsStack) === count($this->stack) && count($elseStack) === count($this->stack)
            && count($stmtsAst) === 0 && count($elseAst) === 0) {
            // 三元运算符、逻辑短路
            // 这种 if 操作之后，不会生成 AST，只会把 cond 替换成结果值，栈的长度不变
            $stmtsResult = array_pop($stmtsStack);
            $elseResult = array_pop($elseStack);
            self::used($stmtsResult); // TODO remove
            self::used($elseResult); // TODO remove
            if ($cond === $stmtsResult) {
                // 逻辑或短路：如果 cond 为真则结果为 cond，即：stmts 块的结果为 cond，else 块的结果为另一个值
                $expr = new LogicalOr($cond, $elseResult);
                $this->setInstructionAttribute($expr);
                $this->replaceOrAssignByOffset(0, $expr);
            } elseif ($cond === $elseResult) {
                // 逻辑与短路：如果 cond 为假则结果为 cond，即：else 块的结果为 cond，stmts 块的结果为另一个值
                $expr = new LogicalAnd($cond, $stmtsResult);
                $this->setInstructionAttribute($expr);
                $this->replaceOrAssignByOffset(0, $expr);
            } else {
                // 三元运算符：根据 cond 的不同，stmts 块和 else 块的结果不同（均不为 cond）
                $expr = new Ternary($cond, $stmtsResult, $elseResult);
                $this->setInstructionAttribute($expr);
                $this->replaceOrAssignByOffset(0, $expr);
            }
            return $expr;
        } elseif (count($stmtsStack) === count($elseStack) || $stmtsIsBreak || $elseIsBreak) {
            // 最普通的 if 语句
            // if (cond) { statements; } else { statements; }
            $if = new If_($cond);
            $if->stmts = $stmtsAst;
            $if->else = new Else_($elseAst);
            $this->setInstructionAttribute($if);
            $this->pushToAst($if); // TODO check if anything wrong

            // 出现 break 表示在循环内部，if 的两个分支，一个是继续执行循环，另一个是直接跳出循环
            // 为了让循环继续进行，出现 break 时，应该选择另一分支的栈作为结果栈，然后继续执行循环，构造出完整的循环的 AST
            // break 语句所在的分支的 AST，直接作为 if 这一分支的 AST 即可
            if ($stmtsIsBreak) {
                $this->stack = $elseStack;
            } elseif ($elseIsBreak) {
                $this->stack = $stmtsStack;
            } else {
                $this->stack = $stmtsStack;
            }
            return $if;
        } else {
            throw new Exception('Unrecognizable if statement');
        }
    }

    protected function _loop($instructions)
    {
        if (count($instructions) >= 4
            && $instructions[0]['operation'] === 'foreach'
            && $instructions[1]['operation'] === 'if'
            && count($instructions[1]['operands'][0]) === 0
            && count($instructions[1]['operands'][1]) >= 1
            // && ($instructions[1]['operands'][1][count($instructions[1]['operands'][1]) - 1]['operation'] === 'return'
            //     || $instructions[1]['operands'][1][count($instructions[1]['operands'][1]) - 1]['operation'] === 'break')
            && $instructions[2]['operation'] === 'pop'
            && $instructions[2]['operands'][0] === 1) {
            // push_string  'var'
            // reference
            // dereference
            // reset
            // push_string  'k'
            // reference
            // push_string  'v'
            // reference
            // loop
            //     foreach
            //     if
            //     else
            //         ......
            //         break // or return
            //     pop  1
            //     ......
            //     next
            // dereference
            // pop  1
            // dereference
            // pop  1
            // pop  1

            // foreach 指令
            $condInstructions = array_slice($instructions, 0, 1);
            $decompiler = new self($condInstructions, $this->stack);
            $decompiler->decompile();
            $condStack = $decompiler->stack;
            self::used($condStack[count($condStack) - 3]);
            self::used($condStack[count($condStack) - 2]);
            self::used($condStack[count($condStack) - 1]);
            self::setAssignTo($condStack[count($condStack) - 3], null);

            // break 之后执行的语句
            $breakInstructions = $instructions[1]['operands'][1];
            $decompiler = new self($breakInstructions, $condStack);
            $breakException = null;
            try {
                $decompiler->decompile();
            } catch (DecompileFunctionBreakException $e) {
                $breakException = $e;
            }
            $breakAst = $decompiler->ast;
            $breakStack = $decompiler->stack;
            if ($breakAst[count($breakAst) - 1] instanceof Break_) {
                array_pop($breakAst);
            }

            if (is_null($breakException)) {
                throw new Exception('foreach without break');
            }

            // 循环体
            $loopInstructions = array_slice($instructions, 2, count($instructions) - 3);
            $decompiler = new self($loopInstructions, $condStack);
            $decompiler->decompile();
            $loopAst = $decompiler->ast;
            $loopStack = $decompiler->stack;
            if (count($this->stack) !== count($loopStack)) {
                var_dump(new StackNotCleanException($this->stack, $loopStack));
            }

            // 前面一定有一个 reset
            assert($this->ast[count($this->ast) - 1] instanceof \PhpParser\Node\Stmt\Expression
                && $this->ast[count($this->ast) - 1]->expr instanceof \PhpParser\Node\Expr\FuncCall
                && $this->ast[count($this->ast) - 1]->expr->name instanceof \PhpParser\Node\Name
                && count($this->ast[count($this->ast) - 1]->expr->name->parts) === 1
                && $this->ast[count($this->ast) - 1]->expr->name->parts[0] === 'reset'
                && count($this->ast[count($this->ast) - 1]->expr->args) === 1
                && $this->ast[count($this->ast) - 1]->expr->args[0] instanceof \PhpParser\Node\Arg);

            $keyVar = $this->getStackItemByOffset(1);
            if ($keyVar instanceof \PhpParser\Node\Expr\ConstFetch
                && $keyVar->name instanceof \PhpParser\Node\Name
                && count($keyVar->name->parts) === 1
                && $keyVar->name->parts[0] === 'null') {
                $keyVar = null;
            }
            $loop = new Foreach_($this->getStackItemByOffset(2), $this->getStackItemByOffset(), [
                'keyVar' => $keyVar,
                'stmts' => $loopAst,
            ]);
            $this->setInstructionAttribute($loop);
            $this->ast[count($this->ast) - 1] = $loop;

            foreach ($breakAst as $stmt) {
                $this->pushToAst($stmt);
            }
            $this->stack = $breakStack;

            if ($breakException->type === 'return') {
                throw $breakException;
            }
            return $loop;
        } else {
            $decompiler = new self($instructions, $this->stack);
            $decompiler->decompile();
            $loopAst = $decompiler->ast;
            $loopStack = $decompiler->stack;

            if (count($this->stack) !== count($loopStack)) {
                var_dump(new StackNotCleanException($this->stack, $loopStack));
            }
            $loop = new While_(new ConstFetch(new Name('true')), $loopAst);
            $this->setInstructionAttribute($loop);
            $this->pushToAst($loop);

            if ($decompiler->breaks) {
                foreach ($decompiler->breaks as $break) {
                    if ($break['exception']->type === 'break') {
                        $this->stack = $break['stack'];
                    }
                }
                foreach ($decompiler->breaks as $break) {
                    if ($break['exception']->type === 'return') {
                        throw $break['exception'];
                    }
                }
            }
            return $loop;
        }
    }

    // ==================== 语言结构 ====================

    protected function _global($name)
    {
        $stmt = new Global_([new Variable(new Name($name))]);
        $this->pushToAst($this->setInstructionAttribute($stmt));
        return $stmt;
    }

    protected function _empty($exprCode)
    {
        $ast = Helper::parseExprCode($exprCode);
        $stmt = new Empty_($ast[0]->expr);
        $this->setInstructionAttribute($stmt);
        $this->replaceOrAssignByOffset(0, $stmt);
        return $stmt;
    }

    protected function _isset($exprCode)
    {
        $ast = Helper::parseExprCode($exprCode);
        $stmt = new Isset_([$ast[0]->expr]);
        $this->setInstructionAttribute($stmt);
        $this->replaceOrAssignByOffset(0, $stmt);
        return $stmt;
    }

    protected function _exit()
    {
        $expr = $this->getStackItemByOffset(0);
        self::used($expr);
        $stmt = new Expression(new Exit_($expr, [
            'kind' => Exit_::KIND_EXIT,
        ]));
        $this->setInstructionAttribute($stmt);
        $this->pushToAst($stmt);
        return $stmt;
    }

    protected function _new()
    {
        $expr = $this->getStackItemByOffset(0);
        assert($expr instanceof String_);
        $stmt = new New_($expr);
        $this->setInstructionAttribute($stmt);
        $this->replaceOrAssignByOffset(0, $stmt);
        return $stmt;
    }

    protected function _echo()
    {
        $stmt = new Echo_([$this->getStackItemByOffset(0)]);
        $this->setInstructionAttribute($stmt);
        $this->replaceByOffset(0, $stmt);
        return $stmt;
    }

    protected function _return()
    {
        $stmt = new Return_($this->pop());
        $this->setInstructionAttribute($stmt);
        $this->pushToAst($stmt);
        throw new DecompileFunctionBreakException('return');
    }

    // ==================== 数组操作 ====================

    protected function _array_dim_fetch($varOffset, $dimOffset)
    {
        $var = $this->getStackItemByOffset($varOffset);
        self::byVal($var); // TODO remove
        self::used($var);
        if (is_null($dimOffset)) {
            $dim = null;
        } else {
            $dim = $this->getStackItemByOffset($dimOffset);
            self::used($dim);
        }
        $expr = new ArrayDimFetch($var, $dim);
        self::byRef($expr);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset($varOffset, $expr);
        return $expr;
    }

    protected function _array_item($key)
    {
        // TODO 特殊操作
        $lastIndex = $this->getStackLastIndex();
        if (is_null($key)) {
            $var = $this->stack[$lastIndex - 1];
            $expr = $this->stack[$lastIndex];
        } else {
            $var = $this->stack[$lastIndex - 2];
            $key = $this->stack[$lastIndex - 1];
            self::used($key);
            $expr = $this->stack[$lastIndex];
        }
        assert($var instanceof Array_);
        self::used($expr);
        $item = new ArrayItem($expr, $key);
        $this->setInstructionAttribute($item);
        $var->items[] = $item;
        return $item;
    }

    protected function _list_assign($varOffset, $exprOffset, $index)
    {
        // TODO 特殊操作
        $var = $this->getStackItemByOffset($varOffset);
        assert($var instanceof Variable);
        self::used($var);
        $expr = $this->getStackItemByOffset($exprOffset);
        self::used($expr);
        if (!self::getAssignToList($expr)) {
            self::setAssignToList($expr, new List_([]));
        }
        $item = new ArrayItem($var, new LNumber($index));
        self::used($item);
        $this->setInstructionAttribute($item);
        $list = self::getAssignToList($expr);
        $list->items[] = $item;
        self::setAssignToList($expr, $list);
    }

    // ==================== 运算符 ====================

    protected function _boolean_not()
    {
        $this->unaryOp(BooleanNot::class);
    }

    protected function _plus()
    {
        $this->binaryOp(Plus::class);
    }

    protected function _minus()
    {
        $this->binaryOp(Minus::class);
    }

    protected function _mul()
    {
        $this->binaryOp(Mul::class);
    }

    protected function _div()
    {
        $this->binaryOp(Div::class);
    }

    protected function _mod()
    {
        $this->binaryOp(Mod::class);
    }

    protected function _concat()
    {
        $this->binaryOp(Concat::class);
    }

    protected function _greater_or_equal()
    {
        $this->binaryOp(GreaterOrEqual::class);
    }

    protected function _greater()
    {
        $this->binaryOp(Greater::class);
    }

    protected function _equal()
    {
        $this->binaryOp(Equal::class);
    }

    protected function _identical()
    {
        $this->binaryOp(Identical::class);
    }

    // ==================== 其他运算 ====================

    protected function _include($type)
    {
        $expr = new Include_($this->getStackItemByOffset(0), $type);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset(0, $expr);
    }

    protected function _method_call($argCount)
    {
        $var = $this->getStackItemByOffset($argCount + 1);
        self::used($var);
        $name = $this->getStackItemByOffset($argCount);
        self::used($name);
        assert($name instanceof String_);
        $args = [];
        for ($i = 0; $i < $argCount; ++$i) {
            $arg = $this->getStackItemByOffset($argCount - 1 - $i);
            self::used($arg);
            $args[$i] = new Arg($arg);
        }
        $expr = new MethodCall($var, $name, $args);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset($argCount + 2, $expr);
    }

    protected function _static_call($argCount)
    {
        $class = $this->getStackItemByOffset($argCount + 1);
        self::used($class);
        assert($class instanceof String_);
        $name = $this->getStackItemByOffset($argCount);
        self::used($name);
        assert($name instanceof String_);
        $args = [];
        for ($i = 0; $i < $argCount; ++$i) {
            $arg = $this->getStackItemByOffset($argCount - 1 - $i);
            self::used($arg);
            $args[$i] = new Arg($arg);
        }
        $expr = new StaticCall($class, $name, $args);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset($argCount + 2, $expr);
    }

    protected function _func_call($argCount)
    {
        $name = $this->getStackItemByOffset($argCount);
        self::used($name);
        assert($name instanceof String_);
        $args = [];
        for ($i = 0; $i < $argCount; ++$i) {
            $arg = $this->getStackItemByOffset($argCount - 1 - $i);
            self::used($arg);
            $args[$i] = new Arg($arg);
        }
        $expr = new FuncCall($name, $args);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset($argCount + 1, $expr);
    }

    protected function _cast_bool()
    {
        $this->unaryOp(Bool_::class);
    }

    protected function _cast_array()
    {
        $this->unaryOp(CastArray::class);
    }

    protected function _cast_double()
    {
        $this->unaryOp(Double::class);
    }

    protected function _property_fetch()
    {
        $var = $this->getStackItemByOffset(1);
        self::byVal($var);
        self::used($var);
        $name = $this->getStackItemByOffset(0);
        self::used($name);
        assert($name instanceof String_);
        $expr = new PropertyFetch($var, $name);
        self::byRef($expr);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset(1, $expr);
    }

    protected function _static_property_fetch()
    {
        $class = $this->getStackItemByOffset(1);
        assert($class instanceof String_);
        self::used($class);
        $name = $this->getStackItemByOffset(0);
        self::used($name);
        assert($name instanceof String_);
        $expr = new StaticPropertyFetch($class, $name);
        self::byRef($expr);
        $this->setInstructionAttribute($expr);
        $this->replaceOrAssignByOffset(1, $expr);
    }

    // ==================== 循环 ====================

    protected function _break()
    {
        $stmt = new Break_();
        $this->setInstructionAttribute($stmt);
        $this->pushToAst($stmt);
        throw new DecompileFunctionBreakException('break');
    }

    protected function _reset()
    {
        $arr = $this->getStackItemByOffset(0);
        self::used($arr);
        $expr = new FuncCall(new Name('reset'), [
            new Arg($arr),
        ]);
        $this->setInstructionAttribute($expr);
        $stmt = new Expression($expr);
        $this->pushToAst($stmt);
        return $stmt;
    }

    protected function _next()
    {
        $arr = $this->getStackItemByOffset(2);
        self::used($arr);
        $expr = new FuncCall(new Name('next'), [
            new Arg($arr),
        ]);
        $this->setInstructionAttribute($expr);
        $stmt = new Expression($expr);
        $this->pushToAst($stmt);
        return $stmt;
    }

    protected function _foreach()
    {
        $arr = $this->getStackItemByOffset(2);
        self::used($arr);
        $keyVar = $this->getStackItemByOffset(1);
        self::used($keyVar);
        $valVar = $this->getStackItemByOffset();
        self::used($valVar);

        $keyFunc = new FuncCall(new Name('key'), [
            new Arg($arr),
        ]);
        $this->setInstructionAttribute($keyFunc);
        $keyExpr = $this->wrapAssign($keyVar, $keyFunc); // TODO replaceOrAssignByOffset
        if ($keyExpr instanceof Assign) {
            $keyStmt = new Expression($keyExpr);
            $this->setInstructionAttribute($keyStmt);
            $this->pushToAst($keyStmt);
        } else {
            $this->replaceOrAssignByOffset(1, $keyExpr);
        }

        $currentFunc = new FuncCall(new Name('current'), [
            new Arg($arr),
        ]);
        $this->setInstructionAttribute($currentFunc);
        $valExpr = $this->wrapAssign($valVar, $currentFunc);
        $valStmt = new Expression($valExpr);
        $this->setInstructionAttribute($valStmt);
        $this->pushToAst($valStmt);

        $cond = new NotIdentical($keyVar, new ConstFetch(new Name('null')));
        $this->setInstructionAttribute($cond);
        $this->push($cond); // Simplify not need BooleanAnd according to php key function
    }

    // ==================== 报错等级 ====================

    protected function _push_error_level()
    {
        // nothing here
    }

    protected function _pop_error_level()
    {
        $this->unaryOp(ErrorSuppress::class);
    }

    protected function _eval_2($code)
    {
        $ast = Helper::parseExprCode($code);
        echo $code, PHP_EOL, Helper::toConditionExpression($ast), PHP_EOL;
    }

    protected function _eval_3()
    {
        assert($this->getStackItemByOffset(1) instanceof StaticPropertyFetch);
    }
}