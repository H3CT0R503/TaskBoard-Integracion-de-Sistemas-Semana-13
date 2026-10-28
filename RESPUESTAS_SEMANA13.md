# Respuestas y bitacora — Semana 13

Guia Practica N.° 12: Servicios Web REST con Laravel — JSON y API Resources

Alumno: Hector Alonso Interiano Figueroa
Asignatura: Integracion de Sistemas — CE-ISC019
Ciclo: 02-2026

Datos sembrados por el seeder, que son los que usan las pruebas:

| Comercio | Dueño | Transacciones |
|---|---|---|
| 1 Cafe Amanecer | Ana | 1 Completada, 2 Iniciada |
| 2 Pupuseria Doña Marta | Ana | 3 Fallida |
| 3 Ferreteria El Tornillo | Carlos | — |
| 4 Libreria La Pagina | Carlos | 4 Iniciada |

---

## Laboratorio 2 — Herramienta de pruebas elegida

**Herramienta que elegi y por que: Postman**, con la coleccion guardada en el repositorio como `TaskBoard-API.postman_collection.json`.

La elegi por tres razones. La primera es que su formato de coleccion, el v2.1, es el estandar que casi todas las demas herramientas importan, asi que el mismo archivo se abre en Insomnia sin convertir nada y no quedo atado a un programa en particular. La segunda es que la coleccion es un archivo de texto que vive junto al codigo, se sube al repositorio y viaja con el proyecto, en vez de quedarse guardada en la nube de una cuenta personal donde el docente no la puede ver. Y la tercera es que permite escribir comprobaciones automaticas en cada peticion, asi que al pulsar Send no me quedo mirando el JSON a ojo para ver si esta bien, sino que la propia herramienta me dice en verde o en rojo si el codigo de estado y la forma de la respuesta son los que deben ser.

Tambien deje en el repositorio el archivo `pruebas-api.http`, que es la opcion A de la guia y funciona con la extension REST Client de VS Code, para que las pruebas se puedan repetir sin instalar nada aparte.

---

## Laboratorio 3 — Experimento: lo que sale al exterior

Para hacer el experimento de verdad volvi a exponer el endpoint con `Comercio::all()`, sin Resource, y mire la respuesta real antes de arreglarla. Esto es lo que entregaba:

```json
{
  "id": 1,
  "user_id": 1,
  "nombre_comercio": "Café Amanecer",
  "rubro": "Restaurante",
  "telefono": "2222-1234",
  "created_at": "2026-10-27T22:37:59.000000Z",
  "updated_at": "2026-10-27T22:37:59.000000Z"
}
```

Siete campos, que son exactamente las siete columnas de la tabla, y encima sin la envoltura `data`. Campo por campo:

| Campo en la respuesta | ¿El cliente lo necesita? | ¿Es interno? |
|---|---|---|
| `id` | Si. Es la referencia con la que pide el detalle o crea una transaccion | No, es parte legitima del contrato |
| `user_id` | No. Al cliente no le sirve de nada saber a que usuario pertenece el comercio | Si. Dice como esta organizada la base por dentro, y permite agrupar comercios por dueño desde afuera |
| `nombre_comercio` | Si, es el dato util | A medias. El valor hace falta, pero el nombre de la columna no tiene por que ser el nombre publico |
| `rubro` | Si | No |
| `telefono` | No para listar comercios. Es un dato de contacto, no de operacion | Si. Es informacion personal del comerciante, y salia publicada sin que nadie lo decidiera |
| `created_at` / `updated_at` | Casi nunca. Son de auditoria interna | Si. Dicen cuando se toco cada fila, y eso revela ritmo de operacion que nadie pidio |

El `telefono` fue el que no habia anticipado, y es el que mejor ilustra el punto de la guia: no es un campo sensible como una contrasena, es un dato comun que alguien agrego en su momento para el panel web, y termino publicado en la API sin que nadie tomara esa decision.

Con el Resource puesto, la misma peticion pasa de siete campos a tres. Los que dejaron de salir son `user_id`, `telefono`, `created_at`, `updated_at` y el nombre interno `nombre_comercio`, que ahora viaja como `nombre`.

