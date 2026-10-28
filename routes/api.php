<?php

use App\Http\Controllers\Api\TransaccionApiController;
use App\Http\Resources\ComercioResource;
use App\Models\Comercio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de API — TaskBoard (Pasarela de Pagos)
|--------------------------------------------------------------------------
| La segunda puerta del sistema. Lo que entra por acá no es una
| persona con navegador sino otro programa, y eso cambia tres cosas
| respecto de web.php:
|
|   - Todas las direcciones llevan el prefijo /api automáticamente,
|     así que acá se escribe '/comercios' y la URL real es
|     /api/comercios.
|   - No hay sesión ni cookies. El servidor no recuerda nada entre
|     una petición y la siguiente.
|   - No se exige token CSRF, porque el CSRF es un ataque que se monta
|     sobre las cookies que el navegador manda solo, y acá no hay.
|
| Ese tercer punto es también la deuda del día: sin sesión, estas
| rutas no saben quién pide las cosas, y por eso cualquiera con la
| dirección puede leer, crear y borrar datos de todos los usuarios.
| El aislamiento de la Semana 12 NO se cumple acá. Se resuelve la
| próxima clase, con tokens de Laravel Sanctum.
*/

/*
|--------------------------------------------------------------------------
| Comercios — solo lectura
|--------------------------------------------------------------------------
| Van con funciones en la ruta y no con un controlador porque son dos
| endpoints de lectura y nada más. Las transacciones, que sí escriben,
| usan un controlador.
*/

// Listado. No carga la relación a propósito: el Resource solo incluye
// la clave 'transacciones' cuando viene cargada, así que acá no
// aparece y no se dispara una consulta por cada comercio (N+1).
Route::get('/comercios', function () {
    return ComercioResource::collection(Comercio::all());
});

// Detalle. Acá sí se pide la relación con load(), y por eso este
// endpoint, y solo este, devuelve las transacciones del comercio.
Route::get('/comercios/{comercio}', function (Comercio $comercio) {
    return new ComercioResource($comercio->load('transacciones'));
});

/*
|--------------------------------------------------------------------------
| Transacciones — lectura y escritura
|--------------------------------------------------------------------------
| apiResource declara 5 rutas y no 7: deja fuera create y edit, que en
| el CRUD web servían para MOSTRAR formularios. Una API no tiene
| formularios, así que esas dos direcciones no tendrían nada que
| devolver; el que crea es store y el que modifica es update.
*/
// parameters() renombra el comodin de la URL. Sin esta linea Laravel
// singulariza 'transacciones' en ingles y lo deja como {transaccione},
// que no coincide con el $transaccion del controlador: el route model
// binding deja de resolver y los metodos reciben un modelo vacio en
// vez de la fila pedida.
Route::apiResource('transacciones', TransaccionApiController::class)
    ->parameters(['transacciones' => 'transaccion']);

/*
|--------------------------------------------------------------------------
| Ruta de ejemplo que dejó install:api
|--------------------------------------------------------------------------
| Devuelve el usuario dueño del token que venga en la petición. Hoy
| responde 401 siempre, porque todavía no se emiten tokens. La dejo
| como está porque es el punto de partida de la próxima clase.
*/
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
