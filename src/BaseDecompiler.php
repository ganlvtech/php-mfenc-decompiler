<?php
/**
 * Created by PhpStorm.
 * User: Ganlv
 * Date: 2018/7/20
 * Time: 17:41
 */

namespace Ganlv\MfencDecompiler;


use Exception;
use Ganlv\MfencDecompiler\Exceptions\PopWhenStackEmptyException;
use Ganlv\MfencDecompiler\NodeVisitors\ApplyAssignAttributeNodeVisitor;
use Ganlv\MfencDecompiler\NodeVisitors\SetAttributePopedNodeVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;

class BaseDecompiler
{
    public $instructions;
    public $pointer;
    public $ast = [];
    public $stack = [];
    public $breaks = [];

    public function __construct(array $structuredInstructions, array $stack = [])
    {
        $this->instructions = $structuredInstructions;
        $this->pointer = 0;
        $this->ast = [];
        $this->stack = $stack;
        $this->breaks = [];
    }

    public function decompile()
    {
        while ($this->decompileNextInstruction()) {
        }
    }

    public function getCurrentInstruction()
    {
        return isset($this->instructions[$this->pointer]) ? $this->instructions[$this->pointer] : null;
    }

    public function decompileNextInstruction()
    {
        // echo "=== 指令 {$this->pointer} ===", PHP_EOL;

        $instruction = $this->getCurrentInstruction();
        if (!$instruction) {
            return false;
        }

        // Helper::printStructuredInstructions([$instruction]);

        $method = '_' . $instruction['operation'];
        if (!method_exists($this, $method)) {
            throw new Exception('method not exists ' . $method);
        }
        assert(method_exists($this, $method));

        $this->$method(...$instruction['operands']);

        // echo '--- 指令反编译后AST ---', PHP_EOL;
        // echo Helper::prettyPrint($this->ast), PHP_EOL;
        // echo '--- 指令反编译后栈 ---', PHP_EOL;
        // echo Helper::prettyPrint($this->stack), PHP_EOL;
        // echo '================', PHP_EOL;
        // echo PHP_EOL;

        ++$this->pointer;
        return true;
    }

    // ==================== AST 操作 ====================

    protected function pushToAst($stmt)
    {
        $this->ast[] = $stmt;
    }

    // ==================== 栈操作 ====================

    protected function push($expr)
    {
        $this->stack[] = $expr;
    }

    protected function pop()
    {
        // 某些循环开始的地方可能出现即使是空栈依然要求弹出一个元素
        if (empty($this->stack)) {
            return new PopWhenStackEmptyException();
        }
        return array_pop($this->stack);
    }

    protected function popOrToAst()
    {
        $expr = $this->pop();
        [$expr] = Helper::traverseAst(new ApplyAssignAttributeNodeVisitor(), [$expr]);
        if ($expr instanceof Stmt) {
            $this->pushToAst($expr);
        } elseif ($expr instanceof Scalar || $expr instanceof ConstFetch) {
            // 字面量、常量出栈时，不会造成负面影响，所以 AST 肯定不会变化
            // do nothing
        } elseif ($expr instanceof Variable && is_string($expr->name)) {
            // 简单变量
        } elseif ($expr instanceof ArrayDimFetch
            && $expr->var instanceof Variable
            && is_string($expr->var->name)
            && ($expr->dim instanceof String_ || $expr->dim instanceof LNumber)) {
            // 简单数组带字符串下标或带数字下标
        } elseif ($expr instanceof Expr) {
            if (!self::isUsed($expr)) {
                $this->pushToAst(new Expression($expr));
            }
        } else {
            var_dump($expr);
        }
        Helper::traverseAst(new SetAttributePopedNodeVisitor(), [$expr]);
        return $expr;
    }

    protected function getStackLastIndex()
    {
        return count($this->stack) - 1;
    }

    protected function getStackIndexByOffset($offset = 0)
    {
        $index = $this->getStackLastIndex() - $offset;
        assert($index >= 0);
        return $index;
    }

    protected function getStackItemByOffset($offset = 0)
    {
        return $this->stack[$this->getStackIndexByOffset($offset)];
    }

    protected function replace($varIndex, Node $expr)
    {
        $this->stack[$varIndex] = $expr;
        return $expr;
    }

    protected function replaceByOffset($varOffset, Node $expr)
    {
        return $this->replace($this->getStackIndexByOffset($varOffset), $expr);
    }

    protected function replaceOrAssign($varIndex, Expr $expr)
    {
        $var = $this->stack[$varIndex];
        return $this->replace($varIndex, $this->wrapAssign($var, $expr));
    }

    protected function replaceOrAssignByOffset($varOffset, Expr $expr)
    {
        return $this->replaceOrAssign($this->getStackIndexByOffset($varOffset), $expr);
    }

    protected function wrapAssign(Expr $var, Expr $expr)
    {
        self::used($var);
        if (($var instanceof Variable && self::isByRef($var))
            || ($var instanceof ArrayDimFetch && self::isByRef($var))
            || ($var instanceof PropertyFetch && self::isByRef($var))
            || $var instanceof StaticPropertyFetch && self::isByRef($var)) {
            self::used($expr);
            $assign = new Assign($var, $expr);
            $this->setInstructionAttribute($assign);
            self::setAssignTo($expr, $assign);
            return $expr;
        } else {
            return $expr;
        }
    }

    // ==================== Node 属性 ====================

    protected function setInstructionAttribute(Node $node)
    {
        $node->setAttribute('instruction', $this->getCurrentInstruction());
        return $node;
    }

    public static function used(Node $node)
    {
        $node->setAttribute('used', true);
        return $node;
    }

    public static function isUsed(Node $node)
    {
        return (bool)$node->getAttribute('used', false);
    }

    public static function byRef(Node $node)
    {
        $node->setAttribute('byRef', true);
        return $node;
    }

    public static function byVal(Node $node)
    {
        $node->setAttribute('byRef', false);
        return $node;
    }

    public static function isByRef(Node $node)
    {
        return (bool)$node->getAttribute('byRef', false);
    }

    public static function setAssignTo(Expr $expr, $value)
    {
        $expr->setAttribute('assign_to', $value);
        return $expr;
    }

    public static function getAssignTo(Expr $expr, $default = null)
    {
        return $expr->getAttribute('assign_to', $default);
    }

    public static function setAssignToList(Expr $expr, $value)
    {
        $expr->setAttribute('assign_to_list', $value);
        return $expr;
    }

    public static function getAssignToList(Expr $expr, $default = null)
    {
        return $expr->getAttribute('assign_to_list', $default);
    }
}