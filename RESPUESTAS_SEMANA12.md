# Respuestas y bitacora — Semana 12

Guia N.° 10 (jueves): Cookies, Sesiones y Autenticacion de Usuarios
Guia N.° 11 (viernes): Aislamiento por Usuario y Preparacion del Laboratorio II

Alumno: Hector Alonso Interiano Figueroa
Asignatura: Integracion de Sistemas — CE-ISC019
Ciclo: 02-2026

Datos de prueba sembrados por el seeder, clave `password123` para los dos:

| Usuario | Correo | Comercios | Transacciones |
|---|---|---|---|
| Ana Lopez | `ana@taskboard.test` | 1 Cafe Amanecer, 2 Pupuseria Dona Marta | 1, 2 y 3 |
| Carlos Perez | `carlos@taskboard.test` | 3 Ferreteria El Tornillo, 4 Libreria La Pagina | 4 |

---

## GUIA DEL JUEVES

### Laboratorio 1 — Observa tu primera cookie de sesion

El servidor entrega dos cookies en la primera visita, y estos son los valores reales que devuelve la cabecera `Set-Cookie` del proyecto.

| Atributo | Que hace | Lo que observe |
|---|---|---|
| HttpOnly | JavaScript no puede leer la cookie | `taskboard_session` si lo trae, `XSRF-TOKEN` no. La de sesion es la que identifica, asi que se le cierra la puerta a los scripts, mientras que la del token CSRF tiene que quedar legible porque el navegador la necesita para firmar peticiones por JavaScript |
| SameSite | Limita el envio en peticiones desde otros sitios | `samesite=lax` en las dos. Con lax la cookie viaja cuando uno llega al sitio siguiendo un enlace, pero no acompana a un POST que arme otra pagina, que es justo el camino de un ataque CSRF |
| Expires / Max-Age | Define cuando caduca la cookie | `Max-Age=7200`, dos horas, que es el `SESSION_LIFETIME=120` del `.env` convertido a segundos |

Las dos llevan ademas `path=/`, asi que valen para todo el sitio y no solo para una carpeta.

**Pregunta de analisis.** El valor de `taskboard_session` es una cadena larga e ilegible porque no guarda datos, guarda nada mas un identificador cifrado que el servidor usa de llave para buscar la sesion verdadera en su propio almacen, que en este proyecto es la tabla `sessions`. Si ahi viajara mi nombre o mi correo, cualquiera que leyera la cookie, por ejemplo desde una red compartida o desde una maquina prestada, tendria mis datos sin necesidad de entrar al sistema, y peor todavia, bastaria con editar ese texto para decir que soy otra persona, porque el servidor estaria creyendole al navegador en vez de creerle a sus propios registros. Lo que si vive del lado del servidor, asociado a ese identificador, es el id del usuario autenticado, el token CSRF de la sesion, la URL que yo pedia antes de que me mandaran al login y los mensajes flash de un solo request. Por eso cerrar sesion no es borrar la cookie, es destruir esa fila en el servidor.

**Comprobacion.** Borre la cookie y recargue: el servidor entrego una nueva, con otro valor, y la sesion anterior quedo huerfana del lado del servidor hasta que caduque.

### Laboratorio 3 — Experimento: la contrasena nunca se guarda

Salida real de `php artisan tinker`:

```
$h = Hash::make('secreto123');   // "$2y$12$AT6YgKc5R5NyI..."
Hash::check('secreto123', $h);   // true
Hash::check('otra', $h);         // false
Hash::make('secreto123') === $h; // false
```

