<?php
echo '<pre>';
echo "Current __DIR__: " . __DIR__ . PHP_EOL;
echo "File exists check:" . PHP_EOL;
echo "conn.php: " . (file_exists(__DIR__ . '/api/private/conn.php') ? '✅ found' : '❌ not found') . PHP_EOL;
echo "empresa.php: " . (file_exists(__DIR__ . '/api/private/apiClasses/empresa.php') ? '✅ found' : '❌ not found') . PHP_EOL;
echo '</pre>';
