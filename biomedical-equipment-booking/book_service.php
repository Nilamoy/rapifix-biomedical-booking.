<?php
$pageTitle = "Book Biomedical Engineer Service Callout";
require_once __DIR__ . '/includes/header.php';

// Require hospital login
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'hospital') {
    header("Location: login.php");
    exit;
}

$hospId = $_SESSION['user']['hospital_id'] ?? null;
if (!$hospId) {
    $stmt = $db->prepare("SELECT id FROM hospitals WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $h = $stmt->fetch();
    $hospId = $h['id'] ?? 1;
}

// Fetch Hospital Equipment List
$stmt = $db->prepare("SELECT * FROM equipment WHERE hospital_id = ? ORDER BY equipment_name ASC");
$stmt->execute([$hospId]);
$equipments = $stmt->fetchAll();

$error = '';
$success = '';
$ticketCode = '';

// Pre-fill parameters from URL if any
$preCategory = $_GET['category'] ?? '';
$preUrgency = $_GET['urgency'] ?? 'high';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentId = intval($_POST['equipment_id'] ?? 0);
    $serviceType = $_POST['service_type'] ?? 'breakdown_repair';
    $urgency = $_POST['urgency'] ?? 'high';
    $faultDesc = trim($_POST['fault_description'] ?? '');
    $errorCode = trim($_POST['error_code'] ?? '');
    $prefDate = $_POST['preferred_date'] ?? date('Y-m-d');

    // If new equipment entered on the fly
    if ($equipmentId === 0 && !empty($_POST['new_equipment_name'])) {
        $newEqName = trim($_POST['new_equipment_name']);
        $newCat = $_POST['new_category'] ?? 'General';
        $newModel = trim($_POST['new_brand_model'] ?? 'Standard');
        $newSN = trim($_POST['new_serial_number'] ?? ('SN-' . rand(10000, 99999)));
        $newDept = trim($_POST['new_department'] ?? 'ICU');

        $insEq = $db->prepare("INSERT INTO equipment (hospital_id, equipment_name, category, brand_model, serial_number, department, status) VALUES (?, ?, ?, ?, ?, ?, 'faulty')");
        $insEq->execute([$hospId, $newEqName, $newCat, $newModel, $newSN, $newDept]);
        $equipmentId = $db->lastInsertId();
    }

    if ($equipmentId <= 0 || empty($faultDesc)) {
        $error = "Please select an equipment item and describe the fault/symptom.";
    } else {
        try {
            $tCode = 'TICK-' . rand(10000, 99999);
            
            // Auto dispatch match lookup for suitable engineer in city
            $engStmt = $db->query("SELECT id FROM engineers WHERE availability_status = 'available' LIMIT 1");
            $engMatch = $engStmt->fetch();
            $assignedEngId = $engMatch ? $engMatch['id'] : null;
            $initialStatus = $assignedEngId ? 'assigned' : 'pending';

            $stmt = $db->prepare("
                INSERT INTO service_tickets (ticket_code, hospital_id, equipment_id, engineer_id, service_type, urgency, fault_description, error_code, preferred_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tCode, $hospId, $equipmentId, $assignedEngId, $serviceType, $urgency, $faultDesc, $errorCode, $prefDate, $initialStatus]);
            $ticketId = $db->lastInsertId();

            // Insert initial timeline entry
            $hospName = $_SESSION['user']['hospital_name'] ?? $_SESSION['user']['full_name'];
            $note = "Service request logged. " . ($assignedEngId ? "Auto-matched & assigned to certified Biomedical Field Engineer." : "Awaiting dispatcher assignment.");
            $db->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, 'Hospital Admin', ?)")
               ->execute([$ticketId, $hospName, $note]);

            // Update equipment status
            $db->prepare("UPDATE equipment SET status = 'faulty' WHERE id = ?")->execute([$equipmentId]);

            header("Location: ticket_details.php?id=" . $ticketId . "&booked=1");
            exit;
        } catch (Exception $ex) {
            $error = "Failed to book ticket: " . $ex->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 800px; padding-top: 2rem; padding-bottom: 4rem;">
    <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-md); padding: 2.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid var(--slate-200); padding-bottom: 1.25rem;">
            <div>
                <h1 style="font-size: 1.8rem; margin-bottom: 0.25rem;">Book Biomedical Engineer</h1>
                <p style="color: var(--slate-500); font-size: 0.9rem;">Schedule emergency breakdown callout, preventive maintenance, or safety calibration.</p>
            </div>
            <div class="brand-icon" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-user-gear"></i>
            </div>
        </div>

        <?php if ($error): ?>
            <div style="padding: 0.8rem 1rem; background: var(--critical-bg); color: var(--critical-red); border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.25rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="book_service.php" method="POST">
            <!-- Step 1: Select Equipment -->
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.1rem; color: var(--navy-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 26px; height: 26px; background: var(--cyan-primary); color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">1</span>
                    Select Hospital Equipment
                </h3>

                <div class="form-group">
                    <label class="form-label">Registered Medical Equipment</label>
                    <select name="equipment_id" id="equipment_id" class="form-control" onchange="toggleNewEquipmentForm(this.value)">
                        <option value="">-- Choose from Equipment Inventory --</option>
                        <?php foreach ($equipments as $eq): ?>
                            <option value="<?php echo $eq['id']; ?>" data-dept="<?php echo htmlspecialchars($eq['department']); ?>">
                                <?php echo htmlspecialchars($eq['equipment_name']); ?> (<?php echo htmlspecialchars($eq['brand_model']); ?> - S/N: <?php echo htmlspecialchars($eq['serial_number']); ?>) [Dept: <?php echo htmlspecialchars($eq['department']); ?>]
                            </option>
                        <?php endforeach; ?>
                        <option value="0" style="font-weight: 700; color: var(--cyan-primary);">+ Register & Book New Equipment On-the-Fly</option>
                    </select>
                </div>

                <!-- On-the-fly Equipment Creation Form -->
                <div id="newEquipmentForm" style="display: none; background: var(--slate-50); border: 1px dashed var(--cyan-primary); padding: 1.25rem; border-radius: var(--radius-md); margin-top: 1rem;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--navy-dark);">Quick Equipment Registration</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Equipment Name</label>
                            <input type="text" name="new_equipment_name" class="form-control" placeholder="e.g. Infusion Pump">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Brand & Model</label>
                            <input type="text" name="new_brand_model" class="form-control" placeholder="e.g. Baxter Spectrum IQ">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="new_serial_number" class="form-control" placeholder="e.g. SN-993821">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <input type="text" name="new_department" class="form-control" placeholder="e.g. NICU Bed 12">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Service Parameters -->
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.1rem; color: var(--navy-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 26px; height: 26px; background: var(--cyan-primary); color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">2</span>
                    Service Request Details
                </h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Type of Service Required</label>
                        <select name="service_type" class="form-control">
                            <option value="breakdown_repair">Breakdown & Fault Repair</option>
                            <option value="preventive_maintenance">Preventive Maintenance (PM Periodic)</option>
                            <option value="safety_calibration">Electrical Safety & Calibration Inspection</option>
                            <option value="installation">New Equipment Unboxing & Calibration</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Urgency Level</label>
                        <select name="urgency" class="form-control">
                            <option value="critical" <?php echo $preUrgency === 'critical' ? 'selected' : ''; ?>>🔴 CRITICAL (Active Patient Room / Emergency Breakdown)</option>
                            <option value="high" <?php echo $preUrgency === 'high' ? 'selected' : ''; ?>>🟠 HIGH (Required within 24 Hours)</option>
                            <option value="medium" <?php echo $preUrgency === 'medium' ? 'selected' : ''; ?>>🟡 MEDIUM (Scheduled PM Maintenance)</option>
                            <option value="low">⚪ LOW (Routine Check / Non-Urgent)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">On-Screen Error Code (If Any)</label>
                        <input type="text" name="error_code" class="form-control" placeholder="e.g. ERR-304-PRESS or ERR-09">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preferred Date of Service</label>
                        <input type="date" name="preferred_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Detailed Fault Symptom / Description</label>
                    <textarea name="fault_description" class="form-control" placeholder="Describe the symptom, alarm sounds, physical damage, or test parameters exceeding thresholds..." required></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="hospital_dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem;">
                    <i class="fa-solid fa-paper-plane"></i> Submit & Dispatch Callout
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleNewEquipmentForm(val) {
    const f = document.getElementById('newEquipmentForm');
    if (val === '0') {
        f.style.display = 'block';
    } else {
        f.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
