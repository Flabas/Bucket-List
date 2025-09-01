import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

import 'bootstrap'

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Gestion de l’ombre de la navbar au scroll
(function () {
  const navbars = Array.from(document.querySelectorAll('.navbar-glass, .navbar-liquid'));
  function setupScroll() {
    if (!navbars.length) return;
    const onScroll = () => {
      const scrolled = window.scrollY > 8;
      navbars.forEach(nb => nb.classList.toggle('is-scrolled', scrolled));
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }
  document.addEventListener('DOMContentLoaded', setupScroll);
})();
