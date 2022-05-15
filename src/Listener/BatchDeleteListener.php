<?php

namespace Hyperf\CachePlus\Listener;

use Hyperf\Cache\Listener\DeleteEvent;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\CachePlus\Collector\CacheListenerCollector;
use Hyperf\CachePlus\Event\BatchDeleteListenerEvent;

class BatchDeleteListener implements ListenerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheListenerCollector
     */
    protected $collector;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->collector = $container->get(CacheListenerCollector::class);
    }

    public function listen(): array
    {
        return [
            BatchDeleteListenerEvent::class,
        ];
    }

    /**
     * @param BatchDeleteListenerEvent $event
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function process(object $event)
    {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        $listListener = $this->collector->listListener($event->getListener());
        foreach ($listListener as $value) {
            $className = $value['className'];
            $method = $value['method'];
            $event = new DeleteEvent($className, $method, $event->getArguments());
            $dispatcher->dispatch($event);
        }
    }
}