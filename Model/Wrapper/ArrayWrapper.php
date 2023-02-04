<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Framework\Exception\LocalizedException;

class ArrayWrapper extends AbstractWrapper implements \ArrayAccess
{
    /**
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return parent::__get($offset);
    }

    /**
     * @param type $offset
     * @param type $value
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        throw new LocalizedException(__("Wrapper can not be modified"));
    }

    /**
     * @param type $offset
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        throw new LocalizedException(__("Wrapper can not be modified"));
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return array
     */
    protected function getKeys(): array
    {
        return array_keys($this->data);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            $className = get_class($this);
            $varName = lcfirst(($pos = strrpos($className, '\\')) ? substr($className, $pos + 1) : $className);
            $output = "/** @var \\$className \${$varName}"
                . " */\n\${$varName} ";
            return $output . $this->help();
        } catch (\Exception $e) {
            if (isset($output)) {
                return $output . $e->getMessage();
            }
            return $e->getMessage();
        }
    }
}
