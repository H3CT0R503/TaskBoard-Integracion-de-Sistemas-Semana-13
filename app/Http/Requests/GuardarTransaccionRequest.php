<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas del formulario de transacciones, sacadas del controlador.
 *
 * Antes esto era un validate() dentro de store(). Como clase aparte se
 * reutiliza tal cual en update(), y el controlador queda con la lógica
 * del flujo nada más.
 */
class GuardarTransaccionRequest extends FormRequest
{
    /**
     * Responde si quien envía la petición tiene permiso, que es una
     * pregunta distinta a si los datos son correctos.
     *
     * Artisan lo genera en false y eso devuelve 403. Va en true
     * porque la pregunta de quién entra ya la responde el middleware
     * auth de la ruta: sin sesión, la petición no llega hasta este
     * Form Request.
     *
     * Y la del dueño del comercio, que la semana pasada quedó
     * pendiente justo acá, se resolvió dentro de rules(): el
     * comercio_id se valida contra los comercios del usuario de la
     * sesión, que es más preciso que un true o false para toda la
     * petición y además devuelve un mensaje de validación en vez de
     * un 403 seco.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de cada campo.
     *
     * La de comercio_id es la que importa de más, y esta semana
     * cambió. Antes era exists:comercios,id, que preguntaba solo si
     * el comercio existía: con eso, cambiar el número del campo
     * oculto desde las herramientas del navegador alcanzaba para
     * colgarle una transacción al comercio de otra persona.
     *
     * Ahora el exists lleva un where user_id, así que el comercio
     * tiene que existir Y ser del usuario de la sesión. Filtrar el
     * <select> del formulario no sustituye esto: el navegador es del
     * atacante, y una opción borrada de la lista se vuelve a escribir
     * en dos clics o se manda la petición sin formulario. Lo único
     * que de verdad decide es la validación del servidor.
     *
     * $this->user() dentro de un Form Request es el usuario
     * autenticado, el mismo que devolvería Auth::user().
     *
     * El if de abajo aparece en la Semana 13, cuando el mismo Form
     * Request empezó a validar también las peticiones de la API.
     * Las rutas de routes/api.php no usan sesión, así que ahí
     * $this->user() es null y leerle ->id reventaba con un error 500
     * antes de poder validar nada.
     *
     * Sin usuario la regla cae al exists simple: comprueba que el
     * comercio exista, que es todo lo que se puede comprobar cuando
     * no se sabe quién pregunta. Eso NO es equivalente ni pretende
     * serlo: por la API, hoy, cualquiera puede colgarle una
     * transacción al comercio de otro. Es exactamente el agujero que
     * la guía señala al final, y se cierra la próxima clase con
     * tokens; cuando cada petición traiga identidad, $this->user()
     * dejará de ser null también acá y la rama de arriba volverá a
     * ser la única que corre.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comercio_id' => [
                'required',
                $this->user()
                    ? Rule::exists('comercios', 'id')
                        ->where('user_id', $this->user()->id)
                    : Rule::exists('comercios', 'id'),
            ],
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Mensajes escritos a mano, para que el error explique qué hacer
     * en lugar de nombrar la regla que falló.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente_nombre.required' => 'Debes indicar el nombre del cliente.',
            'monto.required' => 'Debes indicar un monto.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.min' => 'El monto debe ser mayor a cero.',
            // El exists falla igual si el comercio no existe o si es
            // de otra persona, y el mensaje no distingue los dos
            // casos a propósito: no tiene por qué confirmar que el
            // comercio 7 existe y es de alguien más.
            'comercio_id.exists' => 'Ese comercio no está disponible en tu cuenta.',
        ];
    }

    /**
     * Nombres legibles para los mensajes que dejé por defecto. Con
     * esto el error dice "comercio" y no "comercio_id".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cliente_nombre' => 'nombre del cliente',
            'monto' => 'monto de la transacción',
            'comercio_id' => 'comercio',
        ];
    }
}
