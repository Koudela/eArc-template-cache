<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/template-cache
 * @link https://github.com/Koudela/eArc-template-cache/
 * @copyright Copyright (c) 2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\TemplateCache;

use eArc\Data\Entity\Interfaces\EntityInterface;
use eArc\Data\Entity\Interfaces\Events\PrePersistInterface;
use eArc\Data\Entity\Interfaces\Events\PreRemoveInterface;
use eArc\TemplateCache\Cache\CacheInterface;

class TemplateCacheDataBridge implements PreRemoveInterface, PrePersistInterface
{
    protected CacheInterface $cache;
    protected array $entityDependencies;

    public function __construct()
    {
        $this->cache = di_get(TemplateService::class)->getCacheService();
        $this->entityDependencies = di_param(ParameterInterface::ENTITY_DEPENDENCIES, []);
    }

    public function prePersist(EntityInterface $entity): void
    {
        $this->clearCache($entity::class, $entity->getPrimaryKey());
    }

    public static function preRemove(string $fQCN, string $primaryKey): void
    {
        di_get(TemplateCacheDataBridge::class)->clearCache($fQCN, $primaryKey);
    }

    public function clearCache(string $fQCN, $primaryKey): void
    {
        if (array_key_exists($fQCN, $this->entityDependencies)) {
            foreach ($this->entityDependencies[$fQCN] as $template) {
                if ($template instanceof EntityDependencyClusterInterface) {
                    $keys = $template::getEntityDependencyClusterKeys($fQCN, $primaryKey);

                    $this->cache->remove($template::class, $keys);
                }
            }
        }
    }
}
