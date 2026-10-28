# 🚀 Guías de Prácticas Laravel - TaskBoard: Pasarela de Pagos

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Postman](https://img.shields.io/badge/Postman-FF6C37?style=for-the-badge&logo=postman&logoColor=white)
![Git](https://img.shields.io/badge/Git-F05032?style=for-the-badge&logo=git&logoColor=white)
![GitHub](https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white)

---

<p align="center">
  <img src="https://res.cloudinary.com/bcwlyire/image/upload/v1785118882/NUEVO-LOGO-UPED-A-COLOR-scaled_szoqws.jpg" alt="Logo UPED" width="300"/>
</p>

## 🏛️ Información Académica

- **Institución:** Universidad Pedagógica de El Salvador *Luis Alonso Aparicio*
- **Facultad:** Ingeniería
- **Asignatura:** Integración de Sistemas
- **Ciclo:** 02-2026
- **Docente:** Ing. Oscar Contreras
- **Proyecto Integrador:** TaskBoard - Pasarela de Pagos

---

## 📌 Descripción del Proyecto

Este repositorio contiene el trabajo de la **Semana 13** del proyecto **TaskBoard**, una plataforma de **Pasarela de Pagos** desarrollada en el framework **Laravel 12**.

Hasta la Semana 12 TaskBoard atendía **personas con navegador** y respondía con vistas Blade. Esta semana se construye la **segunda puerta**, la que atiende a **otros programas**: endpoints que responden JSON, con la forma de la respuesta controlada por **API Resources** y una colección de pruebas que se puede repetir.

La guía es una sola, la **N.° 12 — Servicios Web REST con Laravel: JSON y API Resources**, y cubre:

1. Activar `routes/api.php` con `php artisan install:api` y crear el primer endpoint JSON.
2. Entender por qué **no se devuelve el modelo directo** y reemplazarlo por **API Resources**.
3. Exponer comercios y transacciones con `Route::apiResource()` y un controlador **sin vistas**.
4. Crear y eliminar por la API reutilizando el Form Request, respondiendo **201, 204, 404 y 422**.
5. El reto: **filtrar transacciones por estado** con un parámetro de consulta.

> ⚠️ **Esta API todavía está abierta.** Las rutas de `routes/api.php` no pasan por el login de la Semana 12, porque una API no usa sesión ni cookies. El aislamiento "cada usuario ve solo lo suyo" **no se cumple aquí**: se resuelve la próxima clase, con tokens de Laravel Sanctum. La guía lo plantea así a propósito y la reflexión está en las respuestas.

> 📄 Las respuestas a las preguntas de análisis, la tabla de verificación y la bitácora están en **[RESPUESTAS_SEMANA13.md](RESPUESTAS_SEMANA13.md)**.

---

## 📁 Estructura del Proyecto

Los archivos marcados son los que cambian esta semana. La parte web de la Semana 12 **no se toca**:

```text
taskboard/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── TransaccionApiController.php      # NUEVO  index, store, show, update, destroy
│   │   │   ├── AuthController.php                    # web, sin cambios
│   │   │   ├── ComercioController.php                # web, sin cambios
│   │   │   └── TransaccionController.php             # web, sin cambios
│   │   ├── Requests/
│   │   │   ├── RegistroRequest.php                   # sin cambios
│   │   │   └── GuardarTransaccionRequest.php         # MODIF. ahora también valida la API
│   │   └── Resources/
│   │       ├── ComercioResource.php                  # NUEVO  la forma pública de un comercio
│   │       └── TransaccionResource.php               # NUEVO  la forma pública de una transacción
│   └── Models/                                       # sin cambios
├── bootstrap/
│   └── app.php                                       # MODIF. lo tocó install:api (api: routes/api.php)
├── database/
│   └── migrations/
│       └── ..._create_personal_access_tokens_table.php  # NUEVO  la creó install:api (tokens, Semana 14)
├── routes/
│   ├── api.php                                       # NUEVO  endpoints JSON
│   └── web.php                                       # sin cambios
├── TaskBoard-API.postman_collection.json             # NUEVO  colección de pruebas (Postman / Insomnia)
├── pruebas-api.http                                  # NUEVO  las mismas pruebas para REST Client
├── RESPUESTAS_SEMANA13.md                            # NUEVO  respuestas, verificación y bitácora
└── README.md
```

---

## ⚙️ Puesta en marcha

El proyecto corre sobre **XAMPP** (Apache + MySQL) con la base de datos `taskboard_13`.

### 1. Crear la base de datos

```sql
CREATE DATABASE taskboard_13 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Preparar el entorno

```bash
composer install
cp .env.example .env
php artisan key:generate
```

| Variable | Valor |
|---|---|
| `DB_DATABASE` | `taskboard_13` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | *(vacío, usuario por defecto de XAMPP)* |
| `SESSION_DRIVER` | `database` |
| `SESSION_COOKIE` | `taskboard_session` |

### 3. Crear las tablas y los datos de ejemplo

```bash
php artisan migrate:fresh --seed
php artisan serve
```

### 4. Usuarios y datos de prueba

Los usuarios son los de la Semana 12 y siguen sirviendo para la parte web. La API, por ahora, no los necesita.

| Usuario | Correo | Contraseña | Comercios |
|---|---|---|---|
| Ana López | `ana@taskboard.test` | `password123` | 1 Café Amanecer, 2 Pupusería Doña Marta |
| Carlos Pérez | `carlos@taskboard.test` | `password123` | 3 Ferretería El Tornillo, 4 Librería La Página |

---

## 🔌 Endpoints de la API

Todas las direcciones llevan el prefijo `/api`, y **todas las peticiones deben enviar `Accept: application/json`**.

| Verbo | URI | Acción | Respuesta |
|---|---|---|---|
| GET | `/api/comercios` | Listado de comercios | 200 + `data`, **sin** `transacciones` |
| GET | `/api/comercios/{id}` | Un comercio | 200 + `data` **con** `transacciones`; 404 si no existe |
| GET | `/api/transacciones` | Listado de transacciones | 200 + `data` |
| GET | `/api/transacciones?estado=Completada` | Listado filtrado *(reto)* | 200 + `data`, vacío si no hay coincidencias |
| GET | `/api/transacciones/{id}` | Una transacción | 200 + `data`; 404 si no existe |
| POST | `/api/transacciones` | Crear | **201** + el objeto creado; **422** si la validación falla |
| PUT/PATCH | `/api/transacciones/{id}` | Actualizar | 200 + el objeto actualizado |
| DELETE | `/api/transacciones/{id}` | Eliminar | **204** sin cuerpo; 404 al repetirlo |

`apiResource` declara **5 rutas y no 7**: deja fuera `create` y `edit`, que en el CRUD web servían para *mostrar formularios*. Una API no tiene formularios, así que esas direcciones no tendrían nada que devolver.

### Ejemplo de respuesta

`GET /api/comercios/1`

```json
{
  "data": {
    "id": 1,
    "nombre": "Café Amanecer",
    "rubro": "Restaurante",
    "transacciones": [
      {
        "id": 1,
        "cliente": "María López",
        "monto": 45,
        "estado": "Completada",
        "fecha": "2026-10-27T22:28:05+00:00"
      }
    ]
  }
}
```

---

## 🧪 Cómo probar la API

En el repositorio van **las dos colecciones**, con las mismas peticiones:

### Opción A — Postman (o Insomnia)

`TaskBoard-API.postman_collection.json`, en formato **Postman v2.1**, que **Insomnia también importa**.

- **Postman:** *Import* → arrastrar el archivo.
- **Insomnia:** *Import* → *From File*.

Cada petición trae **pruebas automáticas** en la pestaña *Tests*: al pulsar **Send**, la herramienta verifica sola el código de estado y la forma de la respuesta, y el resultado sale en *Test Results*. Por ejemplo, la petición 1 comprueba que no salga `user_id`, la 2 que el monto sea número y no texto, y la 12 que un filtro sin coincidencias devuelva `200` con `data: []`.

> Para la petición 10 (el experimento del `Accept`) hay que **apagar** *Settings → Automatically follow redirects*; si está encendida, Postman sigue la redirección y no se ve el `302`.

### Opción B — REST Client en VS Code

`pruebas-api.http`. Instalar la extensión **REST Client** (Huachao Mao), abrir el archivo y pulsar *Send Request* sobre la línea del método.

---

## 🧠 Decisiones de diseño

### El Resource es el contrato

Devolver `Comercio::all()` funcionaba, pero publicaba el esquema de la tabla: agregar una columna la exponía sin que nadie lo decidiera, y renombrarla rompía a todos los clientes. El Resource corta esa dependencia.

```php
return [
    'id' => $this->id,
    'nombre' => $this->nombre_comercio,   // la columna puede cambiar de nombre
    'rubro' => $this->rubro,
    // user_id, created_at y updated_at NO salen
];
```

### `whenLoaded()` evita el problema N+1

La clave `transacciones` aparece **solo** si la relación venía cargada:

```php
'transacciones' => TransaccionResource::collection(
    $this->whenLoaded('transacciones')
),
```

En el listado no se carga, así que la clave ni se asoma y no se dispara una consulta por cada comercio. En el detalle sí, porque ahí se pide con `load('transacciones')`.

### El monto sale como número y la fecha en ISO 8601

```php
'monto' => (float) $this->monto,                  // 25.5, no "$25.50"
'fecha' => $this->created_at->toIso8601String(),  // 2026-10-27T22:28:05+00:00
```

La API entrega el **dato**; el formato visual lo decide quien lo muestra, que es el único que sabe en qué país e idioma está el lector.

### Un Form Request que ahora sirve para las dos puertas

La regla de `comercio_id` de la Semana 12 usaba `$this->user()->id`. En la API **no hay sesión**, así que `$this->user()` es `null` y eso reventaba con un 500 antes de validar nada:

```php
'comercio_id' => [
    'required',
    $this->user()
        ? Rule::exists('comercios', 'id')->where('user_id', $this->user()->id)
        : Rule::exists('comercios', 'id'),
],
```

Sin usuario la regla cae al `exists` simple, que es todo lo que se puede comprobar cuando no se sabe quién pregunta. **No es equivalente**: por la API, hoy, cualquiera puede colgarle una transacción al comercio de otro. Es el agujero que la guía señala y que se cierra con tokens la próxima clase.

---

## ✅ Verificación

Las 8 pruebas de la tabla de la guía, más el reto, se recorrieron contra el servidor. El detalle está en [RESPUESTAS_SEMANA13.md](RESPUESTAS_SEMANA13.md).

Las **13 peticiones de la colección** se ejecutaron una por una contra la API y todas devolvieron el código esperado:

```text
OK  1. Listar comercios                        200
OK  2. Ver un comercio con sus transacciones   200
OK  3. Comercio que no existe                  404
OK  4. Listar transacciones                    200
OK  5. Ver una transaccion                     200
OK  6. Transaccion que no existe               404
OK  7. Crear una transaccion con datos validos 201
OK  8. Crear con datos invalidos               422
OK  9. Eliminar la transaccion creada          204
OK  11. RETO: filtro con resultados            200
OK  12. RETO: filtro sin coincidencias         200
OK  13. RETO: sin filtro sigue funcionando     200

Peticiones: 13 | Fallos: 0
```

### Dos correcciones que hicieron falta

**El parámetro de la ruta.** `apiResource` singulariza en inglés y dejaba las rutas como `/api/transacciones/{transaccione}`, que no coincide con el `$transaccion` del controlador, así que el route model binding dejaba de resolver:

```php
Route::apiResource('transacciones', TransaccionApiController::class)
    ->parameters(['transacciones' => 'transaccion']);
```

**El estado nulo en el 201.** El POST devolvía `"estado": null` aunque la fila quedaba correcta en la base, porque ese valor lo pone el *default* de la migración y `create()` no relee la fila. Se resuelve con `$transaccion->refresh()` antes de armar la respuesta.
