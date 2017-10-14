<?php

namespace AutoMapperPlus;

use AutoMapperPlus\Configuration\AutoMapperConfig;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use AutoMapperPlus\Exception\UnregisteredMappingException;
use function Functional\map;

/**
 * Class AutoMapper
 *
 * @package AutoMapperPlus
 */
class AutoMapper implements AutoMapperInterface
{
    /**
     * @var AutoMapperConfigInterface
     */
    private $autoMapperConfig;

    /**
     * AutoMapper constructor.
     *
     * @param AutoMapperConfigInterface $autoMapperConfig
     */
    function __construct(AutoMapperConfigInterface $autoMapperConfig = null)
    {
        $this->autoMapperConfig = $autoMapperConfig ?: new AutoMapperConfig();
    }

    /**
     * @inheritdoc
     */
    public static function initialize(callable $configurator): AutoMapperInterface
    {
        $mapper = new static;
        $configurator($mapper->autoMapperConfig);

        return $mapper;
    }

    /**
     * @inheritdoc
     */
    public function map($from, string $to)
    {
        $fromClass = get_class($from);
        $mapping = $this->autoMapperConfig->getMappingFor($fromClass, $to);
        $this->ensureConfigExists($fromClass, $to);

        // Check if we need to skip the constructor.
        if ($mapping->shouldSkipConstructor()) {
            $toReflectionClass = new \ReflectionClass($to);
            $toObject = $toReflectionClass->newInstanceWithoutConstructor();
        }
        else {
            $toObject = new $to;
        }

        return $this->mapToObject($from, $toObject);
    }

    /**
     * @inheritdoc
     */
    public function mapMultiple($from, string $to): array
    {
        return map($from, function ($source) use ($to) {
            return $this->map($source, $to);
        });
    }

    /**
     * @inheritdoc
     */
    public function mapToObject($from, $to)
    {
        $fromReflectionClass = new \ReflectionClass($from);
        $toReflectionClass = new \ReflectionClass($to);

        // First, check if a mapping exists for the given objects.
        $this->ensureConfigExists(
            $fromReflectionClass->getName(),
                $toReflectionClass->getName()
        );

        $mapping = $this->autoMapperConfig->getMappingFor(
            $fromReflectionClass->getName(),
            $toReflectionClass->getName()
        );

        foreach ($toReflectionClass->getProperties() as $destinationProperty) {
            $mappingOperation = $mapping->getMappingCallbackFor($destinationProperty->getName());
            $mappingOperation(
                $from,
                $to,
                $destinationProperty->getName(),
                $this->autoMapperConfig
            );
        }

        return $to;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): AutoMapperConfigInterface
    {
        return $this->autoMapperConfig;
    }

    /**
     * @param string $from
     * @param string $to
     * @return void
     * @throws UnregisteredMappingException
     */
    protected function ensureConfigExists(string $from, string $to): void
    {
        $configExists = $this->autoMapperConfig->hasMappingFor($from, $to);
        if (!$configExists) {
            throw UnregisteredMappingException::fromClasses($from, $to);
        }
    }
}
