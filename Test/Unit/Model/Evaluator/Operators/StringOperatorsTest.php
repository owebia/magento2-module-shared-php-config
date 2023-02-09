<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator\Operators;

/**
 * Test String Operators
 * https://www.php.net/manual/en/language.operators.string.php
 */
class StringOperatorsTest extends AbstractOperatorTestCase
{
    /**
     * Test Concatenation
     */
    public function testConcatenation()
    {
        $this->parse('$a = "a" . "b";')
            ->assertVariableSame('$a', 'ab');
    }
}
