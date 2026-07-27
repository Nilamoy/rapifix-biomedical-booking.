<?php
$currentUser = $_SESSION['user'] ?? null;
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="brand-logo">
            <div class="brand-icon">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <span>Rapi<span style="color: var(--cyan-primary)">Fix</span></span>
        </a>

        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <?php if ($currentUser): ?>
                <?php if ($currentUser['role'] === 'hospital'): ?>
                    <li><a href="hospital_dashboard.php" class="nav-link">Hospital Dashboard</a></li>
                    <li><a href="book_service.php" class="nav-link">Book Service</a></li>
                    <li><a href="equipment_list.php" class="nav-link">Equipment Roster</a></li>
                <?php elseif ($currentUser['role'] === 'engineer'): ?>
                    <li><a href="engineer_dashboard.php" class="nav-link">Engineer Portal</a></li>
                <?php elseif ($currentUser['role'] === 'admin'): ?>
                    <li><a href="admin_dashboard.php" class="nav-link">Admin Console</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <?php if ($currentUser): ?>
                <div class="user-badge">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    <span class="role-pill role-<?php echo $currentUser['role']; ?>"><?php echo strtoupper($currentUser['role']); ?></span>
                </div>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link" style="margin-right: 0.5rem;">Sign In</a>
                <a href="register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-hospital-user"></i> Hospital / Engineer Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
