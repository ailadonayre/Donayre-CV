document.addEventListener('DOMContentLoaded', () => {
    initializePage();
    initializeDarkMode();
    enhanceInteractions();
    addResponsiveHandling();
    addAccessibilityFeatures();

    // For edit_resume trait selector
    if (typeof updateGlobalTraitInputs === 'function') updateGlobalTraitInputs();
});

/* ----------------------------
   PAGE INITIALIZATION
----------------------------- */
function initializePage() {
    document.body.style.transition = 'opacity 0.8s ease-in';
    document.body.style.opacity = '0';
    setTimeout(() => { document.body.style.opacity = '1'; }, 100);

    const profileImg = document.querySelector('.profile-image');
    if (profileImg) {
        const reveal = () => {
            profileImg.style.transition = 'all 0.6s ease';
            profileImg.style.transform = 'scale(1)';
            profileImg.style.opacity = '1';
        };

        profileImg.style.transform = 'scale(0.8)';
        profileImg.style.opacity = '0';

        if (profileImg.complete && profileImg.naturalWidth !== 0) {
            setTimeout(reveal, 150);
        } else {
            profileImg.addEventListener('load', reveal, { once: true });
        }
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('animate-visible');
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.card').forEach(card => observer.observe(card));
}

/* ----------------------------
   DARK MODE
----------------------------- */
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    if (!darkModeToggle) return;

    const savedTheme = localStorage.getItem('resume-theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyTheme('dark');
    } else {
        applyTheme('light');
    }

    darkModeToggle.addEventListener('click', e => {
        e.preventDefault();
        const current = body.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        body.style.transition = 'all 0.3s ease';
        applyTheme(next);
        localStorage.setItem('resume-theme', next);

        darkModeToggle.style.transition = 'transform 0.45s ease';
        darkModeToggle.style.transform = 'rotate(360deg)';
        setTimeout(() => darkModeToggle.style.transform = 'rotate(0deg)', 450);
    });

    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    if (mq.addEventListener) {
        mq.addEventListener('change', e => {
            if (!localStorage.getItem('resume-theme')) applyTheme(e.matches ? 'dark' : 'light');
        });
    } else if (mq.addListener) {
        mq.addListener(e => {
            if (!localStorage.getItem('resume-theme')) applyTheme(e.matches ? 'dark' : 'light');
        });
    }
}

function applyTheme(theme) {
    const body = document.body;
    const toggle = document.getElementById('darkModeToggle');
    if (!toggle) return;
    const icon = toggle.querySelector('i');
    body.setAttribute('data-theme', theme);

    if (theme === 'dark') {
        if (icon) icon.className = 'fa-solid fa-sun';
        toggle.setAttribute('aria-label', 'Switch to light mode');
    } else {
        if (icon) icon.className = 'fa-solid fa-moon';
        toggle.setAttribute('aria-label', 'Switch to dark mode');
    }
}

/* ----------------------------
   INTERACTIONS
----------------------------- */
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

function enhanceCards() {
    document.querySelectorAll('.card').forEach(c => {
        c.addEventListener('mouseenter', () => c.style.transform = 'translateY(-5px)');
        c.addEventListener('mouseleave', () => c.style.transform = 'translateY(0)');
    });
}

function enhanceButtons() {
    const buttons = document.querySelectorAll('.dark-mode-toggle');
    buttons.forEach(btn => {
        btn.addEventListener('mouseenter', () => btn.style.transform = 'translateY(-2px) scale(1.05)');
        btn.addEventListener('mouseleave', () => btn.style.transform = 'translateY(0) scale(1)');
        btn.addEventListener('click', () => {
            btn.style.transform = 'scale(0.95)';
            setTimeout(() => btn.style.transform = 'translateY(-2px) scale(1.05)', 150);
        });
    });
}

function enhanceTechTags() {
    document.querySelectorAll('.tech-tag').forEach(tag => {
        tag.addEventListener('mouseenter', () => {
            tag.style.transform = 'translateY(-2px) scale(1.05)';
            tag.style.boxShadow = '0 6px 15px rgba(239, 35, 60, 0.18)';
        });
        tag.addEventListener('mouseleave', () => {
            tag.style.transform = 'translateY(0) scale(1)';
            tag.style.boxShadow = 'none';
        });
    });
}

function enhanceTimeline() {
    document.querySelectorAll('.timeline-content').forEach(c => {
        c.addEventListener('mouseenter', () => {
            const marker = c.parentElement.querySelector('.timeline-marker');
            if (marker) {
                marker.style.transform = 'scale(1.2)';
                marker.style.background = '#d80032';
            }
        });
        c.addEventListener('mouseleave', () => {
            const marker = c.parentElement.querySelector('.timeline-marker');
            if (marker) {
                marker.style.transform = 'scale(1)';
                marker.style.background = '#ef233c';
            }
        });
    });
}

function enhanceSocialLinks() {
    document.querySelectorAll('.social-icon').forEach(link => {
        link.addEventListener('click', () => {
            link.style.transform = 'scale(0.9)';
            setTimeout(() => link.style.transform = 'scale(1)', 150);
        });
    });
}

