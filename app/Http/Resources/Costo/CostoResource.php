<?php

namespace App\Http\Resources\Costo;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'type_discount' => $this->resource->type_discount,
            'discount' => $this->resource->discount,
            'type_count' => $this->resource->type_count,
            'num_use' => $this->resource->num_use,
            'type_costo' => $this->resource->type_costo,
            'state' => $this->resource->state,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i A'), //6 AM 6 PM
            'products' => $this->resource->products->map(function ($product_aux) {
                return [
                    'id' => $product_aux->product->id,
                    'title' => $product_aux->product->title,
                    "slug" => $product_aux->product->slug,
                    'imagen' => ImageHelper::getImageUrl($product_aux->product->imagen),
                    'id_aux' => $product_aux->id,
                ];
            }),
            'categories' => $this->resource->categories->map(function ($categorie_aux) {
                return [
                    'id' => $categorie_aux->categorie->id,
                    'name' => $categorie_aux->categorie->name,
                    'imagen' => ImageHelper::getImageUrl($categorie_aux->categorie->imagen),
                    'id_aux' => $categorie_aux->id
                ];
            }),
            'brands' => $this->resource->brands->map(function ($brand_aux) {
                return [
                    'id' => $brand_aux->brand->id,
                    'name' => $brand_aux->brand->name,
                    'id_aux' => $brand_aux->id
                ];
            }),
        ];
    }
}
