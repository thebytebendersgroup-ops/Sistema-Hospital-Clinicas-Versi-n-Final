# SIGSM — Sistema Informático de Gestión de Servicios Médicos

Módulo **Documentación y Encuestas** del Hospital de Clínicas: el equipo de salud carga material
informativo para los pacientes (instructivos, folletos, indicaciones) organizado por servicio, y arma
encuestas de satisfacción. Los pacientes lo consultan desde el celular escaneando **un código QR**.

Hecho con **PHP + MySQL + HTML/CSS/JavaScript (Bootstrap 5)**, para correr en **XAMPP**.
Todos los datos del sistema son **ficticios**.

---

## 1. Cómo funciona

```
Funcionario                                  Paciente
-----------                                  --------
1. Inicia sesión (login)                     1. Escanea el QR del hospital
2. Entra al panel                            2. Entra al portal (sin usuario ni contraseña)
3. Carga documentos por servicio             3. Busca / filtra y abre documentos publicados
4. Crea categorías de encuestas y encuestas    4. Ve las encuestas, filtra por categoría,
   (constructor) y las publica                   busca y responde una (anónima)                                  5. No puede modificar nada
5. Mira resultados, gráficos y exporta CSV
6. Muestra o imprime el QR (un solo QR)
```

- La página de inicio del sistema solo tiene el **login del funcionario**.
- El **QR es uno solo**, igual para todos: lleva a `presentacion/portal-paciente.php`.
  Está en el panel, opción **Código QR** (se imprime o se descarga).

### Roles

| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Todo lo de médico/enfermería, más: **usuarios** (alta, edición, baja lógica), **servicios**, **eliminar** documentos y encuestas, ver la **registro de acciones** y el historial de cada documento. |
| **Médico** | Cargar/editar/publicar/ocultar documentos; crear, editar, duplicar, publicar y cerrar encuestas; **agregar y editar categorías de encuestas**; ver resultados y exportar CSV; mostrar el QR; cambiar su contraseña. |
| **Enfermería** | Igual que el médico. |
| **Paciente** | No inicia sesión. **Consulta** documentos publicados; **ve las encuestas publicadas, las filtra por categoría, las busca** y las **responde**. |

## 2. Requisitos

- XAMPP con **Apache 2.4**, **PHP 8.0 o superior** (extensiones `pdo_mysql`, `fileinfo`, `mbstring`: vienen activas en XAMPP) y **MySQL/MariaDB**
- Navegador actualizado
- Internet (Bootstrap y el generador de QR se cargan desde un CDN)

## 3. Instalación paso a paso

1. **Copiar la carpeta** a `C:\xampp\htdocs\` con el nombre `sigsm`
   (debe quedar `C:\xampp\htdocs\sigsm\index.php`, sin otra carpeta `sigsm` adentro).
2. **Encender XAMPP:** *Start* en **Apache** y en **MySQL**.
3. **Crear la base:** abrir <http://localhost/phpmyadmin> → pestaña **Importar** y subir, en este orden:
   1. `sql/01_ddl.sql` (crea la base `sigsm_prueba` y las tablas)
   2. `sql/02_dml.sql` (datos de prueba)
   Si se vuelve a importar, la base `sigsm_prueba` se recrea desde cero.
4. **Conexión:** en `datos/conexion.php` están usuario y clave de MySQL (por defecto XAMPP: `root`, sin clave).
5. **Abrir:** <http://localhost/sigsm/>

La carpeta `uploads/` ya trae **9 PDF de ejemplo** (`ejemplo_01.pdf` … `ejemplo_09.pdf`) que corresponden a
los 9 documentos de la base. La carpeta `logs/` se llena sola con los errores internos.

## 4. Usuarios de prueba (uno por rol)

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@hc.edu.uy` | `123456` |
| Médico | `medico@hc.edu.uy` | `func123` |
| Enfermería | `enfermera@hc.edu.uy` | `func123` |

Las contraseñas se guardan con Bcrypt (`password_hash`), nunca en texto plano. Después de 5 intentos fallidos
el email queda bloqueado 10 minutos (se cambia en `config/config.php`).

