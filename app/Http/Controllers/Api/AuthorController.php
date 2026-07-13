<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListQueryRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;

class AuthorController extends BaseListController
{
    public function index(ListQueryRequest $request)
    {
        return $this->list($request, Author::class, AuthorResource::class);
    }
}
