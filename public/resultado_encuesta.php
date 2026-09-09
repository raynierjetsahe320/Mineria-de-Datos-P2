<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
global $conexion;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: encuesta.php');
    exit;
}

$stmt = mysqli_prepare($conexion, 'SELECT * FROM encuesta_respuestas WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$respuesta = $resultado ? mysqli_fetch_assoc($resultado) : null;
mysqli_stmt_close($stmt);

if (!$respuesta || ($user['rol'] !== 'admin' && intval($respuesta['user_id']) !== intval($user['id']))) {
    header('Location: encuesta.php');
    exit;
}

$preguntas = [
    1 => ['texto' => 'Satisfacción con la limpieza', 'opciones' => ['Muy satisfecho', 'Satisfecho', 'Poco satisfecho', 'Nada satisfecho']],
    2 => ['texto' => 'Espacio de uso frecuente', 'opciones' => ['Aulas', 'Biblioteca', 'Áreas deportivas', 'Laboratorios']],
    3 => ['texto' => 'Medio para enterarse de actividades', 'opciones' => ['Redes sociales', 'Carteles', 'Docentes', 'Compañeros']],
    4 => ['texto' => 'Primera mejora para la FES', 'opciones' => ['Seguridad', 'Conectividad', 'Áreas verdes', 'Servicios escolares']],
    5 => ['texto' => 'Participación cultural', 'opciones' => ['Cada semana', 'Cada mes', 'Ocasionalmente', 'Nunca']],
    6 => ['texto' => 'Horario preferido', 'opciones' => ['Antes de clases', 'Entre clases', 'Después de clases', 'Fin de semana']],
];

$registros = [];
$todos = mysqli_query($conexion, 'SELECT sexo, edad, pregunta_1, pregunta_2, pregunta_3, pregunta_4, pregunta_5, pregunta_6 FROM encuesta_respuestas');
if ($todos) {
    while ($fila = mysqli_fetch_assoc($todos)) {
        $registros[] = $fila;
    }
}
$totalRespuestas = count($registros);
$distribuciones = [];
foreach ($preguntas as $numero => $pregunta) {
    $conteos = array_fill_keys($pregunta['opciones'], 0);
    foreach ($registros as $registro) {
        $opcion = $registro['pregunta_' . $numero] ?? '';
        if (array_key_exists($opcion, $conteos)) {
            $conteos[$opcion]++;
        }
    }
    $distribuciones[$numero] = $conteos;
}
$datosGraficas = [];
foreach ($preguntas as $numero => $pregunta) {
    $seleccion = $respuesta['pregunta_' . $numero];
    $conteos = $distribuciones[$numero];
    $porcentajes = [];
    $opcionesGraficas = array_keys($conteos);
    foreach ($opcionesGraficas as $opcion) {
        $porcentajes[] = $totalRespuestas > 0 ? round(($conteos[$opcion] / $totalRespuestas) * 100, 1) : 0;
    }
    $datosGraficas[] = [
        'numero' => $numero,
        'titulo' => $pregunta['texto'],
        'opciones' => $opcionesGraficas,
        'seleccion' => $seleccion,
        'frecuencias' => array_values($conteos),
        'porcentajes' => $porcentajes,
    ];
}
function rango_edad(int $edad): string {
    if ($edad <= 24) return '20 a 24 años';
    if ($edad <= 29) return '25 a 29 años';
    if ($edad <= 39) return '30 a 39 años';
    return '40 años o más';
}
require_once __DIR__ . '/../templates/header.php';
?>
<section class="results-banner">
    <div>
        <p class="eyebrow">Encuesta completada</p>
        <h1>Gracias por compartir tu experiencia</h1>
        <p>Tus respuestas fueron registradas. Aquí puedes comparar tu selección con las <?php echo $totalRespuestas; ?> respuestas acumuladas.</p>
    </div>
    <div class="banner-mark">✓</div>
</section>

<div class="personal-summary">
    <article class="summary-item"><span>Sexo</span><strong><?php echo htmlspecialchars($respuesta['sexo']); ?></strong></article>
    <article class="summary-item"><span>Edad</span><strong><?php echo htmlspecialchars(rango_edad(intval($respuesta['edad']))); ?></strong></article>
    <article class="summary-item"><span>Respuestas acumuladas</span><strong><?php echo $totalRespuestas; ?></strong></article>
</div>

<section class="personal-results">
    <div class="section-heading">
        <p class="eyebrow">Tu selección</p>
        <h2>Resumen de tus respuestas</h2>
    </div>
    <div class="interactive-summary panel">
        <div class="section-heading"><p class="eyebrow">Vista general</p><h2>Mapa de tus elecciones</h2></div>
        <div class="overview-chart-wrap"><canvas id="overviewChart" aria-label="Resumen interactivo de respuestas" role="img"></canvas></div>
        <p class="chart-help">Pasa el cursor sobre cada punto para consultar tu opción y su porcentaje dentro de todas las respuestas.</p>
    </div>
    <div class="answer-charts">
        <?php foreach ($preguntas as $numero => $pregunta):
            $seleccion = $respuesta['pregunta_' . $numero];
        ?>
            <article class="answer-chart">
                <div class="chart-heading"><span>Pregunta <?php echo $numero; ?></span><strong>Tu respuesta: <?php echo htmlspecialchars($seleccion); ?></strong></div>
                <p><?php echo htmlspecialchars($pregunta['texto']); ?></p>
                <small class="chart-base">Base: <?php echo $totalRespuestas; ?> respuestas</small>
                <div class="question-chart-wrap"><canvas id="questionChart<?php echo $numero; ?>" aria-label="Gráfica de la pregunta <?php echo $numero; ?>" role="img"></canvas></div>
                <div class="answer-bars">
                    <?php foreach ($pregunta['opciones'] as $opcion):
                        $activa = $opcion === $seleccion;
                    ?>
                        <div class="answer-bar <?php echo $activa ? 'selected' : ''; ?>">
                            <span><?php echo htmlspecialchars($opcion); ?></span>
                            <div class="bar-track"><i style="width: <?php echo $activa ? '100' : '12'; ?>%"></i></div>
                            <b><?php echo $activa ? 'Tu respuesta' : ''; ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="open-answer-summary panel">
    <div class="section-heading"><p class="eyebrow">Tu voz</p><h2>Respuestas abiertas</h2></div>
    <div class="open-answer-grid">
        <article><span>Mejora para el campus</span><p><?php echo nl2br(htmlspecialchars($respuesta['abierta_1'])); ?></p></article>
        <article><span>Actividad o servicio nuevo</span><p><?php echo nl2br(htmlspecialchars($respuesta['abierta_2'])); ?></p></article>
    </div>
</section>

<div class="result-actions">
    <a class="btn" href="encuesta.php">Volver al inicio</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const encuestaGraficas = <?php echo json_encode($datosGraficas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const colores = ['#20639b', '#f6c85f', '#3caea3', '#ed553b'];
const resumen = document.getElementById('overviewChart');

new Chart(resumen, {
    type: 'radar',
    data: {
        labels: encuestaGraficas.map(item => 'Pregunta ' + item.numero),
        datasets: [{
            label: 'Respuesta seleccionada',
            data: encuestaGraficas.map(item => {
                const indice = item.opciones.indexOf(item.seleccion);
                return item.porcentajes[indice] || 0;
            }),
            backgroundColor: 'rgba(32, 99, 155, .18)',
            borderColor: '#20639b',
            pointBackgroundColor: '#f6c85f',
            pointBorderColor: '#173f5f',
            pointRadius: 6,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { r: { min: 0, max: 100, ticks: { callback: value => value + '%', color: '#64748b' }, pointLabels: { color: '#173f5f', font: { weight: '700' } }, grid: { color: '#dbe3ed' } } },
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: context => {
                const item = encuestaGraficas[context.dataIndex];
                const indice = item.opciones.indexOf(item.seleccion);
                return ' ' + item.seleccion + ': ' + item.frecuencias[indice] + ' respuestas (' + item.porcentajes[indice] + '%)';
            }, title: context => encuestaGraficas[context[0].dataIndex].titulo } }
        }
    }
});

encuestaGraficas.forEach(item => {
    const canvas = document.getElementById('questionChart' + item.numero);
    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: item.opciones,
            datasets: [{
                data: item.frecuencias,
                backgroundColor: item.opciones.map((opcion, index) => opcion === item.seleccion ? colores[index] : '#dbe3ed'),
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#475569', padding: 14, usePointStyle: true } },
                tooltip: { callbacks: { label: context => {
                    const frecuencia = item.frecuencias[context.dataIndex];
                    const porcentaje = item.porcentajes[context.dataIndex];
                    const marca = context.label === item.seleccion ? ' Tu respuesta: ' : ' ';
                    return marca + frecuencia + ' respuestas (' + porcentaje + '%)';
                } } }
            }
        }
    });
});
</script>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
