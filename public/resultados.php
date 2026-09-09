<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
if (!$user || $user['rol'] !== 'admin') {
    header('Location: encuesta.php');
    exit;
}
global $conexion;

$preguntas = [
    'sexo' => 'Sexo',
    'edad' => 'Edad',
    'pregunta_1' => 'Satisfacción con la limpieza',
    'pregunta_2' => 'Espacio de uso frecuente',
    'pregunta_3' => 'Medio para enterarse de actividades',
    'pregunta_4' => 'Primera mejora para la FES',
    'pregunta_5' => 'Participación cultural',
    'pregunta_6' => 'Horario preferido',
];
$opciones = [
    'sexo' => ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'],
    'pregunta_1' => ['Muy satisfecho', 'Satisfecho', 'Poco satisfecho', 'Nada satisfecho'],
    'pregunta_2' => ['Aulas', 'Biblioteca', 'Áreas deportivas', 'Laboratorios'],
    'pregunta_3' => ['Redes sociales', 'Carteles', 'Docentes', 'Compañeros'],
    'pregunta_4' => ['Seguridad', 'Conectividad', 'Áreas verdes', 'Servicios escolares'],
    'pregunta_5' => ['Cada semana', 'Cada mes', 'Ocasionalmente', 'Nunca'],
    'pregunta_6' => ['Antes de clases', 'Entre clases', 'Después de clases', 'Fin de semana'],
];
$total = 0;
$countResult = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM encuesta_respuestas');
if ($countResult) {
    $total = intval(mysqli_fetch_assoc($countResult)['total']);
}
function porcentaje(int $frecuencia, int $total): string {
    return $total > 0 ? number_format(min(100, ($frecuencia / $total) * 100), 1) : '0.0';
}
function resultados_cerrados(mysqli $conexion, string $columna, array $opciones, int $total): array {
    $safeColumn = preg_replace('/[^a-z0-9_]/i', '', $columna);
    $resultados = [];
    $query = mysqli_query($conexion, "SELECT `$safeColumn` AS respuesta, COUNT(*) AS frecuencia FROM encuesta_respuestas GROUP BY `$safeColumn`");
    $conteos = [];
    if ($query) {
        while ($fila = mysqli_fetch_assoc($query)) {
            $conteos[$fila['respuesta']] = intval($fila['frecuencia']);
        }
    }
    foreach ($opciones as $opcion) {
        $frecuencia = $conteos[$opcion] ?? 0;
        $resultados[] = ['respuesta' => $opcion, 'frecuencia' => $frecuencia, 'porcentaje' => porcentaje($frecuencia, $total)];
    }
    return $resultados;
}
function resultados_edad(mysqli $conexion, int $total): array {
    $rangos = ['20 a 24 años' => [20, 24], '25 a 29 años' => [25, 29], '30 a 39 años' => [30, 39], '40 años o más' => [40, 99]];
    $conteos = [];
    $query = mysqli_query($conexion, 'SELECT edad, COUNT(*) AS frecuencia FROM encuesta_respuestas GROUP BY edad');
    if ($query) {
        while ($fila = mysqli_fetch_assoc($query)) {
            foreach ($rangos as $nombre => $rango) {
                if (intval($fila['edad']) >= $rango[0] && intval($fila['edad']) <= $rango[1]) {
                    $conteos[$nombre] = ($conteos[$nombre] ?? 0) + intval($fila['frecuencia']);
                    break;
                }
            }
        }
    }
    $resultados = [];
    foreach ($rangos as $nombre => $_rango) {
        $frecuencia = $conteos[$nombre] ?? 0;
        $resultados[] = ['respuesta' => $nombre, 'frecuencia' => $frecuencia, 'porcentaje' => porcentaje($frecuencia, $total)];
    }
    return $resultados;
}
require_once __DIR__ . '/../templates/header.php';
?>
<section class="survey-intro">
    <p class="eyebrow">Panel de análisis</p>
    <h1>Resultados de la encuesta</h1>
    <p>Distribución de las <?php echo $total; ?> respuestas registradas. Cada pregunta calcula su porcentaje sobre sus propias respuestas.</p>
</section>
<div class="cards summary-cards">
    <article class="card"><div class="card-value"><?php echo $total; ?></div><div class="card-label">Respuestas totales</div></article>
    <article class="card"><div class="card-value">10</div><div class="card-label">Preguntas respondidas</div></article>
    <article class="card"><div class="card-value">100%</div><div class="card-label">Máximo por pregunta</div></article>
</div>
<div class="results-grid">
<?php foreach ($preguntas as $columna => $titulo): ?>
    <?php $filas = $columna === 'edad' ? resultados_edad($conexion, $total) : resultados_cerrados($conexion, $columna, $opciones[$columna], $total); ?>
    <article class="panel result-panel">
        <h2><?php echo htmlspecialchars($titulo); ?></h2>
        <table class="table"><thead><tr><th>Respuesta</th><th>Frecuencia</th><th>Porcentaje</th></tr></thead><tbody>
        <?php foreach ($filas as $fila): ?><tr><td><?php echo htmlspecialchars($fila['respuesta']); ?></td><td><?php echo $fila['frecuencia']; ?></td><td><div class="bar-line"><span style="width: <?php echo $fila['porcentaje']; ?>%"></span></div><?php echo $fila['porcentaje']; ?>%</td></tr><?php endforeach; ?>
        </tbody></table>
    </article>
<?php endforeach; ?>
</div>
<?php
$abiertas = mysqli_query($conexion, 'SELECT abierta_1, abierta_2, created_at FROM encuesta_respuestas ORDER BY created_at DESC');
?>
<section class="panel open-results"><h2>Respuestas abiertas</h2>
<?php if ($abiertas): while ($fila = mysqli_fetch_assoc($abiertas)): ?>
    <article><p><strong>Cambio para mejorar:</strong> <?php echo nl2br(htmlspecialchars($fila['abierta_1'])); ?></p><p><strong>Servicio o actividad:</strong> <?php echo nl2br(htmlspecialchars($fila['abierta_2'])); ?></p></article>
<?php endwhile; endif; ?>
</section>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