## 5. Probar el QR con el celular

El celular **no puede abrir `localhost`**.
1. PC y celular en la **misma wifi**.
2. En la PC: `ipconfig` → anotar la *Dirección IPv4* (ej.: `192.168.1.20`).
3. En `config/config.php` poner `define('URL_BASE', 'http://192.168.1.20/sigsm');`
4. Entrar al panel → **Código QR** → escanear con el celular.
5. Si no abre, permitir Apache en el firewall de Windows (redes privadas).

## 6. Estructura (tres capas)

```
sigsm/
├── index.php                  Entrada: manda al login
├── config/config.php          URL, límites, HTTPS, registro de errores
├── presentacion/              CAPA DE PRESENTACIÓN (lo que se ve; sin SQL)
│   ├── login.php  index.php (inicio con indicadores)  documentos.php  encuestas.php
│   ├── categorias_encuesta.php  encuesta_editar.php (constructor)  resultados.php  usuarios.php  servicios.php
│   ├── registro.php  perfil.php  qr.php
│   ├── portal-paciente.php  encuesta.php  ver_documento_mobile.php     (públicas)
│   └── _cabecera.php  _pie.php  _vista.php                              (piezas comunes)
├── negocio/                   CAPA DE NEGOCIO (valida, decide, controla acceso)
│   ├── sesion.php (sesión, roles, CSRF)  validaciones.php  ayudas.php  auditoria.php  antibots.php
│   └── procesarLogin, logout, guardar_/eliminar_/cambiar_* , enviar_encuesta, exportar_resultados
├── datos/                     CAPA DE DATOS (único lugar con SQL; PDO + consultas preparadas)
│   └── Usuario, Categoria, CategoriaEncuesta, Documento, Encuesta, Pregunta, Resultado, Registro, IntentoLogin, conexion
├── sql/01_ddl.sql  02_dml.sql   Estructura y datos de prueba
├── backup/                    Scripts de respaldo y restauración
├── uploads/                   Archivos subidos (no ejecuta PHP)  ·  logs/  Errores internos
└── docs/                      Requisitos de excelencia y cómo comprobarlos
```

## 7. Base de datos (10 tablas)

| Tabla | Qué guarda |
|---|---|
| `usuario` | Funcionarios (rol admin/médico/enfermera, `activo` = baja lógica) |
| `categoria` | Servicios del hospital (`activo`; nombre único controlado en PHP) |
| `documento` | Material para pacientes: título, descripción, archivo, fecha de emisión, **fecha de carga**, **quién lo cargó**, `publicado`, `activo` |
| `categoria_encuesta` | Categorías de encuestas (Satisfacción, Instalaciones...) que **agrega el funcionario**; `activo` = baja lógica |
| `encuesta` | Título, descripción, servicio (o general), categoría de encuesta (o sin categoría), `estado` (borrador/publicada/cerrada), `version`, `id_origen` |
| `pregunta` | Texto, tipo (única, múltiple, valoración, texto), obligatoria, orden |
| `opcion` | Opciones de las preguntas de tipo única/múltiple |
| `envio` | Un envío de encuesta: **solo la fecha** (anónimo) |
| `respuesta` | Lo respondido: opción, valoración 1–5 o texto |
| `registro` / `intento_login` | Quién hizo qué y cuándo / intentos fallidos de login |

## 8. Seguridad

- Contraseñas con `password_hash()` / `password_verify()`; mínimo 6 caracteres con letras y números.
- **Consultas preparadas (PDO)** en toda la capa de datos.
- **Token CSRF** en todos los formularios que modifican datos (y el logout es POST).
- **Escape de salida** (`e()`): todo texto de la base se escapa antes de mostrarse.
- **Validación doble:** JavaScript y PHP (siempre en el servidor).
- **Archivos:** solo PDF/JPG/PNG de hasta 2 MB; se controla extensión y contenido real; nombre al azar; `uploads/` no ejecuta PHP.
- **Sesión:** `session_regenerate_id`, cookie `HttpOnly` + `SameSite`, permisos por rol en cada acción (no solo ocultando botones).
- **Login:** límite de intentos fallidos con bloqueo temporal.
- **Formularios públicos:** campo trampa + tiempo mínimo (anti-robots), token de un solo uso (sin duplicados al recargar), transacción.
- **Baja lógica** de usuarios, servicios, documentos y encuestas; la información no se pierde.
- **Errores internos** a `logs/errores.log`, nunca en pantalla.
- **Registro de acciones** de acciones importantes (con detalle de qué cambió en un documento).

