<?php
session_start();
$_SESSION['expiring_soon_alert_shown'] = 1;
http_response_code(204); // No content 