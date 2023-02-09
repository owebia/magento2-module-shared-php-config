<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Exception;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use OutOfBoundsException;
use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Model\Wrapper\AbstractWrapper;
use Owebia\SharedPhpConfig\Model\Wrapper\SourceWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PhpParser\PrettyPrinter;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Evaluator
{
    private const UNDEFINED_INDEX = 301;

    /**
     * @var array
     */
    private $debugOutput = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var WrapperContext
     */
    private $wrapperContext;

    /**
     * @var PrettyPrinter\Standard
     */
    private $prettyPrinter = null;

    /**
     * @param Escaper $escaper
     * @param WrapperContext $wrapperContext
     */
    public function __construct(
        Escaper $escaper,
        WrapperContext $wrapperContext
    ) {
        $this->escaper = $escaper;
        $this->wrapperContext = $wrapperContext;
    }

    /**
     * @return string
     */
    public function getDebugOutput()
    {
        return implode("\n", $this->debugOutput);
    }

    /**
     * Reset
     */
    public function reset()
    {
        $this->debugOutput = [];
        $this->errors = [];
    }

    /**
     * @param string $msg
     * @param mixed $expr
     * @throws Exception
     */
    private function error($msg, $expr)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $this->errors[] = [
            'level' => 'ERROR',
            'msg' => $msg,
            // 'code' => $this->prettyPrint($expr),
            'expression' => $expr,
            'line' => $trace[0]['line']
        ];
        throw new LocalizedException(__($msg));
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        $msg = [];
        foreach ($this->errors as $error) {
            $msg[] = $error['msg'];
        }
        return implode('<br/>', $msg);
    }

    /**
     * @param ParserContextInterface $context
     * @param mixed $node
     * @param mixed $result
     * @param bool $wrap
     * @return mixed
     */
    private function debug(ParserContextInterface $context, $node, $result, bool $wrap = true)
    {
        if ($context->getDebug()) {
            $right = $this->prettyPrint($result);
            $left = $this->prettyPrint($node);
            $uid = 'p' . uniqid();
            if ($left !== $right) {
                $this->debugOutput[] = '<div data-target="#' . $uid . '"><pre class=php>'
                        . $this->escaper->escapeHtml($left)
                    . '</pre>'
                    . '<div class="hidden target" id="' . $uid . '"><pre class="php result">'
                        . $this->escaper->escapeHtml("// Result\n$right")
                    . '</pre></div></div>';
            }
        }
        return $wrap ? $this->wrapperContext->wrap($result) : $result;
    }

    /**
     * @return PrettyPrinter\Standard
     */
    public function getPrettyPrinter(): PrettyPrinter\Standard
    {
        return $this->prettyPrinter ??= new PrettyPrinter\Standard(['shortArraySyntax' => true]);
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function prettyPrint($value): string
    {
        if (!isset($value) || is_bool($value) || is_int($value) || is_string($value)) {
            return var_export($value, true) ?? '';
        } elseif (is_float($value)) {
            return (string)$value;
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_object($item) || is_array($item)) {
                    return 'array(size:' . count($value) . ')';
                }
            }
            // return $this->getPrettyPrinter()->pExpr_Array(new Node\Expr\Array_($value));
            return var_export($value, true) ?? '';
        } elseif (is_object($value)) {
            if ($value instanceof Node) {
                if ($value->hasAttribute('comments')) {
                    $value->setAttribute('comments', []);
                }
                return rtrim($this->getPrettyPrinter()->prettyPrint([
                    $value
                ]), ';');
            } elseif ($value instanceof AbstractWrapper) {
                return (string)$value;
            } else {
                return "/** @var " . get_class($value) . " \$obj */ \$obj";
            }
        } else {
            return (string)$value;
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param array $stmts
     * @return mixed
     */
    public function evaluateStmts(ParserContextInterface $context, $stmts)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_) {
                return $stmt;
            }

            $result = $this->evaluate($context, $stmt);
            if (is_array($result) && $this->doesArrayContainOnly($result, NodeAbstract::class)) {
                $result = $this->evaluateStmts($context, $result);
            }
            if ($result instanceof Node\Stmt\Return_) {
                return $result;
            }
        }
        return null;
    }

    /**
     * @param array $data
     * @param string $className
     * @return bool
     */
    private function doesArrayContainOnly(array $data, string $className): bool
    {
        foreach ($data as $item) {
            if (!is_a($item, $className)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param ParserContextInterface $context
     * @param mixed $expression
     * @return mixed
     * @throws Exception
     */
    public function evaluate(ParserContextInterface $context, $expression)
    {
        return $this->evl($context, $expression);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr $expression
     * @param int $increment
     * @param bool $incrementBefore
     * @return mixed
     * @throws Exception
     */
    private function incOp(ParserContextInterface $context, $expression, $increment, $incrementBefore)
    {
        $variableName = $expression->var->name;
        $oldValue = $context->getRegistry()->get($variableName);
        $newValue = $oldValue + $increment;
        $context->getRegistry()->register($variableName, $newValue, true);
        return $this->debug($context, $expression, $incrementBefore ? $newValue : $oldValue);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExpr(ParserContextInterface $context, Node\Expr $expr)
    {
        $className = get_class($expr);
        if ($expr instanceof Node\Expr\BinaryOp) {
            return $this->evalNodeExprBinaryOp($context, $expr);
        } elseif ($expr instanceof Node\Expr\AssignOp) {
            return $this->evalNodeExprAssignOp($context, $expr);
        } elseif ($expr instanceof Node\Expr\Cast) {
            return $this->evalNodeExprCast($context, $expr);
        }

        switch ($className) {
            // Arithmetic Operators
            // https://www.php.net/manual/en/language.operators.arithmetic.php
            case Node\Expr\UnaryMinus::class:
                return $this->debug($context, $expr, - $this->evl($context, $expr->expr));
            case Node\Expr\UnaryPlus::class:
                return $this->debug($context, $expr, + $this->evl($context, $expr->expr));

            // Bitwise Operators
            // https://www.php.net/manual/en/language.operators.bitwise.php
            case Node\Expr\BitwiseNot::class:
                return $this->debug($context, $expr, ~ $this->evl($context, $expr->expr));

            // Comparison Operators
            // https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.ternary
            case Node\Expr\Ternary::class:
                return $this->debug($context, $expr, $this->evl($context, $expr->cond)
                    ? $this->evl($context, $expr->if)
                    : $this->evl($context, $expr->else));

            // Incrementing/Decrementing Operators
            // https://www.php.net/manual/en/language.operators.increment.php
            case Node\Expr\PreDec::class:
                return $this->incOp($context, $expr, -1, true);
            case Node\Expr\PreInc::class:
                return $this->incOp($context, $expr, 1, true);
            case Node\Expr\PostDec::class:
                return $this->incOp($context, $expr, -1, false);
            case Node\Expr\PostInc::class:
                return $this->incOp($context, $expr, 1, false);

            // Logical Operators
            // https://www.php.net/manual/en/language.operators.logical.php
            case Node\Expr\BooleanNot::class:
                return $this->debug($context, $expr, !$this->evl($context, $expr->expr));

            // https://www.php.net/manual/en/function.isset.php
            case Node\Expr\Isset_::class:
                try {
                    $result = $this->evl($context, $expr->vars[0]);
                } catch (OutOfBoundsException $e) {
                    $result = null;
                }
                return $this->debug($context, $expr, $result !== null);

            case Node\Expr\Array_::class:
                return $this->evalNodeExprArray($context, $expr);

            case Node\Expr\ArrayDimFetch::class:
                return $this->evalNodeExprArrayDimFetch($context, $expr);

            case Node\Expr\Assign::class:
                return $this->evalNodeExprAssign($context, $expr);

            case Node\Expr\Closure::class:
                return $this->evalNodeExprClosure($context, $expr);

            case Node\Expr\ConstFetch::class:
                return $this->debug($context, $expr, constant($expr->name->parts[0]));

            case Node\Expr\FuncCall::class:
                return $this->evalNodeExprFuncCall($context, $expr);

            case Node\Expr\MethodCall::class:
                return $this->evalNodeExprMethodCall($context, $expr);

            case Node\Expr\PropertyFetch::class:
                return $this->evalNodeExprPropertyFetch($context, $expr);

            case Node\Expr\StaticPropertyFetch::class:
                return $this->evalNodeExprStaticPropertyFetch($expr);

            case Node\Expr\Variable::class:
                return $this->debug($context, $expr, $context->getRegistry()->get($expr->name));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\Array_ $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprArray(ParserContextInterface $context, Node\Expr\Array_ $expr)
    {
        $items = [];
        foreach ($expr->items as $item) {
            $value = $this->evl($context, $item->value);
            if (isset($item->key)) {
                $items[$this->evl($context, $item->key)] = $value;
            } else {
                $items[] = $value;
            }
        }
        return $this->debug($context, $expr, $items);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\ArrayDimFetch $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprArrayDimFetch(ParserContextInterface $context, Node\Expr\ArrayDimFetch $expr)
    {
        $propertyName = $this->evl($context, $expr->dim);
        $variable = $this->evl($context, $expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($context, $variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($context, $variable);
        }
        if (!is_array($variable)) {
            $variableName = $expr->var->name ?? '';
            return $this->error("Unsupported ArrayDimFetch expression"
                . " - Variable \${$variableName} is not an array", $expr);
        } elseif (is_array($variable) && isset($variable[$propertyName])) {
            return $this->debug($context, $expr, $variable[$propertyName]);
        } elseif (is_array($variable) && !isset($variable[$propertyName])) {
            $this->debug($context, $expr, null);
            throw new OutOfBoundsException("Undefined index: $propertyName", static::UNDEFINED_INDEX);
        }
        return $this->error("Unsupported ArrayDimFetch expression", $expr);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\AssignOp $expression
     * @param callback $callback
     * @return mixed
     * @throws Exception
     */
    private function evalAssignOp(ParserContextInterface $context, Node\Expr\AssignOp $expression, $callback)
    {
        $variableName = $expression->var->name;
        $value = $callback(
            $context->getRegistry()->get($variableName),
            $this->evl($context, $expression->expr)
        );
        $context->getRegistry()->register($variableName, $value, true);
        return $this->debug($context, $expression, $value);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\Assign $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprAssign(ParserContextInterface $context, Node\Expr\Assign $expr)
    {
        if (isset($expr->var->name)
            && isset($expr->expr)
            && ($expr->var instanceof Node\Expr\Variable)
        ) {
            // $a = ...
            $variableName = $expr->var->name;
            $value = $this->evl($context, $expr->expr);
            $context->getRegistry()->register($variableName, $value, true);
            return $this->debug($context, $expr, $value);
        } elseif (isset($expr->var->var)
            && isset($expr->expr)
            && ($expr->var instanceof Node\Expr\ArrayDimFetch)
        ) {
            // $a[] = ...
            $rootVar = $expr->var;
            $indexes = [];
            while (isset($rootVar->var)) {
                $indexes[] = isset($rootVar->dim) ? $this->evl($context, $rootVar->dim) : null;
                $rootVar = $rootVar->var;
            }
            $rootVariableName = $rootVar->name;
            $array = $context->getRegistry()->get($rootVariableName);
            $tmpArray =& $array;
            $indexes = array_reverse($indexes);
            $lastIndex = array_pop($indexes);
            foreach ($indexes as $index) {
                $tmpArray =& $tmpArray[$index];
            }

            $value = $this->evl($context, $expr->expr);
            if ($lastIndex === null) {
                $tmpArray[] = $value;
            } else {
                $tmpArray[$lastIndex] = $value;
            }

            $context->getRegistry()->register($rootVariableName, $array, true);
            return $this->debug($context, $expr, $array);
        } else {
            return $this->error("Unsupported Assign expression", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\AssignOp $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprAssignOp(ParserContextInterface $context, Node\Expr\AssignOp $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // Arithmetic Operators
            // https://www.php.net/manual/en/language.operators.arithmetic.php
            case Node\Expr\AssignOp\Plus::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a + $b);
            case Node\Expr\AssignOp\Minus::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a - $b);
            case Node\Expr\AssignOp\Mul::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a * $b);
            case Node\Expr\AssignOp\Div::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a / $b);
            case Node\Expr\AssignOp\Mod::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a % $b);
            case Node\Expr\AssignOp\Pow::class:
                // Operator **=
                // Introduced in PHP 5.6
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a ** $b);

            // String Operators
            // https://www.php.net/manual/en/language.operators.string.php
            case Node\Expr\AssignOp\Concat::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a . $b);

            // Bitwise Operators
            // http://www.php.net/manual/en/language.operators.bitwise.php
            case Node\Expr\AssignOp\BitwiseAnd::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a & $b);
            case Node\Expr\AssignOp\BitwiseOr::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a | $b);
            case Node\Expr\AssignOp\BitwiseXor::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a ^ $b);
            case Node\Expr\AssignOp\ShiftLeft::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a << $b);
            case Node\Expr\AssignOp\ShiftRight::class:
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a >> $b);

            // Null coalescing assignment operator ??=
            // https://www.php.net/manual/en/migration74.new-features.php#migration74.new-features.core.null-coalescing-assignment-operator
            // Introduced in PHP 7.4
            // Introduced in nikic/php-parser:4.*
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            case 'Node\\Expr\\AssignOp\\Coalesce':
                return $this->evalAssignOp($context, $expr, fn($a, $b) => $a ?? $b);

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\BinaryOp $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprBinaryOp(ParserContextInterface $context, Node\Expr\BinaryOp $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // Arithmetic Operators
            // https://www.php.net/manual/en/language.operators.arithmetic.php
            case Node\Expr\BinaryOp\Plus::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) + $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Minus::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) - $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Mul::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) * $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Div::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) / $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Mod::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) % $this->evl($context, $expr->right)
                );
            // Operator **
            // Introduced in PHP 5.6
            case Node\Expr\BinaryOp\Pow::class:
                return $this->debug(
                    $context,
                    $expr,
                    pow($this->evl($context, $expr->left), $this->evl($context, $expr->right))
                );

            // Bitwise Operators
            // https://www.php.net/manual/en/language.operators.bitwise.php
            case Node\Expr\BinaryOp\BitwiseAnd::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) & $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\BitwiseOr::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) | $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\BitwiseXor::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) ^ $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\ShiftLeft::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) << $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\ShiftRight::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) >> $this->evl($context, $expr->right)
                );

            // Comparison Operators
            // https://www.php.net/manual/en/language.operators.comparison.php
            case Node\Expr\BinaryOp\Equal::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) == $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Identical::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) === $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\NotEqual::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) != $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\NotIdentical::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) !== $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Smaller::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) < $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Greater::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) > $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\SmallerOrEqual::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) <= $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\GreaterOrEqual::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) >= $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\Spaceship::class:
                $left = $this->evl($context, $expr->left);
                $right = $this->evl($context, $expr->right);
                return $this->debug($context, $expr, $left == $right ? 0 : ($left < $right ? -1 : 1));

            // Logical Operators
            // https://www.php.net/manual/en/language.operators.logical.php
            case Node\Expr\BinaryOp\LogicalAnd::class:
                return $this->debug(
                    $context,
                    $expr,
                    // phpcs:ignore Squiz.Operators.ValidLogicalOperators.NotAllowed
                    $this->evl($context, $expr->left) and $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\LogicalOr::class:
                return $this->debug(
                    $context,
                    $expr,
                    // phpcs:ignore Squiz.Operators.ValidLogicalOperators.NotAllowed
                    $this->evl($context, $expr->left) or $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\LogicalXor::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) xor $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\BooleanAnd::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) && $this->evl($context, $expr->right)
                );
            case Node\Expr\BinaryOp\BooleanOr::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) || $this->evl($context, $expr->right)
                );

            // String Operators
            // https://www.php.net/manual/en/language.operators.string.php
            case Node\Expr\BinaryOp\Concat::class:
                return $this->debug(
                    $context,
                    $expr,
                    $this->evl($context, $expr->left) . $this->evl($context, $expr->right)
                );

            // Null coalescing operator ??
            // https://www.php.net/manual/en/migration70.new-features.php#migration70.new-features.null-coalesce-op
            // Introduced in PHP 7.0
            case Node\Expr\BinaryOp\Coalesce::class:
                try {
                    $left = $this->evl($context, $expr->left);
                } catch (OutOfBoundsException $e) {
                    $left = null;
                }
                return $this->debug($context, $expr, null !== $left ? $left : $this->evl($context, $expr->right));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\Cast $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprCast(ParserContextInterface $context, Node\Expr\Cast $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // https://www.php.net/manual/en/language.types.type-juggling.php#language.types.typecasting
            case Node\Expr\Cast\Int_::class:
                return $this->debug($context, $expr, (int)$this->evl($context, $expr->expr));
            case Node\Expr\Cast\Bool_::class:
                return $this->debug($context, $expr, (bool)$this->evl($context, $expr->expr));
            case Node\Expr\Cast\Double::class:
                return $this->debug($context, $expr, (float)$this->evl($context, $expr->expr));
            case Node\Expr\Cast\String_::class:
                return $this->debug($context, $expr, (string)$this->evl($context, $expr->expr));
            case Node\Expr\Cast\Array_::class:
                return $this->debug($context, $expr, (array)$this->evl($context, $expr->expr));
            case Node\Expr\Cast\Object_::class:
                return $this->debug($context, $expr, (object)$this->evl($context, $expr->expr));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\Closure $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprClosure(ParserContextInterface $context, Node\Expr\Closure $expr)
    {
        if ($expr->static !== false) {
            return $this->error("Unsupported code - closure \$expression->static !== false", $expr);
        }
        if ($expr->byRef !== false) {
            return $this->error("Unsupported code - closure \$expression->byRef !== false", $expr);
        }

        $evaluator = $this;
        return $this->debug(
            $context,
            $expr,
            function () use ($context, $expr, $evaluator) {
                $args = func_get_args();
                $registry = $context->getRegistry();
                $registry->createScope();
                try {
                    foreach ($expr->params as $param) {
                        // v.3 $param->name, v.4 $param->var->name
                        $varName = $param->var->name ?? $param->name;
                        $value = empty($args) ? $evaluator->evaluate($context, $param) : array_shift($args);
                        $registry->register($varName, $this->wrapperContext->wrap($value));
                    }

                    foreach ($expr->uses as $use) {
                        // v.3 $use->var, v.4 $use->var->name
                        $varName = $use->var->name ?? $use->var;
                        $value = $registry->get($varName, $registry->getCurrentScopeIndex() - 1);
                        $registry->register($varName, $this->wrapperContext->wrap($value));
                    }

                    $result = $evaluator->evaluateStmts($context, $expr->stmts);
                    if ($result instanceof Node\Stmt\Return_) {
                        $result = $evaluator->evaluate($context, $result);
                    }
                } catch (Exception $e) {
                    $registry->deleteScope();
                    throw $e;
                }
                $registry->deleteScope();
                return $result;
            }
        );
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\FuncCall $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprFuncCall(ParserContextInterface $context, Node\Expr\FuncCall $expr)
    {
        if (isset($expr->name->parts)) {
            if (count($expr->name->parts) != 1) {
                return $this->error("Unsupported FuncCall expression", $expr);
            }

            $functionName = $expr->name->parts[0];
            $functionProviderPool = $context->getFunctionProviderPool();
            if ($functionProviderPool->functionExists($functionName)) {
                $args = $this->evaluateArgs($context, $expr);
                $result = $functionProviderPool->call($functionName, $args);
                return $this->debug($context, $expr, $result);
            } else {
                return $this->error("Unknown function '{$functionName}'", $expr);
            }
        } elseif ($expr->name instanceof Node\Expr\Variable) {
            $variable = $context->getRegistry()->get($expr->name->name);
            if (!isset($variable)) {
                return $this->error("Unsupported FuncCall expression - Unkown function", $expr);
            }

            if (!is_callable($variable)) {
                return $this->error("Unsupported FuncCall expression - Variable is not a function", $expr);
            }

            $args = $this->evaluateArgs($context, $expr);
            $result = $this->callFunction($variable, $args);
            return $this->debug($context, $expr, $result);
        } else {
            return $this->error("Unsupported FuncCall expression", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\MethodCall $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprMethodCall(ParserContextInterface $context, Node\Expr\MethodCall $expr)
    {
        $methodName = $this->evl($context, $expr->name);
        $variable = $this->evl($context, $expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($context, $variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($context, $variable);
        }

        $method = null;
        $variableName = $expr->var->name ?? '';
        if (!isset($variable)) {
            return $this->error("Unsupported MethodCall expression"
                . " - Unkown variable \${$variableName}", $expr);
        }
        if (is_object($variable) && isset($variable->{$methodName}) && is_callable($variable->{$methodName})) {
            $method = $variable->{$methodName};
        } elseif ($variable instanceof AbstractWrapper && is_callable([
            $variable,
            $methodName
        ])) {
            $method = [
                $variable,
                $methodName,
            ];
        } elseif ($variable instanceof SourceWrapper && is_callable([
            $variable->getSource(),
            $methodName
        ])) {
            $method = [
                $variable->getSource(),
                $methodName,
            ];
        } elseif (is_array($variable) && isset($variable[$methodName]) && is_callable($variable[$methodName])) {
            $method = $variable[$methodName];
        }
        if (!$method) {
            return $this->error(
                "Unsupported MethodCall expression - Unkown method `$methodName`",
                $expr
            );
        }
        $args = $this->evaluateArgs($context, $expr);
        $result = $this->callFunction($method, $args);
        $result = $this->wrapperContext->wrap($result);
        return $this->debug($context, $expr, $result);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Expr\PropertyFetch $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprPropertyFetch(ParserContextInterface $context, Node\Expr\PropertyFetch $expr)
    {
        $propertyName = $this->evl($context, $expr->name);
        $variable = $this->evl($context, $expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($context, $variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($context, $variable);
        }
        if (!isset($variable) && isset($expr->var->name) && is_string($expr->var->name)) {
            return $this->error("Unknown variable \${$expr->var->name}", $expr);
        }

        if (is_array($variable) && isset($variable[$propertyName])) {
            return $this->debug($context, $expr, $variable[$propertyName]);
        } elseif (is_object($variable)
            && $variable instanceof AbstractWrapper
        ) {
            return $this->debug($context, $expr, $variable->$propertyName);
        } elseif (is_object($variable) && isset($variable->{$propertyName})) {
            return $this->debug($context, $expr, $variable->{$propertyName});
        } elseif (is_object($variable)) {
            return $this->error("Unsupported PropertyFetch expression - " . get_class($variable), $expr);
        }
        return $this->error("Unsupported PropertyFetch expression", $expr);
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeExprStaticPropertyFetch(Node\Expr\StaticPropertyFetch $expr)
    {
        // StaticPropertyFetch is forbidden
        return $this->error("Unsupported StaticPropertyFetch expression", $expr);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Scalar $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeScalar(ParserContextInterface $context, Node\Scalar $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            case Node\Scalar\DNumber::class:
            case Node\Scalar\LNumber::class:
            case Node\Scalar\String_::class:
                return $this->debug($context, $expr, $expr->value);

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Stmt $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeStmt(ParserContextInterface $context, Node\Stmt $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // Introduced in nikic/php-parser:4.*
            // Don't use ::class to keep compatibility with nikic/php-parser:3.*
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            case 'PhpParser\\Node\\Stmt\\Expression':
                return $this->debug($context, $expr, $this->evl($context, $expr->expr));

            case Node\Stmt\Foreach_::class:
                return $this->evalNodeStmtForeach($context, $expr);

            case Node\Stmt\Global_::class:
                foreach ($expr->vars as $var) {
                    $variableName = $var->name;
                    $context->getRegistry()->declareGlobalAtCurrentScope($variableName);
                }
                return $this->debug($context, $expr, null);

            case Node\Stmt\If_::class:
                return $this->evalNodeStmtIf($context, $expr);

            case Node\Stmt\Nop::class:
                return null;

            case Node\Stmt\Return_::class:
                return $this->debug($context, $expr, $this->evl($context, $expr->expr));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Stmt\Foreach_ $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeStmtForeach(ParserContextInterface $context, Node\Stmt\Foreach_ $expr)
    {
        $exp = $this->evl($context, $expr->expr);
        $valueVar = $this->evl($context, $expr->valueVar->name);
        $keyVar = $expr->keyVar ? $this->evl($context, $expr->keyVar->name) : null;
        if (!is_array($exp)) {
            return $this->error("Unsupported Foreach_ expression - Undefined variable", $expr);
        }
        foreach ($exp as $key => $value) {
            $context->getRegistry()->register($valueVar, $this->wrapperContext->wrap($value), true);
            if ($keyVar) {
                $context->getRegistry()->register($keyVar, $this->wrapperContext->wrap($key), true);
            }
            $result = $this->evaluateStmts($context, $expr->stmts);
            if ($result instanceof Node\Stmt\Return_) {
                return $this->debug($context, $expr, $result);
            }
        }
        return $this->debug($context, $expr, null);
    }

    /**
     * @param ParserContextInterface $context
     * @param Node\Stmt\If_ $expr
     * @return mixed
     * @throws Exception
     */
    private function evalNodeStmtIf(ParserContextInterface $context, Node\Stmt\If_ $expr)
    {
        $cond = $this->evl($context, $expr->cond);
        if ($cond) {
            return $this->debug($context, $expr, $this->evaluateStmts($context, $expr->stmts), $wrap = false);
        }

        if (isset($expr->elseifs)) {
            foreach ($expr->elseifs as $elseif) {
                $cond = $this->evl($context, $elseif->cond);
                if ($cond) {
                    return $this->debug($context, $expr, $this->evaluateStmts($context, $elseif->stmts), $wrap = false);
                }
            }
        }

        if (isset($expr->else)) {
            return $this->debug($context, $expr, $this->evaluateStmts($context, $expr->else->stmts), $wrap = false);
        }

        return $this->debug($context, $expr, null);
    }

    /**
     * @param ParserContextInterface $context
     * @param mixed $expr
     * @return mixed
     * @throws Exception
     */
    private function evl(ParserContextInterface $context, $expr)
    {
        if (is_string($expr)) {
            return $expr;
        }
        if (is_array($expr)) {
            return $expr;
        }

        if ($expr instanceof Node\Scalar) {
            return $this->evalNodeScalar($context, $expr);
        } elseif ($expr instanceof Node\Stmt) {
            return $this->evalNodeStmt($context, $expr);
        } elseif ($expr instanceof Node\Expr) {
            return $this->evalNodeExpr($context, $expr);
        }

        $className = get_class($expr);
        switch ($className) {
            // Introduced in nikic/php-parser:4.*
            // Don't use ::class to keep compatibility with nikic/php-parser:3.*
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            case 'PhpParser\\Node\\Identifier':
                return $this->debug($context, $expr, (string)$expr);

            case Node\Name::class:
                if (!isset($expr->parts) || count($expr->parts) != 1) {
                    return $this->error("Unsupported Name expression", $expr);
                }
                return $this->debug($context, $expr, $expr->parts[0]);

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param mixed $method
     * @param array $args
     * @return type
     */
    private function callFunction($method, $args = [])
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        return call_user_func_array($method, $args);
    }

    /**
     * @param ParserContextInterface $context
     * @param type $expr
     * @return array
     */
    private function evaluateArgs(ParserContextInterface $context, $expr)
    {
        $args = [];
        foreach ($expr->args as $arg) {
            $args[] = $this->evl($context, $arg->value);
        }
        return $args;
    }
}
