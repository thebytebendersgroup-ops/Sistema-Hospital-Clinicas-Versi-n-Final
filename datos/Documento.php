<?php
// ============================================================
// CAPA DE DATOS - Documento
// Todo lo que se hace con la tabla "documento" (alta, listado, edición y baja).
// Usa consultas preparadas: los "?" se completan al ejecutar, así nadie puede
// meter SQL escribiendo en los formularios (inyección SQL).
//
// Los documentos son material general para los pacientes (instructivos, folletos).
// No pertenecen a un paciente: pertenecen a una categoría (el servicio del hospital)
// y los carga un funcionario (id_usuario).
// ============================================================

class Documento {

    // Atributos del documento
    public $id;
    public $titulo;
    public $descripcion;
    public $archivo;
    public $fecha_emision;
    public $publicado = 1;   // 1 = lo ve el paciente, 0 = oculto
    public $id_categoria;
    public $id_usuario;      // funcionario que lo cargó

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Guarda el documento: si ya tiene id hace UPDATE, si no hace INSERT.
    public function guardar() {
        if ($this->id) {
            // Al editar no se cambia quién lo cargó ni cuándo
            $sql = "UPDATE documento
                    SET titulo = ?, descripcion = ?, archivo = ?, fecha_emision = ?,
                        id_categoria = ?, publicado = ?
                    WHERE id = ?";
            $this->pdo->prepare($sql)->execute([
                $this->titulo, $this->descripcion, $this->archivo, $this->fecha_emision,
                $this->id_categoria, $this->publicado ? 1 : 0, $this->id
            ]);
        } else {
            $sql = "INSERT INTO documento
                        (titulo, descripcion, archivo, fecha_emision, fecha_carga, publicado, id_categoria, id_usuario)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->pdo->prepare($sql)->execute([
                $this->titulo, $this->descripcion, $this->archivo, $this->fecha_emision,
                date('Y-m-d H:i:s'), $this->publicado ? 1 : 0, $this->id_categoria, $this->id_usuario
            ]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    // Baja lógica: el documento no se borra de la base, solo queda inactivo.
    public function darDeBaja($id) {
        $this->pdo->prepare("UPDATE documento SET activo = 0 WHERE id = ?")->execute([$id]);
    }

    // Publica (1) u oculta (0) un documento.
    public function cambiarPublicado($id, $publicado) {
        $this->pdo->prepare("UPDATE documento SET publicado = ? WHERE id = ?")
                  ->execute([$publicado ? 1 : 0, $id]);
    }

    // Consulta base: documentos activos junto con su categoría y quién los cargó.
    private function consultaBase() {
        return "SELECT d.id, d.titulo, d.descripcion, d.archivo, d.fecha_emision, d.fecha_carga,
                       d.publicado, d.id_categoria, d.id_usuario,
                       c.nombre AS categoria, c.activo AS categoria_activa,
                       u.nombre AS autor
                FROM documento d
                JOIN categoria c ON c.id = d.id_categoria
                JOIN usuario   u ON u.id = d.id_usuario
                WHERE d.activo = 1";
    }

    // Arma las condiciones de búsqueda (título y servicio) para el panel.
    private function filtros($buscar, $idCategoria) {
        $sql = '';
        $valores = [];
        if ($buscar !== '') {
            // se escapan % y _ para que se busquen como texto común
            $sql .= " AND d.titulo LIKE ? ESCAPE '!'";
            $valores[] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
        }
        if ($idCategoria !== '') {
            $sql .= " AND d.id_categoria = ?";
            $valores[] = $idCategoria;
        }
        return [$sql, $valores];
    }

    // Lista para el PANEL del funcionario: busca por título, filtra por servicio y pagina.
    // Se ordena por servicio para mostrarlos agrupados en carpetas.
    public function listarPanel($buscar, $idCategoria, $limite, $desplazamiento) {
        list($condiciones, $valores) = $this->filtros($buscar, $idCategoria);
        $sql = $this->consultaBase() . $condiciones
             . " ORDER BY c.nombre, d.fecha_emision DESC, d.id DESC"
             . " LIMIT " . (int) $limite . " OFFSET " . (int) $desplazamiento;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    // Cuántos documentos cumplen el filtro (para calcular las páginas).
    public function contarPanel($buscar, $idCategoria) {
        list($condiciones, $valores) = $this->filtros($buscar, $idCategoria);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM documento d WHERE d.activo = 1" . $condiciones
        );
        $stmt->execute($valores);
        return (int) $stmt->fetchColumn();
    }

    // Lista para el PORTAL público: solo documentos activos, publicados y de servicios activos.
    public function listarPublicos() {
        $sql = $this->consultaBase() . " AND d.publicado = 1 AND c.activo = 1"
             . " ORDER BY c.nombre, d.fecha_emision DESC, d.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    // Un documento por su id (para editar o para la vista del celular).
    // Devuelve false si no existe o fue eliminado. Si $soloPublico es true,
    // tampoco devuelve los ocultos ni los de servicios dados de baja (vista del paciente).
    public function buscarPorId($id, $soloPublico = false) {
        $sql = $this->consultaBase() . " AND d.id = ?";
        if ($soloPublico) {
            $sql .= " AND d.publicado = 1 AND c.activo = 1";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Cantidad total de documentos activos (indicadores del inicio).
    public function contarActivos() {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM documento WHERE activo = 1")->fetchColumn();
    }

    // Cantidad de servicios que tienen al menos un documento activo.
    public function contarServiciosConDocumentos() {
        return (int) $this->pdo->query("SELECT COUNT(DISTINCT id_categoria) FROM documento WHERE activo = 1")->fetchColumn();
    }
}
