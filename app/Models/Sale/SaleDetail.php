<?php

namespace App\Models\Sale;

use App\Models\Product\Product;
use App\Models\Product\ProductVariation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "sale_id",
        "product_id",
        "type_discount",
        "discount",
        "type_campaing",
        "code_cupon",
        "code_discount",
        "product_variation_id",
        "quantity",
        "price_unit",
        "subtotal",
        "total",
        "currency",
        "updated_at"
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set("America/Argentina/Buenos_Aires");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set("America/Argentina/Buenos_Aires");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function user()
    {
        return $this->belongsTo(Sale::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function product_variation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
