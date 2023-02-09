<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator\Operators;

/**
 * Test Null Coalescing Operator
 * https://www.php.net/manual/en/migration70.new-features.php#migration70.new-features.null-coalesce-op
 */
class NullCoalescingOperatorTest extends AbstractOperatorTestCase
{
    /**
     * Test Null Coalescing Operator
     */
    public function testNullCoalescingOperator()
    {
        $this->parse('$a = null; $b = null ?? $a ?? 3;')
            ->assertVariableSame('$a', null)
            ->assertVariableSame('$b', 3);
    }
}