**Por que dos hashes de la misma clave son distintos y aun asi `Hash::check()` funciona.** Porque bcrypt no cifra la contrasena sola, le agrega antes una cadena aleatoria distinta en cada llamada, que se llama sal, y despues guarda esa misma sal dentro del texto del hash junto con el costo y el resultado. Por eso dos hashes de `secreto123` nunca se parecen, aunque la clave sea la misma. Cuando llega el momento de comprobar, `Hash::check()` no vuelve a cifrar a ciegas para comparar cadenas, lo que hace es leer la sal y el costo que venian guardados en el hash almacenado, repetir el calculo con esos mismos parametros sobre la clave que acaban de escribir, y ver si el resultado coincide. La sal existe justamente para que dos personas con la misma contrasena no compartan el mismo hash, porque si lo compartieran, un atacante que rompiera uno solo tendria resueltos todos los demas de un golpe, y ademas podria usar tablas ya calculadas en vez de atacar cuenta por cuenta.

**Regla de oro verificada.** `User::first()->password` devuelve `$2y$12$jQH/WqsgsOxLu...`, nunca la clave original. Un hash no se puede deshacer, por eso ningun sistema serio ofrece recuperar la contrasena, solo crear una nueva.

### Laboratorio 6 — Pregunta de analisis: por que el logout debe ser POST

Porque un GET lo puede disparar cualquier pagina sin que yo haga nada. Si `/logout` respondiera a un GET, bastaria con que otro sitio pusiera una imagen oculta con `<img src="http://taskboard.test/logout">` para que mi navegador pidiera esa direccion solo con abrir esa pagina, y como el navegador manda mis cookies de sesion en cada peticion al dominio, el servidor veria una solicitud perfectamente valida mia y me sacaria de la sesion sin que yo lo pidiera. Eso es CSRF, y aunque cerrar sesion a la fuerza no roba nada, si es una molestia que un tercero decida por mi, y la misma puerta abierta sirve para cosas mas graves si otras acciones siguen el mismo patron. Con POST el ataque ya no es gratis, porque el formulario exige el token de `@csrf`, que es un valor atado a mi sesion que la pagina ajena no puede leer ni adivinar. Hay una segunda razon, mas de diseno que de seguridad: los GET deben ser peticiones que no cambian nada, y cerrar sesion cambia el estado del servidor, asi que por definicion no le corresponde ese verbo.

### Verificacion profesional completa — las 8 pruebas

Probadas una por una contra el servidor, con dos sesiones independientes.

| # | Accion | Resultado esperado | Obtenido |
|---|---|---|---|
| 1 | Registro con datos validos | Crea el usuario, abre sesion y muestra "Bienvenido...!" | OK. Redirige 302 a `/comercios`, con el mensaje "Bienvenido, Hector Alonso Interiano Figueroa!" y la barra mostrando "Hola, Hector Alonso Interiano Figueroa" con el boton Salir |
| 2 | Registro con un correo ya usado | Error de unicidad, conserva nombre y correo | OK. "Ese correo ya esta registrado." y los dos campos vuelven llenos con `old()` |
| 3 | Contrasena de 5 caracteres | Error de longitud minima | OK. "La contrasena debe tener al menos 8 caracteres." |
| 4 | Confirmacion distinta | Error de confirmacion | OK. "Las contrasenas no coinciden." |
| 5 | Login con clave incorrecta | "Las credenciales no coinciden." | OK. Conserva el correo con `onlyInput('email')` y deja la clave vacia |
| 6 | Login correcto tras pedir una ruta protegida | Redirige a la pagina que pedia | OK. Pedi `/comercios/2` sin sesion y despues del login `intended()` me llevo a `/comercios/2`, no al listado |
| 7 | Abrir `/comercios` sin sesion | Redirige a `/login` | OK. 302 a `http://127.0.0.1:8012/login` |
| 8 | Pulsar Salir y volver atras | La sesion esta destruida, `/comercios` redirige a `/login` | OK. El logout devuelve 302 a `/login` y `/comercios` vuelve a rebotar |

### Reto y cierre del dia — las tres preguntas

**1. Que viaja en la cookie de sesion y que se queda en el servidor.** En la cookie viaja un identificador cifrado y nada mas, un valor que por si solo no dice nada de mi. Lo que se queda del lado del servidor, en la tabla `sessions`, es el contenido real de la sesion: el id del usuario autenticado, el token CSRF, la URL que yo intentaba abrir antes de que el middleware me mandara al login y los mensajes flash que duran un solo request. La cookie es la llave, los datos son lo que esta detras de la puerta, y por eso cambiar la llave no cambia lo que hay adentro.

