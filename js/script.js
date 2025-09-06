// Modern Resume Interactive Functionality
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
    initializeDarkMode();
    enhanceInteractions();
    addResponsiveHandling();
    addAccessibilityFeatures();
});

// Initialize the page with smooth loading
function initializePage() {
    // Smooth page entrance
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.opacity = '1';
        document.body.style.transition = 'opacity 0.8s ease-in';
    }, 100);

    // Initialize profile image loading
    const profileImg = document.querySelector('.profile-image');
    if (profileImg) {
        profileImg.addEventListener('load', function() {
            this.style.transform = 'scale(0.8)';
            this.style.opacity = '0';
            setTimeout(() => {
                this.style.transition = 'all 0.6s ease';
                this.style.opacity = '1';
                this.style.transform = 'scale(1)';
            }, 300);
        });
    }

    // Add intersection observer for animations
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-visible');
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
    );

    document.querySelectorAll('.card').forEach(card => {
        observer.observe(card);
    });
}

// Dark Mode Functionality
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('resume-theme') || 'light';
    applyTheme(savedTheme);
    
    darkModeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add smooth transition
        body.style.transition = 'all 0.3s ease';
        applyTheme(newTheme);
        localStorage.setItem('resume-theme', newTheme);
        
        // Animate button with rotation
        this.style.transform = 'rotate(360deg)';
        setTimeout(() => {
            this.style.transform = 'rotate(0deg)';
        }, 300);
    });

    // Handle system preference changes
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', function(e) {
            if (!localStorage.getItem('resume-theme')) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
}

function applyTheme(theme) {
    const body = document.body;
    const darkModeToggle = document.getElementById('darkModeToggle');
    const icon = darkModeToggle.querySelector('i');
    
    body.setAttribute('data-theme', theme);
    
    if (theme === 'dark') {
        icon.className = 'fas fa-sun';
        darkModeToggle.setAttribute('aria-label', 'Switch to light mode');
    } else {
        icon.className = 'fas fa-moon';
        darkModeToggle.setAttribute('aria-label', 'Switch to dark mode');
    }
}

// Enhanced interactions
function enhanceInteractions() {
    enhanceCards();
    enhanceButtons();
    enhanceTechTags();
    enhanceTimeline();
    enhanceSocialLinks();
    enhanceAchievements();
    enhanceExperience();
    addKeyboardSupport();
}

// Card hover effects
function enhanceCards() {
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Button interactions
function enhanceButtons() {
    const buttons = document.querySelectorAll('.btn-print, .dark-mode-toggle');
    
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        button.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            }, 150);
        });
    });
}

// Tech tag interactions - simplified hover effects
function enhanceTechTags() {
    document.querySelectorAll('.tech-tag').forEach(tag => {
        // Simple hover effect with scale and shadow
        tag.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
            this.style.boxShadow = '0 6px 15px rgba(239, 35, 60, 0.3)';
        });
        
        tag.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = 'none';
        });
    });
}

// Timeline animations
function enhanceTimeline() {
    document.querySelectorAll('.timeline-content').forEach(content => {
        content.addEventListener('mouseenter', function() {
            const marker = this.parentElement.querySelector('.timeline-marker');
            if (marker) {
                marker.style.transform = 'scale(1.2)';
                marker.style.background = '#d80032';
            }
        });
        
        content.addEventListener('mouseleave', function() {
            const marker = this.parentElement.querySelector('.timeline-marker');
            if (marker) {
                marker.style.transform = 'scale(1)';
                marker.style.background = '#ef233c';
            }
        });
    });
}

// Social link interactions
function enhanceSocialLinks() {
    document.querySelectorAll('.social-icon').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.href;
            const platform = href.includes('linkedin') ? 'LinkedIn' :
                          href.includes('github') ? 'GitHub' :
                          href.includes('mailto') ? 'Email' :
                          href.includes('tel') ? 'Phone' : 'Website';
            
            // Add click animation
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
            
            // Log interaction (for analytics)
            console.log(`Social link clicked: ${platform}`);
        });
    });
}

// Achievement interactions - simplified
function enhanceAchievements() {
    document.querySelectorAll('.achievement-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.achievement-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.achievement-icon');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        });
    });
}

// Experience interactions
function enhanceExperience() {
    document.querySelectorAll('.experience-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    document.querySelectorAll('.highlight-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1.2)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        });
    });
}

