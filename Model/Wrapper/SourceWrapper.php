<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Framework\DataObject;

class SourceWrapper extends AbstractWrapper
{
    /**
     * @var object|null
     */
    protected ?object $source;

    /**
     * @return object|null
     */
    protected function loadSource(): ?object
    {
        return $this->data;
    }

    /**
     * @return object|null
     */
    public function getSource(): ?object
    {
        return $this->source ??= $this->loadSource();
    }

    /**
     * {@inheritDoc}
     * @see AbstractWrapper::getKeys()
     */
    protected function getKeys(): array
    {
        $source = $this->getSource();
        if ($source instanceof DataObject) {
            return array_keys($source->getData());
        } elseif ($source instanceof \Magento\Framework\Api\AbstractSimpleObject) {
            // Not efficient but only for debug
            // see method _underscore in DataObject
            return array_keys($source->__toArray());
        } else {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     * @see AbstractWrapper::help()
     */
    public function help(): string
    {
        $source = $this->getSource();
        if ($source) {
            return parent::help();
        } else {
            $output = "Help on " . get_class($this) . " : No source defined";
        }
        return $output;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        $source = $this->getSource();
        if (!$source) {
            return null;
        }
        if ($source instanceof DataObject) {
            return $source->getData($key);
        } elseif ($source instanceof \Magento\Framework\Api\AbstractSimpleObject) {
            $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($source, $method)) {
                return $source->{$method}();
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            $className = get_class($this);
            $source = $this->getSource();
            $sourceClassName = null;
            if ($source && is_object($source)) {
                $sourceClassName = get_class($source);
            }
            $varName = lcfirst(($pos = strrpos($className, '\\')) ? substr($className, $pos + 1) : $className);
            $output = "/** @var \\$className \${$varName}"
                . (isset($sourceClassName) ? " (\\$sourceClassName)" : '')
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
