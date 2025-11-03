/**
 * Digital Signage Platform - Main JavaScript
 */

// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Confirm before logout
const logoutLinks = document.querySelectorAll('a[href*="page=logout"]');
logoutLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
});

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.style.borderColor = '#DC3545';
            isValid = false;
        } else {
            field.style.borderColor = '#DDDDDD';
        }
    });
    
    return isValid;
}

// Console welcome message
console.log('%cDigital Signage Platform', 'color: #008080; font-size: 24px; font-weight: bold;');
console.log('%cDeveloped for Rabs Chippy', 'color: #666; font-size: 14px;');