**2. Por que se llama `regenerate()` justo despues de iniciar sesion.** Para evitar la fijacion de sesion. Antes de entrar yo ya tenia una sesion anonima con su identificador, y si ese mismo identificador siguiera valiendo despues del login, entonces quien hubiera conseguido ese valor antes, por ejemplo plantandomelo en el navegador desde un enlace preparado, terminaria con una sesion que de pronto esta autenticada como yo sin haber escrito nunca mi contrasena. Llamarlo justo despues de `Auth::login()` o de `Auth::attempt()` corta ese hilo, porque el identificador viejo deja de servir en el momento exacto en que la sesion pasa de anonima a identificada, que es el unico instante en que el cambio importa.

**3. Diferencia entre los middleware `auth` y `guest`.** Son guardias opuestos y cada uno le cierra la puerta a un grupo distinto. `auth` deja pasar solo a quien ya tiene sesion abierta y rechaza al visitante anonimo, mandandolo a `route('login')` y guardando de paso la URL que pedia para devolverlo ahi despues; es el que protege comercios, transacciones y el logout. `guest` hace lo contrario, deja pasar solo a quien no tiene sesion y rechaza a quien ya entro, devolviendolo al listado; es el que cuida el registro y el login, para que alguien ya autenticado no vuelva al formulario y abra una segunda sesion encima de la primera. Dicho corto, `auth` cuida lo de adentro y `guest` cuida la puerta de entrada.

### Bitacora del jueves

Lo mas delicado de proteger me parecio el momento del login, no el almacenamiento de la contrasena. El hash ya viene resuelto por el cast `hashed` del modelo y uno casi no lo toca, pero el instante en que la sesion cambia de anonima a identificada depende de que yo me acuerde de llamar a `regenerate()`, y si se me olvida nada falla en pantalla, todo sigue funcionando igual de bien, solo que queda abierta una puerta que no se ve. Esa es la clase de error que mas miedo da, el que no produce ningun sintoma.

Para que un atacante usara la sesion de otra persona tendria que conseguir el valor de la cookie `taskboard_session`, y los caminos realistas son robarla con un XSS si el sitio dejara inyectar scripts, interceptarla en transito si la conexion viajara sin HTTPS, leerla de una maquina compartida que quedo con la sesion abierta, o fijarla antes del login si el identificador no se regenerara. Contra los dos primeros ayudan `HttpOnly` y servir el sitio por HTTPS con `SameSite`, contra el tercero el cierre de sesion y la caducidad, y contra el cuarto exactamente el `regenerate()` de esta guia.

---

## GUIA DEL VIERNES

### Laboratorio 1 — Experimento: ver lo que no es mio

Prueba hecha con el proyecto tal como quedo el jueves, es decir con sesion pero todavia sin aislamiento, entrando como Ana y escribiendo a mano direcciones de Carlos.

| Direccion probada | Que se mostro | Deberia mostrarse |
|---|---|---|
| `/comercios` | Los comercios de todos, los de Ana y los de Carlos mezclados | No. Solo los de Ana |
| `/comercios/3` | El panel completo de Ferreteria El Tornillo, que es de Carlos, con todas sus transacciones | No. Ana no tiene por que ver ese comercio |
| `/transacciones/4/editar` | El formulario de edicion cargado con los datos de una transaccion de Carlos, lista para guardar cambios | No. Ni verla ni mucho menos modificarla |

