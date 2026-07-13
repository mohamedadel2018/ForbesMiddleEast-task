<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

class ListQueryCache
{
    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $params
     */
    public function key(string $modelClass, array $params): string
    {
        ksort($params);

        return 'list-query:'.class_basename($modelClass).':'.hash('xxh128', json_encode($params));
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $params
     */
    public function get(string $modelClass, array $params): mixed
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->store()->get($this->key($modelClass, $params));
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $params
     */
    public function put(string $modelClass, array $params, mixed $payload): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->taggedStore()->put(
            $this->key($modelClass, $params),
            $payload,
            config('list-cache.ttl'),
        );
    }

    public function flush(): void
    {
        if ($this->supportsTags()) {
            Cache::store($this->storeName())->tags([config('list-cache.tag')])->flush();

            return;
        }

        Cache::store($this->storeName())->flush();
    }

    public function enabled(): bool
    {
        return (bool) config('list-cache.enabled');
    }

    protected function store(): CacheRepository
    {
        return Cache::store($this->storeName());
    }

    protected function taggedStore(): CacheRepository
    {
        if ($this->supportsTags()) {
            return $this->store()->tags([config('list-cache.tag')]);
        }

        return $this->store();
    }

    protected function supportsTags(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached', 'dynamodb', 'octane'], true);
    }

    protected function storeName(): ?string
    {
        return config('list-cache.store') ?: null;
    }
}
