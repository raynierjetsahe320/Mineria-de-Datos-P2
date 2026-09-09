<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();

// logout sencillo
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// solo admin puede acceder al dashboard completo
if(!$user || $user['rol'] !== 'admin'){
    header('Location: report_list.php');
    exit;
}

// obtener datos para graficas
global $conexion;
// obtener recuento por lugar: incluir edificios (E:) y zonas (Z:) que tengan al menos 1 incidencia
$labels = [];
$counts = [];
$res = mysqli_query($conexion, "(
    SELECT CONCAT('E: ', e.nombre) AS lugar, COUNT(i.id) AS cnt
    FROM incidencias i
    JOIN edificios e ON i.edificio_id = e.id
    GROUP BY e.id
)
UNION ALL
(
    SELECT CONCAT('Z: ', z.nombre) AS lugar, COUNT(i2.id) AS cnt
    FROM incidencias i2
    JOIN zonas z ON i2.zona_id = z.id
    GROUP BY z.id
)
ORDER BY cnt DESC");
if($res){
    while($r = mysqli_fetch_assoc($res)){
        $labels[] = fix_encoding($r['lugar']);
        $counts[] = intval($r['cnt']);
    }
} else {
    error_log('dashboard combined places query failed: '.mysqli_error($conexion));
}

// obtener lista de edificios para mostrar en el mapa
$buildings = [];
// obtener lugares (edificios y zonas) que tienen incidencias
$rb = mysqli_query($conexion, "SELECT tipo, id, nombre, cnt FROM (
    SELECT 'E' as tipo, e.id as id, e.nombre as nombre, COUNT(i.id) as cnt FROM edificios e JOIN incidencias i ON i.edificio_id = e.id GROUP BY e.id
    UNION ALL
    SELECT 'Z' as tipo, z.id as id, z.nombre as nombre, COUNT(i2.id) as cnt FROM zonas z JOIN incidencias i2 ON i2.zona_id = z.id GROUP BY z.id
) t ORDER BY FIELD(tipo, 'E', 'Z'), id");
if($rb){
    while($b = mysqli_fetch_assoc($rb)){
        $buildings[] = $b;
    }
} else {
    error_log('dashboard buildings query failed: '.mysqli_error($conexion));
}

$prio_labels = [];
$prio_counts = [];
$res2 = mysqli_query($conexion, "SELECT prioridad, COUNT(*) as cnt FROM incidencias GROUP BY prioridad");
if($res2) {
    while($p = mysqli_fetch_assoc($res2)){
        $prio_labels[] = fix_encoding($p['prioridad']);
        $prio_counts[] = intval($p['cnt']);
    }
} else {
    error_log('dashboard priority query failed: '.mysqli_error($conexion));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - FES Aragón</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- librerías para exportar a PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

<header class="topbar">
    <div class="container">
        <h1 class="brand">FES Aragón</h1>
        <div class="user-info">
            <span class="user">Hola, <?php echo htmlspecialchars(fix_encoding((string)($user['nombre'] ?? $user['correo'] ?? ''))); ?></span>
            <a class="btn-ghost" href="?logout=1">Cerrar sesión</a>
        </div>
    </div>
</header>

<main class="layout container">
    <nav class="sidebar">
        <ul>
            <li class="active">Dashboard</li>
            <li>Incidencias</li>
            <li>Baños</li>
            <li>Salones</li>
            <li>Reportes</li>
        </ul>
    </nav>

        <section class="content" id="dashboard-content">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h2 class="page-title">Dashboard General</h2>
                    <div>
                        <button id="refreshCharts" class="btn">Actualizar</button>
                        <button id="downloadPdf" class="btn">Descargar PDF</button>
                    </div>
                </div>

        <div class="cards">
            <article class="card">
                <div class="card-value">152</div>
                <div class="card-label">Incidencias activas</div>
            </article>

            <article class="card">
                <div class="card-value">28</div>
                <div class="card-label">Baños fuera de servicio</div>
            </article>

            <article class="card">
                <div class="card-value">A6</div>
                <div class="card-label">Edificio con más reportes</div>
            </article>
        </div>

            <div class="panel">
            <h3 class="panel-title">Incidencias por edificio</h3>
            <div class="chart-wrapper">
                <canvas id="grafica" aria-label="Gráfica de incidencias" role="img"></canvas>
            </div>
        </div>

        <div class="panel">
            <h3 class="panel-title">Incidencias por prioridad</h3>
            <div class="chart-wrapper">
                <canvas id="prioChart" aria-label="Gráfica de prioridades" role="img"></canvas>
            </div>
        </div>

        <div class="panel">
            <h3 class="panel-title">Mapa del campus y lista de edificios</h3>
            <div style="display:flex;gap:18px;align-items:flex-start">
                <div style="flex:1">
                    <img src="assets/campus-map.png" alt="Mapa campus" style="max-width:100%;border-radius:8px;box-shadow:var(--shadow)">
                </div>
                <div style="width:320px;background:var(--card);padding:12px;border-radius:8px;box-shadow:var(--shadow)">
                    <h4>Edificios</h4>
                    <ol>
                    <?php foreach($buildings as $bb): ?>
                        <li><?php echo htmlspecialchars(fix_encoding(($bb['tipo']==='Z' ? 'Z: ' : 'E: ').$bb['nombre'])); ?></li>
                    <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>

    </section>
</main>

<footer class="footer">
    <div class="container">
        <small>© FES Aragón</small>
    </div>
</footer>

<script>
// datos desde PHP
const labels = <?php echo json_encode($labels); ?>;
const counts = <?php echo json_encode($counts); ?>;
const prioLabels = <?php echo json_encode($prio_labels); ?>;
const prioCounts = <?php echo json_encode($prio_counts); ?>;

const ctx = document.getElementById('grafica').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Incidencias',
            backgroundColor: labels.map((_,i)=>['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6'][i%5]),
            borderRadius: 6,
            data: counts
        }]
    },
    options: {responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
});

