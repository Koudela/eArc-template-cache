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

use eArc\DataFilesystem\Services\StaticDirectoryService;
use eArc\TemplateCache\ParameterInterface;

class FilesystemCache implements CacheInterface
{
    protected string $dirName;

    public function __construct()
    {
        $this->dirName = di_param(ParameterInterface::DIR_NAME_POSTFIX, '@earc-template-cache');
    }

    public function has(string $templateFQCN, string $entityDependencyClusterKey): bool
    {
        di_static(StaticDirectoryService::class)::forceChdir($templateFQCN, $this->dirName);

        return file_exists($entityDependencyClusterKey.'.txt');
    }

    public function get(string $templateFQCN, string $entityDependencyClusterKey): string|null
    {
        di_static(StaticDirectoryService::class)::forceChdir($templateFQCN, $this->dirName);

        $renderedTemplate = file_get_contents($entityDependencyClusterKey.'.txt');

        return $renderedTemplate ?: null;
    }

    public function add(string $templateFQCN, string $entityDependencyClusterKey, string $renderedTemplate): void
    {
        di_static(StaticDirectoryService::class)::forceChdir($templateFQCN, $this->dirName);

        file_put_contents($entityDependencyClusterKey.'.txt', $renderedTemplate, LOCK_EX);
    }

    public function remove(string $templateFQCN, array $entityDependencyClusterKeys): void
    {
        di_static(StaticDirectoryService::class)::forceChdir($templateFQCN, $this->dirName);

        foreach ($entityDependencyClusterKeys as $entityDependencyClusterKey) {
            unlink($entityDependencyClusterKey.'.txt');
        }
    }

    public function removeAll(string $templateFQCN): void
    {
        di_static(StaticDirectoryService::class)::forceChdir($templateFQCN, $this->dirName);

        array_map('unlink', glob('*.txt'));
    }
}
