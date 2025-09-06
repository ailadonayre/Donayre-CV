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
    addKeyboardSupport();
}

// Card hover effects
function enhanceCards() {
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        // Add click ripple effect
        card.addEventListener('click', function(e) {
            createRipple(e, this);
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

// Tech tag interactions
function enhanceTechTags() {
    document.querySelectorAll('.tech-tag').forEach(tag => {
        tag.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add pulse animation
            this.style.animation = 'none';
            setTimeout(() => {
                this.style.animation = 'pulse 0.6s ease-in-out';
            }, 10);
            
            // Create floating text effect
            createFloatingText(this.textContent, e.clientX, e.clientY);
            
            // Show tooltip
            showTooltip(`${this.textContent} - Technology skill!`);
        });

        // Enhanced hover with stagger effect
        tag.addEventListener('mouseenter', function() {
            const allTags = this.parentElement.querySelectorAll('.tech-tag');
            allTags.forEach((otherTag, index) => {
                if (otherTag !== this) {
                    setTimeout(() => {
                        otherTag.style.transform = 'scale(0.95)';
                        otherTag.style.opacity = '0.7';
                    }, index * 20);
                }
            });
        });
        
        tag.addEventListener('mouseleave', function() {
            const allTags = this.parentElement.querySelectorAll('.tech-tag');
            allTags.forEach(otherTag => {
                otherTag.style.transform = 'scale(1)';
                otherTag.style.opacity = '1';
            });
        });
    });
}

// Timeline animations
function enhanceTimeline() {
    document.querySelectorAll('.timeline-content').forEach(content => {
        content.addEventListener('mouseenter', function() {
            const marker = this.parentElement.querySelector('.timeline-marker');
            if (marker) {
                marker.style.transform = 'scale(1.3)';
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
        
        // Staggered hover effect for group
        link.addEventListener('mouseenter', function() {
            const container = this.closest('.profile-social');
            if (container) {
                const allLinks = container.querySelectorAll('.social-icon');
                allLinks.forEach((otherLink, index) => {
                    if (otherLink !== this) {
                        setTimeout(() => {
                            otherLink.style.transform = 'scale(0.9) translateY(2px)';
                        }, index * 30);
                    }
                });
            }
        });
        
        link.addEventListener('mouseleave', function() {
            const container = this.closest('.profile-social');
            if (container) {
                const allLinks = container.querySelectorAll('.social-icon');
                allLinks.forEach(otherLink => {
                    otherLink.style.transform = 'scale(1) translateY(0)';
                });
            }
        });
    });
}

// Achievement interactions
function enhanceAchievements() {
    document.querySelectorAll('.achievement-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.achievement-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.achievement-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
        
        item.addEventListener('click', function(e) {
            const title = this.querySelector('.achievement-title').textContent;
            showTooltip(`Achievement: ${title}`);
            createRipple(e, this);
        });
    });
}

// Print/Download PDF functionality
function printPDF() {
    const pdfPath = 'assets/pdf/Donayre-CV.pdf';
    
    // Show loading state
    const printBtn = document.querySelector('.btn-print');
    const originalContent = printBtn.innerHTML;
    printBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Loading...</span>';
    printBtn.disabled = true;
    
    // Create a temporary anchor element for download
    const downloadLink = document.createElement('a');
    downloadLink.href = pdfPath;
    downloadLink.download = 'John_Doe_Resume.pdf';
    downloadLink.style.display = 'none';
    
    document.body.appendChild(downloadLink);
    
    // Check if the file exists by trying to fetch it
    fetch(pdfPath)
        .then(response => {
            if (response.ok) {
                // File exists, proceed with download
                downloadLink.click();
                showTooltip('CV download started!');
            } else {
                // File doesn't exist, show error
                throw new Error('PDF file not found');
            }
        })
        .catch(error => {
            console.error('Error downloading PDF:', error);
            showTooltip('PDF file not found. Please check the file path.', 3000);
            
            // Fallback: try to open in new tab
            const newWindow = window.open(pdfPath, '_blank');
            if (!newWindow) {
                showTooltip('Please allow pop-ups to download the CV.', 3000);
            }
        })
        .finally(() => {
            // Restore button
            setTimeout(() => {
                printBtn.innerHTML = originalContent;
                printBtn.disabled = false;
            }, 1000);
            
            // Remove temporary link
            document.body.removeChild(downloadLink);
        });
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
        { key: 'P', action: 'Download CV' },
        { key: 'D', action: 'Toggle dark mode' },
        { key: 'ESC', action: 'Hide tooltips' },
        { key: '?', action: 'Show this help' }
    ];
    
    const helpText = 'Keyboard Shortcuts:\n' + shortcuts.map(s => `${s.key}: ${s.action}`).join('\n');
    showTooltip(helpText, 4000);
}

// Utility Functions
function createRipple(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(239, 35, 60, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: rippleEffect 0.6s ease-out;
        pointer-events: none;
        z-index: 1;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

function createFloatingText(text, x, y) {
    const floatingEl = document.createElement('div');
    floatingEl.textContent = text;
    floatingEl.style.cssText = `
        position: fixed;
        left: ${x}px;
        top: ${y}px;
        pointer-events: none;
        color: #ef233c;
        font-weight: 700;
        font-size: 1rem;
        z-index: 10000;
        animation: floatUp 2s ease forwards;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    `;
    
    document.body.appendChild(floatingEl);
    
    setTimeout(() => {
        floatingEl.remove();
    }, 2000);
}

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
    
    document.querySelectorAll('.achievement-item').forEach(item => {
        item.setAttribute('role', 'button');
        item.setAttribute('tabindex', '0');
        const title = item.querySelector('.achievement-title').textContent;
        item.setAttribute('aria-label', `Achievement: ${title}`);
    });
    
    // Add keyboard navigation
    document.querySelectorAll('.tech-tag, .achievement-item').forEach(element => {
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
    @keyframes rippleEffect {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    @keyframes floatUp {
        0% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        100% {
            opacity: 0;
            transform: translateY(-80px) scale(0.8);
        }
    }
    
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
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);

// Export for external use
window.ResumeUtils = {
    printPDF,
    showTooltip,
    applyTheme
};