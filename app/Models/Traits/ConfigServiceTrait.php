<?php

namespace Plugins\LaravelConfig\Models\Traits;

use Plugins\LaravelConfig\Models\Config;
use Plugins\MarketManager\Utils\LaravelCache;

/**
 * @mixin Config
 */
trait ConfigServiceTrait
{
    public static function findConfig(?string $itemKey = null, ?string $itemTag = null, array $where = []): null|Config
    {
        if (empty($itemKey)) {
            return null;
        }
        
        if ($itemKey) {
            $where['item_key'] = $itemKey;
        }

        if ($itemTag) {
            $where['item_tag'] = $itemTag;
        }

        return Config::query()->where($where)->first();
    }

    public static function getValueByKey(string $itemKey, ?string $itemTag = null, array $where = [])
    {
        $cacheKey = Config::CACHE_KEY_PREFIX . $itemKey;

        $config = LaravelCache::remember($cacheKey, function () use ($itemKey, $itemTag, $where) {
            $where['item_key'] = $itemKey;
            if ($itemTag) {
                $where['item_tag'] = $itemTag;
            }

            return Config::query()->where($where)->first();
        });

        return $config?->item_value;
    }

    public static function getValueByKeys(array $itemKeys, ?string $itemTag = null, array $where = []): array
    {
        $data = [];

        foreach ($itemKeys as $index => $itemKey) {
            $value = LaravelCache::get($itemKey);
            if ($value) {
                unset($itemKeys[$index]);

                $data[$itemKey] = $value;
            }
        }

        // 所有数据已全部查出
        if (count($data) === count($itemKeys)) {
            return $data;
        }

        // 拼接额外查询条件
        if ($itemTag) {
            $where['item_tag'] = $itemTag;
        }

        // 已查出部分缓存中的 key，还有部分 key 需要查询
        foreach ($itemKeys as $index => $itemKey) {
            $data[$itemKey] = static::getValueByKey($itemKey);
        }

        return $data;
    }

    public static function forgetCache(?string $itemKey = null)
    {
        $cacheKey = Config::CACHE_KEY_PREFIX . $itemKey;
        LaravelCache::forget($cacheKey);
    }
}