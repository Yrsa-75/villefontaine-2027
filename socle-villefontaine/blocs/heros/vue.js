/* Bloc « Grand visuel d'accueil » : la recherche ouvre le panneau « Vos démarches » avec la saisie
   (s'il est présent). Sans JavaScript ou sans panneau, le formulaire cherche dans tout le site. */
(function () {
  'use strict';
  var form = document.querySelector('.immersif form');
  if (!form || !document.getElementById('panneau-demarches')) return;
  function ouvrir(texte) { document.dispatchEvent(new CustomEvent('vf:ouvrir-demarches', { detail: { recherche: texte } })); }
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    ouvrir(form.querySelector('input').value);
  });
  document.querySelectorAll('.heros__suggestions [data-recherche]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      ouvrir(a.getAttribute('data-recherche'));
    });
  });
})();
