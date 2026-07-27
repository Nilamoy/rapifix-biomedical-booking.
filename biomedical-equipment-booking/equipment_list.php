<?php
$pageTitle = "Hospital Equipment Inventory Roster";
require_once __DIR__ . '/includes/header.php';

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

$msg = '';
$err = '';

// Handle Adding New Equipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equipment'])) {
    $eqName = trim($_POST['equipment_name'] ?? '');
    $category = $_POST['category'] ?? 'General Bio-Medical';
    $brandModel = trim($_POST['brand_model'] ?? '');
    $serialNumber = trim($_POST['serial_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $instYear = intval($_POST['installation_year'] ?? date('Y'));
    $warranty = $_POST['warranty_status'] ?? 'under_warranty';

    if (empty($eqName) || empty($brandModel) || empty($serialNumber) || empty($department)) {
        $err = "Please fill in all equipment details.";
    } else {
        $stmt = $db->prepare("INSERT INTO equipment (hospital_id, equipment_name, category, brand_model, serial_number, department, installation_year, warranty_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'operational')");
        $stmt->execute([$hospId, $eqName, $category, $brandModel, $serialNumber, $department, $instYear, $warranty]);
        $msg = "New medical device successfully added to hospital roster!";
    }
}

// Fetch All Equipment for Hospital
$stmt = $db->prepare("SELECT * FROM equipment WHERE hospital_id = ? ORDER BY created_at DESC");
$stmt->execute([$hospId]);
$equipments = $stmt->fetchAll();
?>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h1 class="page-title">Hospital Equipment Inventory Roster</h1>
            <p class="page-subtitle">Track serial numbers, department locations, warranty status, and service history for all medical devices.</p>
        </div>
        <button onclick="document.getElementById('addEquipModal').style.display='block'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add New Medical Asset
        </button>
    </div>

    <?php if ($msg): ?>
        <div style="padding: 0.8rem 1rem; background: var(--success-bg); color: #047857; border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div style="padding: 0.8rem 1rem; background: var(--critical-bg); color: var(--critical-red); border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($err); ?>
        </div>
    <?php endif; ?>

    <!-- Equipment List Table -->
    <div class="card-table-wrapper">
        <div class="table-header">
            <h3 class="table-title"><i class="fa-solid fa-cube" style="color: var(--cyan-primary);"></i> Registered Medical Devices (<?php echo count($equipments); ?>)</h3>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Category</th>
                    <th>Brand / Model</th>
                    <th>Serial Number</th>
                    <th>Department</th>
                    <th>Warranty Status</th>
                    <th>Device Condition</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipments as $eq): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--navy-dark);"><?php echo htmlspecialchars($eq['equipment_name']); ?></strong>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($eq['category']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($eq['brand_model']); ?></td>
                        <td><code><?php echo htmlspecialchars($eq['serial_number']); ?></code></td>
                        <td><?php echo htmlspecialchars($eq['department']); ?></td>
                        <td>
                            <?php if ($eq['warranty_status'] === 'under_warranty'): ?>
                                <span class="badge badge-completed">Under Warranty</span>
                            <?php elseif ($eq['warranty_status'] === 'amc_covered'): ?>
                                <span class="badge badge-assigned">AMC Covered</span>
                            <?php else: ?>
                                <span class="badge badge-low">Contract Expired</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($eq['status'] === 'operational'): ?>
                                <span class="badge badge-completed">Operational</span>
                            <?php elseif ($eq['status'] === 'faulty'): ?>
                                <span class="badge badge-critical">Faulty / Needs Service</span>
                            <?php else: ?>
                                <span class="badge badge-pending">PM Due</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="book_service.php?category=<?php echo urlencode($eq['category']); ?>" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-wrench"></i> Request Service
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Equipment Modal -->
<div id="addEquipModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(11,19,43,0.7); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: var(--white); width: 100%; max-width: 600px; margin: 3rem auto; border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.3rem; color: var(--navy-dark);">Add Medical Device to Roster</h3>
            <button onclick="document.getElementById('addEquipModal').style.display='none'" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--slate-400);">&times;</button>
        </div>

        <form action="equipment_list.php" method="POST">
            <input type="hidden" name="add_equipment" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Equipment Name</label>
                    <input type="text" name="equipment_name" class="form-control" placeholder="e.g. Biphasic Defibrillator" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="Life Support">Life Support & Resuscitation</option>
                        <option value="Diagnostic Imaging">Diagnostic Imaging (MRI/CT/X-Ray)</option>
                        <option value="Operating Theatre">Operating Theatre & Surgical</option>
                        <option value="Cardiology">Cardiology & Cath Lab</option>
                        <option value="Patient Monitoring">Patient Monitoring & Telemetry</option>
                        <option value="Laboratory & Blood Bank">Laboratory & Blood Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Brand & Model</label>
                    <input type="text" name="brand_model" class="form-control" placeholder="e.g. Zoll R Series ALS" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" placeholder="e.g. SN-ZOL-99812" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Hospital Department</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g. Emergency Room (ER)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Warranty / AMC Status</label>
                    <select name="warranty_status" class="form-control">
                        <option value="under_warranty">Under OEM Warranty</option>
                        <option value="amc_covered">AMC Contract Covered</option>
                        <option value="expired">Out of Warranty / Contract Expired</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="document.getElementById('addEquipModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save to Equipment Roster</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
