<?php

namespace App\Models\Product;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSpecification extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'attribute_id',
        'propertie_id',
        'value_add',
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

    //relacion con la tabla de producto
    public function product()
    {
        return $this->belongsTo(Product::class, "product_id");
    }
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, "attribute_id");
    }
    public function propertie()
    {
        return $this->belongsTo(Propertie::class, "propertie_id");
    }
}