// Download PDF functionality
function downloadPDF() {
    const pdfPath = '/Donayre-CV/assets/pdf/Donayre_CV.pdf';  // use correct file name + absolute path

    const downloadBtn = document.querySelector('.btn-print');
    const originalContent = downloadBtn.innerHTML;
    downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Preparing...</span>';
    downloadBtn.disabled = true;

    // Create hidden link to trigger download
    const link = document.createElement('a');
    link.href = pdfPath;
    link.download = 'Donayre_CV.pdf';  // suggested filename
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Restore button
    setTimeout(() => {
        downloadBtn.innerHTML = originalContent;
        downloadBtn.disabled = false;
    }, 1000);
}

// Keyboard support
function addKeyboardSupport() {
    document.addEventListener('keydown', function(e) {
        // Only handle shortcuts when not in input fields
        if (e.target.matches('input, textarea, [contenteditable]')) {
            return;
        }
        
        switch(e.key.toLowerCase()) {
            case 'p':
                if (!e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    printPDF();
                }
                break;
            case 'd':
                e.preventDefault();
                document.getElementById('darkModeToggle').click();
                break;
            case '?':
                if (e.shiftKey) {
                    e.preventDefault();
                    showKeyboardShortcuts();
                }
                break;
            case 'escape':
                // Hide any active tooltips
                const tooltip = document.querySelector('.custom-tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
                break;
        }
    });
}

// Show keyboard shortcuts help
function showKeyboardShortcuts() {
    const shortcuts = [
        { key: 'P', action: 'Print CV' },
        { key: 'D', action: 'Toggle dark mode' },
        { key: 'ESC', action: 'Hide tooltips' },
        { key: '?', action: 'Show this help' }
    ];
    
    const helpText = 'Keyboard Shortcuts:\n' + shortcuts.map(s => `${s.key}: ${s.action}`).join('\n');
    showTooltip(helpText, 4000);
}

// Utility Functions
function showTooltip(message, duration = 2000) {
    // Remove existing tooltip
    const existingTooltip = document.querySelector('.custom-tooltip');
    if (existingTooltip) {
        existingTooltip.remove();
    }
    
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.textContent = message;
    tooltip.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #2b2d42;
        color: #edf2f4;
        padding: 12px 24px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        z-index: 10000;
        box-shadow: 0 4px 20px rgba(43, 45, 66, 0.3);
        animation: tooltipSlide 0.3s ease forwards;
        max-width: 300px;
        text-align: center;
        white-space: pre-line;
        border: 1px solid #ef233c;
    `;
    
    document.body.appendChild(tooltip);
    
    setTimeout(() => {
        tooltip.style.animation = 'tooltipSlide 0.3s ease reverse forwards';
        setTimeout(() => {
            if (tooltip && tooltip.parentNode) {
                tooltip.remove();
            }
        }, 300);
    }, duration);
}

// Responsive handling
function addResponsiveHandling() {
    let resizeTimer;
    
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleResize, 250);
    });
    
    function handleResize() {
        const isMobile = window.innerWidth <= 768;
        
        // Adjust animations for mobile
        if (isMobile) {
            document.querySelectorAll('.card').forEach(card => {
                card.style.transform = 'none';
            });
        }
        
        // Update dark mode toggle size
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (window.innerWidth <= 480) {
            darkModeToggle.style.width = '40px';
            darkModeToggle.style.height = '40px';
            darkModeToggle.style.fontSize = '1rem';
        } else {
            darkModeToggle.style.width = '50px';
            darkModeToggle.style.height = '50px';
            darkModeToggle.style.fontSize = '1.2rem';
        }
    }
    
    // Initial call
    handleResize();
}

// Accessibility features
function addAccessibilityFeatures() {
    // Add focus indicators
    const focusableElements = document.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '3px solid #ef233c';
            this.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });
    
    // Add ARIA labels where needed
    document.querySelectorAll('.tech-tag').forEach(tag => {
        tag.setAttribute('role', 'button');
        tag.setAttribute('tabindex', '0');
        tag.setAttribute('aria-label', `Technology: ${tag.textContent}`);
    });
    
    // Add keyboard navigation
    document.querySelectorAll('.tech-tag').forEach(element => {
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
}

// Add required CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes tooltipSlide {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
`;
document.head.appendChild(style);

// Export for external use
window.ResumeUtils = {
    printPDF,
    showTooltip,
    applyTheme
};