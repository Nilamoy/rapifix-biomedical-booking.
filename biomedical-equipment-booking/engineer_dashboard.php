<?php
$pageTitle = "Biomedical Field Engineer Portal";
require_once __DIR__ . '/includes/header.php';

// Auth check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'engineer') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$engId = $_SESSION['user']['engineer_id'] ?? null;

if (!$engId) {
    $stmt = $db->prepare("SELECT id, specialization, certification, availability_status FROM engineers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $e = $stmt->fetch();
    if ($e) {
        $engId = $e['id'];
        $_SESSION['user']['engineer_id'] = $e['id'];
    }
}

// Handle Status Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $newAvail = $_POST['availability_status'] ?? 'available';
    $db->prepare("UPDATE engineers SET availability_status = ? WHERE id = ?")->execute([$newAvail, $engId]);
}

// Fetch Engineer Profile
$stmt = $db->prepare("SELECT e.*, u.full_name, u.phone, u.email FROM engineers e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
$stmt->execute([$engId]);
$engineer = $stmt->fetch();

// Fetch Assigned Tickets
$stmt = $db->prepare("
    SELECT t.*, h.hospital_name, h.address as hosp_address, h.city as hosp_city, h.emergency_contact,
           e.equipment_name, e.category, e.brand_model, e.serial_number, e.department
    FROM service_tickets t
    JOIN hospitals h ON t.hospital_id = h.id
    JOIN equipment e ON t.equipment_id = e.id
    WHERE t.engineer_id = ?
    ORDER BY CASE WHEN t.status != 'completed' THEN 0 ELSE 1 END, t.created_at DESC
");
$stmt->execute([$engId]);
$assignedTickets = $stmt->fetchAll();

// Handle Quick Ticket Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket_status'])) {
    $tId = intval($_POST['ticket_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'en_route';
    $statusNote = trim($_POST['status_note'] ?? '');

    $db->prepare("UPDATE service_tickets SET status = ? WHERE id = ? AND engineer_id = ?")->execute([$newStatus, $tId, $engId]);

    $engName = $_SESSION['user']['full_name'];
    $note = "Status updated to [" . strtoupper(str_replace('_', ' ', $newStatus)) . "]. " . $statusNote;
    $db->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, 'Biomedical Engineer', ?)")
       ->execute([$tId, $engName, $note]);

    header("Location: engineer_dashboard.php");
    exit;
}
?>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h1 class="page-title">Field Engineer Workspace</h1>
            <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($engineer['full_name']); ?> (<?php echo htmlspecialchars($engineer['specialization']); ?>)</p>
        </div>
        
        <!-- Availability Switcher -->
        <form action="engineer_dashboard.php" method="POST" style="display: flex; align-items: center; gap: 0.75rem; background: var(--white); padding: 0.5rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <input type="hidden" name="update_availability" value="1">
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--slate-600);">Availability:</span>
            <select name="availability_status" onchange="this.form.submit()" class="form-control" style="padding: 0.35rem 0.6rem; font-size: 0.85rem; width: auto;">
                <option value="available" <?php echo $engineer['availability_status'] === 'available' ? 'selected' : ''; ?>>🟢 Available for Dispatch</option>
                <option value="on_site" <?php echo $engineer['availability_status'] === 'on_site' ? 'selected' : ''; ?>>🔵 On-Site Servicing</option>
                <option value="busy" <?php echo $engineer['availability_status'] === 'busy' ? 'selected' : ''; ?>>🟡 Busy / In Transit</option>
                <option value="offline" <?php echo $engineer['availability_status'] === 'offline' ? 'selected' : ''; ?>>⚪ Off-Duty</option>
            </select>
        </form>
    </div>

    <!-- Active Jobs Section -->
    <h3 style="font-size: 1.3rem; margin-bottom: 1.25rem; color: var(--navy-dark);"><i class="fa-solid fa-list-check" style="color: var(--cyan-primary);"></i> Assigned Work Orders & Callouts</h3>

    <?php if (empty($assignedTickets)): ?>
        <div style="background: var(--white); padding: 3rem; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <i class="fa-solid fa-user-check" style="font-size: 2.5rem; color: var(--success-emerald); margin-bottom: 1rem; display: block;"></i>
            <p style="font-size: 1.1rem; font-weight: 600;">No Pending Jobs Assigned</p>
            <p style="font-size: 0.9rem; color: var(--slate-500);">Your dispatch queue is currently clear. You will be notified when new hospital requests arrive.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem;">
            <?php foreach ($assignedTickets as $t): ?>
                <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-sm); overflow: hidden; display: flex; flex-direction: column;">
                    <div style="padding: 1.25rem; background: var(--navy-dark); color: var(--white); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.8rem; color: var(--cyan-light); font-weight: 700;"><?php echo htmlspecialchars($t['ticket_code']); ?></div>
                            <h4 style="color: var(--white); font-size: 1.1rem;"><?php echo htmlspecialchars($t['hospital_name']); ?></h4>
                        </div>
                        <span class="badge badge-<?php echo $t['urgency']; ?>"><?php echo strtoupper($t['urgency']); ?></span>
                    </div>

                    <div style="padding: 1.25rem; flex: 1;">
                        <div style="margin-bottom: 1rem;">
                            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--slate-400); font-weight: 700;">Equipment & Fault</div>
                            <strong style="color: var(--navy-dark); font-size: 1rem;"><?php echo htmlspecialchars($t['equipment_name']); ?></strong>
                            <div style="font-size: 0.85rem; color: var(--slate-600);"><?php echo htmlspecialchars($t['brand_model']); ?> | SN: <code><?php echo htmlspecialchars($t['serial_number']); ?></code></div>
                            <div style="font-size: 0.85rem; color: var(--slate-600);"><i class="fa-solid fa-hospital-user"></i> Dept: <?php echo htmlspecialchars($t['department']); ?></div>
                        </div>

                        <div style="background: var(--slate-50); padding: 0.85rem; border-radius: var(--radius-md); font-size: 0.88rem; color: var(--slate-700); margin-bottom: 1rem; border-left: 3px solid var(--cyan-primary);">
                            <strong>Fault Note:</strong> <?php echo htmlspecialchars($t['fault_description']); ?>
                            <?php if ($t['error_code']): ?>
                                <div style="margin-top: 0.4rem; color: var(--critical-red); font-weight: 600;">Code: <?php echo htmlspecialchars($t['error_code']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div style="font-size: 0.85rem; color: var(--slate-500); margin-bottom: 1rem;">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($t['hosp_address']); ?>, <?php echo htmlspecialchars($t['hosp_city']); ?><br>
                            <i class="fa-solid fa-phone"></i> Emergency Contact: <?php echo htmlspecialchars($t['emergency_contact']); ?>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid var(--slate-200);">
                            <span class="badge badge-<?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span>
                            
                            <div style="display: flex; gap: 0.4rem;">
                                <a href="ticket_details.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View Log</a>
                                <a href="download_report.php?id=<?php echo $t['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="Print/Download PDF Report"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                                <?php if ($t['status'] !== 'completed'): ?>
                                    <a href="job_sheet.php?ticket_id=<?php echo $t['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-signature"></i> Digital Job Sheet</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
