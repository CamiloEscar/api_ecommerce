<?php
namespace App\Models\Cupone;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cupone extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type_discount',
        'discount',
        'type_count',
        'num_use',
        'type_cupone',
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
        return $this->hasMany(CuponeCategorie::class);
    }

    public function products(){
        return $this->hasMany(CuponeProduct::class);
    }

    public function brands(){
        return $this->hasMany(CuponeBrand::class);
    }

    // Nueva relación
    public function userUsages()
    {
        return $this->hasMany(CuponeUserUsage::class);
    }

    // Método helper para verificar si un usuario ya usó este cupón
    public function hasBeenUsedByUser($userId)
    {
        return $this->userUsages()->where('user_id', $userId)->exists();
    }
}
