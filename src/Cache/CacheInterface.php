<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/template-cache
 * @link https://github.com/Koudela/eArc-template-cache/
 * @copyright Copyright (c) 2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\TemplateCache\Cache;

interface CacheInterface
{
    const USE_REDIS = 'redis';
    const USE_FILESYSTEM = 'fs';

    public function has(string $templateFQCN, string $entityDependencyClusterKey): bool;

    public function get(string $templateFQCN, string $entityDependencyClusterKey): string|null;

    public function add(string $templateFQCN, string $entityDependencyClusterKey, string $renderedTemplate): void;

    /**
     * @param string $templateFQCN
     * @param string[] $entityDependencyClusterKeys
     */
    public function remove(string $templateFQCN, array $entityDependencyClusterKeys): void;

    public function removeAll(string $templateFQCN): void;
}
