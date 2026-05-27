// /home/lenovo/work/projects/the_pilates_studio_sy/public/js/web/landing/landing.js
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    const header = document.getElementById('main-header');
    if (header) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 50) {
                header.classList.add('glass', 'shadow-sm');
                header.classList.remove('bg-transparent');
            } else {
                header.classList.remove('glass', 'shadow-sm');
                header.classList.add('bg-transparent');
            }
        });
    }

    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = menuBtn.querySelector('i, svg');
            if (icon) {
                if (mobileMenu.classList.contains('hidden')) {
                    icon.setAttribute('data-lucide', 'menu');
                } else {
                    icon.setAttribute('data-lucide', 'x');
                }
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        });
    }

    function initDarkToggle(toggleId) {
        const toggle = document.getElementById(toggleId);
        if (toggle) {
            toggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            });
        }
    }

    initDarkToggle('dark-toggle');
    initDarkToggle('dark-toggle-mobile');

    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    }

    const filterBtns = document.querySelectorAll('.filter-btn');
    const classCards = document.querySelectorAll('.class-card');

    if (filterBtns.length > 0 && classCards.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;

                filterBtns.forEach(b => {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                classCards.forEach(card => {
                    const category = card.dataset.category;
                    if (filter === 'all' || category === filter) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 50);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(10px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });
    }

    const scheduleTabs = document.querySelectorAll('.schedule-tab');
    const scheduleDays = document.querySelectorAll('.schedule-day');
    const activeDayLabel = document.getElementById('active-day-label');

    if (scheduleTabs.length > 0 && scheduleDays.length > 0) {
        function showDay(index) {
            scheduleTabs.forEach(t => t.classList.remove('active'));
            scheduleTabs[index].classList.add('active');

            scheduleDays.forEach(d => {
                d.classList.add('hidden');
                d.classList.remove('block', 'visible');
            });

            const target = document.querySelector(`.schedule-day[data-day-index="${index}"]`);
            if (target) {
                target.classList.remove('hidden');
                target.classList.add('block', 'visible');
            }

            if (activeDayLabel) {
                const tab = scheduleTabs[index];
                const dayNameEl = tab.querySelector('span:first-child');
                const dateEl = tab.querySelector('span:last-child');
                const dayName = dayNameEl ? dayNameEl.textContent : '';
                const date = dateEl ? dateEl.textContent : '';
                activeDayLabel.innerHTML = `<span class="text-sm font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-4 py-2 rounded-full">${dayName} \u2013 ${date}</span>`;
            }
        }

        scheduleTabs.forEach((tab, idx) => {
            tab.addEventListener('click', () => showDay(idx));
        });

        showDay(0);
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
});

window.handleDeepLink = function(event, scheme, sessionId) {
    event.preventDefault();
    const start = Date.now();
    const fallbackTimeout = 700;
    const deepLink = scheme + '://sessions/' + sessionId;

    window.location.href = deepLink;

    setTimeout(function() {
        const elapsed = Date.now() - start;
        if (elapsed < fallbackTimeout + 50) {
            document.querySelector('#download').scrollIntoView({ behavior: 'smooth' });
        }
    }, fallbackTimeout);
};

window.toggleLangDropdown = function() {
    const dropdown = document.getElementById('lang-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
};

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('lang-dropdown');
    const switcher = document.getElementById('lang-switcher');
    if (dropdown && switcher && !switcher.contains(e.target) && !dropdown.classList.contains('hidden')) {
        dropdown.classList.add('hidden');
    }
});
