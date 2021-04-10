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

interface ParameterInterface
{
    const ENTITY_DEPENDENCIES = 'earc.template_cache.entity_dependencies'; // default []

    const HASH_KEY_PREFIX = 'earc.template_cache.hash_key_name'; // default 'earc-template-cache'
    const DIR_NAME_POSTFIX = 'earc.template_cache.dir_name_postfix'; // default '@earc-template-cache'
    // may be set to CacheInterface::USE_REDIS or CacheInterface::USE_FILESYSTEM
    const INFRASTRUCTURE = 'earc.template_cache.infrastructure'; // default USE_FILESYSTEM
    const REDIS_CONNECTION = 'earc.template_cache.redis_connection'; // default ['localhost']
}
