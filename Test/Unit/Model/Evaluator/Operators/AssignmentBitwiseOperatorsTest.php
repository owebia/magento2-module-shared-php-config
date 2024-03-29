<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator\Operators;

/**
 * Test Assignment Operators - Bitwise Operators
 * https://www.php.net/manual/en/language.operators.assignment.php
 * https://www.php.net/manual/en/language.operators.bitwise.php
 */
class AssignmentBitwiseOperatorsTest extends AbstractOperatorTestCase
{
    /**
     * Test Bitwise And
     */
    public function testAnd()
    {
        $this->parse('$a = 1; $a &= 5;')
            ->assertVariableSame('$a', 1 & 5);
    }

    /**
     * Test Bitwise Or
     */
    public function testBitwiseOr()
    {
        $this->parse('$a = 1; $a |= 2;')
            ->assertVariableSame('$a', 1 | 2);
    }

    /**
     * Test Bitwise Xor
     */
    public function testBitwiseXor()
    {
        $this->parse('$a = 1; $a ^= 5;')
            ->assertVariableSame('$a', 1 ^ 5);
    }

    /**
     * Test Shift left
     */
    public function testShiftLeft()
    {
        $this->parse('$a = 1; $a <<= 5;')
            ->assertVariableSame('$a', 1 << 5);
    }

    /**
     * Test Shift right
     */
    public function testShiftRight()
    {
        $this->parse('$a = 1; $a >>= 5;')
            ->assertVariableSame('$a', 1 >> 5);
    }
}
