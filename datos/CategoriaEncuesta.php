<?php
// ============================================================
// CAPA DE DATOS - CategoriaEncuesta
// Las categorías de encuestas (Satisfacción, Atención al paciente, Instalaciones...).
// Las agrega el funcionario. Sirven para agrupar las encuestas y para que el paciente
// pueda filtrarlas. Es distinta del "servicio" (Cardiología, Emergencia...) que también tiene la encuesta.
// Baja lógica: una categoría dada de baja ya no se ofrece al crear encuestas,
// pero las encuestas que ya la tenían la conservan.
// ============================================================

class CategoriaEncuesta {

    public $id;
    public $nombre;

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Guarda: con id hace UPDATE, sin id hace INSERT.
    public function guardar() {
        if ($this->id) {
            $this->pdo->prepare("UPDATE categoria_encuesta SET nombre = ? WHERE id = ?")
                      ->execute([$this->nombre, $this->id]);
        } else {
            $this->pdo->prepare("INSERT INTO categoria_encuesta (nombre) VALUES (?)")
                      ->execute([$this->nombre]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    public function cambiarEstado($id, $activo) {
        $this->pdo->prepare("UPDATE categoria_encuesta SET activo = ? WHERE id = ?")
                  ->execute([$activo ? 1 : 0, $id]);
    }

    // Para los select de los formularios (por defecto solo las activas).
    public function listar($soloActivas = true) {
        $sql = "SELECT id, nombre, activo FROM categoria_encuesta";
        if ($soloActivas) {
            $sql .= " WHERE activo = 1";
        }
        return $this->pdo->query($sql . " ORDER BY nombre")->fetchAll();
    }

    // Pantalla de administración: cada categoría con cuántas encuestas tiene.
    public function listarConConteo() {
        return $this->pdo->query(
            "SELECT c.id, c.nombre, c.activo,
                    (SELECT COUNT(*) FROM encuesta e WHERE e.id_categoria_encuesta = c.id AND e.activo = 1) AS encuestas
             FROM categoria_encuesta c
             ORDER BY c.nombre"
        )->fetchAll();
    }

    // Categorías que se muestran al paciente: solo las que tienen alguna encuesta publicada.
    public function listarConEncuestasPublicadas() {
        return $this->pdo->query(
            "SELECT c.id, c.nombre
             FROM categoria_encuesta c
             WHERE EXISTS (SELECT 1 FROM encuesta e
                           LEFT JOIN categoria s ON s.id = e.id_categoria
                           WHERE e.id_categoria_encuesta = c.id AND e.activo = 1 AND e.estado = 'publicada'
                             AND (e.id_categoria IS NULL OR s.activo = 1))
             ORDER BY c.nombre"
        )->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, activo FROM categoria_encuesta WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ¿Existe una categoría ACTIVA con ese id? (se usa al validar formularios)
    public function existe($id) {
        $stmt = $this->pdo->prepare("SELECT id FROM categoria_encuesta WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    // ¿Ya hay otra categoría con ese nombre? (evita duplicados)
    public function nombreExiste($nombre, $idExcluido = 0) {
        $stmt = $this->pdo->prepare("SELECT id FROM categoria_encuesta WHERE LOWER(nombre) = LOWER(?) AND id <> ?");
        $stmt->execute([$nombre, (int) $idExcluido]);
        return (bool) $stmt->fetch();
    }
}
