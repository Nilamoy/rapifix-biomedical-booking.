<?php
$pageTitle = "Admin Dispatch Console";
require_once __DIR__ . '/includes/header.php';

// Auth check (Admin only)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = '';
$err = '';

// Handle Engineer Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_engineer'])) {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $engineerId = intval($_POST['engineer_id'] ?? 0);

    if ($ticketId > 0 && $engineerId > 0) {
        // Fetch engineer details for logging
        $eStmt = $db->prepare("SELECT u.full_name, e.specialization FROM engineers e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
        $eStmt->execute([$engineerId]);
        $engData = $eStmt->fetch();

        // Update ticket
        $db->prepare("UPDATE service_tickets SET engineer_id = ?, status = 'assigned' WHERE id = ?")->execute([$engineerId, $ticketId]);

        // Add timeline note
        $note = "Admin dispatched Eng. " . ($engData['full_name'] ?? 'Engineer') . " (" . ($engData['specialization'] ?? 'Specialist') . ") to handle this callout.";
        $db->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, 'System Dispatcher', 'Admin', ?)")
           ->execute([$ticketId, $note]);

        $msg = "Engineer successfully assigned to ticket!";
    }
}

// Handle Engineer Account Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_engineer_approval'])) {
    $targetUserId = intval($_POST['user_id'] ?? 0);
    $actionType = $_POST['action_type'] ?? '';

    if ($targetUserId > 0) {
        if ($actionType === 'approve') {
            $db->prepare("UPDATE users SET approval_status = 'approved' WHERE id = ? AND role = 'engineer'")->execute([$targetUserId]);
            $db->prepare("UPDATE engineers SET availability_status = 'available' WHERE user_id = ?")->execute([$targetUserId]);
            $msg = "Biomedical Engineer account successfully approved & granted login access!";
        } elseif ($actionType === 'reject') {
            $db->prepare("UPDATE users SET approval_status = 'rejected' WHERE id = ? AND role = 'engineer'")->execute([$targetUserId]);
            $msg = "Biomedical Engineer application declined.";
        }
    }
}

// System Metrics
$totalTickets = $db->query("SELECT COUNT(*) FROM service_tickets")->fetchColumn();
$unassignedCount = $db->query("SELECT COUNT(*) FROM service_tickets WHERE engineer_id IS NULL OR status = 'pending'")->fetchColumn();
$totalEngineers = $db->query("SELECT COUNT(*) FROM engineers")->fetchColumn();
$totalHospitals = $db->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
$pendingEngineerCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'engineer' AND approval_status = 'pending'")->fetchColumn();

