<?php

namespace AutoMapperPlus\Configuration;

use AutoMapperPlus\AutoMapper;
use AutoMapperPlus\Exception\InvalidPropertyException;
use AutoMapperPlus\MappingOperation\Implementations\Ignore;
use AutoMapperPlus\MappingOperation\Operation;
use AutoMapperPlus\NameConverter\NamingConvention\CamelCaseNamingConvention;
use AutoMapperPlus\NameConverter\NamingConvention\SnakeCaseNamingConvention;
use AutoMapperPlus\NameResolver\CallbackNameResolver;
use AutoMapperPlus\NameResolver\IdentityNameResolver;
use PHPUnit\Framework\TestCase;
use AutoMapperPlus\Test\Models\NamingConventions\CamelCaseSource;
use AutoMapperPlus\Test\Models\NamingConventions\SnakeCaseSource;
use AutoMapperPlus\Test\Models\SimpleProperties\Destination;
use AutoMapperPlus\Test\Models\SimpleProperties\Source;
use AutoMapperPlus\Test\Models\Visibility\Visibility;

/**
 * Class MappingTest
 *
 * @package AutoMapperPlus\Configuration
 */
class MappingTest extends TestCase
{
    public function testItCanAddAMappingCallback()
    {
        $autoMapperConfig = new AutoMapperConfig();
        $mapping = new Mapping(
            Source::class,
            Destination::class,
            $autoMapperConfig
        );
        $callable = function() { return 'x'; };
        $mapping->forMember('name', $callable);

        $expected = Operation::mapFrom($callable);
        $expected->setOptions($autoMapperConfig->getOptions());

        $this->assertEquals($expected, $mapping->getMappingOperationFor('name'));
    }

    public function testItReturnsTheCorrectClassNames()
    {
        $mapping = new Mapping(
            Source::class,
            Destination::class,
            new AutoMapperConfig()
        );

        $this->assertEquals(Source::class, $mapping->getSourceClassName());
        $this->assertEquals(Destination::class, $mapping->getDestinationClassName());
    }

    public function testItCanReverseMap()
    {
        $config = new AutoMapperConfig();
        $config->registerMapping(Source::class, Destination::class)->reverseMap();

        $this->assertTrue($config->hasMappingFor(Destination::class, Source::class));
    }

    public function testTheOptionsCanBeOverriddenWithSetDefaults()
    {
        $config = new AutoMapperConfig();
        $initialOptions = $config->getOptions();

        $mapping = new Mapping(
            Source::class,
            Destination::class,
            $config
        );

        $mapping->setDefaults(function (Options $options) {
            $options->setDestinationMemberNamingConvention(new SnakeCaseNamingConvention());
        });

        $this->assertEquals(
            $mapping->getOptions()->getDestinationMemberNamingConvention(),
            new SnakeCaseNamingConvention()
        );
        // Ensure the parent options aren't changed.
        $this->assertEquals($initialOptions, $config->getOptions());
    }

    public function testForMemberWithDifferentNames()
    {
        $mapping = new Mapping(
            CamelCaseSource::class,
            SnakeCaseSource::class,
            new AutoMapperConfig()
        );
        $mapping->withNamingConventions(
            new CamelCaseNamingConvention(),
            new SnakeCaseNamingConvention()
        );

        // We're mostly testing if the InvalidPropertyException isn't thrown
        // here.
        $operation = Operation::mapFrom(function () { return 'something'; });
        $mapping->forMember('property_name', $operation);

        $this->assertEquals(
            $operation,
            $mapping->getMappingOperationFor('property_name')
        );
    }

    public function testInvalidPropertyWithFromProperty()
    {
        $mapping = new Mapping(
            Source::class,
            Visibility::class,
            new AutoMapperConfig()
        );

        // Again, we're basically testing an exception isn't thrown.
        $operation = Operation::fromProperty('name');
        $operation->setOptions(Options::default());
        $mapping->forMember('privateProperty', $operation);
        $this->assertEquals(
            $operation,
            $mapping->getMappingOperationFor('privateProperty')
        );
    }

    public function testItSetsOptionsViaHelperMethods()
    {
        $mapping = new Mapping(
            Source::class,
            Destination::class,
            new AutoMapperConfig()
        );

        $mapping->dontSkipConstructor();
        $this->assertEquals(false, $mapping->getOptions()->shouldSkipConstructor());

        $mapping->withNamingConventions(
            new CamelCaseNamingConvention(),
            new SnakeCaseNamingConvention()
        );
        $this->assertInstanceOf(
            CamelCaseNamingConvention::class,
            $mapping->getOptions()->getSourceMemberNamingConvention()
        );
        $this->assertInstanceOf(
            SnakeCaseNamingConvention::class,
            $mapping->getOptions()->getDestinationMemberNamingConvention()
        );

        $mapping->withDefaultOperation(Operation::ignore());
        $this->assertInstanceOf(
            Ignore::class,
            $mapping->getOptions()->getDefaultMappingOperation()
        );

        $mapping->withNameResolver(new CallbackNameResolver(function() {}));
        $this->assertInstanceOf(
            CallbackNameResolver::class,
            $mapping->getOptions()->getNameResolver()
        );
    }

    public function testIfItCanRegisterACustomMapping()
    {
        $mapping = new Mapping(
            Source::class,
            Destination::class,
            new AutoMapperConfig()
        );

        $this->assertFalse($mapping->providesCustomMapper());
        $this->assertNull($mapping->getCustomMapper());

        $mapper = new AutoMapper();
        $mapping->useCustomMapper($mapper);

        $this->assertTrue($mapping->providesCustomMapper());
        $this->assertEquals($mapper, $mapping->getCustomMapper());
    }

    public function testItCanRegisterACallbackWithADifferentPropertyName()
    {
        $mapping = new Mapping(
            SnakeCaseSource::class,
            CamelCaseSource::class,
            new AutoMapperConfig()
        );

        $exception = false;
        try {
            $mapping->forMember('anotherProperty', function () {
              return 5;
            });
        }
        catch (InvalidPropertyException $e) {
            $exception = true;
        }

        $this->assertFalse($exception);
    }

    public function testItCanRegisterAStdClassMapping()
    {
        $mapping = new Mapping(
            \stdClass::class,
            Destination::class,
            new AutoMapperConfig()
        );
        $mapping->forMember('name', Operation::fromProperty('name'));

        $source = new \stdClass();
        $source->name = 'John';
        $destination = new Destination();

        $op = $mapping->getMappingOperationFor('name');
        $op->mapProperty('name', $source, $destination);

        $this->assertEquals('John', $destination->name);
    }
}
