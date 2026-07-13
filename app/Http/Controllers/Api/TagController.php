<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListQueryRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;

class TagController extends BaseListController
{
    public function index(ListQueryRequest $request)
    {
        return $this->list($request, Tag::class, TagResource::class);
    }
}
