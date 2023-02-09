<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator\Operators;

/**
 * Test Incrementing/Decrementing Operators
 * https://www.php.net/manual/en/language.operators.increment.php
 */
class IncrementingDecrementingOperatorsTest extends AbstractOperatorTestCase
{
    /**
     * Test Pre-increment
     */
    public function testPreIncrement()
    {
        $this->parse('$a = 7; $b = ++$a;')
            ->assertVariableSame('$a', ($a = 7) + 1)
            ->assertVariableSame('$b', $b = ++$a);
    }

    /**
     * Test Post-increment
     */
    public function testPostIncrement()
    {
        $this->parse('$a = 7; $b = $a++;')
            ->assertVariableSame('$a', ($a = 7) + 1)
            ->assertVariableSame('$b', $b = $a++);
    }

    /**
     * Test Pre-decrement
     */
    public function testPreDecrement()
    {
        $this->parse('$a = 7; $b = --$a;')
            ->assertVariableSame('$a', ($a = 7) - 1)
            ->assertVariableSame('$b', $b = --$a);
    }

    /**
     * Test Post-decrement
     */
    public function testPostDecrement()
    {
        $this->parse('$a = 7; $b = $a--;')
            ->assertVariableSame('$a', ($a = 7) - 1)
            ->assertVariableSame('$b', $b = $a--);
    }
}
