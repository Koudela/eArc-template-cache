<?php /** @noinspection PhpComposerExtensionStubsInspection */ declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/template-cache
 * @link https://github.com/Koudela/eArc-template-cache/
 * @copyright Copyright (c) 2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\TemplateCache\Cache;

use eArc\TemplateCache\ParameterInterface;
use Redis;

class RedisCache implements CacheInterface
{
    protected Redis|null $redis = null;
    protected string $hashKeyPrefix;
    protected string $default;

    public function __construct()
    {
        $this->hashKeyPrefix = di_param(ParameterInterface::HASH_KEY_PREFIX, 'earc-template-cache');

        $this->redis = new Redis();
        $this->redis->connect(...di_param(ParameterInterface::REDIS_CONNECTION, ['localhost']));
    }

    public function has(string $templateFQCN, string $entityDependencyClusterKey): bool
    {
        return $this->redis->hExists($this->hashKeyPrefix.'::'.$templateFQCN, $entityDependencyClusterKey);
    }

    public function get(string $templateFQCN, string $entityDependencyClusterKey): string|null
    {
        $renderedTemplate = $this->redis->hGet($this->hashKeyPrefix.'::'.$templateFQCN, $entityDependencyClusterKey);

        return $renderedTemplate ?: null;
    }

    public function add(string $templateFQCN, string $entityDependencyClusterKey, string $renderedTemplate): void
    {
        $this->redis->hSet($this->hashKeyPrefix.'::'.$templateFQCN, $entityDependencyClusterKey, $renderedTemplate);
    }

    public function remove(string $templateFQCN, array $entityDependencyClusterKeys): void
    {
        $this->redis->hDel($this->hashKeyPrefix.'::'.$templateFQCN, ...$entityDependencyClusterKeys);
    }

    public function removeAll(string $templateFQCN): void
    {
        $this->redis->del($this->hashKeyPrefix.'::'.$templateFQCN);
    }
}
