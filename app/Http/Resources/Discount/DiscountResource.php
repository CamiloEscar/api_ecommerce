<?php

namespace App\Http\Resources\Discount;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
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
            'start_date' => Carbon::parse($this->resource->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($this->resource->end_date)->format('Y-m-d'),
            'discount_type' => $this->resource->discount_type,
            'state' => $this->resource->state,
            'type_campaing' => $this->resource->type_campaing,
            'created_at' => $this->resource->created_at->format('Y-m-d H:i A'), //6 AM 6 PM
            'products' => $this->resource->products->map(function ($product_aux) {
                return [
                    'id' => $product_aux->product->id,
                    'title' => $product_aux->product->title,
                    'imagen' => env("APP_URL") . "storage/" . $product_aux->product->imagen,
                    'id_aux' => $product_aux->id,
                ];
            }),
            'categories' => $this->resource->categories->map(function ($categorie_aux) {
                return [
                    'id' => $categorie_aux->categorie->id,
                    'name' => $categorie_aux->categorie->name,
                    'imagen' => env("APP_URL") . "storage/" . $categorie_aux->categorie->imagen,
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
