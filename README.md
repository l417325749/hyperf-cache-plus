### Cacheable支持多redis实例
```php
# config/autoload/redis.php
<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => env('REDIS_HOST'),
        'auth' => null,
        'port' => env('REDIS_PORT'),
        'db' => 0,
        'pool' => [
            'min_connections' => 10,
            'max_connections' => 100,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60,
        ],
    ],
    'commonRedis' => [
        'host' => env('COMMON_REDIS_HOST'),
        'auth' => null,
        'port' => env('COMMON_REDIS_PORT'),
        'db' => 0,
        'pool' => [
            'min_connections' => 10,
            'max_connections' => 50,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60,
        ],
    ]
];

# app/Service/TestService.php
<?php

namespace App\Service;

use Hyperf\Cache\Annotation\Cacheable;

class TestService
{
    /**
     * @Cacheable(prefix="useRedisCache", ttl=60, listener="user-update", group="commonRedis")
     * @param int $id
     * @return array
     */
    public function useRedisCache(int $id = 0): array
    {
        return [
            'id' => $id,
            'group' => 'commonRedis',
            'date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * @Cacheable(prefix="useRedisCache2", ttl=60, listener="user-update")
     * @param int $id
     * @return array
     */
    public function useRedisCache2(int $id = 0): array
    {
        return [
            'id' => $id,
            'group' => 'default',
            'date' => date('Y-m-d H:i:s')
        ];
    }

}

# config/autoload/cache.php
<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => Hyperf\Cache\Driver\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
    ],
    'commonRedis' => [
        'driver' => Hyperf\CachePlus\Driver\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
        'group' => 'commonRedis',
    ],
];
```
### Cacheable支持批量删除
```php
# app/Command/Test.php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\TestService;
use Hyperf\CachePlus\Event\BatchDeleteListenerEvent;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @Command
 */
#[Command]
class Test extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject()
     * @var TestService
     */
    protected $TestService;

    /**
     * @Inject()
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @Inject()
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('1:1');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $this->dispatcher->dispatch(new BatchDeleteListenerEvent('user-update', [1]));

        var_dump($this->TestService->useRedisCache(1));
        var_dump($this->TestService->useRedisCache2(1));
    }
}


```