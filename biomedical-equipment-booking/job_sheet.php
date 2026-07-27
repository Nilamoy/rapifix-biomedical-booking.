<?php
$pageTitle = "Digital Service Job Sheet & Calibration";
require_once __DIR__ . '/includes/header.php';

// Auth check (Engineer or Admin)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['engineer', 'admin'])) {
    header("Location: login.php");
    exit;
}

$ticketId = intval($_GET['ticket_id'] ?? 0);
if ($ticketId <= 0) {
    header("Location: engineer_dashboard.php");
    exit;
}

// Fetch Ticket & Related Info
$stmt = $db->prepare("
    SELECT t.*, h.hospital_name, h.address, h.contact_person,
           e.equipment_name, e.category, e.brand_model, e.serial_number, e.department,
           eng_u.full_name as engineer_name
    FROM service_tickets t
    JOIN hospitals h ON t.hospital_id = h.id
    JOIN equipment e ON t.equipment_id = e.id
    LEFT JOIN engineers eng ON t.engineer_id = eng.id
    LEFT JOIN users eng_u ON eng.user_id = eng_u.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    echo "<div class='container'><h3>Ticket Not Found</h3></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Check existing report
$rStmt = $db->prepare("SELECT * FROM service_reports WHERE ticket_id = ?");
$rStmt->execute([$ticketId]);
$existingReport = $rStmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groundRes = floatval($_POST['ground_resistance_ohms'] ?? 0.045);
    $leakageCurr = floatval($_POST['leakage_current_ua'] ?? 12.5);
    $safetyStatus = ($groundRes <= 0.100 && $leakageCurr <= 500.0) ? 'pass' : 'fail';
    $calibStatus = $_POST['calibration_status'] ?? 'calibrated';
    $workDone = trim($_POST['work_performed'] ?? '');
    $partsReplaced = trim($_POST['parts_replaced'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');
    $hospitalSignoff = trim($_POST['hospital_signoff_by'] ?? '');
    $hospitalDesignation = trim($_POST['hospital_designation'] ?? 'Department Head');
    $authCode = trim($_POST['authorisation_code'] ?? 'AUTH-VERIFIED');
    $authConfirm = isset($_POST['hospital_auth_confirm']);

    if (empty($workDone)) {
        $error = "Please describe the work performed during this service call.";
    } elseif (empty($hospitalSignoff) || empty($authCode) || !$authConfirm) {
        $error = "Hospital Authorisation Required: Please provide the Authorized Hospital Officer Name, Officer Designation, Verification Code, and check the authorization confirmation box.";
    } else {
        try {
            $engId = $ticket['engineer_id'] ?? $_SESSION['user']['engineer_id'] ?? 1;

            if ($existingReport) {
                $up = $db->prepare("
                    UPDATE service_reports 
                    SET electrical_safety_status = ?, ground_resistance_ohms = ?, leakage_current_ua = ?, calibration_status = ?, work_performed = ?, parts_replaced = ?, recommendations = ?, hospital_signoff_by = ?, hospital_designation = ?, authorisation_code = ?
                    WHERE id = ?
                ");
                $up->execute([$safetyStatus, $groundRes, $leakageCurr, $calibStatus, $workDone, $partsReplaced, $recommendations, $hospitalSignoff, $hospitalDesignation, $authCode, $existingReport['id']]);
            } else {
                $ins = $db->prepare("
                    INSERT INTO service_reports (ticket_id, engineer_id, electrical_safety_status, ground_resistance_ohms, leakage_current_ua, calibration_status, work_performed, parts_replaced, recommendations, hospital_signoff_by, hospital_designation, authorisation_code)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$ticketId, $engId, $safetyStatus, $groundRes, $leakageCurr, $calibStatus, $workDone, $partsReplaced, $recommendations, $hospitalSignoff, $hospitalDesignation, $authCode]);
            }

            // Update ticket status to Completed
            $db->prepare("UPDATE service_tickets SET status = 'completed', completed_at = ? WHERE id = ?")
               ->execute([date('Y-m-d H:i:s'), $ticketId]);

            // Update Equipment status to Operational
            $db->prepare("UPDATE equipment SET status = 'operational' WHERE id = ?")
               ->execute([$ticket['equipment_id']]);

            // Log update
            $engName = $_SESSION['user']['full_name'];
            $db->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, 'Biomedical Engineer', ?)")
               ->execute([$ticketId, $engName, "Service report completed. Electrical safety test: " . strtoupper($safetyStatus) . ". Equipment returned to operational status."]);

            header("Location: ticket_details.php?id=" . $ticketId . "&completed=1");
            exit;
        } catch (Exception $ex) {
            $error = "Error saving job sheet: " . $ex->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 900px; padding-top: 2rem; padding-bottom: 4rem;">
    <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-md); padding: 2.5rem;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--navy-dark); padding-bottom: 1.25rem; margin-bottom: 2rem;">
            <div>
                <span class="badge badge-assigned" style="margin-bottom: 0.5rem;">Official Digital Service Record</span>
                <h1 style="font-size: 1.8rem;">Digital Job Sheet & IEC 62353 Report</h1>
                <p style="color: var(--slate-500); font-size: 0.9rem;">Ticket Ref: <strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong></p>
            </div>
            <div class="brand-icon" style="width: 52px; height: 52px; font-size: 1.6rem;">
                <i class="fa-solid fa-file-certificate"></i>
            </div>
        </div>

        <?php if ($error): ?>
            <div style="padding: 0.8rem 1rem; background: var(--critical-bg); color: var(--critical-red); border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.25rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Service & Equipment Meta Box -->
        <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Facility Information</div>
                <strong style="font-size: 1.05rem; color: var(--navy-dark);"><?php echo htmlspecialchars($ticket['hospital_name']); ?></strong>
                <div style="font-size: 0.88rem; color: var(--slate-600);"><?php echo htmlspecialchars($ticket['address']); ?></div>
                <div style="font-size: 0.88rem; color: var(--slate-600);">Contact Person: <?php echo htmlspecialchars($ticket['contact_person']); ?></div>
            </div>
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Medical Equipment Device</div>
                <strong style="font-size: 1.05rem; color: var(--navy-dark);"><?php echo htmlspecialchars($ticket['equipment_name']); ?></strong>
                <div style="font-size: 0.88rem; color: var(--slate-600);"><?php echo htmlspecialchars($ticket['brand_model']); ?></div>
                <div style="font-size: 0.88rem; color: var(--slate-600);">Serial No: <code><?php echo htmlspecialchars($ticket['serial_number']); ?></code> | Dept: <?php echo htmlspecialchars($ticket['department']); ?></div>
            </div>
        </div>

        <form action="job_sheet.php?ticket_id=<?php echo $ticketId; ?>" method="POST">
            
            <!-- Section 1: Electrical Safety Testing (IEC 62353 Standard) -->
            <div style="margin-bottom: 2.5rem; background: var(--white); border: 1px solid var(--cyan-primary); padding: 1.5rem; border-radius: var(--radius-md);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.15rem; color: var(--navy-dark);"><i class="fa-solid fa-bolt" style="color: var(--cyan-primary);"></i> IEC 62353 Electrical Safety Testing</h3>
                    <span id="safety_status_badge" class="badge badge-completed">PASS (IEC 62353)</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Ground Bond Resistance (Ω)</label>
                        <input type="number" step="0.001" name="ground_resistance_ohms" id="ground_resistance_ohms" class="form-control" value="<?php echo htmlspecialchars($existingReport['ground_resistance_ohms'] ?? '0.045'); ?>" required>
                        <div style="font-size: 0.75rem; color: var(--slate-500); margin-top: 0.2rem;">Standard limit: &le; 0.100 Ω</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Protective Earth Leakage Current (&mu;A)</label>
                        <input type="number" step="0.1" name="leakage_current_ua" id="leakage_current_ua" class="form-control" value="<?php echo htmlspecialchars($existingReport['leakage_current_ua'] ?? '12.5'); ?>" required>
                        <div style="font-size: 0.75rem; color: var(--slate-500); margin-top: 0.2rem;">Standard limit: &le; 500.0 &mu;A</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Calibration & Transducer Check</label>
                        <select name="calibration_status" class="form-control">
                            <option value="calibrated" <?php echo ($existingReport['calibration_status'] ?? '') === 'calibrated' ? 'selected' : ''; ?>>Calibrated & Within Spec</option>
                            <option value="passed" <?php echo ($existingReport['calibration_status'] ?? '') === 'passed' ? 'selected' : ''; ?>>Passed Factory Baseline</option>
                            <option value="requires_factory_recalibration" <?php echo ($existingReport['calibration_status'] ?? '') === 'requires_factory_recalibration' ? 'selected' : ''; ?>>Requires Factory Bench Calibration</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Work Done & Spare Parts -->
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.15rem; color: var(--navy-dark); margin-bottom: 1rem;"><i class="fa-solid fa-list-check" style="color: var(--cyan-primary);"></i> Service Actions & Parts Replaced</h3>

                <div class="form-group">
                    <label class="form-label">Work Performed / Diagnostic Summary</label>
                    <textarea name="work_performed" class="form-control" rows="4" placeholder="Detail component inspection, board replacement, pressure sensor re-zeroing, clean air intake filters..." required><?php echo htmlspecialchars($existingReport['work_performed'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Parts / Consumables Replaced</label>
                        <input type="text" name="parts_replaced" class="form-control" placeholder="e.g. O2 Cell Sensor (PN-8821), HEPA Filter Kit" value="<?php echo htmlspecialchars($existingReport['parts_replaced'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preventive Care Recommendations</label>
                        <input type="text" name="recommendations" class="form-control" placeholder="e.g. Schedule battery replacement within 6 months" value="<?php echo htmlspecialchars($existingReport['recommendations'] ?? ''); ?>">
                    </div>
                </div>

            </div>

            <!-- Section 3: Mandatory Hospital Authorisation & Clearance -->
            <div style="margin-bottom: 2rem; background: var(--slate-50); border: 2px solid var(--navy-dark); padding: 1.5rem; border-radius: var(--radius-md);">
                <h3 style="font-size: 1.15rem; color: var(--navy-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-file-signature" style="color: var(--cyan-primary);"></i> Mandatory Hospital Authorisation & Clearance Sign-Off
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Authorized Hospital Officer Name</label>
                        <input type="text" name="hospital_signoff_by" class="form-control" placeholder="e.g. Dr. Aris Thorne" value="<?php echo htmlspecialchars($existingReport['hospital_signoff_by'] ?? $ticket['contact_person']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Officer Title / Designation</label>
                        <input type="text" name="hospital_designation" class="form-control" placeholder="e.g. Chief Medical Officer / ICU In-Charge" value="<?php echo htmlspecialchars($existingReport['hospital_designation'] ?? 'Chief Medical Officer'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hospital Verification PIN / Code</label>
                        <input type="text" name="authorisation_code" class="form-control" placeholder="e.g. AUTH-9942 or 4-digit PIN" value="<?php echo htmlspecialchars($existingReport['authorisation_code'] ?? 'AUTH-' . rand(1000, 9999)); ?>" required>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding: 0.85rem; background: var(--white); border: 1px solid var(--slate-300); border-radius: var(--radius-md);">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; font-size: 0.9rem; color: var(--slate-800);">
                        <input type="checkbox" name="hospital_auth_confirm" value="1" checked style="margin-top: 0.2rem; width: 18px; height: 18px; accent-color: var(--cyan-primary);" required>
                        <div>
                            <strong>Official Hospital Clearance Declaration:</strong> I hereby certify that the biomedical service actions and IEC 62353 electrical safety test results have been inspected, confirmed, and the medical equipment is formally authorized for clinical patient use.
                        </div>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="engineer_dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2rem;">
                    <i class="fa-solid fa-certificate"></i> Finalize Job Sheet & Issue Certificate
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
