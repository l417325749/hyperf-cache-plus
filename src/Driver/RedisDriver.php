<?php

namespace Hyperf\CachePlus\Driver;

use Hyperf\Cache\Driver\Driver;
use Hyperf\Cache\Driver\KeyCollectorInterface;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisDriver extends Driver implements KeyCollectorInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $group;

    public function __construct(ContainerInterface $container, array $config)
    {
        parent::__construct($container, $config);

        $this->group = $config['group'];
        $this->redis = $container->get(RedisFactory::class);
    }

    public function get($key, $default = null)
    {
        $res = $this->redis->get($this->group)->get($this->getCacheKey($key));
        if ($res === false) {
            return $default;
        }

        return $this->packer->unpack($res);
    }

    public function fetch(string $key, $default = null): array
    {
        $res = $this->redis->get($this->group)->get($this->getCacheKey($key));
        if ($res === false) {
            return [false, $default];
        }

        return [true, $this->packer->unpack($res)];
    }

    public function set($key, $value, $ttl = null)
    {
        $seconds = $this->secondsUntil($ttl);
        $res = $this->packer->pack($value);
        if ($seconds > 0) {
            return $this->redis->get($this->group)->set($this->getCacheKey($key), $res, $seconds);
        }

        return $this->redis->get($this->group)->set($this->getCacheKey($key), $res);
    }

    public function delete($key)
    {
        return (bool)$this->redis->get($this->group)->del($this->getCacheKey($key));
    }

    public function clear()
    {
        return $this->clearPrefix('');
    }

    public function getMultiple($keys, $default = null)
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCacheKey($key);
        }, $keys);

        $values = $this->redis->get($this->group)->mget($cacheKeys);
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] === false ? $default : $this->packer->unpack($values[$i]);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException('The values is invalid!');
        }

        $cacheKeys = [];
        foreach ($values as $key => $value) {
            $cacheKeys[$this->getCacheKey($key)] = $this->packer->pack($value);
        }

        $seconds = $this->secondsUntil($ttl);
        if ($seconds > 0) {
            foreach ($cacheKeys as $key => $value) {
                $this->redis->get($this->group)->set($key, $value, $seconds);
            }

            return true;
        }

        return $this->redis->get($this->group)->mset($cacheKeys);
    }

    public function deleteMultiple($keys)
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCacheKey($key);
        }, $keys);

        return (bool)$this->redis->get($this->group)->del(...$cacheKeys);
    }

    public function has($key)
    {
        return (bool)$this->redis->get($this->group)->exists($this->getCacheKey($key));
    }

    public function clearPrefix(string $prefix): bool
    {
        $iterator = null;
        $key = $prefix . '*';
        while (true) {
            $keys = $this->redis->get($this->group)->scan($iterator, $this->getCacheKey($key), 10000);
            if (!empty($keys)) {
                $this->redis->get($this->group)->del(...$keys);
            }

            if (empty($iterator)) {
                break;
            }
        }

        return true;
    }

    public function addKey(string $collector, string $key): bool
    {
        return (bool)$this->redis->get($this->group)->sAdd($this->getCacheKey($collector), $key);
    }

    public function keys(string $collector): array
    {
        return $this->redis->get($this->group)->get($this->group)->sMembers($this->getCacheKey($collector)) ?? [];
    }

    public function delKey(string $collector, ...$key): bool
    {
        return (bool)$this->redis->get($this->group)->sRem($this->getCacheKey($collector), ...$key);
    }
}
