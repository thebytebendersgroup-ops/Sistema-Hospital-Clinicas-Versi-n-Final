<?php
// ============================================================
// CAPA DE DATOS - Usuario
// Funcionario del hospital que entra al sistema (administrador, médico o enfermería).
// Acá están las consultas del login y de la administración de usuarios,
// para que la capa de negocio no tenga SQL.
// ============================================================

class Usuario {

    // Atributos (se llenan cuando el login es correcto, o al editar)
    public $id;
    public $nombre;
    public $email;
    public $rol;

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Busca el email en la base y compara la contraseña con password_verify().
    // Devuelve true si el login es correcto (y llena los atributos), false si no.
    public function iniciarSesion($email, $contrasenia) {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre, email, contrasenia, rol
             FROM usuario
             WHERE email = ? AND activo = 1"
        );
        $stmt->execute([$email]);
        $fila = $stmt->fetch();

        // Si el usuario no existe o la contraseña está mal, el resultado es el mismo: false.
        if (!$fila || !password_verify($contrasenia, $fila['contrasenia'])) {
            return false;
        }

        $this->id     = $fila['id'];
        $this->nombre = $fila['nombre'];
        $this->email  = $fila['email'];
        $this->rol    = $fila['rol'];
        return true;
    }

    // Alta o edición. $hashContrasenia: la contraseña YA convertida con password_hash()
    // (en el alta es obligatoria; al editar, si viene vacía no se cambia).
    public function guardar($hashContrasenia = '') {
        if ($this->id) {
            $this->pdo->prepare("UPDATE usuario SET nombre = ?, email = ?, rol = ? WHERE id = ?")
                      ->execute([$this->nombre, $this->email, $this->rol, $this->id]);
            if ($hashContrasenia !== '') {
                $this->cambiarContrasenia($this->id, $hashContrasenia);
            }
        } else {
            $this->pdo->prepare("INSERT INTO usuario (nombre, email, contrasenia, rol) VALUES (?, ?, ?, ?)")
                      ->execute([$this->nombre, $this->email, $hashContrasenia, $this->rol]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    // Baja lógica (0) o reactivación (1) de un usuario.
    public function cambiarEstado($id, $activo) {
        $this->pdo->prepare("UPDATE usuario SET activo = ? WHERE id = ?")->execute([$activo ? 1 : 0, $id]);
    }

    public function cambiarContrasenia($id, $hashContrasenia) {
        $this->pdo->prepare("UPDATE usuario SET contrasenia = ? WHERE id = ?")->execute([$hashContrasenia, $id]);
    }

    // ¿La contraseña actual es correcta? (se pide al cambiarla)
    public function contraseniaCorrecta($id, $contrasenia) {
        $stmt = $this->pdo->prepare("SELECT contrasenia FROM usuario WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();
        return $hash !== false && password_verify($contrasenia, $hash);
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, email, rol, activo FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ¿Ya existe otro usuario con ese email? ($idExcluido es el que se está editando)
    public function emailExiste($email, $idExcluido = 0) {
        $stmt = $this->pdo->prepare("SELECT id FROM usuario WHERE LOWER(email) = LOWER(?) AND id <> ?");
        $stmt->execute([$email, (int) $idExcluido]);
        return (bool) $stmt->fetch();
    }

    // Cuántos administradores activos hay (para no dejar el sistema sin administrador).
    public function contarAdministradoresActivos() {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'admin' AND activo = 1")->fetchColumn();
    }

    // Listado paginado con búsqueda por nombre o email.
    public function listar($buscar, $limite, $desplazamiento) {
        list($condicion, $valores) = $this->filtro($buscar);
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre, email, rol, activo FROM usuario" . $condicion
            . " ORDER BY activo DESC, nombre LIMIT " . (int) $limite . " OFFSET " . (int) $desplazamiento
        );
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function contar($buscar) {
        list($condicion, $valores) = $this->filtro($buscar);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuario" . $condicion);
        $stmt->execute($valores);
        return (int) $stmt->fetchColumn();
    }

    private function filtro($buscar) {
        if ($buscar === '') {
            return ['', []];
        }
        $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
        return [" WHERE (nombre LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')", [$patron, $patron]];
    }
}
