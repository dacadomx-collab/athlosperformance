<?php
/**
 * AXON_DCD Intelligence Systems - DCD LABS
 * Proyecto: Athlos Performance BCS Cognitive Engine v1.0
 * Clase Core: Puente de compatibilidad hacia la conexión PDO unificada
 * Mandamiento 2: Prepared Statements Obligatorios Nativos
 * Mandamiento 5: Contrato de API en JSON UTF-8
 *
 * NOTA DE UNIFICACIÓN (2026-07-13): esta clase ya no abre su propia
 * conexión PDO ni parsea `core/.env` por su cuenta — delega en
 * `ssos_db()` (public/ssos/config/conexion.php), la ÚNICA fuente de
 * verdad de conexión y variables de entorno del proyecto. Se conserva
 * como fachada por si algún consumidor externo aún referencia
 * `AxonDcd\AthlosEngine\Core\Database::obtenerConexion()`.
 */

declare(strict_types=1);

namespace AxonDcd\AthlosEngine\Core;

use PDO;

require_once __DIR__ . '/../../public/ssos/config/conexion.php';

final class Database
{
    public static function obtenerConexion(): PDO
    {
        return ssos_db();
    }
}
