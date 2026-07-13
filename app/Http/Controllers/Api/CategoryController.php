<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListQueryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends BaseListController
{
    public function index(ListQueryRequest $request)
    {
        return $this->list($request, Category::class, CategoryResource::class);
    }
}
