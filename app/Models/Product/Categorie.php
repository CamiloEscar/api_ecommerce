<?php

namespace App\Models\Product;

use App\Models\Discount\DiscountCategorie;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categorie extends Model
{
    use HasFactory;
    use SoftDeletes; //para que se eliminen visualmente
    //fillable es para setear que campos se podian cambiar
    protected $fillable = [
        "name",
        "icon",
        "imagen",
        "categorie_second_id",
        "categorie_third_id",
        "position",
        "type_categorie",
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

    // Relaciones PADRE (belongsTo - una categoría pertenece a...)
    public function categorie_second()
    {
        return $this->belongsTo(Categorie::class, "categorie_second_id");
    }
    public function categorie_third()
    {
        return $this->belongsTo(Categorie::class, "categorie_third_id");
    }

    // Relaciones HIJOS (hasMany - una categoría tiene muchas...)
    // Las categorías de nivel 2 que pertenecen a esta categoría de nivel 1
    public function categorie_seconds()
    {
        return $this->hasMany(Categorie::class, "categorie_second_id");
    }
    // CORRECCIÓN: Las categorías de nivel 3 que pertenecen a esta categoría de nivel 2
    public function categorie_thirds()
    {
        return $this->hasMany(Categorie::class, "categorie_second_id")
                    ->whereNotNull("categorie_third_id");
    }
    public function product_categorie_firsts()
    {
        return $this->hasMany(Product::class, "categorie_first_id");
    }
    public function product_categorie_seconds()
    {
        return $this->hasMany(Product::class, "categorie_second_id");
    }
    public function product_categorie_thirds()
    {
        return $this->hasMany(Product::class, "categorie_third_id");
    }

    public function discount_categories()
    {
        return $this->hasMany(DiscountCategorie::class, "categorie_id");
    }

    // // Asegúrate de que no haya ningún accesor que esté alterando la ruta de la imagen
    // public function getImagenAttribute($value)
    // {
    //     return $value ? asset('storage/categories/' . $value) : null;
    // }
}
