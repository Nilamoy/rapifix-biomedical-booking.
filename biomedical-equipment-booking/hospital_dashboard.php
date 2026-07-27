<?php
$pageTitle = "Hospital Client Portal";
require_once __DIR__ . '/includes/header.php';

// Auth check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hospital') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$hospId = $_SESSION['user']['hospital_id'] ?? null;

// Fallback lookup if session hospital_id missing
if (!$hospId) {
    $stmt = $db->prepare("SELECT id, hospital_name FROM hospitals WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $h = $stmt->fetch();
    if ($h) {
        $hospId = $h['id'];
        $_SESSION['user']['hospital_id'] = $h['id'];
        $_SESSION['user']['hospital_name'] = $h['hospital_name'];
    }
}

// Fetch Metrics
$stmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE hospital_id = ?");
$stmt->execute([$hospId]);
$totalEquipment = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM service_tickets WHERE hospital_id = ? AND status IN ('pending', 'assigned', 'en_route', 'diagnosing', 'waiting_parts')");
$stmt->execute([$hospId]);
$activeTicketsCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM service_tickets WHERE hospital_id = ? AND urgency = 'critical' AND status != 'completed'");
$stmt->execute([$hospId]);
$criticalCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM service_tickets WHERE hospital_id = ? AND status = 'completed'");
$stmt->execute([$hospId]);
$completedCount = $stmt->fetchColumn();

// Fetch Active Service Tickets with Engineer Info
$stmt = $db->prepare("
    SELECT t.*, e.equipment_name, e.category, e.serial_number, eng_u.full_name as engineer_name, eng_u.phone as engineer_phone
    FROM service_tickets t
    JOIN equipment e ON t.equipment_id = e.id
    LEFT JOIN engineers eng ON t.engineer_id = eng.id
    LEFT JOIN users eng_u ON eng.user_id = eng_u.id
    WHERE t.hospital_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$hospId]);
$tickets = $stmt->fetchAll();
?>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h1 class="page-title"><?php echo htmlspecialchars($_SESSION['user']['hospital_name'] ?? 'Hospital Dashboard'); ?></h1>
            <p class="page-subtitle">Manage medical equipment assets, track engineer dispatches, and review safety compliance certificates.</p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="equipment_list.php" class="btn btn-secondary"><i class="fa-solid fa-list-check"></i> Equipment Roster</a>
            <a href="book_service.php" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i> Request Engineer Callout</a>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon icon-blue">
                <i class="fa-solid fa-microscope"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $totalEquipment; ?></div>
                <div class="metric-lbl">Registered Assets</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon icon-amber">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $activeTicketsCount; ?></div>
                <div class="metric-lbl">Active Service Calls</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon icon-red">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $criticalCount; ?></div>
                <div class="metric-lbl">Critical Urgencies</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon icon-emerald">
                <i class="fa-solid fa-certificate"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $completedCount; ?></div>
                <div class="metric-lbl">Completed Job Sheets</div>
            </div>
        </div>
    </div>

    <!-- Service Tickets Table -->
    <div class="card-table-wrapper">
        <div class="table-header">
            <h3 class="table-title"><i class="fa-solid fa-clipboard-list" style="color: var(--cyan-primary);"></i> Equipment Service Tickets</h3>
            <a href="book_service.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-wrench"></i> New Callout</a>
        </div>

        <?php if (empty($tickets)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--slate-500);">
                <i class="fa-solid fa-file-circle-check" style="font-size: 2.5rem; color: var(--slate-300); margin-bottom: 1rem; display: block;"></i>
                <p style="font-size: 1.1rem; font-weight: 600; color: var(--navy-dark);">No Active Service Tickets</p>
                <p style="font-size: 0.9rem; margin-bottom: 1.5rem;">All hospital equipment is currently operating normally.</p>
                <a href="book_service.php" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i> Log Equipment Maintenance Ticket</a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket Code</th>
                        <th>Equipment & S/N</th>
                        <th>Service Type</th>
                        <th>Urgency</th>
                        <th>Assigned BME Engineer</th>
                        <th>Status</th>
                        <th>Date Booked</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td>
                                <strong><a href="ticket_details.php?id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['ticket_code']); ?></a></strong>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--navy-dark);"><?php echo htmlspecialchars($t['equipment_name']); ?></div>
                                <div style="font-size: 0.78rem; color: var(--slate-500);">SN: <?php echo htmlspecialchars($t['serial_number']); ?></div>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; font-weight: 500;">
                                    <?php 
                                    $types = [
                                        'breakdown_repair' => 'Breakdown Repair',
                                        'preventive_maintenance' => 'Preventive Maint.',
                                        'safety_calibration' => 'Safety & Calibration',
                                        'installation' => 'Installation / Setup'
                                    ];
                                    echo $types[$t['service_type']] ?? $t['service_type'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $t['urgency']; ?>"><?php echo strtoupper($t['urgency']); ?></span>
                            </td>
                            <td>
                                <?php if ($t['engineer_name']): ?>
                                    <div style="font-weight: 600; color: var(--slate-800);"><i class="fa-solid fa-user-doctor" style="color: var(--cyan-primary);"></i> <?php echo htmlspecialchars($t['engineer_name']); ?></div>
                                <?php else: ?>
                                    <span style="font-style: italic; color: var(--slate-400);">Awaiting Dispatch</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--slate-500);">
                                <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
                            </td>
                            <td>
                                <a href="ticket_details.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
