<?php
/**
 * AXON_DCD Intelligence Systems - DCD LABS
 * Proyecto: Athlos Performance BCS Cognitive Engine v1.0
 * Clase Core: Conexión Centralizada PDO y Control CORS Blindado
 * Mandamiento 2: Prepared Statements Obligatorios Nativos
 * Mandamiento 5: Contrato de API en JSON UTF-8
 */

declare(strict_types=1);

namespace AxonDcd\AthlosEngine\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database 
{
    /**
     * Instancia única de conexión PDO (Inmutabilidad Singleton)
     */
    private static ?PDO $conexion_instancia = null;

    /**
     * Intercepta y valida los orígenes remotos (CORS) contra la lista blanca del .env
     */
    public static function inicializarEntorno(): void 
    {
        $ruta_env = dirname(__DIR__) . '/.env';
        if (!file_exists($ruta_env)) {
            self::emitirErrorCritico("Archivo de configuración interna .env ausente.");
        }

        $configuracion = parse_ini_file($ruta_env, true);
        if ($configuracion === false) {
            self::emitirErrorCritico("Error de lectura o sintaxis rota en el entorno .env.");
        }

        $origen_peticion = $_SERVER['HTTP_ORIGIN'] ?? '';
        $origenes_permitidos_raw = $configuracion['SEGURIDAD_CORS']['ALLOWED_ORIGINS'] ?? '';
        $origenes_permitidos = array_map('trim', explode(',', $origenes_permitidos_raw));

        if (in_array($origen_peticion, origenes_permitidos, true)) {
            header("Access-Control-Allow-Origin: {$origen_peticion}");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
            header("Access-Control-Allow-Credentials: true");
        }

        // Responder inmediatamente a las solicitudes de verificación OPTIONS de los navegadores
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

    /**
     * Entrega el puente inmutable de interacción con la Base de Datos
     */
    public static function obtenerConexion(): PDO 
    {
        if (self::$conexion_instancia === null) {
            self::inicializarEntorno();

            $ruta_env = dirname(__DIR__) . '/.env';
            $configuracion = parse_ini_file($ruta_env, true);

            $host    = $configuracion['BASE_DE_DATOS']['DB_HOST'] ?? 'localhost';
            $port    = $configuracion['BASE_DE_DATOS']['DB_PORT'] ?? '3306';
            $db_name = $configuracion['BASE_DE_DATOS']['DB_NAME'] ?? 'tourfindycom_nova_db';
            $usuario = $configuracion['BASE_DE_DATOS']['DB_USER'] ?? 'tourfindycom_nova_db_user';
            $password= $configuracion['BASE_DE_DATOS']['DB_PASS'] ?? '';
            $charset = $configuracion['BASE_DE_DATOS']['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset={$charset}";
            
            $opciones_pdo = [
                // Forzar excepciones estrictas ante fallos lógicos
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Retornos indexados únicamente asociativos bajo vocabulario controlado
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // DESACTIVACIÓN DE EMULACIÓN DE PREPARACIÓN DE DECLARACIONES
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Seteo nativo de codificación y colación multi-idioma segura
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}' COLLATE 'utf8mb4_unicode_ci'"
            ];

            try {
                self::$conexion_instancia = new PDO($dsn, $usuario, $password, $opciones_pdo);
            } catch (PDOException $excepcion) {
                // Registro silencioso del error real en logs privados del servidor cPanel
                $ruta_log = dirname(dirname(__DIR__)) . '/logs/backend.log';
                $directorio_log = dirname($ruta_log);
                
                if (!is_dir($directorio_log)) {
                    mkdir($directorio_log, 0755, true);
                }

                $mensaje_log = sprintf(
                    "[%s] [CRITICAL SQL BREACH/FAIL] Error de conexión: %s \n",
                    date('Y-m-d H:i:s'),
                    $excepcion->getMessage()
                );
                error_log($mensaje_log, 3, $ruta_log);

                // Contrato estricto: Cero filtraciones de contraseñas o nombres al cliente externo
                self::emitirErrorCritico("Error de infraestructura de datos. Comunicación abortada.");
            }
        }

        return self::$conexion_instancia;
    }

    /**
     * Formateador unificado de salidas JSON ante catástrofes de servidor (HTTP 500)
     */
    private static function emitirErrorCritico(string $mensaje_usuario): void 
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }

        echo json_encode([
            'status' => 'error',
            'code'   => 500,
            'error'  => [
                'type'    => 'INTERNAL_SERVER_ERROR',
                'message' => $mensaje_usuario
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
}