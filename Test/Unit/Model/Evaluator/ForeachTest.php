<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator;

class ForeachTest extends AbstractTestCase
{
    /**
     * Test Foreach
     */
    public function testForeach()
    {
        $this->parse('$a = 0; foreach ([ 1, 2, 3, 4, 5 ] as $b) { $a += $b; }')
            ->assertVariableSame('$a', 15);
    }
}
