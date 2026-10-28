<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarTransaccionRequest;
use App\Http\Resources\TransaccionResource;
use App\Models\Transaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * La misma información que el controlador web, por la otra puerta.
 *
 * Ningún método devuelve una vista: todos devuelven un Resource, y
 * Laravel lo convierte en JSON. Tampoco hay redirects, porque del
 * otro lado no hay navegador que siga una redirección; lo que hay es
 * un programa que espera un código de estado y un cuerpo.
 *
 * Los códigos que usa este controlador:
 *   200  la consulta salió bien
 *   201  se creó un recurso (store)
 *   204  se hizo lo pedido y no hay nada que devolver (destroy)
 *   404  ese id no existe (lo lanza el route model binding)
 *   422  los datos no pasaron la validación (lo lanza el Form Request)
 */
class TransaccionApiController extends Controller
{
    /**
     * GET /api/transacciones — listado, con filtro opcional por estado.
     *
     * El reto de la guía. El parámetro llega por la URL
     * (?estado=Completada) y when() aplica el where solo si trae
     * algo, así que sin el parámetro la consulta queda exactamente
     * como estaba y el listado sigue devolviendo todo.
     *
     * Un estado que no existe, o uno válido sin filas, devuelve una
     * lista vacía con 200 y no un 404. El 404 significa "esa
     * dirección no corresponde a ningún recurso", y /api/transacciones
     * sí existe: la consulta se entendió y se resolvió bien. Que el
     * resultado tenga cero elementos es una respuesta correcta, no un
     * error. Devolver 404 obligaría al cliente a tratar como fallo
     * algo que simplemente es una lista sin elementos.
     *
     * latest() ordena por created_at descendente: lo más reciente
     * primero, que es lo que espera quien consulta movimientos.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $estado = $request->query('estado');

        $transacciones = Transaccion::query()
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest()
            ->get();

        return TransaccionResource::collection($transacciones);
    }

    /**
     * POST /api/transacciones — crea una transacción.
     *
     * Reutiliza el Form Request del CRUD web a propósito: las reglas
     * de un monto o de un nombre de cliente no cambian porque la
     * petición venga de un programa en vez de un formulario. Si algo
     * falla, la ejecución nunca llega a la primera línea del método y
     * Laravel responde 422 con los errores en JSON, siempre que la
     * petición lleve Accept: application/json.
     *
     * El 201 se pone a mano porque el valor por defecto de un Resource
     * es 200, y 200 significa "acá está lo que pediste" mientras que
     * 201 significa "se creó algo nuevo". Para un cliente la
     * diferencia importa: con 201 sabe que hay un recurso que antes
     * no existía, sin tener que deducirlo del cuerpo.
     *
     * El campo estado no se acepta aunque venga en la petición:
     * only() lo deja fuera, la columna nace en 'Iniciada' por el
     * default de la migración, y el recorrido entre estados lo
     * controla moverEstado() con su mapa de transiciones.
     */
    public function store(GuardarTransaccionRequest $request): JsonResponse
    {
        $transaccion = Transaccion::create($request->only([
            'comercio_id', 'cliente_nombre', 'monto',
        ]));

        // refresh() relee la fila recién insertada.
        //
        // Hace falta porque 'estado' no viaja en la petición: lo pone
        // la base de datos con el default de la migración. create()
        // devuelve el modelo con los campos que YO le pasé, y como
        // ese no era uno de ellos, el objeto en memoria lo tiene en
        // null aunque la fila ya diga 'Iniciada'. Sin esta línea la
        // respuesta 201 sale con "estado": null y el cliente cree que
        // la transacción nació sin estado.
        $transaccion->refresh();

        return (new TransaccionResource($transaccion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/transacciones/{transaccion} — una sola transacción.
     *
     * El modelo entra resuelto desde la ruta, y si el id no existe el
     * 404 se lanza antes de ejecutar el método. Con el encabezado
     * Accept: application/json ese 404 llega como JSON y no como la
     * página HTML de error.
     */
    public function show(Transaccion $transaccion): TransaccionResource
    {
        return new TransaccionResource($transaccion);
    }

    /**
     * PUT/PATCH /api/transacciones/{transaccion} — actualiza.
     *
     * apiResource declara esta ruta, así que el método se implementa
     * para no dejar un 501 colgando. Usa el mismo Form Request, por
     * la misma razón que store().
     */
    public function update(
        GuardarTransaccionRequest $request,
        Transaccion $transaccion
    ): TransaccionResource {
        $transaccion->update($request->validated());

        return new TransaccionResource($transaccion);
    }

    /**
     * DELETE /api/transacciones/{transaccion} — elimina.
     *
     * Devuelve 204: se hizo lo que se pidió y no hay cuerpo que
     * mandar. Devolver la transacción borrada sería raro, porque ya
     * no existe; devolver un mensaje de éxito obligaría al cliente a
     * leer el cuerpo para enterarse de algo que el código ya dice.
     *
     * Un segundo DELETE al mismo id responde 404, porque el route
     * model binding ya no encuentra la fila. Eso es correcto, y es
     * justo lo que la tabla de verificación pide comprobar.
     *
     * Nota: este destroy no repite la regla del CRUD web, que se
     * niega a borrar una transacción Completada. La diferencia es
     * deliberada y queda anotada acá porque es una deuda, no un
     * olvido: mientras la API no tenga identidad (los tokens son el
     * tema de la próxima clase) tampoco tiene a quién rendirle
     * cuentas, y las reglas que dependen de quién pide la acción se
     * aplican hoy en la puerta que sí sabe quién entró.
     */
    public function destroy(Transaccion $transaccion): Response
    {
        $transaccion->delete();

        return response()->noContent();
    }
}
