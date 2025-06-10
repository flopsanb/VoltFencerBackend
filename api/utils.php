<?php
declare(strict_types=1);

/**
 * Clase Utils
 * Funciones generales para la aplicación (fechas, IP, UUID, CLI, strings...)
 * 
 * @author Francisco Lopez
 * @version 1.1
 */
class Utils {

    /**
     * Comprueba si la ejecución es en línea de comandos
     * @return bool
     */
    public function is_cli(): bool {
        return (
            defined('STDIN') ||
            PHP_SAPI === 'cli' ||
            array_key_exists('SHELL', $_ENV) ||
            (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['argv'])) ||
            !array_key_exists('REQUEST_METHOD', $_SERVER)
        );
    }

    /**
     * Obtiene la IP real del cliente considerando proxies
     * @return string
     */
    public function getRealIP(): string {
        $ip = $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        // Si hay múltiples IPs separadas por coma, se toma la primera
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }

        return trim($ip);
    }

    /**
     * Convierte fecha ISO completa a YYYY-MM-DD
     * @param string $fecha_iso
     * @return string
     */
    public function formatearFechaISO(string $fecha_iso): string {
        return explode('T', $fecha_iso)[0];
    }

    /**
     * Genera un UUID v4 aleatorio
     * @return string
     */
    public function generarUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Elimina acentos y símbolos de una cadena
     * @param string $string
     * @return string
     */
    public function eliminar_caracteres(string $string): string {
        $string = trim($string);

        $acentos = [
            'á'=>'a', 'à'=>'a', 'ä'=>'a', 'â'=>'a', 'ª'=>'a',
            'Á'=>'A', 'À'=>'A', 'Â'=>'A', 'Ä'=>'A',
            'é'=>'e', 'è'=>'e', 'ë'=>'e', 'ê'=>'e',
            'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E',
            'í'=>'i', 'ì'=>'i', 'ï'=>'i', 'î'=>'i',
            'Í'=>'I', 'Ì'=>'I', 'Ï'=>'I', 'Î'=>'I',
            'ó'=>'o', 'ò'=>'o', 'ö'=>'o', 'ô'=>'o',
            'Ó'=>'O', 'Ò'=>'O', 'Ö'=>'O', 'Ô'=>'O',
            'ú'=>'u', 'ù'=>'u', 'ü'=>'u', 'û'=>'u',
            'Ú'=>'U', 'Ù'=>'U', 'Û'=>'U', 'Ü'=>'U',
            'ñ'=>'n', 'Ñ'=>'N', 'ç'=>'c', 'Ç'=>'C'
        ];

        $string = strtr($string, $acentos);
        $string = preg_replace('/[^A-Za-z0-9 ]/', ' ', $string);

        return $string;
    }

    /**
     * Rellena con ceros a la izquierda hasta longitud deseada
     * @param mixed $valor
     * @param int $long
     * @return string
     */
    public function zero_fill($valor, int $long = 0): string {
        return str_pad((string)$valor, $long, '0', STR_PAD_LEFT);
    }
}
