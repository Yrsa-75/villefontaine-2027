/* ==========================================================================
   Schéma interactif : ouverture des fiches depuis les points de l'image.
   Sans ce script, la liste complète des fiches reste lisible sous l'image.
   Accessibilité : chaque point est un bouton (aria-expanded), Échap referme
   la fiche et rend le focus au point, les bulles des sigles se ferment à Échap.
   ========================================================================== */
(function () {
  'use strict';

  document.querySelectorAll('[data-schema]').forEach(function (schema) {
    var points = Array.prototype.slice.call(schema.querySelectorAll('.schema__point'));
    if (!points.length) return;

    function bulleDe(point) { return document.getElementById(point.getAttribute('aria-controls')); }
    function ficheDe(point) { return schema.querySelector('[data-fiche="' + point.getAttribute('data-point') + '"]'); }

    function fermer(point, rendreFocus) {
      var bulle = bulleDe(point), fiche = ficheDe(point);
      if (bulle) bulle.hidden = true;
      if (fiche) fiche.classList.remove('est-actif');
      point.setAttribute('aria-expanded', 'false');
      if (rendreFocus) point.focus();
    }
    function ouvert() { return points.filter(function (p) { return p.getAttribute('aria-expanded') === 'true'; })[0] || null; }
    function ouvrir(point) {
      points.forEach(function (p) { if (p !== point) fermer(p, false); });
      var bulle = bulleDe(point), fiche = ficheDe(point);
      if (fiche) fiche.classList.add('est-actif');
      point.setAttribute('aria-expanded', 'true');
      if (bulle) {
        bulle.hidden = false;
        // Le focus passe à la fiche : au clavier, la touche Tab continue dans ses liens ; un lecteur d'écran en lit le titre.
        bulle.focus({ preventScroll: window.matchMedia('(min-width: 761px)').matches });
      }
    }

    points.forEach(function (point) {
      var bulle = bulleDe(point), fiche = ficheDe(point);
      point.addEventListener('click', function () {
        if (point.getAttribute('aria-expanded') === 'true') fermer(point, false); else ouvrir(point);
      });
      if (bulle) {
        var croix = bulle.querySelector('[data-fermer]');
        if (croix) croix.addEventListener('click', function () { fermer(point, true); });
      }
      // Survol d'une fiche de la liste : le point correspondant ressort sur l'image.
      if (fiche) {
        fiche.addEventListener('mouseenter', function () { point.classList.add('est-survole'); });
        fiche.addEventListener('mouseleave', function () { point.classList.remove('est-survole'); });
      }
    });

    schema.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      // Une bulle de sigle affichée se referme d'abord ; sinon c'est la fiche ouverte.
      var sigle = e.target.closest ? e.target.closest('.sigle') : null;
      if (sigle && !sigle.classList.contains('est-ferme')) { sigle.classList.add('est-ferme'); return; }
      var p = ouvert();
      if (p) fermer(p, true);
    });

    // Un clic en dehors de l'image referme la fiche ouverte.
    document.addEventListener('click', function (e) {
      var p = ouvert();
      if (p && !e.target.closest('.schema__image')) fermer(p, false);
    });

    // Bulles des sigles : refermées par Échap, elles redeviennent disponibles au survol ou focus suivant.
    schema.querySelectorAll('.sigle').forEach(function (sigle) {
      ['mouseleave', 'blur'].forEach(function (evt) {
        sigle.addEventListener(evt, function () { sigle.classList.remove('est-ferme'); });
      });
    });
  });
})();
