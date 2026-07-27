<?php
$pageTitle = "On-Demand Biomedical Engineer Booking Platform";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero">
    <div class="hero-grid">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 1rem; background: rgba(0, 180, 216, 0.15); border: 1px solid rgba(0, 180, 216, 0.3); border-radius: var(--radius-full); color: var(--cyan-light); font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-bolt-lightning"></i> 24/7 Rapid Response Medical Equipment Dispatch
            </div>
            <h1 class="hero-title">Book Certified <span class="text-gradient">Biomedical Engineers</span> for Hospital Equipment</h1>
            <p class="hero-subtitle">
                Zero downtime for critical healthcare assets. Instant dispatch of CBET & CHTM certified biomedical equipment technicians (BMET) for emergency breakdown repairs, preventive maintenance (PM), and IEC 62353 safety calibration.
            </p>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'hospital'): ?>
                    <a href="book_service.php" class="btn btn-primary" style="padding: 0.9rem 1.8rem; font-size: 1.05rem;">
                        <i class="fa-solid fa-wrench"></i> Book Engineer Callout
                    </a>
                    <a href="hospital_dashboard.php" class="btn btn-secondary" style="padding: 0.9rem 1.8rem; font-size: 1.05rem;">
                        <i class="fa-solid fa-chart-line"></i> View Hospital Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary" style="padding: 0.9rem 1.8rem; font-size: 1.05rem;">
                        <i class="fa-solid fa-hospital-user"></i> Hospital Registration
                    </a>
                    <a href="login.php" class="btn btn-secondary" style="padding: 0.9rem 1.8rem; font-size: 1.05rem;">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
                    </a>
                <?php endif; ?>
            </div>

            <div class="hero-stats">
                <div>
                    <div class="stat-number">1,480+</div>
                    <div class="stat-label">Serviced Medical Assets</div>
                </div>
                <div>
                    <div class="stat-number">38 Mins</div>
                    <div class="stat-label">Avg Emergency Dispatch</div>
                </div>
                <div>
                    <div class="stat-number">99.8%</div>
                    <div class="stat-label">Safety Compliance Rate</div>
                </div>
            </div>
        </div>

        <div>
            <div class="hero-card">
                <h3 style="color: var(--white); font-size: 1.3rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: var(--warning-amber);"></i> Emergency Callout Request
                </h3>
                <p style="color: var(--slate-300); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Experiencing a sudden ventilator failure, MRI error code, or monitor malfunction? Request an immediate engineer dispatch.
                </p>
                <form action="book_service.php" method="GET">
                    <div class="form-group">
                        <label class="form-label" style="color: var(--slate-200);">Equipment Category</label>
                        <select name="category" class="form-control" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: var(--white);">
                            <option value="Life Support" style="color:#333;">Life Support (Ventilator, Defibrillator)</option>
                            <option value="Diagnostic Imaging" style="color:#333;">Diagnostic Imaging (MRI, CT, X-Ray)</option>
                            <option value="Operating Theatre" style="color:#333;">Operating Theatre & Surgical Lasers</option>
                            <option value="Patient Monitoring" style="color:#333;">ICU & Patient Monitoring Systems</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="color: var(--slate-200);">Urgency Level</label>
                        <select name="urgency" class="form-control" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: var(--white);">
                            <option value="critical" style="color:#333;">🔴 CRITICAL (Patient Room / ICU Breakdown)</option>
                            <option value="high" style="color:#333;">🟠 HIGH (Urgent Service Needed Today)</option>
                            <option value="medium" style="color:#333;">🟡 MEDIUM (Scheduled Inspection / PM)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fa-solid fa-paper-plane"></i> Proceed to Dispatch Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Services Grid -->
