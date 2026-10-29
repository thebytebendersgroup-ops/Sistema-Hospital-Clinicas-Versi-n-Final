<?php
// ============================================================
// Conexión a la base de datos con PDO.
// Uso en otros archivos:
//     require_once __DIR__ . '/../datos/conexion.php';
//     $pdo = (new Conexion())->getConexion();
// ============================================================

class Conexion {

    // Datos de conexión (valores por defecto de XAMPP)
    private $host    = "localhost";
    private $puerto  = "3306";
    private $dbname  = "sigsm_prueba";   // base de prueba (la crea sql/01_ddl.sql)
    private $usuario = "root";
    private $clave   = "";
    private $charset = "utf8mb4";

    private $pdo;

    public function __construct() {
        $dsn = "mysql:host={$this->host};port={$this->puerto};dbname={$this->dbname};charset={$this->charset}";

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // los errores lanzan excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // las filas vuelven como arreglo asociativo
            PDO::ATTR_EMULATE_PREPARES   => false,                   // consultas preparadas reales
        ];

        try {
            $this->pdo = new PDO($dsn, $this->usuario, $this->clave, $opciones);
        } catch (PDOException $e) {
            // El detalle técnico va al log; al usuario solo se le muestra un mensaje general.
            error_log("Error de conexión: " . $e->getMessage());
            die("No se pudo conectar con la base de datos. Revise datos/conexion.php y que MySQL esté encendido en XAMPP.");
        }
    }

    // Devuelve el objeto PDO para hacer consultas.
    public function getConexion() {
        return $this->pdo;
    }
}