**Pregunta de analisis: por que el middleware `auth` no resuelve esto.** Porque `auth` responde una pregunta distinta de la que aca hace falta. `auth` responde "quien eres", es decir comprueba que haya una sesion abierta y que detras de ella exista un usuario de verdad, y con eso basta para cerrarle la puerta a un desconocido. Lo que no responde es "que es tuyo", que es una pregunta sobre cada dato en particular y no sobre la persona. Ana esta perfectamente autenticada cuando abre `/comercios/3`, su sesion es legitima y su cookie es valida, asi que el guardia la deja pasar sin objetar nada, porque su trabajo termino en el momento en que confirmo que no es un anonimo. La pregunta que falta es la de autorizacion, y esa hay que contestarla fila por fila, comparando el `user_id` del recurso contra el `Auth::id()` de la sesion, que es justo lo que hacen el filtro del `index()` y los `abort_unless` de esta guia. Autenticacion y autorizacion son dos capas, y saltarse la segunda deja el sistema con llave en la puerta pero con todas las gavetas abiertas.

### Laboratorio 5 — Pregunta de analisis: filtrar el select no basta

Si solo filtro el `<select>` del formulario y dejo la regla `exists:comercios,id` como estaba, lo unico que logro es que la lista se vea limpia, pero no impido nada. Con las herramientas de desarrollo abiertas, cualquiera edita el HTML de la pagina, cambia el `value` de una opcion por el id de un comercio ajeno o agrega una opcion nueva a mano, y envia el formulario con total normalidad; ni siquiera hace falta tanto, porque la peticion se puede armar directamente desde la consola o desde curl sin pasar por el formulario, mandando el `comercio_id` que a uno se le antoje. El punto de fondo es que el navegador es territorio del atacante y todo lo que ocurre ahi es una sugerencia, no una garantia: el HTML que yo envio es apenas lo que propongo que pase, y el usuario puede responder lo que quiera. La validacion del servidor hace falta porque es el unico lugar que el atacante no controla, y por eso la regla quedo como `Rule::exists('comercios','id')->where('user_id', $this->user()->id)`, que obliga a que el comercio exista y ademas sea mio. Filtrar el select sigue valiendo la pena, pero como comodidad para el usuario honesto, no como medida de seguridad.

### Laboratorio 6 — Matriz de pruebas con dos sesiones

Ana en una ventana normal, Carlos en una de incognito. Cada peticion se armo a mano para probar lo que la interfaz ya no ofrece.

| # | Quien | Accion | Resultado esperado | Obtenido |
|---|---|---|---|---|
| 1 | Ana | Entrar y abrir `/comercios` | Solo sus comercios | OK. Cafe Amanecer y Pupuseria Dona Marta, nada de Carlos |
| 2 | Ana | Abrir `/comercios/3` y `/comercios/4`, de Carlos | 404 | OK. 404 en los dos, y 200 en `/comercios/1`, que si es suyo |
| 3 | Ana | Abrir `/transacciones/4/editar`, de Carlos | 404 | OK. 404, y 200 en `/transacciones/2/editar`, que si es suya |
| 4 | Ana | Eliminar una transaccion de Carlos con un DELETE a mano | 404, no se elimina | OK. 404, y la transaccion 4 sigue en la tabla |
| 5 | Ana | Mover el estado de una transaccion de Carlos con un PATCH a mano | 404, no cambia | OK. 404, y la transaccion 4 sigue en Iniciada |
| 6 | Carlos | Crear una transaccion en un comercio propio | Se crea con exito | OK. 302 al panel de Libreria La Pagina, con la transaccion registrada |
| 7 | Carlos | Entrar y abrir `/comercios` | Solo sus comercios | OK. Ferreteria El Tornillo y Libreria La Pagina |
| 8 | Sin sesion | Abrir `/comercios` y `/comercios/1` | Redirige a `/login` | OK. 302 a `/login` en los dos casos |

Pruebas extra que agregue por mi cuenta:

| Quien | Accion | Obtenido |
|---|---|---|
| Ana | POST a `/transacciones` forzando `comercio_id=4`, de Carlos | Rebota con el error de validacion "Ese comercio no esta disponible en tu cuenta." y no inserta nada |
| Ana | Abrir `/comercios/4/transacciones/nueva`, de Carlos | 404 antes de mostrar el formulario, asi no se filtra ni el nombre del comercio |

