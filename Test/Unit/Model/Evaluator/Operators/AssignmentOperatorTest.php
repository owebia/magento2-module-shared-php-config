<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator\Operators;

/**
 * Test Assignment Operators
 * https://www.php.net/manual/en/language.operators.assignment.php
 */
class AssignmentOperatorTest extends AbstractOperatorTestCase
{
    /**
     * Test Assignment Operator
     */
    public function testAssignmentOperator()
    {
        $this->parse('$a = 7;')
            ->assertVariableSame('$a', 7);
    }

    /**
     * Test Assignment Operator on array item
     */
    public function testAssignmentOperatorArrayItem()
    {
        $this->parse('$a = [ "a" => [ 0, 2 ], 3, 5 ]; $a["a"][1] = 3; $b = $a["a"][1];')
            ->assertVariableSame('$b', 3);
    }
}
