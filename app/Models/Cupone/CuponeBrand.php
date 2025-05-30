<?php

namespace App\Models\Cupone;

use App\Models\Product\Brand;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuponeBrand extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'cupone_id',
        'brand_id',
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

    public function brand(){
        return $this->belongsTo(Brand::class);
    }
}
