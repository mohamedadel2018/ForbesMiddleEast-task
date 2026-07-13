<?php

return [

    /*
    |--------------------------------------------------------------------------
    | List Query Cache
    |--------------------------------------------------------------------------
    |
    | Caches paginated list API responses in Redis to reduce database load
    | for repeated filter/search/sort combinations.
    |
    */

    'enabled' => env('LIST_CACHE_ENABLED', true),

    'ttl' => (int) env('LIST_CACHE_TTL', 300),

    'store' => env('LIST_CACHE_STORE'),

    'tag' => 'list-queries',

];
