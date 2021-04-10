# eArc-template-cache

Cache for the [earc/native-php-template-engine](https://github.com/Koudela/eArc-native-php-template-engine)
which uses the [earc/data](https://github.com/Koudela/eArc-data) abstraction to 
invalidate cache items via the deterministic template entity dependencies instead 
of a time based probabilistic invalidation scheme. 

Uses a redis server or filesystem for the cache infrastructure.

## installation

Install the earc template cache library via composer.

```
$ composer require earc/template-cache
```

## preconditions

This cache is applicable to all templates the rendering result does solly depend
on the state of the applied entities. If it depends on configuration either the 
cache has to be reset every time the configuration changes, or the configuration
state has to be saved and retrieved via an entity.

It requires a clear structured approach to template design and some bookkeeping, 
as for the implementation of the `EntityDependencyClusterInterface` and the entities
dependencies parameter you have to be aware of the templates dependencies.

## bootstrap

The `TemplateCacheDataBridge` has to be registered to the earc/data `preRemove`
and `prePersist` event.

```php
use eArc\Data\ParameterInterface;
use eArc\TemplateCache\TemplateCacheDataBridge;

di_tag(ParameterInterface::TAG_PRE_REMOVE, TemplateCacheDataBridge::class);
di_tag(ParameterInterface::TAG_PRE_PERSIST, TemplateCacheDataBridge::class);
```

To cache the rendered templates you can choose between the filesystem and
a redis server as infrastructure.

### using a redis server

To use the redis server, set the infrastructure parameter to `USE_REDIS`.

```php
use eArc\TemplateCache\Cache\CacheInterface;
use eArc\TemplateCache\ParameterInterface;

di_set_param(ParameterInterface::INFRASTRUCTURE, CacheInterface::USE_REDIS);
```

By default, earc/template-cache uses `localhost` and the defaults
of the php-redis-extension. You can overwrite these defaults:

```php
use eArc\TemplateCache\ParameterInterface;

di_set_param(ParameterInterface::REDIS_CONNECTION, ['127.0.0.1', 6379]);
```

This array is handed to the `Redis::connect()` method as arguments. Consult the
[phpredis documentation](https://github.com/phpredis/phpredis/#connect-open) for
valid values and configuration options.

Now earc/template-cache is ready to use.

### using the filesystem

To use the filesystem, set the infrastructure parameter to `USE_FILESYSTEM`.

```php
use eArc\TemplateCache\Cache\CacheInterface;
use eArc\TemplateCache\ParameterInterface;

di_set_param(ParameterInterface::INFRASTRUCTURE, CacheInterface::USE_FILESYSTEM);
```

Then configure the data filesystem path for the
[earc/data-filesystem](https://github.com/Koudela/eArc-data-filesystem) bridge.

```php
use eArc\DataFilesystem\ParameterInterface;

di_set_param(ParameterInterface::DATA_PATH, '/path/to/save/the/entity/data');
```

Now earc/template-cache is ready to use.

## basic usage

### the entity dependency cluster

A templates rendering should only change if the state of the application changes.
In modern software architecture state is expressed via entities. Thus, if we make
the entities dependencies of a template explicit, we have a better invalidation
scheme than using a probabilistic approach. To keep things reasonable simple we do
not track each property value, but only the changes of the entities (via the 
earc/data abstraction).

All entity classes that contribute their property values to a template in direct or 
indirect line are added to a so-called entity dependency cluster. A set of concrete
entities form a node of the entity dependency cluster. Each node has to be
represented by a unique key.

The challenge is to determine all nodes (and their keys) to the change of a single
entity. This is done by implementing the `EntityDependencyClusterInterface` for 
each cacheable template.

If the cluster consists of one entity, it is very simple:

```php
use eArc\Data\Entity\AbstractEntity;
use eArc\NativePHPTemplateEngine\AbstractTemplateModel;
use eArc\TemplateCache\EntityDependencyClusterInterface;

class MyEntity extends AbstractEntity
{
    public string $value;
    
    public function __construct(string $primaryKey, string $value) 
    {
        $this->primaryKey = $primaryKey;
        $this->value = $value;
    }
}

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
    {?>
        <h1><?= $this->myEntity->value ?></h1>
    <?php}
}
```

To get the template by a dependent entity the dependencies have to be added to the
entity dependency parameter.

```php
use eArc\TemplateCache\ParameterInterface;

di_set_param(ParameterInterface::class, [
    MyEntity::class => [MyTemplate::class],
]);
```

Let's make it a little more complex.

```php
use eArc\Data\Entity\AbstractEntity;
use eArc\NativePHPTemplateEngine\AbstractTemplateModel;
use eArc\TemplateCache\EntityDependencyClusterInterface;
use eArc\TemplateCache\ParameterInterface;

class MyEntityA extends AbstractEntity
{
    public string $value;
    
    public function __construct(string $primaryKey, string $value) 
    {
        $this->primaryKey = $primaryKey;
        $this->value = $value;
    }
}

class MyEntityB extends AbstractEntity
{
    public string $value;
    public string $myEntityA_PK;
    
    public function __construct(string $primaryKey, string $value, MyEntityA $myEntityA) 
    {
        $this->primaryKey = $primaryKey;
        $this->value = $value;
        $this->myEntityA_PK = $myEntityA->getPrimaryKey();
    }
    
    public function getMyEntityA(): MyEntityA
    {
        return data_load(MyEntityA::class, $this->myEntityA_PK);
    }
}

class MyClusterTemplate extends AbstractTemplateModel implements EntityDependencyClusterInterface
{
    protected MyEntity $myEntity;
    protected MyEntityB $myEntityB;
    
    public function __construct(MyEntity $myEntity, MyEntityB $myEntityB)
    {
        $this->myEntity = $myEntity;
        $this->myEntityB = $myEntityB;
    }

    public static function getEntityDependencyClusterKeys(string $fQCN, string $primaryKey) : array|null
    {
        switch ($fQCN) {
            case MyEntity::class:
                $result = [];
                $primaryKeysOfA = data_find(MyEntityA::class, []);
                $primaryKeysOfB = data_find(MyEntityB::class, []);
                foreach ($primaryKeysOfA as $pkA) {
                    foreach ($primaryKeysOfB as $pkB) {
                        $result[] = $primaryKey.'::'.$pkB.'::'.$pkA;
                    }
                }
                return $result;
            case MyEntityA::class:
                $result = [];
                $primaryKeys = data_find(MyEntity::class, []);
                $primaryKeysOfB = data_find(MyEntityB::class, []);
                foreach ($primaryKeys as $pk) {
                    foreach ($primaryKeysOfB as $pkB) {
                        $result[] = $pk.'::'.$pkB.'::'.$primaryKey;
                    }
                }
                return $result;
            case MyEntityB::class:
                $result = [];
                $primaryKeysOfA = data_find(MyEntityA::class, []);
                $primaryKeys = data_find(MyEntity::class, []);
                foreach ($primaryKeys as $pk) {
                    foreach ($primaryKeysOfA as $pkA) {
                        $result[] = $pk.'::'.$primaryKey.'::'.$pkA;
                    }
                }
                return $result;
        }

        return null;
    }
    
    public function getEntityDependencyClusterKey(): string
    {
        return $this->myEntity->getPrimaryKey().'::'.$this->myEntityB->getPrimaryKey().'::'.$this->myEntityB->myEntityA_PK;
    }
    
    protected function template(): void
    {?>
        <h1><?= $this->myEntity->value ?></h1>
        <div><?= $this->myEntityB->value ?></div>
        <div><?= $this->myEntityB->getMyEntityA()->value ?><div>
    <?php}
}

di_set_param(ParameterInterface::class, [
    MyEntity::class => [
        MyTemplate::class,
        MyClusterTemplate::class,
    ],
    MyEntityA::class => [MyClusterTemplate::class],
    MyEntityB::class => [MyClusterTemplate::class],
]);
```

As you may have noticed. The combinatorial explosion limits this approach,
but also makes all kinds of caching less efficient. This means the templates individual
result is presumably not used very often but will be presumably be invalidated
more likely. 

There are several approaches to circumvent the combinatorial explosion:
1. Cache sub-templates in these cases only.
2. Use embedded entities if possible.
3. In the case of relations calculate the entity dependency cluster keys as if
   the relations were static. (You have to use the fact that the method will be 
   called **pre**persist and **pre**remove, and the old entity data can be 
   retrieved via data_load using the `DataStoreInterface::LOAD_FLAG_SKIP_FIRST_LEVEL_CACHE`)
4. Identify the entities that can hardly ever change and treat them as one
   entity for key generation. In extreme cases you can map many instances of many
   classes to one constant key. Think of a configurable homepage menu. There will
   be only one, and it will only change on maintenance. Thus, it makes sense to map
   it to one static key part. Optimizing the keys this way is analogous to the 
   single responsible principle of objects - each reason to change should map 
   to exactly one key part.

### rendering the templates

Although rendering is possible without any service, you have to use the `TemplateService`
in order to use the cache.

```php
use eArc\TemplateCache\TemplateService;

$template = new MyTemplate(data_load(MyEntity::class, 'my-primary-key'));
$renderedTemplate = di_get(TemplateService::class)->getRendered($template);
```

### clearing the cache

The cache will be invalidated on persist/remove of dependent entities. You can
utilize the `TemplateService` to clear the cache or parts of it on demand.

```php
use eArc\TemplateCache\TemplateService;

// clears the complete template cache
di_get(TemplateService::class)->clearCache(); 

$cacheService = di_get(TemplateService::class)->getCacheService(); 

// clears all rendered MyTemplate instances
$cacheService->removeAll(MyTemplate::class); 

// clears the rendered MyTemplate instance corresponding to the entity
// dependency cluster node key 'my-cluster-key-xyz' 
$cacheService->remove(MyTemplate::class, ['my-cluster-key-xyz']);
```

## advanced usage

### naming of the redis hash key

If you use the cache together with a redis server, earc/template-cache uses 
[redis hashes](https://redis.io/commands#hash) to cache the rendered templates.
 By default, the hash-key are prefixed with `earc-template-cache`. If you need 
another prefix to manage the redis namespace, you can overwrite the default:

```php
use eArc\TemplateCache\ParameterInterface;

di_set_param(ParameterInterface::HASH_KEY_PREFIX, 'my-hash-key-prefix');
```

### naming of the filesystem directory

If you use the cache together with the filesystem, earc/template cache uses the 
`@earc-template-cache` postfix to extend the filesystem entity path of 
earc/data-filesystem to cache the rendered templates. You can change this by 
setting the `DIR_NAME_POSTFIX` parameter.

```php
use eArc\TemplateCache\ParameterInterface;

di_set_param(ParameterInterface::DIR_NAME_POSTFIX, '@my-dir-name-postfix');
```

## releases

### release 0.0

* the first official release
* PHP ^8.0
