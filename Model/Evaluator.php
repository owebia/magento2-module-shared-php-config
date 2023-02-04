<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Magento\Framework\Escaper;
use Owebia\SharedPhpConfig\Api\FunctionProxyInterface;
use Owebia\SharedPhpConfig\Api\RegistryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use PhpParser\Node;

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
     * @var bool
     */
    private $debug = false;

    /**
     * @var array
     */
    private $debugOutput = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var integer
     */
    private $counter = 1;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var WrapperContext
     */
    private $wrapperContext;

    /**
     * @var FunctionProxyInterface
     */
    private $functionProxy;

    /**
     * @var RegistryInterface
     */
    private $registry = null;

    /**
     * @var \PhpParser\PrettyPrinter\Standard
     */
    private $prettyPrinter = null;

    /**
     * @param Escaper $escaper
     * @param WrapperContext $wrapperContext
     * @param FunctionProxyInterface|null $functionProxy
     * @param RegistryInterface $registry
     * @param bool $debug = false
     */
    public function __construct(
        Escaper $escaper,
        WrapperContext $wrapperContext,
        FunctionProxyInterface $functionProxy = null,
        RegistryInterface $registry,
        bool $debug = false
    ) {
        $this->escaper = $escaper;
        $this->wrapperContext = $wrapperContext;
        $this->functionProxy = $functionProxy;
        $this->registry = $registry;
        $this->debug = $debug;
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
        $this->counter = 1;
    }

    /**
     * @param string $msg
     * @param mixed $expr
     * @throws \Exception
     */
    private function error($msg, $expr)
    {
        $trace = debug_backtrace(false);
        $this->errors[] = [
            'level' => 'ERROR',
            'msg' => $msg,
            // 'code' => $this->prettyPrint($expr),
            'expression' => $expr,
            'line' => $trace[0]['line']
        ];
        throw new \Magento\Framework\Exception\LocalizedException(__($msg));
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
     * @param mixed $node
     * @param mixed $result
     * @param bool $wrap
     * @return mixed
     */
    private function debug($node, $result, $wrap = true)
    {
        if ($this->debug) {
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
     * @return \PhpParser\PrettyPrinter\Standard
     */
    public function getPrettyPrinter()
    {
        return $this->prettyPrinter ??= new \PhpParser\PrettyPrinter\Standard([
            'shortArraySyntax' => true
        ]);
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function prettyPrint($value)
    {
        if (!isset($value) || is_bool($value) || is_int($value) || is_string($value)) {
            return var_export($value, true);
        } elseif (is_float($value)) {
            return (string) $value;
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_object($item) || is_array($item)) {
                    return 'array(size:' . count($value) . ')';
                }
            }
            // return $this->getPrettyPrinter()->pExpr_Array(new \PhpParser\Node\Expr\Array_($value));
            return var_export($value, true);
        } elseif (is_object($value)) {
            if ($value instanceof Node) {
                if ($value->hasAttribute('comments')) {
                    $value->setAttribute('comments', []);
                }
                return rtrim($this->getPrettyPrinter()->prettyPrint([
                    $value
                ]), ';');
            } elseif ($value instanceof \Owebia\SharedPhpConfig\Model\Wrapper\AbstractWrapper) {
                return (string) $value;
            } else {
                return "/** @var " . get_class($value) . " \$obj */ \$obj";
            }
        } else {
            return $value;
        }
    }

    /**
     * @param array $stmts
     * @return mixed
     */
    public function evaluateStmts($stmts)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_) {
                return $stmt;
            }

            $result = $this->evaluate($stmt);
            if (is_array($result) && $this->doesArrayContainOnly($result, \PhpParser\NodeAbstract::class)) {
                $result = $this->evaluateStmts($result);
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
     * @param mixed $expression
     * @return mixed
     * @throws \Exception
     */
    public function evaluate($expression)
    {
        return $this->evl($expression);
    }

    /**
     * @param \PhpParser\Node\Expr $expression
     * @param int $increment
     * @param bool $incrementBefore
     * @return mixed
     * @throws \Exception
     */
    private function incOp($expression, $increment, $incrementBefore)
    {
        $variableName = $expression->var->name;
        $oldValue = $this->registry->get($variableName);
        $newValue = $oldValue + $increment;
        $this->registry->register($variableName, $newValue, true);
        return $this->debug($expression, $incrementBefore ? $newValue : $oldValue);
    }

    /**
     * @param \PhpParser\Node\Expr $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExpr(Node\Expr $expr)
    {
        $className = get_class($expr);
        if ($expr instanceof Node\Expr\BinaryOp) {
            return $this->evalNodeExprBinaryOp($expr);
        } elseif ($expr instanceof Node\Expr\AssignOp) {
            return $this->evalNodeExprAssignOp($expr);
        } elseif ($expr instanceof Node\Expr\Cast) {
            return $this->evalNodeExprCast($expr);
        }

        switch ($className) {
            // Arithmetic Operators
            // https://www.php.net/manual/en/language.operators.arithmetic.php
            case Node\Expr\UnaryMinus::class:
                return $this->debug($expr, - $this->evl($expr->expr));
            case Node\Expr\UnaryPlus::class:
                return $this->debug($expr, + $this->evl($expr->expr));

            // Bitwise Operators
            // https://www.php.net/manual/en/language.operators.bitwise.php
            case Node\Expr\BitwiseNot::class:
                return $this->debug($expr, ~ $this->evl($expr->expr));

            // Comparison Operators
            // https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.ternary
            case Node\Expr\Ternary::class:
                return $this->debug($expr, $this->evl($expr->cond)
                    ? $this->evl($expr->if)
                    : $this->evl($expr->else));

            // Incrementing/Decrementing Operators
            // https://www.php.net/manual/en/language.operators.increment.php
            case Node\Expr\PreDec::class:
                return $this->incOp($expr, -1, true);
            case Node\Expr\PreInc::class:
                return $this->incOp($expr, 1, true);
            case Node\Expr\PostDec::class:
                return $this->incOp($expr, -1, false);
            case Node\Expr\PostInc::class:
                return $this->incOp($expr, 1, false);

            // Logical Operators
            // https://www.php.net/manual/en/language.operators.logical.php
            case Node\Expr\BooleanNot::class:
                return $this->debug($expr, !$this->evl($expr->expr));

            // https://www.php.net/manual/en/function.isset.php
            case Node\Expr\Isset_::class:
                try {
                    $result = $this->evl($expr->vars[0]);
                } catch (\OutOfBoundsException $e) {
                    $result = null;
                }
                return $this->debug($expr, $result  !== null);

            case Node\Expr\Array_::class:
                return $this->evalNodeExprArray($expr);

            case Node\Expr\ArrayDimFetch::class:
                return $this->evalNodeExprArrayDimFetch($expr);

            case Node\Expr\Assign::class:
                return $this->evalNodeExprAssign($expr);

            case Node\Expr\Closure::class:
                return $this->evalNodeExprClosure($expr);

            case Node\Expr\ConstFetch::class:
                return $this->debug($expr, constant($expr->name->parts[0]));

            case Node\Expr\FuncCall::class:
                return $this->evalNodeExprFuncCall($expr);

            case Node\Expr\MethodCall::class:
                return $this->evalNodeExprMethodCall($expr);

            case Node\Expr\PropertyFetch::class:
                return $this->evalNodeExprPropertyFetch($expr);

            case Node\Expr\StaticPropertyFetch::class:
                return $this->evalNodeExprStaticPropertyFetch($expr);

            case Node\Expr\Variable::class:
                return $this->debug($expr, $this->registry->get($expr->name));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Expr\Array_ $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprArray(Node\Expr\Array_ $expr)
    {
        $items = [];
        foreach ($expr->items as $item) {
            $value = $this->evl($item->value);
            if (isset($item->key)) {
                $items[$this->evl($item->key)] = $value;
            } else {
                $items[] = $value;
            }
        }
        return $this->debug($expr, $items);
    }

    /**
     * @param \PhpParser\Node\Expr\ArrayDimFetch $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprArrayDimFetch(Node\Expr\ArrayDimFetch $expr)
    {
        $propertyName = $this->evl($expr->dim);
        $variable = $this->evl($expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($variable);
        }
        if (!is_array($variable)) {
            $variableName = $expr->var->name ?? '';
            return $this->error("Unsupported ArrayDimFetch expression"
                . " - Variable \${$variableName} is not an array", $expr);
        } elseif (is_array($variable) && isset($variable[$propertyName])) {
            return $this->debug($expr, $variable[$propertyName]);
        } elseif (is_array($variable) && !isset($variable[$propertyName])) {
            $this->debug($expr, null);
            throw new \OutOfBoundsException("Undefined index: $propertyName", static::UNDEFINED_INDEX);
        }
        return $this->error("Unsupported ArrayDimFetch expression", $expr);
    }

    /**
     * @param \PhpParser\Node\Expr\AssignOp $expression
     * @param callback $callback
     * @return mixed
     * @throws \Exception
     */
    private function evalAssignOp(Node\Expr\AssignOp $expression, $callback)
    {
        $variableName = $expression->var->name;
        $value = $callback(
            $this->registry->get($variableName),
            $this->evl($expression->expr)
        );
        $this->registry->register($variableName, $value, true);
        return $this->debug($expression, $value);
    }

    /**
     * @param \PhpParser\Node\Expr\Assign $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprAssign(Node\Expr\Assign $expr)
    {
        if (isset($expr->var->name)
            && isset($expr->expr)
            && ($expr->var instanceof Node\Expr\Variable)
        ) {
            // $a = ...
            $variableName = $expr->var->name;
            $value = $this->evl($expr->expr);
            $this->registry->register($variableName, $value, true);
            return $this->debug($expr, $value);
        } elseif (isset($expr->var->var)
            && isset($expr->expr)
            && ($expr->var instanceof Node\Expr\ArrayDimFetch)
        ) {
            // $a[] = ...
            $rootVar = $expr->var;
            $indexes = [];
            while (isset($rootVar->var)) {
                $indexes[] = isset($rootVar->dim) ? $this->evl($rootVar->dim) : null;
                $rootVar = $rootVar->var;
            }
            $rootVariableName = $rootVar->name;
            $array = $this->registry->get($rootVariableName);
            $tmpArray =& $array;
            $indexes = array_reverse($indexes);
            $lastIndex = array_pop($indexes);
            foreach ($indexes as $index) {
                $tmpArray =& $tmpArray[$index];
            }

            $value = $this->evl($expr->expr);
            if ($lastIndex === null) {
                $tmpArray[] = $value;
            } else {
                $tmpArray[$lastIndex] = $value;
            }

            $this->registry->register($rootVariableName, $array, true);
            return $this->debug($expr, $array);
        } else {
            return $this->error("Unsupported Assign expression", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Expr\AssignOp $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprAssignOp(Node\Expr\AssignOp $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // Arithmetic Operators
            // https://www.php.net/manual/en/language.operators.arithmetic.php
            case Node\Expr\AssignOp\Plus::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left + $right;
                });
            case Node\Expr\AssignOp\Minus::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left - $right;
                });
            case Node\Expr\AssignOp\Mul::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left * $right;
                });
            case Node\Expr\AssignOp\Div::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left / $right;
                });
            case Node\Expr\AssignOp\Mod::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left % $right;
                });
            case Node\Expr\AssignOp\Pow::class:
                // Operator **=
                // Introduced in PHP 5.6
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left ** $right;
                });

            // String Operators
            // https://www.php.net/manual/en/language.operators.string.php
            case Node\Expr\AssignOp\Concat::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left . $right;
                });

            // Bitwise Operators
            // http://www.php.net/manual/en/language.operators.bitwise.php
            case Node\Expr\AssignOp\BitwiseAnd::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left & $right;
                });
            case Node\Expr\AssignOp\BitwiseOr::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left | $right;
                });
            case Node\Expr\AssignOp\BitwiseXor::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left ^ $right;
                });
            case Node\Expr\AssignOp\ShiftLeft::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left << $right;
                });
            case Node\Expr\AssignOp\ShiftRight::class:
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left >> $right;
                });

            // Null coalescing assignment operator ??=
            // https://www.php.net/manual/en/migration74.new-features.php#migration74.new-features.core.null-coalescing-assignment-operator
            // Introduced in PHP 7.4
            // Introduced in nikic/php-parser:4.*
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            case 'Node\\Expr\\AssignOp\\Coalesce':
                return $this->evalAssignOp($expr, function ($left, $right) {
                    return $left ?? $right;
                });

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Expr\BinaryOp $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprBinaryOp(Node\Expr\BinaryOp $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // Arithmetic Operators
            // https://www.php.net/manual/en/language.operators.arithmetic.php
            case Node\Expr\BinaryOp\Plus::class:
                return $this->debug($expr, $this->evl($expr->left) + $this->evl($expr->right));
            case Node\Expr\BinaryOp\Minus::class:
                return $this->debug($expr, $this->evl($expr->left) - $this->evl($expr->right));
            case Node\Expr\BinaryOp\Mul::class:
                return $this->debug($expr, $this->evl($expr->left) * $this->evl($expr->right));
            case Node\Expr\BinaryOp\Div::class:
                return $this->debug($expr, $this->evl($expr->left) / $this->evl($expr->right));
            case Node\Expr\BinaryOp\Mod::class:
                return $this->debug($expr, $this->evl($expr->left) % $this->evl($expr->right));
            // Operator **
            // Introduced in PHP 5.6
            case Node\Expr\BinaryOp\Pow::class:
                return $this->debug($expr, pow($this->evl($expr->left), $this->evl($expr->right)));

            // Bitwise Operators
            // https://www.php.net/manual/en/language.operators.bitwise.php
            case Node\Expr\BinaryOp\BitwiseAnd::class:
                return $this->debug($expr, $this->evl($expr->left) & $this->evl($expr->right));
            case Node\Expr\BinaryOp\BitwiseOr::class:
                return $this->debug($expr, $this->evl($expr->left) | $this->evl($expr->right));
            case Node\Expr\BinaryOp\BitwiseXor::class:
                return $this->debug($expr, $this->evl($expr->left) ^ $this->evl($expr->right));
            case Node\Expr\BinaryOp\ShiftLeft::class:
                return $this->debug($expr, $this->evl($expr->left) << $this->evl($expr->right));
            case Node\Expr\BinaryOp\ShiftRight::class:
                return $this->debug($expr, $this->evl($expr->left) >> $this->evl($expr->right));

            // Comparison Operators
            // https://www.php.net/manual/en/language.operators.comparison.php
            case Node\Expr\BinaryOp\Equal::class:
                return $this->debug($expr, $this->evl($expr->left) == $this->evl($expr->right));
            case Node\Expr\BinaryOp\Identical::class:
                return $this->debug($expr, $this->evl($expr->left) === $this->evl($expr->right));
            case Node\Expr\BinaryOp\NotEqual::class:
                return $this->debug($expr, $this->evl($expr->left) != $this->evl($expr->right));
            case Node\Expr\BinaryOp\NotIdentical::class:
                return $this->debug($expr, $this->evl($expr->left) !== $this->evl($expr->right));
            case Node\Expr\BinaryOp\Smaller::class:
                return $this->debug($expr, $this->evl($expr->left) < $this->evl($expr->right));
            case Node\Expr\BinaryOp\Greater::class:
                return $this->debug($expr, $this->evl($expr->left) > $this->evl($expr->right));
            case Node\Expr\BinaryOp\SmallerOrEqual::class:
                return $this->debug($expr, $this->evl($expr->left) <= $this->evl($expr->right));
            case Node\Expr\BinaryOp\GreaterOrEqual::class:
                return $this->debug($expr, $this->evl($expr->left) >= $this->evl($expr->right));
            case Node\Expr\BinaryOp\Spaceship::class:
                $left = $this->evl($expr->left);
                $right = $this->evl($expr->right);
                return $this->debug($expr, $left == $right ? 0 : ($left < $right ? -1 : 1));

            // Logical Operators
            // https://www.php.net/manual/en/language.operators.logical.php
            case Node\Expr\BinaryOp\LogicalAnd::class:
                // phpcs:ignore Squiz.Operators.ValidLogicalOperators.NotAllowed
                return $this->debug($expr, $this->evl($expr->left) and $this->evl($expr->right));
            case Node\Expr\BinaryOp\LogicalOr::class:
                // phpcs:ignore Squiz.Operators.ValidLogicalOperators.NotAllowed
                return $this->debug($expr, $this->evl($expr->left) or $this->evl($expr->right));
            case Node\Expr\BinaryOp\LogicalXor::class:
                return $this->debug($expr, $this->evl($expr->left) xor $this->evl($expr->right));
            case Node\Expr\BinaryOp\BooleanAnd::class:
                return $this->debug($expr, $this->evl($expr->left) && $this->evl($expr->right));
            case Node\Expr\BinaryOp\BooleanOr::class:
                return $this->debug($expr, $this->evl($expr->left) || $this->evl($expr->right));

            // String Operators
            // https://www.php.net/manual/en/language.operators.string.php
            case Node\Expr\BinaryOp\Concat::class:
                return $this->debug($expr, $this->evl($expr->left) . $this->evl($expr->right));

            // Null coalescing operator ??
            // https://www.php.net/manual/en/migration70.new-features.php#migration70.new-features.null-coalesce-op
            // Introduced in PHP 7.0
            case Node\Expr\BinaryOp\Coalesce::class:
                try {
                    $left = $this->evl($expr->left);
                } catch (\OutOfBoundsException $e) {
                    $left = null;
                }
                return $this->debug($expr, null !== $left ? $left : $this->evl($expr->right));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Expr\Cast $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprCast(Node\Expr\Cast $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // https://www.php.net/manual/en/language.types.type-juggling.php#language.types.typecasting
            case Node\Expr\Cast\Int_::class:
                return $this->debug($expr, (int) $this->evl($expr->expr));
            case Node\Expr\Cast\Bool_::class:
                return $this->debug($expr, (bool) $this->evl($expr->expr));
            case Node\Expr\Cast\Double::class:
                return $this->debug($expr, (double) $this->evl($expr->expr));
            case Node\Expr\Cast\String_::class:
                return $this->debug($expr, (string) $this->evl($expr->expr));
            case Node\Expr\Cast\Array_::class:
                return $this->debug($expr, (array) $this->evl($expr->expr));
            case Node\Expr\Cast\Object_::class:
                return $this->debug($expr, (object) $this->evl($expr->expr));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Expr\Closure $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprClosure(Node\Expr\Closure $expr)
    {
        if ($expr->static !== false) {
            return $this->error("Unsupported code - closure \$expression->static !== false", $expr);
        }
        if ($expr->byRef !== false) {
            return $this->error("Unsupported code - closure \$expression->byRef !== false", $expr);
        }

        $evaluator = $this;
        return $this->debug(
            $expr,
            function () use ($expr, $evaluator) {
                $args = func_get_args();
                $registry = $evaluator->registry;
                $registry->createScope();
                try {
                    foreach ($expr->params as $param) {
                        // v.3 $param->name, v.4 $param->var->name
                        $varName = $param->var->name ?? $param->name;
                        $value = empty($args) ? $evaluator->evaluate($param) : array_shift($args);
                        $registry->register($varName, $this->wrapperContext->wrap($value));
                    }

                    foreach ($expr->uses as $use) {
                        // v.3 $use->var, v.4 $use->var->name
                        $varName = $use->var->name ?? $use->var;
                        $value = $registry->get($varName, $registry->getCurrentScopeIndex() - 1);
                        $registry->register($varName, $this->wrapperContext->wrap($value));
                    }

                    $result = $evaluator->evaluateStmts($expr->stmts);
                    if ($result instanceof Node\Stmt\Return_) {
                        $result = $evaluator->evaluate($result);
                    }
                } catch (\Exception $e) {
                    $registry->deleteScope();
                    throw $e;
                }
                $registry->deleteScope();
                return $result;
            }
        );
    }

    /**
     * @param \PhpParse\Node\Expr\FuncCall $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprFuncCall(Node\Expr\FuncCall $expr)
    {
        if (isset($expr->name->parts)) {
            if (count($expr->name->parts) != 1) {
                return $this->error("Unsupported FuncCall expression", $expr);
            }

            $functionName = $expr->name->parts[0];
            if ($this->functionProxy->functionExists($functionName)) {
                $functionName = [$this->functionProxy, $functionName];
            } else {
                return $this->error("Unknown function '{$functionName}'", $expr);
            }

            $args = $this->evaluateArgs($expr);
            $result = $this->callFunction($functionName, $args);
            return $this->debug($expr, $result);
        } elseif ($expr->name instanceof Node\Expr\Variable) {
            $variable = $this->registry->get($expr->name->name);
            if (!isset($variable)) {
                return $this->error("Unsupported FuncCall expression - Unkown function", $expr);
            }

            if (!is_callable($variable)) {
                return $this->error("Unsupported FuncCall expression - Variable is not a function", $expr);
            }

            $args = $this->evaluateArgs($expr);
            $result = $this->callFunction($variable, $args);
            return $this->debug($expr, $result);
        } else {
            return $this->error("Unsupported FuncCall expression", $expr);
        }
    }

    /**
     * @param \PhpParse\Node\Expr\MethodCall $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprMethodCall(Node\Expr\MethodCall $expr)
    {
        $methodName = $this->evl($expr->name);
        $variable = $this->evl($expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($variable);
        }

        $method = null;
        $variableName = $expr->var->name ?? '';
        if (!isset($variable)) {
            return $this->error("Unsupported MethodCall expression"
                . " - Unkown variable \${$variableName}", $expr);
        }
        if (is_object($variable) && isset($variable->{$methodName}) && is_callable($variable->{$methodName})) {
            $method = $variable->{$methodName};
        } elseif ($variable instanceof \Owebia\SharedPhpConfig\Model\Wrapper\AbstractWrapper && is_callable([
            $variable,
            $methodName
        ])) {
            $method = [
                $variable,
                $methodName,
            ];
        } elseif ($variable instanceof \Owebia\SharedPhpConfig\Model\Wrapper\SourceWrapper && is_callable([
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
                "Unsupported MethodCall expression - Unkown method"
                    . (is_callable([$variable, $methodName]) ? '1' : '0'),
                $expr
            );
        }
        $args = $this->evaluateArgs($expr);
        $result = $this->callFunction($method, $args);
        $result = $this->wrapperContext->wrap($result);
        return $this->debug($expr, $result);
    }

    /**
     * @param \PhpParser\Node\Expr\PropertyFetch $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprPropertyFetch(Node\Expr\PropertyFetch $expr)
    {
        $propertyName = $this->evl($expr->name);
        $variable = $this->evl($expr->var);
        if ($variable instanceof Node\Expr\ArrayItem) {
            $variable = $this->evl($variable->value);
        }
        if ($variable instanceof Node\Expr\Array_) {
            $variable = $this->evl($variable);
        }
        if (!isset($variable) && isset($expr->var->name) && is_string($expr->var->name)) {
            return $this->error("Unknown variable \${$expr->var->name}", $expr);
        }

        if (is_array($variable) && isset($variable[$propertyName])) {
            return $this->debug($expr, $variable[$propertyName]);
        } elseif (is_object($variable)
            && $variable instanceof \Owebia\SharedPhpConfig\Model\Wrapper\AbstractWrapper
        ) {
            return $this->debug($expr, $variable->$propertyName);
        } elseif (is_object($variable) && isset($variable->{$propertyName})) {
            return $this->debug($expr, $variable->{$propertyName});
        } elseif (is_object($variable)) {
            return $this->error("Unsupported PropertyFetch expression - " . get_class($variable), $expr);
        }
        return $this->error("Unsupported PropertyFetch expression", $expr);
    }

    /**
     * @param \PhpParser\Node\Expr\StaticPropertyFetch $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeExprStaticPropertyFetch(Node\Expr\StaticPropertyFetch $expr)
    {
        // StaticPropertyFetch is forbidden
        return $this->error("Unsupported StaticPropertyFetch expression", $expr);
    }

    /**
     * @param \PhpParser\Node\Scalar $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeScalar(Node\Scalar $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            case Node\Scalar\DNumber::class:
            case Node\Scalar\LNumber::class:
            case Node\Scalar\String_::class:
                return $this->debug($expr, $expr->value);

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Stmt $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeStmt(Node\Stmt $expr)
    {
        $className = get_class($expr);
        switch ($className) {
            // Introduced in nikic/php-parser:4.*
            // Don't use ::class to keep compatibility with nikic/php-parser:3.*
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            case 'PhpParser\\Node\\Stmt\\Expression':
                return $this->debug($expr, $this->evl($expr->expr));

            case Node\Stmt\Foreach_::class:
                return $this->evalNodeStmtForeach($expr);

            case Node\Stmt\Global_::class:
                foreach ($expr->vars as $var) {
                    $variableName = $var->name;
                    $value = $this->registry->getGlobal($variableName);
                    $this->registry->declareGlobalAtCurrentScope($variableName);
                }
                return $this->debug($expr, null);

            case Node\Stmt\If_::class:
                return $this->evalNodeStmtIf($expr);

            case Node\Stmt\Nop::class:
                return null;

            case Node\Stmt\Return_::class:
                return $this->debug($expr, $this->evl($expr->expr));

            default:
                return $this->error("Unsupported expression {$className}", $expr);
        }
    }

    /**
     * @param \PhpParser\Node\Stmt\Foreach_ $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeStmtForeach(Node\Stmt\Foreach_ $expr)
    {
        $exp = $this->evl($expr->expr);
        $valueVar = $this->evl($expr->valueVar->name);
        $keyVar = $expr->keyVar ? $this->evl($expr->keyVar->name) : null;
        if (!is_array($exp)) {
            return $this->error("Unsupported Foreach_ expression - Undefined variable", $expr);
        }
        foreach ($exp as $key => $value) {
            $this->registry->register($valueVar, $this->wrapperContext->wrap($value), true);
            if ($keyVar) {
                $this->registry->register($keyVar, $this->wrapperContext->wrap($key), true);
            }
            $result = $this->evaluateStmts($expr->stmts);
            if ($result instanceof Node\Stmt\Return_) {
                return $this->debug($expr, $result);
            }
        }
        return $this->debug($expr, null);
    }

    /**
     * @param \PhpParser\Node\Stmt\If_ $expr
     * @return mixed
     * @throws \Exception
     */
    private function evalNodeStmtIf(Node\Stmt\If_ $expr)
    {
        $cond = $this->evl($expr->cond);
        if ($cond) {
            return $this->debug($expr, $this->evaluateStmts($expr->stmts), $wrap = false);
        }

        if (isset($expr->elseifs)) {
            foreach ($expr->elseifs as $elseif) {
                $cond = $this->evl($elseif->cond);
                if ($cond) {
                    return $this->debug($expr, $this->evaluateStmts($elseif->stmts), $wrap = false);
                }
            }
        }

        if (isset($expr->else)) {
            return $this->debug($expr, $this->evaluateStmts($expr->else->stmts), $wrap = false);
        }

        return $this->debug($expr, null);
    }

    /**
     * @param mixed $expr
     * @return mixed
     * @throws \Exception
     */
    private function evl($expr)
    {
        if (is_string($expr)) {
            return $expr;
        }
        if (is_array($expr)) {
            return $expr;
        }

        if ($expr instanceof Node\Scalar) {
            return $this->evalNodeScalar($expr);
        } elseif ($expr instanceof Node\Stmt) {
            return $this->evalNodeStmt($expr);
        } elseif ($expr instanceof Node\Expr) {
            return $this->evalNodeExpr($expr);
        }

        $className = get_class($expr);
        switch ($className) {
            // Introduced in nikic/php-parser:4.*
            // Don't use ::class to keep compatibility with nikic/php-parser:3.*
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            case 'PhpParser\\Node\\Identifier':
                return $this->debug($expr, (string) $expr);

            case Node\Name::class:
                if (!isset($expr->parts) || count($expr->parts) != 1) {
                    return $this->error("Unsupported Name expression", $expr);
                }
                return $this->debug($expr, $expr->parts[0]);

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
     * @param type $expr
     * @return array
     */
    private function evaluateArgs($expr)
    {
        $args = [];
        foreach ($expr->args as $arg) {
            $args[] = $this->evl($arg->value);
        }
        return $args;
    }
}