**Pregunta de analisis: que datos de una pasarela de pagos nunca deberian salir en una respuesta publica.** Lo primero que nunca debe salir es cualquier cosa que sirva para hacerse pasar por alguien o para cobrar en su nombre, es decir el hash de la contrasena, los tokens de acceso, las llaves de API del comercio y los datos completos de una tarjeta, que ni siquiera deberian estar guardados enteros. Lo segundo son los datos personales que no hacen falta para la operacion que se esta pidiendo, como el documento de identidad del cliente, su direccion o su telefono, porque una respuesta que entrega de mas convierte cualquier filtracion menor en una fuga de datos personales. Y hay una tercera categoria que se pasa por alto, que son los datos internos del negocio: el numero de cuenta bancaria donde se deposita, la comision que la pasarela le cobra a ese comercio o el identificador del usuario dueño, porque aunque no sirvan para robar directamente, si sirven para que un competidor entienda como esta armado el negocio por dentro.

Dos ejemplos de campos que podria agregar a futuro y que saldrian solos si siguiera devolviendo el modelo directo: una columna `cuenta_bancaria` en comercios, para saber donde depositar lo cobrado, y una columna `comision_porcentaje`, para registrar cuanto cobra la pasarela por cada transaccion. Ninguna de las dos la agregaria pensando en la API, y justamente por eso aparecerian publicadas sin que nadie lo notara. Con el Resource de por medio, agregar la columna no cambia la respuesta: hay que ir al `toArray()` y escribirla a mano, que es una decision consciente.

---

## Laboratorio 5 — Pregunta de analisis: el monto como numero

El monto se devuelve como `25.5` y no como `"$25.50"` porque son dos cosas distintas: una es el dato y la otra es como se ve ese dato. La API entrega el dato, y el numero es la unica forma en que un programa lo puede usar sin trabajo extra, porque lo puede sumar, comparar o promediar de una vez. Si llegara como texto con simbolo, cada cliente tendria que quitarle el signo de dolar, quitarle las comas de los miles y convertirlo a numero antes de poder hacer cualquier cuenta, y ese trabajo se repetiria en cada aplicacion que consuma la API, con la posibilidad de equivocarse en cada una.

Hay ademas un problema de fondo peor que la molestia: el formato visual no es universal. En El Salvador se escribe `25.50` con punto decimal, pero en buena parte de Europa el mismo monto se escribe `25,50` con coma, y los separadores de miles se invierten igual. Si el servidor decide el formato, le impone a todo el mundo una convencion local, y una aplicacion que atienda a dos paises no tendria forma de mostrarlo bien en los dos. Lo mismo pasa con la moneda: el simbolo `$` no dice si son dolares o pesos.

**Quien deberia decidir el formato visual:** el cliente, es decir quien muestra el dato, porque es el unico que sabe quien lo va a leer. La aplicacion movil sabe en que idioma esta configurado el telefono, la pagina web sabe en que pais esta el visitante, y el reporte contable sabe que necesita dos decimales siempre. El servidor no sabe nada de eso, y adivinar seria elegir mal para alguien. La misma logica aplica a la fecha, que por eso sale en ISO 8601: un formato que no es el que nadie quiere leer, pero que cualquier lenguaje sabe interpretar y convertir a lo que su usuario si quiere leer.

---

## Laboratorio 8 — Experimento: ¿y si olvido el encabezado Accept?

La misma peticion invalida, enviada dos veces, con y sin el encabezado. Resultado real:

| Peticion | Codigo de estado | Forma de la respuesta |
|---|---|---|
| Con `Accept: application/json` | **422** | JSON (`application/json`) con un objeto `errors` que nombra `cliente_nombre` y `monto`, cada uno con su mensaje en español |
| Sin el encabezado `Accept` | **302** | HTML (`text/html`), una redireccion hacia `http://127.0.0.1:8013/`, sin ningun dato sobre lo que fallo |

