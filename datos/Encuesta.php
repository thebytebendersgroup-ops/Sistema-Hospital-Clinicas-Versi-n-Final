<?php
// ============================================================
// CAPA DE DATOS - Encuesta
// Una encuesta tiene título, descripción, servicio (o es general), un estado
// (borrador, publicada o cerrada) y una versión. Los envíos de los pacientes
// son ANÓNIMOS: la tabla envio solo guarda la fecha, nunca quién respondió.
// ============================================================

require_once __DIR__ . '/Pregunta.php';

class Encuesta {

    // Atributos
    public $id;
    public $titulo;
    public $descripcion;
    public $id_categoria;    // servicio (null = encuesta general)
    public $id_categoria_encuesta;   // categoría de la encuesta (null = sin categoría)
    public $id_usuario;      // quién la creó

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Alta (siempre como borrador) o edición de título, descripción y servicio.
    public function guardar() {
        if ($this->id) {
            $this->pdo->prepare("UPDATE encuesta SET titulo = ?, descripcion = ?, id_categoria = ?, id_categoria_encuesta = ? WHERE id = ?")
                      ->execute([$this->titulo, $this->descripcion, $this->id_categoria, $this->id_categoria_encuesta, $this->id]);
        } else {
            $this->pdo->prepare(
                "INSERT INTO encuesta (titulo, descripcion, estado, version, fecha_creacion, id_categoria, id_categoria_encuesta, id_usuario)
                 VALUES (?, ?, 'borrador', 1, ?, ?, ?, ?)"
            )->execute([$this->titulo, $this->descripcion, date('Y-m-d H:i:s'), $this->id_categoria, $this->id_categoria_encuesta, $this->id_usuario]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    // Cambia el estado: 'borrador', 'publicada' o 'cerrada'.
    public function cambiarEstado($id, $estado) {
        $this->pdo->prepare("UPDATE encuesta SET estado = ? WHERE id = ?")->execute([$estado, $id]);
    }

    // Baja lógica: la encuesta y sus respuestas se conservan, pero ya no se ve.
    public function darDeBaja($id) {
        $this->pdo->prepare("UPDATE encuesta SET activo = 0 WHERE id = ?")->execute([$id]);
    }

    // Consulta base: encuesta con su servicio, quién la creó y cuántas preguntas y envíos tiene.
    private function consultaBase() {
        return "SELECT e.id, e.titulo, e.descripcion, e.estado, e.version, e.fecha_creacion,
                       e.id_categoria, e.id_categoria_encuesta, e.id_origen, e.id_usuario,
                       c.nombre AS servicio, c.activo AS servicio_activo,
                       ce.nombre AS categoria_encuesta, ce.activo AS categoria_encuesta_activa,
                       u.nombre AS autor,
                       (SELECT COUNT(*) FROM pregunta p WHERE p.id_encuesta = e.id) AS preguntas,
                       (SELECT COUNT(*) FROM envio v WHERE v.id_encuesta = e.id) AS envios
                FROM encuesta e
                LEFT JOIN categoria c ON c.id = e.id_categoria
                LEFT JOIN categoria_encuesta ce ON ce.id = e.id_categoria_encuesta
                JOIN usuario u ON u.id = e.id_usuario
                WHERE e.activo = 1";
    }

    // La encuesta con ese id (false si no existe o fue eliminada).
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare($this->consultaBase() . " AND e.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // La encuesta junto con sus preguntas y opciones (para el constructor y para responderla).
    public function cargarCompleta($id) {
        $encuesta = $this->buscarPorId($id);
        if ($encuesta) {
            $encuesta['lista_preguntas'] = (new Pregunta($this->pdo))->listarPorEncuesta($id);
        }
        return $encuesta;
    }

    // Condiciones de búsqueda del panel: título, servicio y estado.
    private function filtros($buscar, $idCategoria, $estado, $idTipo = '') {
        $sql = '';
        $valores = [];
        if ($buscar !== '') {
            $sql .= " AND e.titulo LIKE ? ESCAPE '!'";
            $valores[] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
        }
        if ($idCategoria === 'general') {
            $sql .= " AND e.id_categoria IS NULL";
        } elseif ($idCategoria !== '') {
            $sql .= " AND e.id_categoria = ?";
            $valores[] = $idCategoria;
        }
        if ($estado !== '') {
            $sql .= " AND e.estado = ?";
            $valores[] = $estado;
        }
        // Categoría de la encuesta: 'sin' = sin categoría, o el id de una categoría
        if ($idTipo === 'sin') {
            $sql .= " AND e.id_categoria_encuesta IS NULL";
        } elseif ($idTipo !== '') {
            $sql .= " AND e.id_categoria_encuesta = ?";
            $valores[] = $idTipo;
        }
        return [$sql, $valores];
    }

    // Listado paginado para el panel.
    public function listarPanel($buscar, $idCategoria, $estado, $limite, $desplazamiento, $idTipo = '') {
        list($condiciones, $valores) = $this->filtros($buscar, $idCategoria, $estado, $idTipo);
        $stmt = $this->pdo->prepare(
            $this->consultaBase() . $condiciones
            . " ORDER BY e.id DESC LIMIT " . (int) $limite . " OFFSET " . (int) $desplazamiento
        );
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function contarPanel($buscar, $idCategoria, $estado, $idTipo = '') {
        list($condiciones, $valores) = $this->filtros($buscar, $idCategoria, $estado, $idTipo);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM encuesta e WHERE e.activo = 1" . $condiciones);
        $stmt->execute($valores);
        return (int) $stmt->fetchColumn();
    }

    // Encuestas que ve el paciente en el portal: publicadas y activas, generales o de un servicio activo.
    public function listarPublicadas() {
        return $this->pdo->query(
            $this->consultaBase() . " AND e.estado = 'publicada' AND (e.id_categoria IS NULL OR c.activo = 1)
             ORDER BY e.titulo"
        )->fetchAll();
    }

    // Cantidad de encuestas publicadas (indicadores del inicio).
    public function contarPublicadas() {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM encuesta WHERE activo = 1 AND estado = 'publicada'"
        )->fetchColumn();
    }

    // Copia una encuesta (con sus preguntas y opciones) como un borrador nuevo.
    //  - $comoVersion = true:  nueva VERSIÓN de la misma encuesta (mismo título, versión + 1,
    //                          queda anotada la encuesta de origen).
    //  - $comoVersion = false: DUPLICADO (título "Copia de ...", versión 1).
    // Devuelve el id de la encuesta nueva.
    public function duplicar($idOriginal, $idUsuario, $comoVersion) {
        $original = $this->buscarPorId($idOriginal);
        if (!$original) {
            return false;
        }
        $preguntas = (new Pregunta($this->pdo))->listarPorEncuesta($idOriginal);

        $this->pdo->beginTransaction();
        try {
            $titulo  = $comoVersion ? $original['titulo'] : mb_substr('Copia de ' . $original['titulo'], 0, 120);
            $version = $comoVersion ? $original['version'] + 1 : 1;
            $origen  = $comoVersion ? $idOriginal : null;

            $this->pdo->prepare(
                "INSERT INTO encuesta (titulo, descripcion, estado, version, fecha_creacion, id_categoria, id_categoria_encuesta, id_origen, id_usuario)
                 VALUES (?, ?, 'borrador', ?, ?, ?, ?, ?, ?)"
            )->execute([$titulo, $original['descripcion'], $version, date('Y-m-d H:i:s'),
                        $original['id_categoria'], $original['id_categoria_encuesta'], $origen, $idUsuario]);
            $idNueva = $this->pdo->lastInsertId();

            $insertarPregunta = $this->pdo->prepare(
                "INSERT INTO pregunta (id_encuesta, texto, tipo, obligatoria, orden) VALUES (?, ?, ?, ?, ?)"
            );
            $insertarOpcion = $this->pdo->prepare("INSERT INTO opcion (id_pregunta, texto, orden) VALUES (?, ?, ?)");
            foreach ($preguntas as $pregunta) {
                $insertarPregunta->execute([$idNueva, $pregunta['texto'], $pregunta['tipo'],
                                            $pregunta['obligatoria'], $pregunta['orden']]);
                $idPregunta = $this->pdo->lastInsertId();
                foreach ($pregunta['opciones'] as $opcion) {
                    $insertarOpcion->execute([$idPregunta, $opcion['texto'], $opcion['orden']]);
                }
            }
            $this->pdo->commit();
            return $idNueva;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Guarda TODAS las respuestas de un envío con una transacción (o se guarda todo o nada).
    // $respuestas: lista de ['id_pregunta' => 1, 'id_opcion' => null, 'valor_escala' => 4, 'valor_texto' => null]
    // Solo se guarda la fecha del envío: no se sabe quién respondió.
    public function guardarEnvio($idEncuesta, $respuestas) {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("INSERT INTO envio (id_encuesta, fecha) VALUES (?, ?)")
                      ->execute([$idEncuesta, date('Y-m-d H:i:s')]);
            $idEnvio = $this->pdo->lastInsertId();

            $insertar = $this->pdo->prepare(
                "INSERT INTO respuesta (id_envio, id_pregunta, id_opcion, valor_escala, valor_texto)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($respuestas as $r) {
                $insertar->execute([$idEnvio, $r['id_pregunta'], $r['id_opcion'], $r['valor_escala'], $r['valor_texto']]);
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
