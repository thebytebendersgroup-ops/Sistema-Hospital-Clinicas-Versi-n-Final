# Requisitos de excelencia y cómo comprobarlos

Para cada requisito: dónde está en el código y cómo demostrarlo.

| # | Requisito | Dónde está | Cómo comprobarlo |
|---|---|---|---|
| 1 | Usuarios: alta, edición, baja lógica | usuarios.php, guardar_usuario.php, cambiar_estado_usuario.php | Admin > Usuarios: crear, editar, dar de baja; el dado de baja no puede entrar. |
| 2 | Cambio de contraseña | perfil.php, cambiar_contrasenia.php | Mi cuenta: cambiar clave; probar que la vieja ya no sirve. |
| 3 | Servicios sin duplicados | servicios.php, validaciones.php (validarServicio) | Admin > Servicios: crear el mismo nombre dos veces; el segundo se rechaza. |
| 4 | Servicio dado de baja: tratamiento de documentos y encuestas | cambiar_estado_servicio.php, consultas con c.activo = 1 | Dar de baja Cardiología: sus documentos y encuestas desaparecen del portal; al reactivar vuelven. Se avisa antes con la cantidad afectada. |
| 5 | Documentos: descripción, quién y cuándo, reemplazar PDF, publicado/oculto, historial | documentos.php, guardar_documento.php, registro.php | Editar un documento, cambiar el PDF y ocultarlo; abrir el historial (reloj) y ver qué cambió. |
| 6 | Categorías de encuestas agregadas por el funcionario; el paciente filtra y busca por categoría | categorias_encuesta.php, guardar_categoria_encuesta.php, CategoriaEncuesta.php, portal-paciente.php | Encuestas > Categorías: crear una; elegirla en una encuesta y publicarla; en el portal (pestaña Encuestas) filtrar por esa categoría y buscar por texto. |
| 7 | Constructor de encuestas completo | encuestas.php, encuesta_editar.php, guardar_pregunta.php | Crear encuesta, agregar los 4 tipos, ordenar, marcar obligatorias, ver vista previa. |
| 8 | Borrador / publicada / cerrada | cambiar_estado_encuesta.php | Publicar y cerrar; intentar editar una publicada (no deja). |
| 9 | Duplicar y nuevas versiones con historial | duplicar_encuesta.php, Encuesta::duplicar | Botón versión en una encuesta con respuestas: crea v2 en borrador y conserva las respuestas de la v1. |
| 10 | Transacciones | Encuesta::guardarEnvio, Encuesta::duplicar, Pregunta::guardar | Leer el código: beginTransaction / commit / rollBack. |
| 11 | Preguntas y opciones pertenecen a la encuesta | validarRespuestasEnvio, guardar_pregunta.php | Caso CP-26 y CP-32: opción de otra pregunta o pregunta ajena se rechaza. |
| 12 | Sin envío duplicado al recargar | nuevoTokenUnico / usarTokenUnico, PRG | Caso CP-31: reenviar o recargar no suma una respuesta más. |
| 13 | Validación por tipo; solo fecha (anónimo) | validaciones.php, tabla envio | Caso CP-32; la tabla envio solo tiene id, id_encuesta y fecha. |
| 14 | Estadísticas: porcentajes, gráficos, textos, filtros, CSV | resultados.php, Resultado.php, exportar_resultados.php | Resultados > detalle: barras y %; filtrar por servicio/fechas; exportar CSV y abrirlo en Excel. |
| 15 | Panel con indicadores | index.php | Inicio: documentos, servicios, encuestas, respuestas y promedio. |
| 16 | Paginación | ayudas.php (paginar), _vista.php | Listados con más de 10 filas muestran páginas. |
| 17 | Registro de acciones y confirmaciones | auditoria.php, registro.php, confirm() en botones | Hacer acciones y revisarlas en Registro de acciones; eliminar pide confirmación. |
| 18 | Límite de intentos de login | procesarLogin.php, IntentoLogin.php | Fallar 5 veces: queda bloqueado 10 minutos. |
| 19 | Anti-bots en formularios públicos | antibots.php, enviar_encuesta.php | Caso CP-30. |
| 20 | Registro de errores internos | config.php (logs/errores.log) | Provocar un error (apagar MySQL): pantalla con mensaje general y detalle en logs/errores.log. |
| 21 | Configuración HTTPS | config.php (FORZAR_HTTPS), README sección 8 | Activar SSL en XAMPP y FORZAR_HTTPS=true: http:// redirige a https://. |
| 22 | Respaldos de base y PDF; comprobación de recuperación | backup/*.bat, *.sh, verificar_recuperacion.sql | Ejecutar backup.bat; restaurar en sigsm_verificacion y comparar conteos. |
