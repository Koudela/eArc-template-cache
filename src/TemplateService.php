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

use eArc\TemplateCache\Cache\CacheInterface;
use eArc\TemplateCache\Cache\FilesystemCache;
use eArc\TemplateCache\Cache\RedisCache;

class TemplateService
{
    protected CacheInterface $cacheService;
    protected array $entityDependencies;

    public function __construct()
    {
        $infrastructure = di_param(ParameterInterface::INFRASTRUCTURE, CacheInterface::USE_FILESYSTEM);
        $this->cacheService = $infrastructure === CacheInterface::USE_REDIS ?
            di_get(RedisCache::class) : di_get(FilesystemCache::class);

        $this->entityDependencies = di_param(ParameterInterface::ENTITY_DEPENDENCIES, []);
    }

    public function getCacheService(): CacheInterface
    {
        return $this->cacheService;
    }

    public function clearCache(): void
    {
        $templates = [];

        foreach ($this->entityDependencies as $entityDependency) {
            foreach ($entityDependency as $template) {
                $templates[$template] = $template;
            }
        }

        foreach ($templates as $template) {
            $this->cacheService->removeAll($template);
        }
    }

    public function getRendered(EntityDependencyClusterInterface $template): string
    {
        $clusterKey = $template->getEntityDependencyClusterKey();

        if (!$renderedTemplate = $this->cacheService->get($template::class, $clusterKey)) {
            $renderedTemplate = (string) $template;

            $this->cacheService->add($template::class, $clusterKey, $renderedTemplate);
        }

        return $renderedTemplate;
    }
}