### HTTPS (opcional, para producción)

1. XAMPP trae un certificado de prueba. En `C:\xampp\apache\conf\extra\httpd-ssl.conf` verificar `DocumentRoot "C:/xampp/htdocs"` y `ServerName localhost:443`.
2. En el XAMPP Control Panel reiniciar Apache y abrir <https://localhost/sigsm/> (el navegador avisa que el certificado es de prueba: aceptar).
3. Cuando funcione, en `config/config.php` poner `define('FORZAR_HTTPS', true);` y en `URL_BASE` usar `https://`.
   Desde ahí todo `http://` se redirige a `https://` y la cookie de sesión se marca como segura.
4. En un servidor real se usa un certificado válido (por ejemplo Let's Encrypt).

## 9. Respaldos y recuperación

Cada respaldo guarda la **base de datos** (`sigsm.sql`) y los **PDF** (`uploads.zip`) en `backup/copias/FECHA/`.

| Qué | Windows (XAMPP) | Linux/Mac |
|---|---|---|
| Hacer respaldo | doble clic en `backup\backup.bat` | `sh backup/backup.sh` |
| Restaurar (copia de prueba) | `backup\restaurar.bat backup\copias\FECHA` | `sh backup/restaurar.sh backup/copias/FECHA` |
| Restaurar de verdad | agregar `sigsm_prueba` al final | agregar `sigsm_prueba` al final |

**Comprobación de recuperación** (para demostrarla): 1) hacer un respaldo; 2) ejecutar el restaurar **sin** nombre de base:
se crea `sigsm_verificacion` y el script muestra la cantidad de filas por tabla; 3) comparar con la base original
(`backup/verificar_recuperacion.sql` en phpMyAdmin): los números deben coincidir; 4) abrir un PDF de `uploads.zip`.
Recomendado: respaldo diario (Programador de tareas de Windows ejecutando `backup.bat`).

## 10. Pruebas

El detalle (casos con resultado esperado / obtenido) está en el informe final (`docs/Informe_final_SIGSM.pdf`).
Para probar en **otra computadora**: copiar la carpeta, instalar XAMPP, importar los dos `.sql` y seguir la sección 3;
anotar fecha, PC y resultado en la tabla "Prueba en otra computadora" del informe.

## 11. GitHub

```bash
cd sigsm
git init
git add .
git commit -m "feat: estructura base y login del funcionario"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/sigsm.git
git push -u origin main
```
Después, un commit por cada avance (`git add .` + `git commit -m "..." ` + `git push`).
Convención: `feat:` funcionalidad nueva · `fix:` arreglo · `docs:` documentación · `refactor:` ordenar código · `test:` pruebas.
Ejemplos: `feat: constructor de encuestas con cuatro tipos de pregunta`, `fix: el CSV no escapaba fórmulas`.
No se suben respaldos ni logs (están en `.gitignore`).

## 12. Problemas comunes

- **"Not Found"** → la carpeta no está en `C:\xampp\htdocs\sigsm` o quedó una carpeta `sigsm` dentro de otra.
- **"No se pudo conectar con la base de datos"** → MySQL apagado, `.sql` sin importar, o usuario/clave de `datos/conexion.php` distintos.
- **Pantalla en blanco / error 500** → mirar `logs/errores.log` y `C:\xampp\apache\logs\error.log`.
- **No sube un archivo** → debe ser PDF/JPG/PNG de hasta 2 MB; revisar permisos de `uploads/`. Si `php.ini` limita `upload_max_filesize`, subirlo a 4M.
- **El QR no abre en el celular** → ver sección 5.
- **"Demasiados intentos fallidos"** → esperar 10 minutos (o vaciar la tabla `intento_login`).
