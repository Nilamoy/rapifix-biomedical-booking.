<?php
$pageTitle = "Login to Portal";
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please fill in all credentials.";
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $appStatus = $user['approval_status'] ?? 'approved';

            if ($user['role'] === 'engineer' && $appStatus === 'pending') {
                $error = "🔒 Account Pending Approval: Your Biomedical Engineer account is currently pending Admin verification. You will be able to sign in once approved.";
            } elseif ($user['role'] === 'engineer' && $appStatus === 'rejected') {
                $error = "⛔ Application Declined: Your Biomedical Engineer registration request was not approved.";
            } else {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];

                // Get role specific entity ID
                if ($user['role'] === 'hospital') {
                    $hStmt = $db->prepare("SELECT id, hospital_name FROM hospitals WHERE user_id = ?");
                    $hStmt->execute([$user['id']]);
                    $hosp = $hStmt->fetch();
                    if ($hosp) {
                        $_SESSION['user']['hospital_id'] = $hosp['id'];
                        $_SESSION['user']['hospital_name'] = $hosp['hospital_name'];
                    }
                    header("Location: hospital_dashboard.php");
                    exit;
                } elseif ($user['role'] === 'engineer') {
                    $eStmt = $db->prepare("SELECT id FROM engineers WHERE user_id = ?");
                    $eStmt->execute([$user['id']]);
                    $eng = $eStmt->fetch();
                    if ($eng) {
                        $_SESSION['user']['engineer_id'] = $eng['id'];
                    }
                    header("Location: engineer_dashboard.php");
                    exit;
                } elseif ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit;
                }
            }
        } else {
            $error = "Invalid email address or password.";
        }
    }
}
?>

<div class="container" style="max-width: 500px; padding-top: 3rem; padding-bottom: 4rem;">
    <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-md); padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 50px; height: 50px; font-size: 1.5rem;">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h2 style="font-size: 1.8rem; margin-bottom: 0.25rem;">Sign In to RapiFix</h2>
            <p style="color: var(--slate-500); font-size: 0.9rem;">Access your Hospital, Engineer, or Admin Portal</p>
        </div>

        <?php if ($error): ?>
            <div style="padding: 0.8rem 1rem; background: var(--critical-bg); color: var(--critical-red); border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="e.g. metro@hospital.org" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; margin-top: 0.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
        </form>

        <!-- One-Click Demo Credentials for User Testing -->
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed var(--slate-200);">
            <p style="font-size: 0.82rem; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; text-align: center;">
                ⚡ Quick Demo One-Click Fill
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button type="button" onclick="fillCredentials('metro@hospital.org', 'password123')" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                    <i class="fa-solid fa-hospital" style="color: var(--info-blue);"></i> Login as Hospital (Metropolitan Gen)
                </button>
                <button type="button" onclick="fillCredentials('marcus.vance@bme-pros.com', 'password123')" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                    <i class="fa-solid fa-user-gear" style="color: var(--success-emerald);"></i> Login as Engineer (Eng. Vance)
                </button>
                <button type="button" onclick="fillCredentials('admin@rapifix.com', 'password123')" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                    <i class="fa-solid fa-shield-halved" style="color: var(--warning-amber);"></i> Login as System Admin
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--slate-500);">
            Don't have an account? <a href="register.php">Register Here</a>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, password) {
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="password"]').value = password;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
