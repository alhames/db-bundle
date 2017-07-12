<?php

namespace DbBundle\Tests;

use DbBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest.
 */
class ConfigurationTest extends TestCase
{
    /**
     * Some basic tests to make sure the configuration is correctly processed in
     * the standard case.
     */
    public function testProcessSimpleCase()
    {
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process($configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
