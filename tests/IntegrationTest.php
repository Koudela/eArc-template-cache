<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/template-cache
 * @link https://github.com/Koudela/eArc-template-cache/
 * @copyright Copyright (c) 2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\TemplateCacheTests;

use eArc\Data\Initializer;
use eArc\Data\ParameterInterface;
use eArc\TemplateCache\TemplateCacheDataBridge;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function init()
    {
        Initializer::init();

        di_tag(ParameterInterface::TAG_PRE_REMOVE, TemplateCacheDataBridge::class);
        di_tag(ParameterInterface::TAG_PRE_PERSIST, TemplateCacheDataBridge::class);
    }
}
