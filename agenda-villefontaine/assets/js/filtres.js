/* ==========================================================================
   Agenda : filtres par thématique (boutons à bascule), sans rechargement.
   N'affiche que « data-max » événements à la fois ; annonce le résultat
   aux lecteurs d'écran.
   ========================================================================== */
(function () {
  'use strict';
  var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('.vf-agenda').forEach(function (bloc) {
    var max = parseInt(bloc.getAttribute('data-max'), 10) || 999;
    var filtres = bloc.querySelectorAll('[data-filtre]');
    var cartes = bloc.querySelectorAll('.agenda > li[data-theme]');
    var annonce = bloc.querySelector('[data-annonce]');
    var vide = bloc.querySelector('.agenda__vide');
    var mois = bloc.querySelectorAll('[data-mois]');

    function appliquer(choix, animer) {
      var n = 0;
      cartes.forEach(function (li) {
        var montre = (choix === 'tout' || li.getAttribute('data-theme') === choix) && n < max;
        li.hidden = !montre;
        if (montre) {
          n++;
          if (animer && !reduit) {
            li.style.opacity = '0'; li.style.transform = 'translateY(12px)';
            requestAnimationFrame(function () { requestAnimationFrame(function () { li.style.opacity = ''; li.style.transform = ''; }); });
          }
        }
      });
      /* Page Agenda : masque les mois devenus vides */
      mois.forEach(function (m) { m.hidden = !m.querySelector('.agenda > li:not([hidden])'); });
      if (vide) vide.hidden = n !== 0;
      return n;
    }
    appliquer('tout', false);
    filtres.forEach(function (bouton) {
      bouton.addEventListener('click', function () {
        filtres.forEach(function (b) { b.setAttribute('aria-pressed', b === bouton ? 'true' : 'false'); });
        var n = appliquer(bouton.getAttribute('data-filtre'), true);
        if (annonce) annonce.textContent = n + (n > 1 ? ' événements affichés' : ' événement affiché');
      });
    });
  });
})();
