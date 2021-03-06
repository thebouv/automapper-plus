<?php

namespace AutoMapperPlus\MappingOperation;

use AutoMapperPlus\Configuration\Options;
use AutoMapperPlus\PropertyAccessor\PropertyAccessorInterface;

/**
 * Class DefaultMappingOperation
 *
 * @package AutoMapperPlus\MappingOperation
 */
class DefaultMappingOperation implements MappingOperationInterface
{
    /**
     * @var string
     */
    private $sourcePropertyName;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @inheritdoc
     */
    public function mapProperty(string $propertyName, $source, $destination): void
    {
        $this->sourcePropertyName = '';
        if (!$this->canMapProperty($propertyName, $source)) {
            // Alternatively throw an error here.
            return;
        }
        $sourceValue = $this->getSourceValue($source, $propertyName);
        $this->setDestinationValue($destination, $propertyName, $sourceValue);
    }

    /**
     * @inheritdoc
     */
    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $propertyName
     * @param $source
     * @return bool
     */
    protected function canMapProperty(string $propertyName, $source): bool
    {
        $sourcePropertyName = $this->getSourcePropertyName($propertyName);

        return $this->getPropertyAccessor()->hasProperty($source, $sourcePropertyName);
    }

    /**
     * @param $source
     * @param string $propertyName
     * @return mixed
     */
    protected function getSourceValue($source, string $propertyName)
    {
        return $this->getPropertyAccessor()->getProperty(
            $source,
            $this->getSourcePropertyName($propertyName)
        );
    }

    /**
     * @param $destination
     * @param string $propertyName
     * @param $value
     */
    protected function setDestinationValue($destination, string $propertyName, $value): void
    {
        $this->getPropertyAccessor()->setProperty($destination, $propertyName, $value);
    }

    /**
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->options->getPropertyAccessor();
    }

    /**
     * Returns the name of the property we should fetch from the source object.
     *
     * @param string $propertyName
     * @return string
     */
    protected function getSourcePropertyName(string $propertyName): string
    {
        // Lazy way of caching the source property name.
        if (!empty($this->sourcePropertyName)) {
            return $this->sourcePropertyName;
        }

        $this->sourcePropertyName = $this->options->getNameResolver()->getSourcePropertyName(
            $propertyName,
            $this,
            $this->options
        );

        return $this->sourcePropertyName;
    }
}
