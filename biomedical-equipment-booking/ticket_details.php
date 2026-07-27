<?php
$pageTitle = "Service Ticket Details";
require_once __DIR__ . '/includes/header.php';

$ticketId = intval($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    header("Location: index.php");
    exit;
}

// Handle Adding Timeline Update Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_timeline_note'])) {
    $note = trim($_POST['status_note'] ?? '');
    if (!empty($note) && isset($_SESSION['user'])) {
        $author = $_SESSION['user']['full_name'];
        $role = ucfirst($_SESSION['user']['role']);
        $db->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, ?, ?)")
           ->execute([$ticketId, $author, $role, $note]);
        header("Location: ticket_details.php?id=" . $ticketId);
        exit;
    }
}

// Fetch Full Ticket Info
$stmt = $db->prepare("
    SELECT t.*, h.hospital_name, h.address as hosp_address, h.city as hosp_city, h.emergency_contact, h.contact_person,
           e.equipment_name, e.category, e.brand_model, e.serial_number, e.department, e.warranty_status,
           eng_u.full_name as engineer_name, eng.specialization as engineer_spec, eng_u.phone as engineer_phone, eng.rating as engineer_rating
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

// Fetch Timeline Updates
$uStmt = $db->prepare("SELECT * FROM ticket_updates WHERE ticket_id = ? ORDER BY created_at ASC");
$uStmt->execute([$ticketId]);
$timeline = $uStmt->fetchAll();

// Fetch Completed Service Report (Job Sheet) if exists
$rStmt = $db->prepare("SELECT * FROM service_reports WHERE ticket_id = ?");
$rStmt->execute([$ticketId]);
$report = $rStmt->fetch();
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    
    <!-- Top Header Bar -->
    <div style="background: var(--navy-dark); color: var(--white); padding: 1.75rem 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: var(--shadow-md);">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-<?php echo $ticket['urgency']; ?>"><?php echo strtoupper($ticket['urgency']); ?> URGENCY</span>
                <span style="color: var(--cyan-light); font-weight: 700; font-family: monospace; font-size: 1.1rem;"><?php echo htmlspecialchars($ticket['ticket_code']); ?></span>
            </div>
            <h1 style="color: var(--white); font-size: 1.8rem;"><?php echo htmlspecialchars($ticket['equipment_name']); ?> Callout</h1>
            <div style="color: var(--slate-300); font-size: 0.9rem;">
                <i class="fa-solid fa-hospital"></i> <?php echo htmlspecialchars($ticket['hospital_name']); ?> | Dept: <?php echo htmlspecialchars($ticket['department']); ?>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span class="badge badge-<?php echo $ticket['status']; ?>" style="font-size: 0.95rem; padding: 0.4rem 1rem;">
                STATUS: <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
            </span>

            <a href="download_report.php?id=<?php echo $ticketId; ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #fff; border-color: rgba(255,255,255,0.3);">
                <i class="fa-solid fa-file-pdf"></i> Download PDF Copy
            </a>
            
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'engineer' && $ticket['status'] !== 'completed'): ?>
                <a href="job_sheet.php?ticket_id=<?php echo $ticketId; ?>" class="btn btn-primary"><i class="fa-solid fa-file-signature"></i> Fill Job Sheet</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Pipeline Steps -->
    <div style="background: var(--white); border-radius: var(--radius-md); border: 1px solid var(--slate-200); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
            <?php
            $statuses = ['pending', 'assigned', 'en_route', 'diagnosing', 'completed'];
            $currentIdx = array_search($ticket['status'], $statuses);
            if ($currentIdx === false && $ticket['status'] === 'waiting_parts') $currentIdx = 3;
            
            foreach ($statuses as $idx => $st):
                $isDone = $idx <= $currentIdx;
                $isCurrent = $idx === $currentIdx;
            ?>
                <div style="text-align: center; flex: 1; z-index: 1;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: <?php echo $isDone ? 'var(--cyan-primary)' : 'var(--slate-200)'; ?>; color: <?php echo $isDone ? '#fff' : 'var(--slate-500)'; ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: 700;">
                        <?php echo $isDone ? '<i class="fa-solid fa-check"></i>' : ($idx + 1); ?>
                    </div>
                    <div style="font-size: 0.82rem; font-weight: <?php echo $isCurrent ? '700' : '500'; ?>; color: <?php echo $isCurrent ? 'var(--navy-dark)' : 'var(--slate-500)'; ?>;">
                        <?php echo strtoupper(str_replace('_', ' ', $st)); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- Main Details Column -->
        <div>
            
            <!-- Equipment & Fault Details -->
            <div style="background: var(--white); border-radius: var(--radius-md); border: 1px solid var(--slate-200); padding: 1.5rem; margin-bottom: 2rem;">
                <h3 style="font-size: 1.2rem; color: var(--navy-dark); margin-bottom: 1rem;"><i class="fa-solid fa-microscope" style="color: var(--cyan-primary);"></i> Medical Equipment & Fault Profile</h3>
                
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--slate-500); text-transform: uppercase;">Equipment Name</div>
                        <strong><?php echo htmlspecialchars($ticket['equipment_name']); ?></strong>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--slate-500); text-transform: uppercase;">Brand & Model</div>
                        <strong><?php echo htmlspecialchars($ticket['brand_model']); ?></strong>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--slate-500); text-transform: uppercase;">Serial Number</div>
                        <code><?php echo htmlspecialchars($ticket['serial_number']); ?></code>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--slate-500); text-transform: uppercase;">Service Type</div>
                        <strong><?php echo strtoupper(str_replace('_', ' ', $ticket['service_type'])); ?></strong>
                    </div>
                </div>

                <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid var(--cyan-primary);">
                    <div style="font-size: 0.8rem; color: var(--slate-500); text-transform: uppercase; font-weight: 700; margin-bottom: 0.3rem;">Logged Fault Description</div>
                    <p style="font-size: 0.95rem; color: var(--slate-800);"><?php echo htmlspecialchars($ticket['fault_description']); ?></p>
                    <?php if ($ticket['error_code']): ?>
                        <div style="margin-top: 0.5rem; color: var(--critical-red); font-weight: 700; font-size: 0.88rem;">
                            Error Code: <code><?php echo htmlspecialchars($ticket['error_code']); ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Digital Certificate Summary (if Completed) -->
            <?php if ($report): ?>
                <div class="certificate-box">
                    <div class="cert-header">
                        <div class="badge badge-completed" style="margin-bottom: 0.5rem;">IEC 62353 Safety Certificate Issued</div>
                        <h3 style="color: var(--navy-dark); font-size: 1.4rem;">Digital Service & Safety Clearance</h3>
                        <p style="font-size: 0.85rem; color: var(--slate-500);">Signed by: <strong><?php echo htmlspecialchars($report['hospital_signoff_by']); ?></strong> on <?php echo date('M d, Y H:i', strtotime($report['signed_at'])); ?></p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center; margin-bottom: 1.5rem;">
                        <div style="background: #fff; padding: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                            <div style="font-size: 0.75rem; color: var(--slate-500); text-transform: uppercase;">Ground Bond</div>
                            <strong style="font-size: 1.1rem; color: var(--navy-dark);"><?php echo $report['ground_resistance_ohms']; ?> Ω</strong>
                        </div>
                        <div style="background: #fff; padding: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                            <div style="font-size: 0.75rem; color: var(--slate-500); text-transform: uppercase;">Leakage Current</div>
                            <strong style="font-size: 1.1rem; color: var(--navy-dark);"><?php echo $report['leakage_current_ua']; ?> &mu;A</strong>
                        </div>
                        <div style="background: #fff; padding: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                            <div style="font-size: 0.75rem; color: var(--slate-500); text-transform: uppercase;">Safety Result</div>
                            <strong style="font-size: 1.1rem; color: var(--success-emerald);"><?php echo strtoupper($report['electrical_safety_status']); ?></strong>
                        </div>
                    </div>

                    <div style="font-size: 0.9rem; color: var(--slate-700); margin-bottom: 0.8rem;">
                        <strong>Work Performed:</strong> <?php echo htmlspecialchars($report['work_performed']); ?>
                    </div>
                    <?php if ($report['parts_replaced']): ?>
                        <div style="font-size: 0.88rem; color: var(--slate-600);">
                            <strong>Parts Replaced:</strong> <?php echo htmlspecialchars($report['parts_replaced']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Timeline & Updates -->
            <div style="background: var(--white); border-radius: var(--radius-md); border: 1px solid var(--slate-200); padding: 1.5rem;">
                <h3 style="font-size: 1.2rem; color: var(--navy-dark); margin-bottom: 1rem;"><i class="fa-solid fa-stream" style="color: var(--cyan-primary);"></i> Service Communication & Dispatch Timeline</h3>

                <div class="timeline">
                    <?php foreach ($timeline as $up): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-meta">
                                    <strong><?php echo htmlspecialchars($up['author_name']); ?> (<?php echo htmlspecialchars($up['author_role']); ?>)</strong>
                                    <span><?php echo date('M d, Y g:i A', strtotime($up['created_at'])); ?></span>
                                </div>
                                <p style="font-size: 0.9rem; color: var(--slate-700);"><?php echo htmlspecialchars($up['status_note']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add Note Form -->
                <?php if (isset($_SESSION['user'])): ?>
                    <form action="ticket_details.php?id=<?php echo $ticketId; ?>" method="POST" style="margin-top: 1.5rem; border-top: 1px solid var(--slate-200); padding-top: 1rem;">
                        <input type="hidden" name="add_timeline_note" value="1">
                        <div class="form-group">
                            <label class="form-label">Add Note / Status Update to Timeline</label>
                            <input type="text" name="status_note" class="form-control" placeholder="Type an update or comment..." required>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-comment-dots"></i> Post Update</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div>
            
            <!-- Assigned Engineer Box -->
            <div style="background: var(--white); border-radius: var(--radius-md); border: 1px solid var(--slate-200); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="font-size: 1rem; color: var(--navy-dark); margin-bottom: 0.75rem;"><i class="fa-solid fa-user-doctor" style="color: var(--cyan-primary);"></i> Assigned Biomedical Engineer</h4>
                
                <?php if ($ticket['engineer_name']): ?>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="width: 42px; height: 42px; background: var(--cyan-glow); color: var(--cyan-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700;">
                            <?php echo substr($ticket['engineer_name'], 0, 1); ?>
                        </div>
                        <div>
                            <strong style="color: var(--navy-dark);"><?php echo htmlspecialchars($ticket['engineer_name']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--slate-500);"><?php echo htmlspecialchars($ticket['engineer_spec']); ?></div>
                        </div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--slate-600); margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-star" style="color: var(--warning-amber);"></i> Rating: <strong><?php echo $ticket['engineer_rating']; ?> / 5.0</strong>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--slate-600);">
                        <i class="fa-solid fa-phone"></i> Direct: <strong><?php echo htmlspecialchars($ticket['engineer_phone']); ?></strong>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 1rem; color: var(--slate-400);">
                        <i class="fa-solid fa-clock" style="font-size: 1.5rem; margin-bottom: 0.4rem;"></i>
                        <p style="font-size: 0.85rem;">Pending Dispatcher Assignment</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hospital Info Box -->
            <div style="background: var(--white); border-radius: var(--radius-md); border: 1px solid var(--slate-200); padding: 1.25rem;">
                <h4 style="font-size: 1rem; color: var(--navy-dark); margin-bottom: 0.75rem;"><i class="fa-solid fa-hospital" style="color: var(--cyan-primary);"></i> Hospital Facility Location</h4>
                <strong style="color: var(--navy-dark);"><?php echo htmlspecialchars($ticket['hospital_name']); ?></strong>
                <p style="font-size: 0.85rem; color: var(--slate-600); margin: 0.4rem 0;"><?php echo htmlspecialchars($ticket['hosp_address']); ?>, <?php echo htmlspecialchars($ticket['hosp_city']); ?></p>
                <div style="font-size: 0.85rem; color: var(--slate-600);">
                    <strong>Emergency Desk:</strong> <?php echo htmlspecialchars($ticket['emergency_contact']); ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
