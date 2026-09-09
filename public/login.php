<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth.php';

if (!isset($_SESSION['user_id'])) {
    $userId = get_anonymous_survey_user();
    if ($userId > 0) {
        $_SESSION['user_id'] = $userId;
    }
}

header('Location: encuesta.php');
exit;
