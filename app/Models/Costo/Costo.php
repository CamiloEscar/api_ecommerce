<?php

namespace App\Models\Costo;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Costo extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'code',
        'type_discount',
        'discount',
        'type_count',
        'num_use',
        'type_costo',
        'state'
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

    public function categories(){
        return $this->hasMany(CostoCategorie::class);
    }
    public function products(){
        return $this->hasMany(CostoProduct::class);
    }
    public function brands(){
        return $this->hasMany(CostoBrand::class);
    }
};
