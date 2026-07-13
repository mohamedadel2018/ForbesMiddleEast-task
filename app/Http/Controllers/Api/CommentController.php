<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListQueryRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;

class CommentController extends BaseListController
{
    public function index(ListQueryRequest $request)
    {
        return $this->list($request, Comment::class, CommentResource::class);
    }
}
