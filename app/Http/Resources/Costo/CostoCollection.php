<?php

namespace App\Http\Resources\Costo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CostoCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "data" => CostoResource::collection($this->collection)
        ];
    }
}
