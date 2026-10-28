<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Campos que create() tiene permitido escribir. La contraseña
     * entra acá porque el registro la manda, pero nunca llega en
     * texto plano a la tabla: el cast de abajo la cifra antes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Lo que queda fuera cuando el modelo se convierte a arreglo o a
     * JSON. Si mañana una ruta devuelve un User, el hash y el token
     * de "recordarme" no se escapan en la respuesta.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * El cast 'hashed' es la pieza clave del día: al asignar
     * password, Eloquent le aplica Hash::make() solo. Por eso el
     * controlador de registro no llama a Hash en ninguna línea, y
     * aun así la tabla jamás guarda la clave legible.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Un usuario es dueño de sus comercios.
     *
     * Este es el lado hasMany de la relación, y con él declarado
     * $user->comercios() ya trae el where user_id armado por
     * Eloquent. Eso es lo que permite que el listado filtre por el
     * usuario de la sesión sin que yo escriba la consulta, y sin
     * recibir el dueño desde el formulario.
     */
    public function comercios(): HasMany
    {
        return $this->hasMany(Comercio::class);
    }
}
