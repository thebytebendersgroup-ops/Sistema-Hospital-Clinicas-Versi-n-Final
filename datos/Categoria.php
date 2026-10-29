<?php
// ============================================================
// CAPA DE DATOS - Categoria (los servicios del hospital)
// Alta, edición y baja lógica. Una categoría dada de baja deja de ofrecerse
// en los formularios y sus documentos y encuestas dejan de verse en el portal
// (no se pierde nada: si se vuelve a activar, todo reaparece).
// ============================================================

class Categoria {

    public $id;
    public $nombre;

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Guarda el servicio: con id hace UPDATE, sin id hace INSERT.
    public function guardar() {
        if ($this->id) {
            $this->pdo->prepare("UPDATE categoria SET nombre = ? WHERE id = ?")
                      ->execute([$this->nombre, $this->id]);
        } else {
            $this->pdo->prepare("INSERT INTO categoria (nombre) VALUES (?)")
                      ->execute([$this->nombre]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    // Activa (1) o da de baja (0) un servicio.
    public function cambiarEstado($id, $activo) {
        $this->pdo->prepare("UPDATE categoria SET activo = ? WHERE id = ?")
                  ->execute([$activo ? 1 : 0, $id]);
    }

    // Servicios para los select de los formularios (por defecto solo los activos).
    public function listar($soloActivas = true) {
        $sql = "SELECT id, nombre, activo FROM categoria";
        if ($soloActivas) {
            $sql .= " WHERE activo = 1";
        }
        return $this->pdo->query($sql . " ORDER BY nombre")->fetchAll();
    }

    // Todos los servicios con cuántos documentos y encuestas tiene cada uno (pantalla de administración).
    public function listarConConteo() {
        return $this->pdo->query(
            "SELECT c.id, c.nombre, c.activo,
                    (SELECT COUNT(*) FROM documento d WHERE d.id_categoria = c.id AND d.activo = 1) AS documentos,
                    (SELECT COUNT(*) FROM encuesta e WHERE e.id_categoria = c.id AND e.activo = 1) AS encuestas
             FROM categoria c
             ORDER BY c.nombre"
        )->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, activo FROM categoria WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ¿Existe un servicio ACTIVO con ese id? (se usa al validar formularios)
    public function existe($id) {
        $stmt = $this->pdo->prepare("SELECT id FROM categoria WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    // ¿Ya hay otro servicio con ese nombre? (evita duplicados; $idExcluido es el que se está editando)
    public function nombreExiste($nombre, $idExcluido = 0) {
        $stmt = $this->pdo->prepare("SELECT id FROM categoria WHERE LOWER(nombre) = LOWER(?) AND id <> ?");
        $stmt->execute([$nombre, (int) $idExcluido]);
        return (bool) $stmt->fetch();
    }
}