### Laboratorio 7 — Depuracion sin interfaz con tinker

Salida real:

```
Auth::attempt(['email' => 'ana@taskboard.test', 'password' => 'password123']);  // true
Auth::attempt(['email' => 'ana@taskboard.test', 'password' => 'incorrecta']);   // false
User::where('email','ana@taskboard.test')->first()->comercios->count();         // 2
Comercio::where('user_id', 2)->count();                                         // 2
Hash::check('password123', User::first()->password);                            // true
Comercio::find(1)->usuario->name;                                               // "Ana Lopez"
```

**Un caso donde tinker me habria ahorrado tiempo.** El dia que el login me fallaba siempre, aun escribiendo bien la contrasena. Estuve un buen rato revisando el formulario, el `name` de los campos y la ruta del POST, convencido de que el problema estaba en la pantalla, cuando un `Hash::check('password123', User::first()->password)` en tinker me habria dicho en un segundo que el hash guardado no correspondia a esa clave, y de ahi habria ido directo al verdadero culpable, que era un usuario creado antes de que el cast `hashed` estuviera puesto, con la clave en texto plano. La leccion es que cuando algo falla conviene preguntarle primero a la capa mas baja, porque la interfaz tiene demasiadas piezas en medio y cada una se ve sospechosa.

### Laboratorio 8 — Reto: limitar los intentos de login

Solucion aplicada en `routes/web.php`:

```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.attempt');
```

**Criterios de aceptacion, verificados:**

1. Tras 5 intentos fallidos en un minuto, el sexto devuelve 429. Verificado partiendo del limitador en cero: los intentos 1 al 5 responden 302 con el mensaje de credenciales, y el intento 6 responde **429 Too Many Requests**.
2. Un login correcto dentro del limite sigue funcionando. Verificado: con el contador en cero, el login correcto responde 302 hacia `/comercios`. Conviene notar que el contador suma envios, no fracasos, asi que un login correcto tambien gasta uno de los cinco.
3. Pasado el minuto se puede volver a intentar. Verificado: tras expirar la ventana, el mismo login que antes recibia 429 vuelve a pasar normalmente.

**Desventaja de limitar por direccion IP en una red compartida.** El problema es que la IP no identifica a una persona, identifica a una salida a internet, y en un aula, una oficina o cualquier lugar detras de un NAT todos los equipos salen con la misma. Entonces el limite deja de ser personal y pasa a ser colectivo: cinco intentos fallidos de un companero distraido que no se acuerda de su clave agotan la cuota de todo el salon, y los demas se encuentran con un 429 sin haber escrito nada. Eso es una negacion de servicio que ni siquiera hace falta provocar con mala intencion, basta con que varios se equivoquen al mismo tiempo. Y el problema tiene su reverso, porque un atacante con varias IP, que hoy se consiguen baratas, reparte sus intentos entre todas y el limite por IP casi no lo roza.

**Como se podria mejorar.** Lo razonable es contar por la combinacion de correo mas IP, que es justo lo que hace el `RateLimiter` de Laravel cuando uno define el limitador a mano, porque asi los errores de un companero no afectan mi cuenta y un ataque contra mi correo sigue frenado aunque cambie de IP. Encima de eso ayudan medidas que no dependen de la red: un retraso que crece con cada fallo, de modo que probar miles de claves se vuelva lento aunque nunca se bloquee del todo, un captcha a partir del tercer intento, un aviso por correo al dueno de la cuenta cuando hay una racha de fallos, y la autenticacion de dos factores, que es la unica que mantiene la cuenta cerrada aun cuando la contrasena ya se filtro.

**Autoevaluacion del reto.** Provoque el 429 con 6 intentos fallidos seguidos, y comprobe que un login correcto funciona antes de agotar el limite y tambien despues de que la ventana expira.

### Bitacora del viernes

