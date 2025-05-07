import './bootstrap.js'
import './styles/app.css'

import Alpine from 'alpinejs'
import { carouselComponent, sliderCategorie } from './js/home' // le chemin est relatif à assets/

window.Alpine = Alpine

// 🔐 Enregistrement des composants
Alpine.data('carouselComponent', carouselComponent)
Alpine.data('sliderCategorie', sliderCategorie)

Alpine.start()

document.addEventListener('DOMContentLoaded', function () {
    const originalNav = document.querySelector('nav[aria-label="Navigation principale"]');
    let fixedNav = null;
    let isFixedVisible = false;

    function createFixedNav() {
        if (fixedNav) return;

        fixedNav = originalNav.cloneNode(true);
        fixedNav.classList.add('fixed', 'top-0', 'left-0', 'right-0', 'z-50', 'shadow-md');
        fixedNav.classList.remove('hidden');
        fixedNav.style.display = 'none';
        document.body.appendChild(fixedNav);
    }

    function updateFixedNavVisibility() {
        const isLargeScreen = window.innerWidth >= 1024;
        const scrollThreshold = document.querySelector('header')?.offsetHeight || 80;

        if (isLargeScreen) {
            createFixedNav();
            if (window.scrollY > scrollThreshold) {
                if (!isFixedVisible) {
                    fixedNav.style.display = 'block';
                    isFixedVisible = true;
                }
            } else {
                if (isFixedVisible) {
                    fixedNav.style.display = 'none';
                    isFixedVisible = false;
                }
            }
        } else {
            if (fixedNav) {
                fixedNav.style.display = 'none';
                isFixedVisible = false;
            }
        }
    }

    window.addEventListener('scroll', updateFixedNavVisibility);
    window.addEventListener('resize', updateFixedNavVisibility);
    updateFixedNavVisibility();
});