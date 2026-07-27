<?php
/**
 * Database Initializer Script
 * RapiFix Systems
 */

require_once __DIR__ . '/db.php';

try {
    $db = getDBConnection();
    echo "<div style='font-family:sans-serif; padding:20px; background:#e0f2fe; color:#0369a1; border-radius:8px;'>";
    echo "<h2>RapiFix Database Successfully Initialized!</h2>";
    echo "<p>Connected to Database via PDO. Schema created and seed data loaded.</p>";
    echo "<ul>";
    echo "<li><strong>Admin User:</strong> admin@rapifix.com / password123</li>";
    echo "<li><strong>Hospital User:</strong> metro@hospital.org / password123</li>";
    echo "<li><strong>Engineer User:</strong> marcus.vance@bme-pros.com / password123</li>";
    echo "</ul>";
    echo "<a href='../index.php' style='display:inline-block; padding:10px 20px; background:#0284c7; color:#fff; text-decoration:none; border-radius:4px; margin-top:10px;'>Go to RapiFix Homepage &rarr;</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; padding:20px; background:#fef2f2; color:#991b1b; border-radius:8px;'>";
    echo "<h2>Database Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
