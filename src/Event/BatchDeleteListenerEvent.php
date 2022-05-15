<?php

namespace Hyperf\CachePlus\Event;

use Hyperf\Cache\Exception\CacheException;
use Hyperf\Utils\ApplicationContext;
use Hyperf\CachePlus\Collector\CacheListenerCollector;

/**
 * Class BatchDeleteListenerEvent
 * @package Hyperf\CachePlus\Event
 */
class BatchDeleteListenerEvent
{
    /**
     * @var string
     */
    protected $listener;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @param string $listener
     * @param array $arguments
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(string $listener, array $arguments)
    {
        $collector = ApplicationContext::getContainer()->get(CacheListenerCollector::class);

        $config = $collector->listListener($listener);
        if (!$config) {
            throw new CacheException(sprintf('listener %s is not defined.', $listener));
        }

        $this->listener = $listener;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getListener(): string
    {
        return $this->listener;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}