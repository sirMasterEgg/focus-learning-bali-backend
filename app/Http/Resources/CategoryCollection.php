<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\Category */
class CategoryCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        })->toArray();
    }
}