// Fetch Pending Engineer Registrations Requiring Approval
$pendingEngineers = $db->query("
    SELECT u.id as user_id, u.full_name, u.email, u.phone, u.created_at, e.specialization, e.certification, e.years_experience, e.city
    FROM users u
    JOIN engineers e ON e.user_id = u.id
    WHERE u.role = 'engineer' AND u.approval_status = 'pending'
    ORDER BY u.created_at DESC
")->fetchAll();

// Fetch Pending / Unassigned Tickets
$unassignedTickets = $db->query("
    SELECT t.*, h.hospital_name, h.city, e.equipment_name, e.category, e.serial_number
    FROM service_tickets t
    JOIN hospitals h ON t.hospital_id = h.id
    JOIN equipment e ON t.equipment_id = e.id
    WHERE t.engineer_id IS NULL OR t.status = 'pending'
    ORDER BY CASE WHEN t.urgency = 'critical' THEN 0 WHEN t.urgency = 'high' THEN 1 ELSE 2 END, t.created_at DESC
")->fetchAll();

// Fetch All Service Tickets
$allTickets = $db->query("
    SELECT t.*, h.hospital_name, e.equipment_name, eng_u.full_name as engineer_name
    FROM service_tickets t
    JOIN hospitals h ON t.hospital_id = h.id
    JOIN equipment e ON t.equipment_id = e.id
    LEFT JOIN engineers eng ON t.engineer_id = eng.id
    LEFT JOIN users eng_u ON eng.user_id = eng_u.id
    ORDER BY t.created_at DESC
")->fetchAll();

// Fetch All Engineers
$engineersList = $db->query("
    SELECT e.*, u.full_name, u.email, u.phone
    FROM engineers e
    JOIN users u ON e.user_id = u.id
")->fetchAll();
?>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h1 class="page-title">Admin & Dispatch Control Console</h1>
            <p class="page-subtitle">Manage emergency callouts, dispatch qualified Biomedical Engineers, and oversee system operations.</p>
        </div>
        <a href="register.php" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Add New Engineer Account</a>
    </div>

    <?php if ($msg): ?>
        <div style="padding: 0.8rem 1rem; background: var(--success-bg); color: #047857; border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon icon-blue">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $totalTickets; ?></div>
                <div class="metric-lbl">Total Callout Tickets</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon icon-amber">
                <i class="fa-solid fa-headset"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $unassignedCount; ?></div>
                <div class="metric-lbl">Awaiting Engineer Dispatch</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon icon-emerald">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $totalEngineers; ?></div>
                <div class="metric-lbl">Certified Field Engineers</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon icon-red">
                <i class="fa-solid fa-user-clock"></i>
            </div>
            <div>
                <div class="metric-val"><?php echo $pendingEngineerCount; ?></div>
                <div class="metric-lbl">Pending BME Approvals</div>
            </div>
        </div>
    </div>

    <!-- Pending Engineer Approvals Section -->
    <?php if (!empty($pendingEngineers)): ?>
        <div class="card-table-wrapper" style="border: 2px solid var(--cyan-primary); margin-bottom: 2.5rem;">
            <div class="table-header" style="background: var(--cyan-glow);">
                <h3 class="table-title" style="color: var(--navy-dark);"><i class="fa-solid fa-user-shield" style="color: var(--cyan-primary);"></i> Pending Biomedical Engineer Registration Approvals (<?php echo count($pendingEngineers); ?>)</h3>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Engineer Name</th>
                        <th>Email & Phone</th>
                        <th>Specialization</th>
                        <th>Certification</th>
                        <th>Exp. & Location</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingEngineers as $pe): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pe['full_name']); ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars($pe['email']); ?></div>
                                <div style="font-size: 0.78rem; color: var(--slate-500);"><?php echo htmlspecialchars($pe['phone']); ?></div>
                            </td>
                            <td><span style="font-weight: 600; color: var(--navy-dark);"><?php echo htmlspecialchars($pe['specialization']); ?></span></td>
                            <td style="font-size: 0.85rem; color: var(--slate-600);"><?php echo htmlspecialchars($pe['certification']); ?></td>
                            <td><?php echo $pe['years_experience']; ?> Yrs (<?php echo htmlspecialchars($pe['city']); ?>)</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form action="admin_dashboard.php" method="POST">
                                        <input type="hidden" name="action_engineer_approval" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $pe['user_id']; ?>">
                                        <input type="hidden" name="action_type" value="approve">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-check"></i> Approve & Activate</button>
                                    </form>
                                    <form action="admin_dashboard.php" method="POST">
                                        <input type="hidden" name="action_engineer_approval" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $pe['user_id']; ?>">
                                        <input type="hidden" name="action_type" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-user-xmark"></i> Decline</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Unassigned Tickets Dispatch Console -->
    <?php if (!empty($unassignedTickets)): ?>
        <div class="card-table-wrapper" style="border: 2px solid var(--warning-amber); margin-bottom: 2.5rem;">
            <div class="table-header" style="background: #fffbeb;">
                <h3 class="table-title" style="color: #b45309;"><i class="fa-solid fa-bell-concierge"></i> Unassigned Callout Requests Requiring Dispatch</h3>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Ref Code</th>
                        <th>Hospital Facility</th>
                        <th>Equipment & Category</th>
                        <th>Urgency</th>
                        <th>Fault Note</th>
                        <th>Assign Engineer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassignedTickets as $ut): ?>
                        <tr>
                            <td><strong><a href="ticket_details.php?id=<?php echo $ut['id']; ?>"><?php echo htmlspecialchars($ut['ticket_code']); ?></a></strong></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($ut['hospital_name']); ?></strong></div>
                                <div style="font-size: 0.78rem; color: var(--slate-500);"><?php echo htmlspecialchars($ut['city']); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($ut['equipment_name']); ?></div>
                                <div style="font-size: 0.78rem; color: var(--slate-500);"><?php echo htmlspecialchars($ut['category']); ?></div>
                            </td>
                            <td><span class="badge badge-<?php echo $ut['urgency']; ?>"><?php echo strtoupper($ut['urgency']); ?></span></td>
                            <td style="max-width: 250px; font-size: 0.85rem;"><?php echo htmlspecialchars($ut['fault_description']); ?></td>
                            <td>
                                <form action="admin_dashboard.php" method="POST" style="display: flex; gap: 0.5rem;">
                                    <input type="hidden" name="assign_engineer" value="1">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ut['id']; ?>">
                                    <select name="engineer_id" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.82rem; width: auto;" required>
                                        <option value="">-- Choose Engineer --</option>
                                        <?php foreach ($engineersList as $eng): ?>
                                            <option value="<?php echo $eng['id']; ?>">
                                                <?php echo htmlspecialchars($eng['full_name']); ?> (<?php echo htmlspecialchars($eng['specialization']); ?>) [<?php echo strtoupper($eng['availability_status']); ?>]
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-check"></i> Assign</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Master Tickets Table -->
    <div class="card-table-wrapper">
        <div class="table-header">
            <h3 class="table-title"><i class="fa-solid fa-list" style="color: var(--cyan-primary);"></i> Master Callout Log (All Tickets)</h3>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Hospital</th>
                    <th>Equipment</th>
                    <th>Urgency</th>
                    <th>Assigned BME</th>
                    <th>Status</th>
                    <th>Date Booked</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allTickets as $t): ?>
                    <tr>
                        <td><strong><a href="ticket_details.php?id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['ticket_code']); ?></a></strong></td>
                        <td><?php echo htmlspecialchars($t['hospital_name']); ?></td>
                        <td><?php echo htmlspecialchars($t['equipment_name']); ?></td>
                        <td><span class="badge badge-<?php echo $t['urgency']; ?>"><?php echo strtoupper($t['urgency']); ?></span></td>
                        <td>
                            <?php if ($t['engineer_name']): ?>
                                <span style="font-weight: 600; color: var(--navy-dark);"><?php echo htmlspecialchars($t['engineer_name']); ?></span>
                            <?php else: ?>
                                <span style="color: var(--slate-400); font-style: italic;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span></td>
                        <td style="font-size: 0.85rem; color: var(--slate-500);"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                        <td><a href="ticket_details.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