const ctx2 = document.getElementById('prioChart').getContext('2d');
const prioChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {labels: prioLabels, datasets:[{data: prioCounts, backgroundColor:['#ef4444','#f59e0b','#10b981','#2563eb']}]},
    options: {responsive:true, maintainAspectRatio:false, cutout: '60%', plugins:{legend:{position:'top'}}}
});

document.getElementById('refreshCharts').addEventListener('click', ()=> location.reload());

// Descargar PDF
document.getElementById('downloadPdf').addEventListener('click', async () => {
    const el = document.getElementById('dashboard-content');

    // crear un encabezado temporal con logo y fecha
    const header = document.createElement('div');
    header.style.display = 'flex';
    header.style.justifyContent = 'space-between';
    header.style.alignItems = 'center';
    header.style.padding = '12px 18px';
    header.style.background = '#ffffff';
    header.style.borderBottom = '1px solid #e6eefc';

    // logo simple SVG
    const logo = document.createElement('div');
    logo.innerHTML = '<svg width="56" height="56" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect rx="12" width="100" height="100" fill="#2563eb"/><text x="50" y="58" font-family="Arial" font-size="46" fill="white" text-anchor="middle">FA</text></svg>';
    logo.style.display = 'flex';
    logo.style.alignItems = 'center';

    const titleWrap = document.createElement('div');
    titleWrap.innerHTML = '<div style="font-size:18px;color:#0f172a;font-weight:700">Dashboard FES Aragón</div><div style="font-size:12px;color:#6b7280">Reporte generado por administrador</div>';

    const dateWrap = document.createElement('div');
    const now = new Date();
    dateWrap.innerText = now.toLocaleString();
    dateWrap.style.color = '#6b7280';
    dateWrap.style.fontSize = '12px';

    header.appendChild(logo);
    header.appendChild(titleWrap);
    header.appendChild(dateWrap);

    // insertar encabezado al principio del contenedor para la captura
    el.parentElement.insertBefore(header, el);

    // captura con mayor resolución
    const canvas = await html2canvas(el.parentElement, {scale:3, useCORS:true, backgroundColor:'#ffffff'});
    const imgData = canvas.toDataURL('image/png');
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({orientation:'landscape', unit:'pt', format:'a4'});

    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();

    // calcular dimensiones manteniendo relación
    const img = new Image();
    img.src = imgData;
    img.onload = function(){
        const imgWidth = img.width;
        const imgHeight = img.height;
        const ratio = Math.min(pageWidth / imgWidth, pageHeight / imgHeight);
        const drawWidth = imgWidth * ratio;
        const drawHeight = imgHeight * ratio;
        const x = (pageWidth - drawWidth) / 2;
        const y = 20; // dejar espacio superior

        pdf.addImage(imgData, 'PNG', x, y, drawWidth, drawHeight);

        // pie de página
        const footerText = 'FES Aragón — Dashboard • Generado: ' + new Date().toLocaleString();
        pdf.setFontSize(10);
        pdf.setTextColor(120);
        pdf.text(footerText, 40, pageHeight - 30);

        pdf.save('dashboard-fesaragon.pdf');

        // eliminar encabezado temporal
        header.remove();
    };
});
</script>

</body>
</html>
<!-- cleaned duplicate content -->
