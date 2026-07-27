/**
 * BioMedCare - Client-Side Interactivity
 */

document.addEventListener('DOMContentLoaded', () => {
    // Dynamic Equipment Category Selection
    const equipmentSelect = document.getElementById('equipment_id');
    const deptInput = document.getElementById('department_display');

    if (equipmentSelect && deptInput) {
        equipmentSelect.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const dept = selectedOption.getAttribute('data-dept');
            deptInput.value = dept || '';
        });
    }

    // Auto dismiss flash alerts
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Electrical Safety Pass/Fail Auto Calculator for Job Sheet
    const groundInput = document.getElementById('ground_resistance_ohms');
    const leakageInput = document.getElementById('leakage_current_ua');
    const safetyBadge = document.getElementById('safety_status_badge');

    function updateSafetyStatus() {
        if (!groundInput || !leakageInput || !safetyBadge) return;
        const ground = parseFloat(groundInput.value) || 0;
        const leakage = parseFloat(leakageInput.value) || 0;

        // IEC 62353 Standards: Ground < 0.1 Ohm, Leakage < 500 uA
        if (ground <= 0.100 && leakage <= 500.0) {
            safetyBadge.className = 'badge badge-completed';
            safetyBadge.innerText = 'PASS (IEC 62353 Compliant)';
        } else {
            safetyBadge.className = 'badge badge-critical';
            safetyBadge.innerText = 'FAIL (Exceeds Safety Threshold)';
        }
    }

    if (groundInput && leakageInput) {
        groundInput.addEventListener('input', updateSafetyStatus);
        leakageInput.addEventListener('input', updateSafetyStatus);
        updateSafetyStatus();
    }
});
