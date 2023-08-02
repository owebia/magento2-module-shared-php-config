<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Framework\DataObject;
use Owebia\SharedPhpConfig\Model\WrapperContext;

abstract class AbstractWrapper
{
    /**
     * @var WrapperContext
     */
    protected $wrapperContext;

    /**
     * @var DataObject
     */
    protected $cache;

    /**
     * @var mixed
     */
    protected $data = null;

    /**
     * @var array
     */
    protected array $aliasMap = [];

    /**
     * @var array
     */
    protected array $additionalAttributes = [];

    /**
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->wrapperContext = $wrapperContext;
        $this->data = $data;
        $this->cache = $this->wrapperContext->create(DataObject::class);
    }

    /**
     * @return bool
     */
    protected function isBackendOrder(): bool
    {
        return $this->backendAuthSession->isLoggedIn();
    }

    /**
     * return array
     */
    protected function getAdditionalData(): array
    {
        $data = [];
        foreach ($this->additionalAttributes as $k) {
            $data[$k] = $this->{$k};
        }
        return $data;
    }

    /**
     * @param mixed $value
     * @param string|null $variableName
     * @return mixed
     */
    protected function convertToString($value, $variableName = null)
    {
        if (!isset($value)
            || is_bool($value)
            || is_float($value)
            || is_int($value)
            || is_string($value)
        ) {
            return var_export($value, true);
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_object($item) || is_array($item)) {
                    return 'array(size:' . count($value) . ')';
                }
            }
            return var_export($value, true);
        } elseif (is_object($value)) {
            $variableName = $variableName ?? 'obj';
            return "/** @var \\" . get_class($value) . " */ \$$variableName";
        } else {
            return $value;
        }
    }

    /**
     * @return array
     */
    abstract protected function getKeys(): array;

    /**
     * @param string $key
     * @return mixed
     */
    abstract protected function loadData(string $key);

    /**
     * @param mixed $value
     * @param string $key
     * @return string
     */
    protected function helpValue($value, $key)
    {
        $value = $this->wrapperContext->getEscaper()->escapeHtml(
            $this->convertToString($this->wrapperContext->wrap($value), $key)
        );
        $value = str_replace("\n", "\n    ", $value);
        return "    " . $this->convertToString($key) . " => " . $value;
    }

    /**
     * @return string
     */
    public function help()
    {
        $output = " [\n";
        foreach ($this->getKeys() as $k) {
            $output .= $this->helpValue($this->{$k}, $k) . "\n";
        }
        if ($this->aliasMap) {
            $output .= "  // aliases\n";
            foreach ($this->aliasMap as $k => $originalKey) {
                $output .= $this->helpValue($this->{$k}, $k) . " // $originalKey\n";
            }
        }
        $additionalData = array_keys($this->getAdditionalData());
        if ($additionalData) {
            $output .= "  // additional attributes\n";
            foreach ($additionalData as $k) {
                $output .= $this->helpValue($this->{$k}, $k) . "\n";
            }
        }
        $output .= "]";
        return $output;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->aliasMap[$name])) {
            return $this->__get($this->aliasMap[$name]);
        }
        if (!$this->cache->hasData($name)) {
            $value = $this->wrapperContext->wrap($this->loadData((string) $name));
            $this->cache->setData($name, $value);
        }
        return $this->cache->getData($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        $value = $this->__get($name);
        return $value !== null;
    }
}
