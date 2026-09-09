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
$historial = mysqli_query($conexion, 'SELECT id, sexo, edad, abierta_1, abierta_2, created_at FROM encuesta_respuestas ORDER BY created_at DESC, id DESC');
require_once __DIR__ . '/../templates/header.php';
?>
<section class="survey-intro">
    <p class="eyebrow">Registro cronológico</p>
    <h1>Historial de respuestas abiertas</h1>
    <p>Consulta las respuestas abiertas conforme fueron registradas en la encuesta.</p>
</section>

<section class="panel history-panel">
    <div class="history-header">
        <h2>Entradas registradas</h2>
        <span class="history-count">Historial completo</span>
    </div>
    <div class="history-list">
        <?php if ($historial && mysqli_num_rows($historial) > 0): ?>
            <?php while ($fila = mysqli_fetch_assoc($historial)): ?>
                <article class="history-entry">
                    <div class="history-entry-header">
                        <div>
                            <span class="history-id">Respuesta #<?php echo intval($fila['id']); ?></span>
                            <h3><?php echo htmlspecialchars($fila['created_at']); ?></h3>
                        </div>
                        <span class="history-context"><?php echo htmlspecialchars($fila['sexo']); ?> · <?php echo intval($fila['edad']); ?> años</span>
                    </div>
                    <div class="history-answers">
                        <div>
                            <span class="history-label">¿Qué cambio concreto haría más agradable tu experiencia en el campus?</span>
                            <p><?php echo nl2br(htmlspecialchars($fila['abierta_1'])); ?></p>
                        </div>
                        <div>
                            <span class="history-label">¿Qué actividad o servicio nuevo te gustaría encontrar en la FES Aragón?</span>
                            <p><?php echo nl2br(htmlspecialchars($fila['abierta_2'])); ?></p>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="muted">Todavía no hay respuestas abiertas registradas.</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
