<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListQueryRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseListController extends Controller
{
    /**
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     * @param  class-string<JsonResource>  $resourceClass
     */
    protected function list(
        ListQueryRequest $request,
        string $modelClass,
        string $resourceClass,
    ): JsonResponse {
        $paginator = $modelClass::query()
            ->with($modelClass::defaultRelations())
            ->advancedList(
                $request->filters(),
                $request->searchTerm(),
                $request->sortExpression(),
            )
            ->paginate($request->perPage());

        return $resourceClass::collection($paginator)->response();
    }
}