**Pregunta de analisis: por que una redireccion es una respuesta inutil para un programa.** Porque una redireccion no es una respuesta, es una instruccion para que el navegador vaya a buscar la respuesta a otro lado, y ese mecanismo solo tiene sentido cuando del otro lado hay una persona mirando una pantalla. Cuando el servidor le contesta 302 a un navegador, lo que esta diciendo es "vuelve a la pagina anterior, donde el formulario te va a mostrar los errores que guarde en tu sesion", y eso funciona porque el navegador sigue la direccion solo y porque hay una sesion que recuerda los mensajes. Un programa que consume la API no tiene nada de eso: no tiene sesion, no tiene formulario al cual volver y no tiene a nadie que lea los mensajes en pantalla.

Lo que recibe entonces es un codigo que no le dice si la peticion funciono o no. El 302 no significa error, asi que un cliente mal escrito lo tomaria por exito y seguiria adelante creyendo que la transaccion se creo, cuando en realidad no se creo nada. Y si decide seguir la redireccion, lo que le llega es una pagina HTML pensada para ojos humanos, de la que tendria que extraer los errores leyendo etiquetas, que es justo lo que una API existe para evitar. Por eso el `Accept: application/json` no es un detalle de configuracion sino parte de la peticion: es la forma en que el cliente le avisa al servidor que del otro lado hay una maquina, y Laravel cambia toda su manera de responder en funcion de ese dato.

---

## Verificacion profesional completa — las 8 pruebas

Las ocho recorridas contra el servidor con la coleccion.

| # | Peticion | Resultado esperado | Obtenido |
|---|---|---|---|
| 1 | `GET /api/comercios` | 200 + lista en "data", sin user_id | OK. Los campos son exactamente `id`, `nombre` y `rubro`; sin `user_id`, sin `created_at` y sin la clave `transacciones` |
| 2 | `GET /api/comercios/1` | 200 + comercio con transacciones | OK. Aparece la clave `transacciones`, con el monto como numero (`45`, no `"45.00"`) y la fecha como `2026-10-27T22:28:05+00:00` |
| 3 | `GET /api/comercios/999` | 404 en JSON | OK. 404 con `Content-Type: application/json` |
| 4 | `GET /api/transacciones` | 200 + lista de transacciones | OK. Las 4 sembradas, dentro de "data" |
| 5 | POST con datos validos | 201 + objeto creado | OK. 201 y el objeto con su `id`, y el `estado` en "Iniciada" |
| 6 | POST con monto negativo | 422 + errors | OK. 422 con `errors.cliente_nombre` y `errors.monto`, cada uno con su mensaje |
| 7 | DELETE de la transaccion creada | 204 sin cuerpo | OK. 204 con 0 bytes de cuerpo, y un segundo DELETE al mismo id responde 404 |
| 8 | POST invalido sin `Accept` | 302, no JSON | OK. 302 hacia la raiz, en HTML, sin los errores por ningun lado |

Ademas probe el `PUT /api/transacciones/1`, que `apiResource` tambien declara: responde 200 con la transaccion actualizada.

---

## Reto — Filtrar las transacciones por estado

Solucion aplicada en `index()` del controlador de la API:

```php
$estado = $request->query('estado');

$transacciones = Transaccion::query()
    ->when($estado, fn ($q) => $q->where('estado', $estado))
    ->latest()
    ->get();
```

**Criterios de aceptacion, verificados:**

| Peticion | Resultado |
|---|---|
| `GET /api/transacciones` | 200, las 5 transacciones, con los tres estados presentes |
| `GET /api/transacciones?estado=Completada` | 200, 1 resultado, todos Completada |
| `GET /api/transacciones?estado=Iniciada` | 200, 3 resultados, todos Iniciada |
| `GET /api/transacciones?estado=Fallida` | 200, 1 resultado, todos Fallida |
| `GET /api/transacciones?estado=NoExiste` | 200 con `"data": []` |

