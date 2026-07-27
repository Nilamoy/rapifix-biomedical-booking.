<?php
$pageTitle = "Register - Hospital or Engineer";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'hospital';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $countryCode = trim($_POST['country_code'] ?? '+91');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $phone = !empty($phoneNumber) ? ($countryCode . ' ' . $phoneNumber) : '';

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Check if email exists
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = "An account with this email address already exists.";
            } else {
                $approvalStatus = ($role === 'engineer') ? 'pending' : 'approved';
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role, approval_status, phone) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $email, $hash, $role, $approvalStatus, $phone]);
                $userId = $db->lastInsertId();

                if ($role === 'hospital') {
                    $hName = trim($_POST['hospital_name'] ?? $fullName);
                    $fType = $_POST['facility_type'] ?? 'General Hospital';
                    $addr = trim($_POST['address'] ?? 'Main Boulevard');
                    $city = trim($_POST['city'] ?? 'New York');
                    $cPerson = trim($_POST['contact_person'] ?? $fullName);
                    $eContact = trim($_POST['emergency_contact'] ?? $phone);

                    $hStmt = $db->prepare("INSERT INTO hospitals (user_id, hospital_name, facility_type, address, city, contact_person, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $hStmt->execute([$userId, $hName, $fType, $addr, $city, $cPerson, $eContact]);
                    $success = "Hospital registration successful! You may now sign in.";
                } else {
                    $spec = trim($_POST['specialization'] ?? 'ICU & Life Support');
                    $cert = trim($_POST['certification'] ?? 'Certified Biomedical Equipment Technician (CBET)');
                    $exp = intval($_POST['years_experience'] ?? 3);
                    $city = trim($_POST['city'] ?? 'New York');

                    $eStmt = $db->prepare("INSERT INTO engineers (user_id, specialization, certification, years_experience, availability_status, city) VALUES (?, ?, ?, ?, 'offline', ?)");
                    $eStmt->execute([$userId, $spec, $cert, $exp, $city]);
                    $success = "Registration submitted! Your Biomedical Engineer profile is pending Admin verification & approval. You can log in once approved.";
                }
            }
        } catch (Exception $ex) {
            $error = "Database Error: " . $ex->getMessage();
        }
    }
}
?>

<div class="container" style="max-width: 680px; padding-top: 2rem; padding-bottom: 4rem;">
    <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-md); padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 style="font-size: 1.8rem; margin-bottom: 0.25rem;">Create an Account</h2>
            <p style="color: var(--slate-500); font-size: 0.9rem;">Join RapiFix to book engineers or offer biomedical services</p>
        </div>

        <?php if ($error): ?>
            <div style="padding: 0.8rem 1rem; background: var(--critical-bg); color: var(--critical-red); border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.25rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="padding: 0.8rem 1rem; background: var(--success-bg); color: #047857; border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.25rem;">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?> <a href="login.php" style="font-weight: 700;">Login Now &rarr;</a>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="regForm">
            <div class="form-group">
                <label class="form-label">I am registering as a:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <label style="padding: 0.9rem; border: 2px solid var(--slate-200); border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.6rem; cursor: pointer;" id="lblHosp">
                        <input type="radio" name="role" value="hospital" checked onclick="toggleRoleFields('hospital')">
                        <div>
                            <strong>Hospital / Clinic</strong>
                            <div style="font-size: 0.78rem; color: var(--slate-500);">Book equipment service</div>
                        </div>
                    </label>

                    <label style="padding: 0.9rem; border: 2px solid var(--slate-200); border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.6rem; cursor: pointer;" id="lblEng">
                        <input type="radio" name="role" value="engineer" onclick="toggleRoleFields('engineer')">
                        <div>
                            <strong>Biomedical Engineer</strong>
                            <div style="font-size: 0.78rem; color: var(--slate-500);">Accept repair service calls</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Full Name / Account Contact</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Dr. Jane Smith" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="jane@hospital.org" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone / Emergency Contact</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <select name="country_code" class="form-control" style="width: 140px; flex-shrink: 0;">
                            <option value="+91" selected>🇮🇳 +91 (IN)</option>
                            <option value="+1">🇺🇸 +1 (US)</option>
                            <option value="+44">🇬🇧 +44 (UK)</option>
                            <option value="+971">🇦🇪 +971 (UAE)</option>
                            <option value="+65">🇸🇬 +65 (SG)</option>
                            <option value="+61">🇦🇺 +61 (AU)</option>
                            <option value="+49">🇩🇪 +49 (DE)</option>
                            <option value="+81">🇯🇵 +81 (JP)</option>
                            <option value="+966">🇸🇦 +966 (SA)</option>
                        </select>
                        <input type="tel" name="phone_number" class="form-control" placeholder="7908892778" pattern="[0-9]{7,14}" title="Please enter valid phone number digits" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <!-- Hospital Specific Fields -->
            <div id="hospitalFields">
                <h4 style="font-size: 1rem; color: var(--navy-dark); margin: 1.5rem 0 1rem; border-top: 1px solid var(--slate-200); padding-top: 1rem;">
                    Hospital & Facility Profile
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Hospital / Clinic Name</label>
                        <input type="text" name="hospital_name" class="form-control" placeholder="St. Hope Medical Center">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Facility Type</label>
                        <select name="facility_type" class="form-control">
                            <option value="Tertiary Care Hospital">Tertiary Care Hospital</option>
                            <option value="Super-Specialty Center">Super-Specialty Center</option>
                            <option value="Community Clinic">Community Clinic</option>
                            <option value="Diagnostic Imaging Center">Diagnostic Imaging Center</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="New York">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="100 Health Way, Wing B">
                    </div>
                </div>
            </div>

            <!-- Engineer Specific Fields -->
            <div id="engineerFields" style="display: none;">
                <h4 style="font-size: 1rem; color: var(--navy-dark); margin: 1.5rem 0 1rem; border-top: 1px solid var(--slate-200); padding-top: 1rem;">
                    Biomedical Credentials & Specialization
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Primary Specialization</label>
                        <select name="specialization" class="form-control">
                            <option value="ICU Ventilators & Life Support Systems">ICU Ventilators & Life Support Systems</option>
                            <option value="Diagnostic Imaging (MRI, CT, X-Ray)">Diagnostic Imaging (MRI, CT, X-Ray)</option>
                            <option value="Surgical Lasers & Anesthesia">Surgical Lasers & Anesthesia Workstations</option>
                            <option value="Dialysis & Blood Purification">Dialysis & Blood Purification Equipment</option>
                            <option value="General Bio-Medical Equipment">General Bio-Medical Equipment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Certification</label>
                        <input type="text" name="certification" class="form-control" value="Certified Biomedical Equipment Technician (CBET)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Years of Field Experience</label>
                        <input type="number" name="years_experience" class="form-control" value="5" min="1" max="40">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.9rem; margin-top: 1.5rem;">
                <i class="fa-solid fa-user-plus"></i> Complete Registration
            </button>
        </form>
    </div>
</div>

<script>
function toggleRoleFields(role) {
    const hosp = document.getElementById('hospitalFields');
    const eng = document.getElementById('engineerFields');
    if (role === 'hospital') {
        hosp.style.display = 'block';
        eng.style.display = 'none';
    } else {
        hosp.style.display = 'none';
        eng.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
