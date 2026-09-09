<?php
// Public index for webroot — simple redirect to the login page.
// Keep this file output-free so headers can be sent correctly.
header('Location: login.php');
exit;
