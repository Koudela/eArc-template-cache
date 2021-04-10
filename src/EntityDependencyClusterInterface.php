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

use eArc\NativePHPTemplateEngine\TemplateInterface;

/**
 * An entity dependency cluster is a set of entities classes, where each entity change
 * does change the template outcome. The entity dependency cluster has to be complete,
 * that means the templates outcome is not allowed to change due to other changes.
 * A node of an entity cluster is a set of concrete entities. Each node has to
 * have an unique key.
 */
interface EntityDependencyClusterInterface extends TemplateInterface
{
    /**
     * Returns the keys of all nodes of the entity dependency clusters of the
     * template that contain the referenced entity.
     *
     * @param string $fQCN
     * @param string $primaryKey
     *
     * @return string[]|null
     */
    public static function getEntityDependencyClusterKeys(string $fQCN, string $primaryKey): array|null;

    /**
     * Returns the key for the node of the entity dependency cluster representing
     * the template instance.
     *
     * @return string
     */
    public function getEntityDependencyClusterKey(): string;
}
