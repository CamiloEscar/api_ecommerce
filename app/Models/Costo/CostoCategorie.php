<?php

namespace App\Models\Costo;

use App\Models\Product\Categorie;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostoCategorie extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'costo_id',
        'categorie_id',
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

    public function categorie(){
        return $this->belongsTo(Categorie::class);
    }
}