**Por que un filtro sin resultados responde 200 y no 404.** Porque las dos respuestas contestan preguntas distintas y confundirlas le complica la vida al cliente. El 404 significa que la direccion pedida no corresponde a ningun recurso, es decir que el cliente se equivoco de puerta, y `/api/transacciones` si existe: el servidor la reconocio, entendio el filtro y ejecuto la consulta sin ningun problema. Todo salio bien, nada mas que la respuesta correcta a esa pregunta resulto ser una lista sin elementos, y una lista vacia es un resultado valido, no una falla.

La diferencia se nota al escribir el cliente. Con 200 y `"data": []`, el programa recorre la lista, no encuentra nada y muestra "no hay cobros completados", que es un caso normal. Con 404 estaria obligado a tratar ese caso por la rama de los errores, mezclando "no hay resultados" con "la direccion esta mal escrita" y con "el recurso se borro", que son tres situaciones que se resuelven de maneras completamente distintas. Dicho de otro modo, el codigo de estado describe que paso con la peticion, no cuantas filas trajo; lo segundo ya lo dice el cuerpo, contando los elementos.

**Autoevaluacion del reto.** Probe el filtro con un estado que tiene resultados y con uno que no, comprobe que el listado sin filtro sigue devolviendo todo igual que antes, y agregue las cuatro peticiones a la coleccion.

---

## Reflexion y cierre: una API todavia abierta

**1. Por que una API no usa la sesion del navegador para saber quien eres.** Porque la sesion es un invento que depende del navegador y del usuario que lo esta manejando. Funciona asi: el servidor guarda los datos de la sesion y le entrega al navegador una cookie con el identificador, y el navegador la devuelve sola en cada peticion siguiente, sin que el programador haga nada. Ese automatismo es comodo para una persona navegando, pero no sirve para un programa: un servicio que consume la API puede ser un script que corre de madrugada, una aplicacion movil o el servidor de otra empresa, y ninguno de ellos tiene un almacen de cookies ni razon para tenerlo. Ademas, una API se supone sin estado, es decir que cada peticion se entiende sola y trae todo lo que hace falta para atenderla, mientras que la sesion es exactamente lo contrario, porque obliga al servidor a recordar algo entre una peticion y la siguiente. Y hay una razon de seguridad encima: justamente porque el navegador manda la cookie sola es que existe el CSRF, y un esquema donde la credencial se manda a proposito en cada peticion no tiene ese problema.

**2. Que ocurriria si alguien descubre la direccion de mi API en una pasarela de pagos real.** Hoy, tal como quedo, seria grave, porque la API no pide nada para contestar. Cualquiera que escriba la direccion puede listar todos los comercios del sistema con sus rubros, ver todas las transacciones de todos los usuarios con el nombre del cliente y el monto de cada cobro, y eso ya es una fuga de datos sin que nadie toque un solo registro. Pero no se queda en leer: tambien puede crear transacciones a nombre de cualquier comercio y, sobre todo, puede borrarlas, y en una pasarela borrar transacciones es destruir la constancia de cobros que de verdad ocurrieron, con lo que un comercio se quedaria sin como demostrar que le pagaron. El aislamiento que se construyo en la Semana 12 no vale nada aca, porque protege la puerta web y esta es otra puerta. Y encontrarla no es dificil: las direcciones de API siguen convenciones conocidas, asi que basta con probar `/api/` detras del dominio.

**3. Que necesita llevar cada peticion para identificarse.** Necesita una credencial que el cliente mande a proposito en cada peticion, y lo habitual es un token en el encabezado `Authorization`, con la forma `Authorization: Bearer <token>`. La idea es que el usuario se identifique una vez con su correo y su contrasena, el servidor le entregue a cambio una cadena larga asociada a el, y de ahi en adelante cada peticion lleve esa cadena para que el servidor sepa quien pregunta sin necesidad de recordar nada. Tiene dos ventajas sobre la cookie: el cliente la manda porque decide mandarla, asi que el CSRF deja de aplicar, y el token se puede revocar o dejar vencer sin tocar la contrasena del usuario. Una vez que la peticion traiga identidad, `Auth::id()` volvera a funcionar en la API y se podra repetir ahi el mismo filtrado por dueño del CRUD web. Es el tema de la proxima clase, con Laravel Sanctum.

