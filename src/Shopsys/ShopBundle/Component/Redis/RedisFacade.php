<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Redis;

use Redis;
use Shopsys\FrameworkBundle\Component\Redis\RedisFacade as BaseRedisFacade;

class RedisFacade extends BaseRedisFacade
{

    /**
     * @param string $pattern
     * @param mixed|null $id
     * @param string|null id
     */
    public function clearCacheByPattern($pattern = '', $id = null)
    {
        $redis = $this->findCacheClientByPattern($pattern);
        $prefix = (string)$redis->getOption(Redis::OPT_PREFIX);

        $pattern = $prefix . '*';
        if ($id !== null) {
            $pattern .= $id . '*';
        }

        if (!$this->hasAnyKey($redis, $pattern)) {
            return;
        }
        $redis->eval("return redis.call('del', unpack(redis.call('keys', ARGV[1])))", [$pattern]);
    }

    /**
     * @param string $pattern
     * @return \Redis|null
     */
    public function findCacheClientByPattern(string $pattern): Redis
    {
        foreach ($this->getCacheClients() as $redis) {
            $prefix = (string)$redis->getOption(Redis::OPT_PREFIX);

            if (strpos($prefix, $pattern) !== false) {
                return $redis;
            }
        }

        return null;
    }
}
