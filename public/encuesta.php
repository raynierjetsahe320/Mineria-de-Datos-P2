<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
global $conexion;

$preguntas = [
    1 => ['texto' => '¿Qué tan satisfecho estás con la limpieza del campus?', 'opciones' => ['Muy satisfecho', 'Satisfecho', 'Poco satisfecho', 'Nada satisfecho']],
    2 => ['texto' => '¿Qué espacio usas con mayor frecuencia?', 'opciones' => ['Aulas', 'Biblioteca', 'Áreas deportivas', 'Laboratorios']],
    3 => ['texto' => '¿Cómo te enteras de las actividades universitarias?', 'opciones' => ['Redes sociales', 'Carteles', 'Docentes', 'Compañeros']],
    4 => ['texto' => '¿Qué aspecto mejorarías primero en la FES?', 'opciones' => ['Seguridad', 'Conectividad', 'Áreas verdes', 'Servicios escolares']],
    5 => ['texto' => '¿Con qué frecuencia participas en actividades culturales?', 'opciones' => ['Cada semana', 'Cada mes', 'Ocasionalmente', 'Nunca']],
    6 => ['texto' => '¿Qué horario prefieres para actividades extracurriculares?', 'opciones' => ['Antes de clases', 'Entre clases', 'Después de clases', 'Fin de semana']],
];
$errores = [];
$enviada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sexo = $_POST['sexo'] ?? '';
    $edad = filter_var($_POST['edad'] ?? null, FILTER_VALIDATE_INT);
    $respuestas = [];
    foreach ($preguntas as $numero => $pregunta) {
        $respuesta = $_POST['pregunta_' . $numero] ?? '';
        if (!in_array($respuesta, $pregunta['opciones'], true)) {
            $errores[] = 'Selecciona una opción válida en la pregunta ' . $numero . '.';
        }
        $respuestas[$numero] = $respuesta;
    }
    if (!in_array($sexo, ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'], true)) {
        $errores[] = 'Selecciona una opción válida para sexo.';
    }
    if ($edad === false || $edad < 20 || $edad > 99) {
        $errores[] = 'La edad debe estar entre 20 y 99 años.';
    }
    $abierta1 = trim($_POST['abierta_1'] ?? '');
    $abierta2 = trim($_POST['abierta_2'] ?? '');
    if ($abierta1 === '' || $abierta2 === '') {
        $errores[] = 'Responde las dos preguntas abiertas.';
    }
    if (mb_strlen($abierta1) > 500 || mb_strlen($abierta2) > 500) {
        $errores[] = 'Cada respuesta abierta puede tener máximo 500 caracteres.';
    }

    if (!$errores) {
        $stmt = mysqli_prepare($conexion, 'INSERT INTO encuesta_respuestas (user_id, sexo, edad, pregunta_1, pregunta_2, pregunta_3, pregunta_4, pregunta_5, pregunta_6, abierta_1, abierta_2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $userId = intval($user['id']);
        mysqli_stmt_bind_param($stmt, 'isissssssss', $userId, $sexo, $edad, $respuestas[1], $respuestas[2], $respuestas[3], $respuestas[4], $respuestas[5], $respuestas[6], $abierta1, $abierta2);
        $enviada = mysqli_stmt_execute($stmt);
        $respuestaId = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
        if (!$enviada) {
            $errores[] = 'No se pudo guardar la respuesta. Intenta nuevamente.';
        } else {
            header('Location: resultado_encuesta.php?id=' . intval($respuestaId));
            exit;
        }
    }
}

$totalRespuestas = 0;
$datosGraficas = [];
$consultaTotal = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM encuesta_respuestas');
if ($consultaTotal) {
    $totalRespuestas = intval(mysqli_fetch_assoc($consultaTotal)['total']);
}
foreach ($preguntas as $numero => $pregunta) {
    $conteos = [];
    $columna = 'pregunta_' . $numero;
    foreach ($pregunta['opciones'] as $opcion) {
        $opcionSql = mysqli_real_escape_string($conexion, $opcion);
        $consulta = mysqli_query($conexion, "SELECT COUNT(*) AS frecuencia FROM encuesta_respuestas WHERE `$columna` = '$opcionSql'");
        $fila = $consulta ? mysqli_fetch_assoc($consulta) : null;
        $conteos[$opcion] = $fila ? intval($fila['frecuencia']) : 0;
    }
    $datosGraficas[] = [
        'numero' => $numero,
        'titulo' => $pregunta['texto'],
        'opciones' => array_keys($conteos),
        'frecuencias' => array_values($conteos),
    ];
}
$rangosEdad = [
    '20 a 24 años' => [20, 24],
    '25 a 29 años' => [25, 29],
    '30 a 39 años' => [30, 39],
    '40 años o más' => [40, 99],
];
$conteosEdad = array_fill_keys(array_keys($rangosEdad), 0);
$consultaEdades = mysqli_query($conexion, 'SELECT edad, COUNT(*) AS frecuencia FROM encuesta_respuestas GROUP BY edad');
if ($consultaEdades) {
    while ($fila = mysqli_fetch_assoc($consultaEdades)) {
        foreach ($rangosEdad as $rango => $limites) {
            if (intval($fila['edad']) >= $limites[0] && intval($fila['edad']) <= $limites[1]) {
                $conteosEdad[$rango] += intval($fila['frecuencia']);
                break;
            }
        }
    }
}
$opcionesSexo = ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'];
$conteosSexo = array_fill_keys($opcionesSexo, 0);
$consultaSexo = mysqli_query($conexion, 'SELECT sexo, COUNT(*) AS frecuencia FROM encuesta_respuestas GROUP BY sexo');
if ($consultaSexo) {
    while ($fila = mysqli_fetch_assoc($consultaSexo)) {
        if (array_key_exists($fila['sexo'], $conteosSexo)) {
            $conteosSexo[$fila['sexo']] = intval($fila['frecuencia']);
        }
    }
}
require_once __DIR__ . '/../templates/header.php';
?>
<section class="survey-intro">
    <p class="eyebrow">FES Aragón · Participación universitaria</p>
    <h1>Tu experiencia también construye el campus</h1>
    <p>Esta encuesta toma menos de cinco minutos. Tus respuestas se usarán para conocer tendencias y priorizar mejoras.</p>
</section>
<?php if ($enviada): ?>
    <div class="success">Gracias. Tu respuesta quedó registrada correctamente.</div>
    <a class="btn" href="encuesta.php">Registrar otra respuesta</a>
    <?php if ($user['rol'] === 'admin'): ?><a class="btn btn-secondary" href="resultados.php">Ver resultados</a><?php endif; ?>
<?php else: ?>
    <?php if ($errores): ?><div class="alert"><?php echo htmlspecialchars(implode(' ', $errores)); ?></div><?php endif; ?>
    <form method="POST" class="survey-form">
        <fieldset class="survey-section">
            <legend>Sobre ti</legend>
            <div class="form-grid">
                <label>Sexo
                    <select name="sexo" required>
                        <option value="">Selecciona una opción</option>
                        <?php foreach (['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'] as $opcion): ?>
                            <option value="<?php echo htmlspecialchars($opcion); ?>" <?php echo (($_POST['sexo'] ?? '') === $opcion) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcion); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Edad
                    <input type="number" name="edad" min="20" max="99" value="<?php echo htmlspecialchars($_POST['edad'] ?? ''); ?>" required>
                </label>
            </div>
        </fieldset>
        <fieldset class="survey-section">
            <legend>Tu vida universitaria</legend>
            <?php foreach ($preguntas as $numero => $pregunta): ?>
                <div class="question">
                    <p><strong><?php echo $numero; ?>.</strong> <?php echo htmlspecialchars($pregunta['texto']); ?></p>
                    <div class="option-grid">
                        <?php foreach ($pregunta['opciones'] as $opcion): ?>
                            <label class="option"><input type="radio" name="pregunta_<?php echo $numero; ?>" value="<?php echo htmlspecialchars($opcion); ?>" <?php echo (($_POST['pregunta_' . $numero] ?? '') === $opcion) ? 'checked' : ''; ?> required><span><?php echo htmlspecialchars($opcion); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <fieldset class="survey-section">
            <legend>Tu voz</legend>
            <label>¿Qué cambio concreto haría más agradable tu experiencia en el campus?
                <textarea name="abierta_1" maxlength="500" required><?php echo htmlspecialchars($_POST['abierta_1'] ?? ''); ?></textarea>
            </label>
            <label>¿Qué actividad o servicio nuevo te gustaría encontrar en la FES Aragón?
                <textarea name="abierta_2" maxlength="500" required><?php echo htmlspecialchars($_POST['abierta_2'] ?? ''); ?></textarea>
            </label>
        </fieldset>
        <button class="btn" type="submit">Enviar encuesta</button>
    </form>
