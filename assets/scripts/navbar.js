// Navbar Enhanced JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.custom-navbar');
    const navLinks = document.querySelectorAll('.custom-nav-link');
    const dropdownItems = document.querySelectorAll('.custom-dropdown-item');

    // Effet de scroll sur la navbar
    let lastScrollTop = 0;

    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Ajouter/retirer la classe scrolled pour l'effet de réduction
        if (scrollTop > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }

        // Effet de masquage/affichage lors du scroll
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scroll vers le bas - masquer la navbar
            navbar.style.transform = 'translateY(-100%)';
        } else {
            // Scroll vers le haut - afficher la navbar
            navbar.style.transform = 'translateY(0)';
        }

        lastScrollTop = scrollTop;
    }

    // Throttle la fonction de scroll pour de meilleures performances
    let ticking = false;
    function updateScroll() {
        if (!ticking) {
            requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }

    window.addEventListener('scroll', updateScroll, { passive: true });

    // Animation des liens au survol
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });

        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });
    });

    // Animation des éléments dropdown
    dropdownItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(8px) scale(1.02)';
        });

        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0) scale(1)';
        });
    });

    // Amélioration du toggler mobile - sans interférer avec Bootstrap
    const toggler = document.querySelector('.custom-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (toggler && navbarCollapse) {
        // Écouter l'événement Bootstrap plutôt que le clic direct
        navbarCollapse.addEventListener('shown.bs.collapse', function() {
            this.style.animation = 'navSlideIn 0.3s ease-out';
        });
    }

    // Fermer le menu mobile lors du clic sur un lien - sans interférer avec Bootstrap
    const mobileLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                // Utiliser l'API Bootstrap pour fermer proprement
                const collapseInstance = bootstrap.Collapse.getOrCreateInstance(navbarCollapse);
                collapseInstance.hide();
            }
        });
    });

    // Effet de parallaxe subtil pour la navbar
    function handleParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.2;

        if (navbar) {
            navbar.style.backgroundPosition = `center ${rate}px`;
        }
    }

    window.addEventListener('scroll', handleParallax, { passive: true });

    // Animation d'apparition progressive des éléments de navigation
    function animateNavItems() {
        const navItems = document.querySelectorAll('.nav-item');

        navItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                item.style.transition = 'all 0.3s ease-out';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Déclencher l'animation au chargement
    animateNavItems();

    // Amélioration de l'accessibilité
    navLinks.forEach(link => {
        link.addEventListener('focus', function() {
            this.style.outline = '2px solid var(--primary)';
            this.style.outlineOffset = '2px';
        });

        link.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });

    // Gestion des raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Alt + M pour ouvrir/fermer le menu mobile
        if (e.altKey && e.key === 'm') {
            e.preventDefault();
            if (toggler && window.innerWidth < 992) {
                toggler.click();
            }
        }

        // Échap pour fermer les dropdowns - utiliser l'API Bootstrap
        if (e.key === 'Escape') {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                const dropdownInstance = bootstrap.Dropdown.getOrCreateInstance(dropdown.previousElementSibling);
                dropdownInstance.hide();
            });
        }
    });

    // Indicateur de progression de scroll
    function createScrollProgress() {
        const progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress';
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            z-index: 1001;
            transition: width 0.1s ease-out;
        `;

        document.body.appendChild(progressBar);

        function updateProgress() {
            const scrollTop = window.pageYOffset;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;

            progressBar.style.width = scrollPercent + '%';
        }

        window.addEventListener('scroll', updateProgress, { passive: true });
    }

    // Activer l'indicateur de progression
    createScrollProgress();

    // S'assurer que Bootstrap dropdown fonctionne correctement
    const dropdownToggleList = document.querySelectorAll('.dropdown-toggle');
    dropdownToggleList.forEach(function(dropdownToggleEl) {
        // Initialiser explicitement chaque dropdown
        new bootstrap.Dropdown(dropdownToggleEl);
    });
});