function enhanceAchievements() {
    document.querySelectorAll('.achievement-item').forEach(item => {
        item.addEventListener('mouseenter', () => {
            const icon = item.querySelector('.achievement-icon');
            if (icon) icon.style.transform = 'scale(1.1)';
        });
        item.addEventListener('mouseleave', () => {
            const icon = item.querySelector('.achievement-icon');
            if (icon) icon.style.transform = 'scale(1)';
        });
    });
}

function enhanceExperience() {
    document.querySelectorAll('.experience-item').forEach(item => {
        item.addEventListener('mouseenter', () => item.style.transform = 'translateY(-3px)');
        item.addEventListener('mouseleave', () => item.style.transform = 'translateY(0)');
    });

    document.querySelectorAll('.highlight-item').forEach(item => {
        item.addEventListener('mouseenter', () => {
            const icon = item.querySelector('i');
            if (icon) icon.style.transform = 'scale(1.2)';
        });
        item.addEventListener('mouseleave', () => {
            const icon = item.querySelector('i');
            if (icon) icon.style.transform = 'scale(1)';
        });
    });
}

/* ----------------------------
   KEYBOARD SUPPORT
----------------------------- */
function addKeyboardSupport() {
    document.addEventListener('keydown', e => {
        if (e.target && e.target.matches('input, textarea, [contenteditable]')) return;
        const key = (e.key || '').toLowerCase();

        switch (key) {
            case 'd':
                e.preventDefault();
                const toggle = document.getElementById('darkModeToggle');
                if (toggle) toggle.click();
                break;
            case '?':
                if (e.shiftKey) {
                    e.preventDefault();
                    showKeyboardShortcuts();
                }
                break;
            case 'escape':
                const tooltip = document.querySelector('.custom-tooltip');
                if (tooltip) tooltip.remove();
                break;
        }
    });
}

function showKeyboardShortcuts() {
    const shortcuts = [
        { key: 'D', action: 'Toggle dark mode' },
        { key: 'ESC', action: 'Hide tooltips' },
        { key: '?', action: 'Show this help' }
    ];

    const helpText = 'Keyboard Shortcuts:\n' +
        shortcuts.map(s => `${s.key}: ${s.action}`).join('\n');
    showTooltip(helpText, 4000);
}

/* ----------------------------
   TOOLTIP + ACCESSIBILITY
----------------------------- */
function showTooltip(message, duration = 2000) {
    const existing = document.querySelector('.custom-tooltip');
    if (existing) existing.remove();

    const tip = document.createElement('div');
    tip.className = 'custom-tooltip';
    tip.textContent = message;
    tip.style.cssText = `
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

    document.body.appendChild(tip);
    setTimeout(() => {
        tip.style.animation = 'tooltipSlide 0.3s ease reverse forwards';
        setTimeout(() => tip.remove(), 300);
    }, duration);
}

function addAccessibilityFeatures() {
    document.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    ).forEach(el => {
        el.addEventListener('focus', () => {
            el.style.outline = '3px solid #ef233c';
            el.style.outlineOffset = '2px';
        });
        el.addEventListener('blur', () => el.style.outline = 'none');
    });

    document.querySelectorAll('.tech-tag').forEach(tag => {
        tag.setAttribute('role', 'button');
        tag.setAttribute('tabindex', '0');
        tag.setAttribute('aria-label', `Technology: ${tag.textContent}`);
    });

    document.querySelectorAll('.tech-tag').forEach(el => {
        el.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                el.click();
            }
        });
    });
}

/* ----------------------------
   RESPONSIVENESS
----------------------------- */
function addResponsiveHandling() {
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleResize, 250);
    });
    handleResize();

    function handleResize() {
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            document.querySelectorAll('.card').forEach(c => c.style.transform = 'none');
        }

        const toggle = document.getElementById('darkModeToggle');
        if (toggle) {
            if (window.innerWidth <= 480) {
                toggle.style.width = '40px';
                toggle.style.height = '40px';
                toggle.style.fontSize = '1rem';
            } else {
                toggle.style.width = '50px';
                toggle.style.height = '50px';
                toggle.style.fontSize = '1.2rem';
            }
        }
    }
}

/* Tooltip animation */
const style = document.createElement('style');
style.textContent = `
    @keyframes tooltipSlide {
        from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
`;
document.head.appendChild(style);

/* Expose minimal utilities */
window.ResumeUtils = {
    showTooltip,
    applyTheme
};

/* Trait helper for edit_resume.php */
function toggleGlobalTrait(el) {
    if (!el) return;
    el.classList.toggle('selected');
    updateGlobalTraitInputs();
}

function updateGlobalTraitInputs() {
    const container = document.getElementById('experience-traits-global-inputs');
    if (!container) return;
    container.innerHTML = '';
    document.querySelectorAll('#experience-traits-global .trait-option.selected').forEach(opt => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'experience_traits_global[]';
        input.value = opt.dataset.value;
        container.appendChild(input);
    });
}