<?php endif; ?>
<section class="survey-live-charts panel">
    <div class="section-heading">
        <p class="eyebrow">Datos acumulados</p>
        <h2>Respuestas de la comunidad</h2>
        <p class="chart-help">Estas gráficas muestran las <?php echo $totalRespuestas; ?> respuestas guardadas hasta ahora.</p>
    </div>
    <h3 class="chart-group-title">Gráficas de distribución</h3>
    <div class="live-chart-grid">
        <?php foreach ($datosGraficas as $grafica): ?>
            <article class="live-chart-card">
                <h3><?php echo htmlspecialchars($grafica['titulo']); ?></h3>
                <div class="live-chart-box"><div class="live-chart-wrap"><canvas id="liveChart<?php echo $grafica['numero']; ?>" aria-label="Gráfica circular de respuestas" role="img"></canvas></div></div>
            </article>
        <?php endforeach; ?>
    </div>
    <h3 class="chart-group-title">Datos demográficos</h3>
    <div class="demographic-chart-grid">
        <article class="live-chart-card">
            <h3>Distribución por rangos de edad</h3>
            <div class="live-chart-box"><div class="live-chart-wrap"><canvas id="ageChart" aria-label="Gráfica de rangos de edad" role="img"></canvas></div></div>
        </article>
        <article class="live-chart-card">
            <h3>Distribución por sexo</h3>
            <div class="live-chart-box"><div class="live-chart-wrap"><canvas id="sexChart" aria-label="Gráfica por sexo" role="img"></canvas></div></div>
        </article>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const datosEncuesta = <?php echo json_encode($datosGraficas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const coloresEncuesta = ['#20639b', '#f6c85f', '#3caea3', '#ed553b', '#94a3b8'];
datosEncuesta.forEach(item => {
    new Chart(document.getElementById('liveChart' + item.numero), {
        type: 'doughnut',
        data: {
            labels: item.opciones,
            datasets: [{ data: item.frecuencias, backgroundColor: coloresEncuesta, borderColor: '#fff', borderWidth: 3, hoverOffset: 10 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#475569', usePointStyle: true, padding: 12 } },
                tooltip: { callbacks: { label: context => {
                    const total = item.frecuencias.reduce((sum, value) => sum + value, 0);
                    const porcentaje = total ? ((context.raw / total) * 100).toFixed(1) : '0.0';
                    return ' ' + context.raw + ' respuestas (' + porcentaje + '%)';
                } } }
            }
        }
    });
});
const datosEdad = <?php echo json_encode(['opciones' => array_keys($conteosEdad), 'frecuencias' => array_values($conteosEdad)], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const datosSexo = <?php echo json_encode(['opciones' => array_keys($conteosSexo), 'frecuencias' => array_values($conteosSexo)], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
function crearGraficaDemografica(elementId, datos, colores) {
    new Chart(document.getElementById(elementId), {
        type: 'doughnut',
        data: { labels: datos.opciones, datasets: [{ data: datos.frecuencias, backgroundColor: colores, borderColor: '#fff', borderWidth: 3, hoverOffset: 10 }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#475569', usePointStyle: true, padding: 12 } },
                tooltip: { callbacks: { label: context => {
                    const total = datos.frecuencias.reduce((sum, value) => sum + value, 0);
                    const porcentaje = total ? ((context.raw / total) * 100).toFixed(1) : '0.0';
                    return ' ' + context.raw + ' respuestas (' + porcentaje + '%)';
                } } }
            }
        }
    });
}
crearGraficaDemografica('ageChart', datosEdad, ['#20639b', '#3caea3', '#f6c85f', '#ed553b']);
crearGraficaDemografica('sexChart', datosSexo, ['#20639b', '#ed553b', '#3caea3', '#f6c85f']);
</script>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
