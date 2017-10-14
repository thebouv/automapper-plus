<?php

namespace AutoMapperPlus;

use AutoMapperPlus\Configuration\AutoMapperConfig;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use PHPUnit\Framework\TestCase;
use Test\Models\Post\CreatePostViewModel;
use Test\Models\Post\Post;
use Test\Models\SimpleProperties\Destination;
use Test\Models\SimpleProperties\Source;

/**
 * Class AutoMapperTest
 *
 * @package AutoMapperPlus
 */
class AutoMapperTest extends TestCase
{
    protected $source;
    protected $destination;

    /**
     * @var AutoMapperConfig
     */
    protected $config;

    protected function setUp()
    {
        $this->config = new AutoMapperConfig();
    }

    public function testItCanBeInstantiatedStatically()
    {
        $mapper = AutoMapper::initialize(function (AutoMapperConfigInterface $config) {
            $config->registerMapping(Source::class, Destination::class);
        });

        $destination = $mapper->map(new Source(), Destination::class);
        $this->assertInstanceOf(Destination::class, $destination);
    }

    public function testItMapsAPublicProperty()
    {
        $this->config->registerMapping(Source::class, Destination::class);
        $mapper = new AutoMapper($this->config);
        $source = new Source();
        $source->name = 'Hello';
        /** @var Destination $dest */
        $destination = $mapper->map($source, Destination::class);

        $this->assertInstanceOf(Destination::class, $destination);
        $this->assertEquals($source->name, $destination->name);
    }

    public function testItCanMapToAnExistingObject()
    {
        $this->config->registerMapping(Source::class, Destination::class);
        $mapper = new AutoMapper($this->config);
        $source = new Source();
        $source->name = 'Hello';
        $destination = new Destination();
        $destination = $mapper->mapToObject($source, $destination);

        $this->assertEquals($source->name, $destination->name);
    }

    public function testItCanMapWithACallback()
    {
        $this->config->registerMapping(Source::class, Destination::class)
            ->forMember('name', function () {
                return 'NewName';
            });
        $mapper = new AutoMapper($this->config);
        $source = new Source();
        $destination = $mapper->map($source, Destination::class);

        $this->assertEquals('NewName', $destination->name);
    }

    public function testTheConfigurationCanBeRetrieved()
    {
        $config = new AutoMapperConfig();
        $mapper = new AutoMapper($config);

        $this->assertEquals($config, $mapper->getConfiguration());
    }

    public function testItCanMapMultiple()
    {
        $this->config->registerMapping(Source::class, Destination::class);
        $mapper = new AutoMapper($this->config);

        $sourceCollection = [
            new Source('One'),
            new Source('Two'),
            new Source('Three')
        ];

        $destinationCollection = [
            new Destination('One'),
            new Destination('Two'),
            new Destination('Three')
        ];

        $this->assertEquals(
            $destinationCollection,
            $mapper->mapMultiple($sourceCollection, Destination::class)
        );
    }

    public function testItCanMapToAnObjectWithLessProperties()
    {
        $this->config->registerMapping(CreatePostViewModel::class, Post::class);
        $mapper = new AutoMapper($this->config);

        $source = new CreatePostViewModel();
        $source->title = 'Im a title';
        $source->body = 'Im a body';

        $expected = new Post(null, 'Im a title', 'Im a body');
        $this->assertEquals($expected, $mapper->map($source, Post::class));
    }
}