<div class="container">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h2 style="font-size: 2.2rem; margin-bottom: 0.5rem;">Comprehensive Biomedical Engineering Services</h2>
        <p style="color: var(--slate-500); max-width: 600px; margin: 0 auto;">
            Tailored maintenance solutions to extend equipment lifespan, eliminate unexpected downtime, and satisfy Joint Commission & FDA health audits.
        </p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-wrench"></i>
            </div>
            <h3 style="margin-bottom: 0.75rem;">Emergency Breakdown Repair</h3>
            <p style="color: var(--slate-600); font-size: 0.95rem; margin-bottom: 1rem;">
                24/7 on-demand field engineers dispatched with OEM diagnostic tools and certified spare parts for emergency repairs.
            </p>
            <ul style="list-style: none; font-size: 0.88rem; color: var(--slate-500); display: flex; flex-direction: column; gap: 0.4rem;">
                <li><i class="fa-solid fa-check" style="color: var(--success-emerald);"></i> Real-time GPS Engineer Tracking</li>
                <li><i class="fa-solid fa-check" style="color: var(--success-emerald);"></i> Error Code & Transducer Diagnostics</li>
            </ul>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <h3 style="margin-bottom: 0.75rem;">Safety Testing & Calibration</h3>
            <p style="color: var(--slate-600); font-size: 0.95rem; margin-bottom: 1rem;">
                Rigorous IEC 62353 electrical safety testing, ground resistance checks, leakage current measurements, and sensor calibration.
            </p>
            <ul style="list-style: none; font-size: 0.88rem; color: var(--slate-500); display: flex; flex-direction: column; gap: 0.4rem;">
                <li><i class="fa-solid fa-check" style="color: var(--success-emerald);"></i> Digital Calibration Certificate</li>
                <li><i class="fa-solid fa-check" style="color: var(--success-emerald);"></i> Audit-Ready PDF Job Sheets</li>
            </ul>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h3 style="margin-bottom: 0.75rem;">Preventive Maintenance (PM)</h3>
            <p style="color: var(--slate-600); font-size: 0.95rem; margin-bottom: 1rem;">
                Scheduled periodic inspections, filter replacements, battery endurance tests, and optical sensor alignment.
            </p>
            <ul style="list-style: none; font-size: 0.88rem; color: var(--slate-500); display: flex; flex-direction: column; gap: 0.4rem;">
                <li><i class="fa-solid fa-check" style="color: var(--success-emerald);"></i> Customized Maintenance Schedule</li>
                <li><i class="fa-solid fa-check" style="color: var(--success-emerald);"></i> Asset Depreciation & Life Tracking</li>
            </ul>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div style="background: var(--white); padding: 4rem 1.5rem; border-top: 1px solid var(--slate-200); border-bottom: 1px solid var(--slate-200);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">How RapiFix Service Dispatch Works</h2>
            <p style="color: var(--slate-500);">Streamlined 4-step workflow from equipment breakdown to verified safety certificate.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem;">
            <div style="text-align: center; padding: 1.5rem;">
                <div style="width: 50px; height: 50px; background: var(--navy-dark); color: var(--cyan-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; margin: 0 auto 1.25rem;">1</div>
                <h4 style="margin-bottom: 0.5rem;">Select Equipment & Fault</h4>
                <p style="font-size: 0.9rem; color: var(--slate-500);">Choose the affected device from your hospital equipment inventory or input serial number.</p>
            </div>

            <div style="text-align: center; padding: 1.5rem;">
                <div style="width: 50px; height: 50px; background: var(--navy-dark); color: var(--cyan-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; margin: 0 auto 1.25rem;">2</div>
                <h4 style="margin-bottom: 0.5rem;">Automated Dispatch</h4>
                <p style="font-size: 0.9rem; color: var(--slate-500);">The system alerts certified field engineers with exact specialization match in your area.</p>
            </div>

            <div style="text-align: center; padding: 1.5rem;">
                <div style="width: 50px; height: 50px; background: var(--navy-dark); color: var(--cyan-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; margin: 0 auto 1.25rem;">3</div>
                <h4 style="margin-bottom: 0.5rem;">On-Site Service & Testing</h4>
                <p style="font-size: 0.9rem; color: var(--slate-500);">Engineer performs component repair, replaces faulty boards, and executes electrical safety checks.</p>
            </div>

            <div style="text-align: center; padding: 1.5rem;">
                <div style="width: 50px; height: 50px; background: var(--navy-dark); color: var(--cyan-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; margin: 0 auto 1.25rem;">4</div>
                <h4 style="margin-bottom: 0.5rem;">Digital Job Sheet Sign-Off</h4>
                <p style="font-size: 0.9rem; color: var(--slate-500);">Hospital supervisor verifies equipment functionality and signs digital calibration certificate.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
