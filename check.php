<?php
// check.php - Diagnóstico completo de existencia de archivos del backend

echo "<pre>";
echo "🔍 Backend File Check - VoltFencer\n";
echo "Current DIR: " . __DIR__ . "\n\n";

// Lista de rutas relativas a comprobar
$paths = [
    'index.php',
    'router.php',
    'composer.json',
    'composer.lock',
    'Procfile',
    'conn.php',
    'utils.php',
    'text.php',
    'api_utils.php',
    'private/empresa.php',
    'private/usuario.php',
    'private/login.php',
    'private/logout.php',
    'private/proyecto.php',
    'private/mi_empresa.php',
    'private/soporte.php',
    'private/permisos_rol.php',
    'private/rol.php',
    'private/rol_menu.php',
    'private/estado_conexion.php',
    'private/check_token.php',
    'private/check_token_passwd.php',
    'private/check_user.php',
    'private/check_usuarios.php',
    'private/apiClasses/empresa.php',
    'private/apiClasses/usuario.php',
    'private/apiClasses/auth.php',
    'private/apiClasses/proyecto.php',
    'private/apiClasses/soporte.php',
    'private/apiClasses/estado_conexion.php',
    'private/apiClasses/permisos_rol.php',
    'private/apiClasses/rol.php',
    'private/apiClasses/rol_menu.php',
    'private/apiClasses/interfaces/crud.php',
];

// Busca si existe cada archivo del paths
foreach ($paths as $relativePath) {
    $fullPath = __DIR__ . '/' . $relativePath;
    $exists = file_exists($fullPath);

    echo str_pad($relativePath, 50) . ($exists ? "✅ FOUND" : "❌ MISSING") . "\n";
}

echo "</pre>";