---

## Bitacora personal de aprendizaje

**Que diferencia note entre devolver el modelo directo y devolver un Resource.** La diferencia que mas me sorprendio es que el modelo directo no es una decision, es un descuido que funciona. Cuando escribi `Comercio::all()` y me salio el JSON completo, se veia bien y daba la sensacion de estar terminado, pero en realidad lo que estaba publicando era el esquema de mi tabla, y nunca eleji publicarlo: salio asi porque es lo que pasa cuando uno no elige. Con el Resource, en cambio, cada campo que aparece en la respuesta esta ahi porque alguien lo escribio a mano en el `toArray()`, y esa es la diferencia de fondo, que el contenido de la respuesta pasa de ser un efecto secundario de como quedo la base a ser una decision explicita.

Lo otro que me quedo claro es que el Resource desacopla dos cosas que parecian la misma. La columna se llama `nombre_comercio` y afuera sale como `nombre`; si mañana la columna cambia de nombre, se arregla una linea del Resource y ningun cliente se entera. Sin esa capa, el nombre de mis columnas seria parte del contrato publico, y entonces no podria renombrar nada nunca.

**Un caso real de pasarela de pagos donde esa diferencia evitaria un problema.** El caso tipico es el de la columna que se agrega meses despues pensando en otra cosa. Supongamos que para poder depositarle a cada comercio se agrega una columna `cuenta_bancaria` a la tabla `comercios`, en una tarea de back office que no tiene nada que ver con la API. Si el endpoint estuviera devolviendo el modelo directo, esa columna empezaria a salir publicada en `GET /api/comercios` en el mismo momento en que se corre la migracion, sin que nadie escriba una linea sobre la API, sin que aparezca en ninguna revision de codigo y sin que haya ningun error que lo delate, porque todo sigue funcionando. El numero de cuenta de todos los comercios afiliados quedaria accesible para cualquiera que supiera la direccion, y lo mas probable es que nadie lo notara hasta que el daño ya estuviera hecho.

Con el Resource de por medio no pasa nada: la migracion agrega la columna, la base la guarda, el sistema la usa internamente para depositar, y la respuesta de la API sigue devolviendo exactamente `id`, `nombre` y `rubro`, porque es lo unico que dice el `toArray()`. Para que esa cuenta bancaria llegara a salir, alguien tendria que abrir el archivo y escribirla, y en ese momento es imposible hacerlo por accidente.

---

## Checklist de entrega

- [x] `php artisan install:api` ejecutado; `routes/api.php` existe
- [x] `ComercioResource` y `TransaccionResource` creados, sin `user_id` en las respuestas
- [x] `Route::apiResource('transacciones', ...)` y `TransaccionApiController` con `index`, `show`, `store`, `update` y `destroy`
- [x] Coleccion de pruebas guardada: `TaskBoard-API.postman_collection.json` y `pruebas-api.http`
- [x] Las 8 pruebas de la tabla de verificacion completadas
- [x] Reto del filtro resuelto y probado

## Dos correcciones que hicieron falta durante las pruebas

Quedan anotadas porque no estaban en la guia y se encontraron probando, no leyendo.

**El parametro de la ruta.** `Route::apiResource('transacciones', ...)` genera el comodin singularizando en ingles, y dejaba las rutas como `/api/transacciones/{transaccione}`. Como el controlador recibe `Transaccion $transaccion`, los nombres no coincidian y el route model binding dejaba de resolver. Se arregla indicandole el nombre a mano:

```php
Route::apiResource('transacciones', TransaccionApiController::class)
    ->parameters(['transacciones' => 'transaccion']);
```

**El estado nulo en la respuesta del POST.** La primera version devolvia `"estado": null` en el 201, aunque la fila en la base quedaba correctamente en `Iniciada`. La causa es que ese valor lo pone el default de la migracion, y `create()` devuelve el modelo con los campos que se le pasaron, sin releer la fila. Se resuelve con un `$transaccion->refresh()` antes de armar la respuesta.
