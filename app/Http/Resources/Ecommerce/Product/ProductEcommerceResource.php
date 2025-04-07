<?php

namespace App\Http\Resources\Ecommerce\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductEcommerceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $discount_g = null;

        $discount_collection = collect([]);

        $discount_product = $this->resource->discount_product;
        if ($discount_product) {
            $discount_collection->push($discount_product);
        };

        $discount_categorie = $this->resource->discount_categorie;
        if ($discount_categorie) {
            $discount_collection->push($discount_categorie);
        };

        $discount_brand = $this->resource->discount_brand;
        if ($discount_brand) {
            $discount_collection->push($discount_brand);
        };

        if ($discount_collection->count() > 0) {
            $discount_g = $discount_collection->sortByDesc("discount")->values()->all()[0];
        }

        return [
            "id" => $this->resource->id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'sku' => $this->resource->sku,
            'price_ars' => $this->resource->price_ars,
            'price_usd' => $this->resource->price_usd,
            'resumen' => $this->resource->resumen,
            'imagen' => $this->resource->imagen ? env('APP_URL') . "storage/" . $this->resource->imagen : null,
            'state' => $this->resource->state,
            'description' => $this->resource->description,
            'tags' => $this->resource->tags ? json_decode($this->resource->tags, true) : [],
            'brand_id' => $this->resource->brand_id,

            'brand' => $this->resource->brand ? [
                "id" => $this->resource->brand->id,
                "name" => $this->resource->brand->name
            ] : NULL,
            'categorie_first_id' => $this->resource->categorie_first_id,
            'categorie_first' => $this->resource->categorie_first ? [
                "id" => $this->resource->categorie_first->id,
                "name" => $this->resource->categorie_first->name
            ] : NULL,
            'categorie_second_id' => $this->resource->categorie_second_id,
            'categorie_second' => $this->resource->categorie_second ? [
                "id" => $this->resource->categorie_second->id,
                "name" => $this->resource->categorie_second->name
            ] : NULL,
            'categorie_third_id' => $this->resource->categorie_third_id,
            'categorie_third' => $this->resource->categorie_third ? [
                "id" => $this->resource->categorie_third->id,
                "name" => $this->resource->categorie_third->name
            ] : NULL,
            'stock' => $this->resource->stock,
            'created_at' => $this->resource->created_at->format('Y-m-d h:i:s'),
            "images" => $this->resource->images->map(function ($image) {
                return [
                    "id" => $image->id,
                    "imagen" => env("APP_URL") . "storage/" . $image->imagen

                ];
            }),
            "discount_g" => $discount_g,
            "variations" => $this->resource->variations->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'product_id' => $variation->product_id,
                    'attribute_id' => $variation->attribute_id,
                    //relaciones
                    "attribute" => $variation->attribute ? [
                        "name" => $variation->attribute->name,
                        "type_attribute" => $variation->attribute->type_attribute,
                    ] : NULL,
                    'propertie_id' => $variation->propertie_id,
                    //relaciones
                    "propertie" => $variation->propertie ? [
                        "name" => $variation->propertie->name,
                        "code" => $variation->propertie->code,
                    ] : NULL,

                    'value_add' => $variation->value_add,
                    'add_price' => $variation->add_price,
                    'stock' => $variation->stock,
                    "variations" => $variation->variation_children->map(function ($subvariation) {
                        return [
                            'id' => $subvariation->id,
                            'product_id' => $subvariation->product_id,
                            'attribute_id' => $subvariation->attribute_id,
                            //relaciones
                            "attribute" => $subvariation->attribute ? [
                                "name" => $subvariation->attribute->name,
                                "type_attribute" => $subvariation->attribute->type_attribute,
                            ] : NULL,
                            'propertie_id' => $subvariation->propertie_id,
                            //relaciones
                            "propertie" => $subvariation->propertie ? [
                                "name" => $subvariation->propertie->name,
                                "code" => $subvariation->propertie->code,
                            ] : NULL,

                            'value_add' => $subvariation->value_add,
                            'add_price' => $subvariation->add_price,
                            'stock' => $subvariation->stock,
                        ];
                    })
                ];
            })
        ];
    }
}
