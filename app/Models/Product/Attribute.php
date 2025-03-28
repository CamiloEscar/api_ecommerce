<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\Product\Propertie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes; //para que se eliminen visualmente
    //fillable es para setear que campos se podian cambiar
    protected $fillable = [
        "name",
        "type_attribute",
        "state"
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

    public function properties(){
        return $this->hasMany(Propertie::class);
    }
    public function specifications(){
        return $this->hasMany(ProductSpecification::class);
    }
    public function variations(){
        return $this->hasMany(ProductVariation::class);
    }
}
