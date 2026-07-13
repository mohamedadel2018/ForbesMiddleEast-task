<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListQueryRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;

class ArticleController extends BaseListController
{
    public function index(ListQueryRequest $request)
    {
        return $this->list($request, Article::class, ArticleResource::class);
    }
}
