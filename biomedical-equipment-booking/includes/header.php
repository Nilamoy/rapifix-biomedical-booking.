<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - RapiFix" : "RapiFix - Biomedical Engineer On-Demand"; ?></title>
    <meta name="description" content="On-demand biomedical engineering services for hospital equipment repair, preventive maintenance, and safety calibration.">
    <!-- Font Awesome / Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS System -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/nav.php'; ?>
    <div class="main-wrapper">
