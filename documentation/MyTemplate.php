<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/template-cache
 * @link https://github.com/Koudela/eArc-template-cache/
 * @copyright Copyright (c) 2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace {

    use eArc\NativePHPTemplateEngine\AbstractTemplateModel;
    use eArc\TemplateCache\EntityDependencyClusterInterface;

    class MyTemplate extends AbstractTemplateModel implements EntityDependencyClusterInterface
    {
        protected MyEntity $myEntity;

        public function __construct(MyEntity $myEntity)
        {
            $this->myEntity = $myEntity;
        }

        public static function getEntityDependencyClusterKeys(string $fQCN, string $primaryKey) : array|null
        {
            return $fQCN instanceof MyEntity ? [$primaryKey] : null;
        }

        public function getEntityDependencyClusterKey(): string
        {
            return $this->myEntity->getPrimaryKey();
        }

        protected function template(): void
        {
        }
    }
}