La prueba que mas me sorprendio fue la numero 4, eliminar una transaccion de Carlos mandando el DELETE a mano. Antes del cambio yo daba por hecho que el sistema estaba protegido porque en la pantalla de Ana ese boton sencillamente no existe, y armar la peticion por fuera me dejo claro que la interfaz no estaba protegiendo nada, solo estaba escondiendo. El dato se borraba igual, con la misma facilidad, nada mas que por un camino que yo no habia pensado en mirar.

**Por que una pasarela de pagos exige verificar la propiedad en el servidor aunque la interfaz ya oculte los datos ajenos.** Porque esconder no es impedir, y la interfaz es apenas lo que yo le propongo al usuario, no un limite que el deba respetar. El HTML, el JavaScript y los botones viven en la maquina de la otra persona, que puede editarlos, ignorarlos o saltarselos por completo armando la peticion con curl o desde la consola del navegador. Lo unico que de verdad decide es el codigo que corre en el servidor, porque es el unico que el usuario no controla. Un ejemplo concreto: si el panel de Ana ocultara los comercios de Carlos pero `show()` no comparara el `user_id` contra `Auth::id()`, bastaria con cambiar un numero en la URL para abrir el panel completo de Carlos, con el nombre de cada cliente y el monto de cada cobro, y eso ya es una fuga de datos aunque nadie modifique nada. Y si ademas faltara la revision en `destroy()` o en `moverEstado()`, un PATCH escrito a mano podria marcar como Completada una transaccion ajena, es decir dar por cobrado dinero que nunca se movio, que en una pasarela de pagos no es un detalle de interfaz sino contabilidad falsa.

---

## Checklist de entrega

**Jueves**

- [x] Tabla `users` y modelo `User` con el cast `hashed`, `php artisan migrate` ejecutado
- [x] `RegistroRequest.php` y `AuthController.php` con registro, login y logout
- [x] Vistas `auth/registro` y `auth/login` con `@csrf`, `@error` y `old()`
- [x] `routes/web.php` con los grupos `guest` y `auth`
- [x] `layouts/app.blade.php` con la barra superior `@auth` / `@guest`
- [x] Las 8 pruebas de la tabla de verificacion completadas

**Viernes**

- [x] Migracion `user_id` aplicada, relaciones `comercios()` y `usuario()` declaradas
- [x] `ComercioController`: `index()` filtrado y `show()` con `abort_unless` 404
- [x] `TransaccionController`: `autorizar()` en `edit`, `update`, `destroy` y `moverEstado`, y `create`/`store` limitados a comercios propios
- [x] Seeder con Ana y Carlos, matriz de pruebas completada
- [x] Reto resuelto: `throttle:5,1` en el login

---

## Guion para la defensa del Laboratorio II

Antes de empezar: `php artisan migrate:fresh --seed`, una ventana normal y otra de incognito.

1. **Registro y login.** Crear una cuenta nueva desde `/registro` y mostrar que entra sola a `/comercios` con la barra ya personalizada. De paso, ensenar en tinker que `User::first()->password` es un hash y no la clave.
2. **CRUD de transacciones.** Crear una transaccion en un comercio propio, editarla, y mostrarla en el panel con su badge de estado.
3. **Transicion invalida.** Intentar mover una transaccion Completada y explicar que el mapa de transiciones del controlador lo bloquea porque ese dinero ya se movio, y que la vista esconde el boton pero el que decide es el servidor.
4. **Aislamiento.** Con la sesion de Carlos en incognito, anotar el id de uno de sus comercios, volver a la ventana de Ana y escribir esa URL a mano para mostrar el 404. Repetir con `/transacciones/{id}/editar`.
5. **Decision de seguridad.** Explicar por que se eligio 404 y no 403: un 403 confirmaria que ese comercio existe y es de alguien mas, mientras que el 404 responde lo mismo que responderia un id inventado, asi que la URL no sirve ni para averiguar que hay del otro lado.

Como cierre, mostrar el `throttle:5,1` provocando el 429 con seis envios seguidos.
