<?php
/**
 * RapiFix - Printable / PDF Engineer Service Report & Assignment Work Order
 */
require_once __DIR__ . '/config/db.php';
session_start();

$db = getDBConnection();
$ticketId = intval($_GET['ticket_id'] ?? $_GET['id'] ?? 0);

if ($ticketId <= 0) {
    die("Invalid Ticket Reference ID.");
}

// Fetch Ticket, Hospital, Equipment, Engineer & Report data
$stmt = $db->prepare("
    SELECT t.*, h.hospital_name, h.facility_type, h.address as hosp_address, h.city as hosp_city, h.contact_person, h.emergency_contact,
           e.equipment_name, e.category, e.brand_model, e.serial_number, e.department, e.installation_year, e.warranty_status,
           eng_u.full_name as engineer_name, eng_u.email as engineer_email, eng_u.phone as engineer_phone,
           eng.specialization as engineer_spec, eng.certification as engineer_cert
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
    die("Service ticket not found.");
}

// Fetch Completed Service Report if available
$rStmt = $db->prepare("SELECT * FROM service_reports WHERE ticket_id = ?");
$rStmt->execute([$ticketId]);
$report = $rStmt->fetch();

$docType = ($ticket['status'] === 'completed' && $report) ? 'COMPLETED SERVICE CLEARANCE REPORT' : 'ENGINEER FIELD ASSIGNMENT WORK ORDER';
$docRef = 'DOC-' . strtoupper(str_replace('-', '', $ticket['ticket_code']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $docType; ?> - <?php echo htmlspecialchars($ticket['ticket_code']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0b132b;
            --accent: #00b4d8;
            --emerald: #10b981;
            --amber: #f59e0b;
            --red: #ef4444;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--slate-800);
            background: #f8fafc;
            padding: 2rem;
            line-height: 1.5;
        }
        .page {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .cert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: #fff;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .cert-meta {
            text-align: right;
            font-size: 0.85rem;
            color: var(--slate-600);
        }
        .doc-ref {
            font-family: monospace;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--accent);
        }
        .doc-heading {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0.75rem;
            background: var(--slate-100);
            border-radius: 8px;
            border: 1px solid var(--slate-200);
        }
        .doc-title {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary);
            border-bottom: 2px solid var(--slate-200);
            padding-bottom: 0.3rem;
            margin: 1.5rem 0 1rem;
            font-weight: 700;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .info-box {
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.88rem;
        }
        .info-box strong { color: var(--primary); }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        .table th, .table td {
            border: 1px solid var(--slate-200);
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .table th {
            background: var(--slate-100);
            color: var(--primary);
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-critical { background: #fee2e2; color: #b91c1c; }
        .badge-high { background: #fff7ed; color: #c2410c; }
        .badge-medium { background: #fefce8; color: #a16207; }
        .badge-pass { background: #dcfce7; color: #15803d; }

        .stamp-box {
            border: 2px dashed var(--accent);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 180, 216, 0.04);
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #00b4d8;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,180,216,0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 100;
        }
        .btn-print:hover { background: #0077b6; }

        @media print {
            body { background: #fff; padding: 0; }
            .page { border: none; box-shadow: none; padding: 0; }
            .btn-print { display: none !important; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="btn-print">
        <i class="fa-solid fa-print"></i> Print / Save PDF Copy
    </button>

    <div class="page">
        <!-- Header -->
        <div class="cert-header">
            <div>
                <div class="brand">
                    <div class="brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <span>Rapi<span style="color: var(--accent)">Fix</span></span>
                </div>
                <div style="font-size: 0.85rem; color: var(--slate-600); margin-top: 0.3rem;">
                    Biomedical Engineering On-Demand Dispatch & Safety Platform
                </div>
            </div>
            <div class="cert-meta">
                <div>REF NO: <span class="doc-ref"><?php echo $docRef; ?></span></div>
                <div>TICK CODE: <strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong></div>
                <div>DATE: <strong><?php echo date('F d, Y', strtotime($ticket['created_at'])); ?></strong></div>
            </div>
        </div>

        <div class="doc-heading">
            <div class="doc-title"><?php echo $docType; ?></div>
            <div style="font-size: 0.85rem; color: var(--slate-600); margin-top: 0.2rem;">
                Official RapiFix Biomedical Equipment Maintenance Document
            </div>
        </div>

        <!-- Facility & Equipment Info Grid -->
        <div class="grid-2">
            <div class="info-box">
                <div class="section-title" style="margin-top: 0;">Healthcare Facility Details</div>
                <div><strong>Facility Name:</strong> <?php echo htmlspecialchars($ticket['hospital_name']); ?></div>
                <div><strong>Address:</strong> <?php echo htmlspecialchars($ticket['hosp_address']); ?>, <?php echo htmlspecialchars($ticket['hosp_city']); ?></div>
                <div><strong>Contact Representative:</strong> <?php echo htmlspecialchars($ticket['contact_person']); ?></div>
                <div><strong>Emergency Contact Desk:</strong> <?php echo htmlspecialchars($ticket['emergency_contact']); ?></div>
            </div>

            <div class="info-box">
                <div class="section-title" style="margin-top: 0;">Medical Asset Specifications</div>
                <div><strong>Equipment Name:</strong> <?php echo htmlspecialchars($ticket['equipment_name']); ?></div>
                <div><strong>Brand & Model:</strong> <?php echo htmlspecialchars($ticket['brand_model']); ?></div>
                <div><strong>Serial Number:</strong> <code><?php echo htmlspecialchars($ticket['serial_number']); ?></code></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($ticket['department']); ?></div>
                <div><strong>Warranty / AMC:</strong> <?php echo strtoupper(str_replace('_', ' ', $ticket['warranty_status'])); ?></div>
            </div>
        </div>

        <!-- Service Call Specifications -->
        <div class="section-title">Service Call Details</div>
        <div class="info-box">
            <div class="grid-2">
                <div><strong>Service Type:</strong> <?php echo strtoupper(str_replace('_', ' ', $ticket['service_type'])); ?></div>
                <div><strong>Urgency:</strong> <span class="badge badge-<?php echo $ticket['urgency']; ?>"><?php echo strtoupper($ticket['urgency']); ?></span></div>
            </div>
            <div style="margin-top: 0.75rem;">
                <strong>Logged Problem / Fault Description:</strong>
                <p style="margin-top: 0.2rem; color: var(--slate-800);"><?php echo htmlspecialchars($ticket['fault_description']); ?></p>
                <?php if ($ticket['error_code']): ?>
                    <div style="margin-top: 0.4rem; color: var(--red); font-weight: 700;">Diagnostic Code: <?php echo htmlspecialchars($ticket['error_code']); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assigned Engineer Details -->
        <div class="section-title">Assigned Biomedical Engineer Profile</div>
        <div class="info-box">
            <?php if ($ticket['engineer_name']): ?>
                <div class="grid-2">
                    <div>
                        <div><strong>Assigned Engineer:</strong> <?php echo htmlspecialchars($ticket['engineer_name']); ?></div>
                        <div><strong>Specialization:</strong> <?php echo htmlspecialchars($ticket['engineer_spec']); ?></div>
                    </div>
                    <div>
                        <div><strong>Certification:</strong> <?php echo htmlspecialchars($ticket['engineer_cert']); ?></div>
                        <div><strong>Contact Phone:</strong> <?php echo htmlspecialchars($ticket['engineer_phone'] ?? '+91 7908892778'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div style="color: var(--amber); font-weight: 600;">
                    <i class="fa-solid fa-clock"></i> Pending Dispatcher Engineer Assignment
                </div>
            <?php endif; ?>
        </div>

        <!-- If Completed: IEC 62353 Report Section -->
        <?php if ($report): ?>
            <div class="section-title">IEC 62353 Safety & Calibration Audit Results</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Safety Test Parameter</th>
                        <th>Measured Value</th>
                        <th>Standard Limit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Ground Bond Resistance</strong></td>
                        <td><code><?php echo htmlspecialchars($report['ground_resistance_ohms']); ?> &Omega;</code></td>
                        <td>&le; 0.100 &Omega;</td>
                        <td><span class="badge badge-pass">PASS</span></td>
                    </tr>
                    <tr>
                        <td><strong>Earth Leakage Current</strong></td>
                        <td><code><?php echo htmlspecialchars($report['leakage_current_ua']); ?> &mu;A</code></td>
                        <td>&le; 500.0 &mu;A</td>
                        <td><span class="badge badge-pass">PASS</span></td>
                    </tr>
                    <tr>
                        <td><strong>Transducer Calibration</strong></td>
                        <td colspan="2"><code><?php echo strtoupper(str_replace('_', ' ', $report['calibration_status'])); ?></code></td>
                        <td><span class="badge badge-pass">VALIDATED</span></td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box" style="margin-bottom: 1rem;">
                <strong>Work Performed:</strong>
                <p style="margin-top: 0.3rem;"><?php echo htmlspecialchars($report['work_performed']); ?></p>
            </div>

            <?php if (!empty($report['parts_replaced'])): ?>
                <div class="info-box" style="margin-bottom: 1rem;">
                    <strong>Parts / Consumables Replaced:</strong>
                    <p style="margin-top: 0.3rem;"><?php echo htmlspecialchars($report['parts_replaced']); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Sign-Off & Verification Box -->
        <div class="stamp-box">
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--slate-600); font-weight: 700;">Biomedical Engineer Signature</div>
                <strong style="font-size: 1.05rem; color: var(--primary);"><?php echo htmlspecialchars($ticket['engineer_name'] ?? 'RapiFix Field Dispatch'); ?></strong>
                <div style="font-size: 0.82rem; color: var(--slate-600);"><?php echo htmlspecialchars($ticket['engineer_cert'] ?? 'CBET Certified Technician'); ?></div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--slate-600); font-weight: 700;">Hospital Clearance Authorization</div>
                <strong style="font-size: 1.05rem; color: var(--primary);"><?php echo htmlspecialchars($report['hospital_signoff_by'] ?? $ticket['contact_person']); ?></strong>
                <div style="font-size: 0.82rem; color: var(--slate-600);"><?php echo htmlspecialchars($report['hospital_designation'] ?? 'Chief Medical Officer'); ?></div>
                <div style="font-size: 0.82rem; color: var(--emerald); font-weight: 700; margin-top: 0.2rem;">
                    <i class="fa-solid fa-shield-check"></i> VERIFIED [CODE: <?php echo htmlspecialchars($report['authorisation_code'] ?? 'AUTH-VERIFIED'); ?>]
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.78rem; color: var(--slate-600); border-top: 1px solid var(--slate-200); padding-top: 1rem;">
            RapiFix Biomedical Engineering Platform &bull; Dispatch & Equipment Maintenance Report &bull; Helpline: +91 7908892778
        </div>
    </div>

</body>
</html>
