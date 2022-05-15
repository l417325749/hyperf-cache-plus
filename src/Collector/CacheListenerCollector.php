<?php

namespace Hyperf\CachePlus\Collector;

use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\AnnotationCollector;

/**
 * Class CacheListenerCollector
 * @package Hyperf\CachePlus\Collector
 */
class CacheListenerCollector
{
    /**
     * @var array
     */
    protected $collect;

    public function __construct()
    {
        $list = AnnotationCollector::getMethodsByAnnotation(Cacheable::class);
        foreach ($list as $value) {
            /* @var $annotation Cacheable */
            $annotation = $value['annotation'];
            if (!empty($annotation->listener)) {
                $this->pushListener($annotation->listener, [
                    'className' => $value['class'],
                    'method' => $value['method'],
                ]);
            }
        }
    }

    /**
     * @param string $listener
     * @param array $value
     */
    public function pushListener(string $listener, array $value)
    {
        if (!isset($this->collect[$listener])) {
            $this->collect[$listener] = [];
        }
        $this->collect[$listener][] = $value;
    }

    /**
     * @param string $listener
     * @param $default
     * @return mixed|null
     */
    public function listListener(string $listener, $default = null)
    {
        return $this->collect[$listener] ?? $default;
    }

    /**
     * @param string|null $className
     */
    public function clear(?string $className = null): void
    {
        if ($className) {
            foreach ($this->collect as $listener => $list) {
                foreach ($list as $key => $value) {
                    if (isset($value['className']) && $value['className'] === $className) {
                        unset($this->collect[$listener][$key]);
                    }
                }
            }
        } else {
            $this->collect = [];
        }
    }
}