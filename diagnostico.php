<?php
// diagnostico.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnóstico</h1>";

$archivos = [
    'mensajes_enviar_chat.php' => 'Archivo para AJAX del chat',
    'mensajes_leer.php' => 'Ver chat',
    'mensajes.php' => 'Lista de mensajes',
    'mensajes_enviar.php' => 'Formulario de nuevo mensaje',
    'config.php' => 'Configuración',
];

echo "<h2>📁 Archivos en el servidor</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Archivo</th><th>Existe</th><th>Descripción</th></tr>";

foreach ($archivos as $archivo => $desc) {
    $existe = file_exists($archivo);
    $color = $existe ? 'green' : 'red';
    echo "<tr>";
    echo "<td><strong>$archivo</strong></td>";
    echo "<td style='color: $color; font-weight: bold;'>" . ($existe ? '✅ SÍ' : '❌ NO') . "</td>";
    echo "<td>$desc</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>📍 Ruta actual</h2>";
echo "<p>Estás en: <code>" . __DIR__ . "</code></p>";

echo "<h2>📂 Archivos en el directorio</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') {
        echo "<li>$f</li>";
    }
}
echo "</ul>";
?>