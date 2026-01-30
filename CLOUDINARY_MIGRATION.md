# Cloudinary Image URL Migration

## Problema Original
Las imágenes se estaban intentando cargar desde URLs inválidas:
```
https://apiecommerce-production-9896.up.railway.appstorage/slider/...
```

En lugar de obtenerlas desde Cloudinary:
```
https://res.cloudinary.com/devcam/image/upload/...
```

## Solución Implementada

Se implementó un sistema centralizado para manejar URLs de imágenes que detecta automáticamente si una imagen está en Cloudinary o en almacenamiento local.

### 1. Crear Helper para URLs de Imágenes

**Archivo**: `app/Helpers/ImageHelper.php`

Una clase helper que verifica si la URL ya es una URL completa (http/https) o una ruta local y construye la URL apropiada:

```php
public static function getImageUrl(?string $imagePath): ?string
{
    if (empty($imagePath)) {
        return null;
    }

    // Si es una URL completa (Cloudinary, http, https), retornar tal cual
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }

    // Si es una ruta local, prepender APP_URL/storage/
    return env('APP_URL') . 'storage/' . $imagePath;
}
```

### 2. Archivos Actualizados

#### Resources (Formatean respuestas de API)
- `app/Http/Resources/Product/ProductResource.php`
- `app/Http/Resources/Product/CategorieResource.php`
- `app/Http/Resources/Ecommerce/Product/ProductEcommerceResource.php`
- `app/Http/Resources/Ecommerce/Cart/CartEcommerceResource.php`
- `app/Http/Resources/Ecommerce/Sale/SaleResource.php`
- `app/Http/Resources/Discount/DiscountResource.php`
- `app/Http/Resources/Cupone/CuponeResource.php`
- `app/Http/Resources/Costo/CostoResource.php`

**Cambio**: Reemplazar `env('APP_URL') . "storage/" . $imagePath` con `ImageHelper::getImageUrl($imagePath)`

#### Controllers (Lógica de negocio)
- `app/Http/Controllers/Ecommerce/HomeController.php`
- `app/Http/Controllers/Admin/SliderController.php` - **Migrado a usar ImageService**
- `app/Http/Controllers/Admin/Costo/CostoController.php`
- `app/Http/Controllers/Admin/Cupone/CuponeController.php`
- `app/Http/Controllers/Admin/Sale/KpiSaleReportController.php`
- `app/Http/Controllers/AuthController.php` - **Migrado a usar ImageService para avatares**

#### Mail Templates (Vistas Blade)
- `resources/views/mail/sale.blade.php`
- `resources/views/mail/cartabandoned.blade.php`

**Cambio**: Actualizar plantillas para usar `\App\Helpers\ImageHelper::getImageUrl()`

### 3. Cambios en Controladores de Upload

#### SliderController
**Antes**: Guardaba las imágenes en storage local (`slider/` carpeta)
```php
$fileName = $file->hashName();
$path = $file->storeAs("slider", $fileName, "public");
$data['imagen'] = "slider/" . $fileName;
```

**Después**: Ahora usa Cloudinary vía `ImageService`
```php
$data['imagen'] = $this->imageService->upload($file, 'sliders');
```

#### AuthController (Avatares)
**Antes**: Guardaba en storage local (`users/` carpeta)
```php
$path = Storage::putFile("users", $request->file("file_imagen"));
$request->request->add(["avatar" => $path]);
```

**Después**: Ahora usa Cloudinary con eliminación de imagen anterior
```php
if ($user->avatar && strpos($user->avatar, 'http') === 0) {
    $publicId = $imageService->getPublicIdFromUrl($user->avatar);
    if ($publicId) {
        $imageService->delete($publicId);
    }
}
$user->avatar = $imageService->upload($request->file("file_imagen"), 'avatars');
```

### 4. ImageService (Existente - Ya Usa Cloudinary)

Los siguientes controladores ya usaban ImageService correctamente:
- `app/Http/Controllers/Admin/Product/CategorieController.php` - Categorías
- `app/Http/Controllers/Admin/Product/ProductImagenController.php` - Imágenes de productos

No requerían cambios.

## Flujo Completo

### Upload de Imágenes
1. El usuario sube una imagen
2. `ImageService::upload()` envía la imagen a Cloudinary
3. Cloudinary retorna una URL segura (https://res.cloudinary.com/...)
4. Esta URL se guarda en la base de datos

### Obtención de Imágenes
1. Los controladores recuperan las URLs de la base de datos
2. En las respuestas de API, usan `ImageHelper::getImageUrl()` para procesar URLs
3. Si es una URL de Cloudinary (http/https), se retorna tal cual
4. Si es una ruta local, se prepende APP_URL/storage/

### Configuración

En el archivo `.env`:
```
CLOUDINARY_CLOUD_NAME=devcam
CLOUDINARY_API_KEY=735693454976424
CLOUDINARY_API_SECRET=osRfg5fCIE6_uhYw6wqHL8WWX9E
```

En `config/cloudinary.php`:
```php
'cloud_url' => env('CLOUDINARY_URL', 'cloudinary://'.env('CLOUDINARY_KEY').':'.env('CLOUDINARY_SECRET').'@'.env('CLOUDINARY_CLOUD_NAME')),
```

## Beneficios

✅ **Imágenes centralizadas**: Todas desde Cloudinary
✅ **URLs correctas**: Las imágenes se cargan sin errores
✅ **Transformaciones automáticas**: Redimensionamiento, compresión, etc.
✅ **CDN global**: Imágenes servidas desde la ubicación más cercana
✅ **Backwards compatible**: Soporta imágenes locales antiguas si es necesario

## Testing

Para verificar que funciona correctamente:

1. Suba una nueva imagen desde admin
2. Verifique en la respuesta de API que la URL es de Cloudinary
3. En el frontend, las imágenes deberían cargarse correctamente
4. En DevTools, no debe haber errores de `ERR_NAME_NOT_RESOLVED`
