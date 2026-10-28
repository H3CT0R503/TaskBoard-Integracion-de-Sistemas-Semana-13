<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * La forma pública de una transacción.
 *
 * Igual que el de comercios: traduce los nombres internos a los
 * públicos y deja fuera lo que el cliente no necesita.
 */
class TransaccionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente' => $this->cliente_nombre,

            // El cast decimal:2 del modelo devuelve el monto como
            // cadena ("25.50"), y así saldría al JSON entre comillas.
            // El (float) lo entrega como número, que es lo que un
            // programa espera para poder sumarlo sin convertirlo.
            // El formato con símbolo y separadores es decisión de
            // quien muestra el dato, no de quien lo entrega.
            'monto' => (float) $this->monto,

            'estado' => $this->estado,

            // ISO 8601 es el formato que cualquier lenguaje sabe leer
            // y que lleva la zona horaria dentro. Mandar "27/10/2026"
            // obligaría a cada cliente a adivinar si el primer número
            // es el día o el mes.
            'fecha' => $this->created_at->toIso8601String(),
        ];
    }
}
