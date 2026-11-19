/* Bloc « Grand visuel d'accueil » : si la saisie correspond à au moins une démarche, la recherche ouvre
   le panneau « Vos démarches » filtré ; sinon elle lance directement la recherche dans tout le site
   (pas de double recherche). Sans JavaScript ou sans panneau, le formulaire cherche dans tout le site. */
(function () {
  'use strict';
  var form = document.querySelector('.immersif form');
  if (!form || !document.getElementById('panneau-demarches')) return;
  // Renvoie true si le panneau s'est ouvert (au moins une démarche correspond).
  function ouvrir(texte) {
    var detail = { recherche: texte, siResultats: true };
    document.dispatchEvent(new CustomEvent('vf:ouvrir-demarches', { detail: detail }));
    return detail.ouvert === true;
  }
  form.addEventListener('submit', function (e) {
    var texte = form.querySelector('input').value;
    if (!texte.trim()) { e.preventDefault(); document.dispatchEvent(new CustomEvent('vf:ouvrir-demarches', { detail: { recherche: '' } })); return; }
    // Aucune démarche ne correspond : on laisse le formulaire partir vers la recherche du site.
    if (ouvrir(texte)) e.preventDefault();
  });
  document.querySelectorAll('.heros__suggestions [data-recherche]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      var texte = a.getAttribute('data-recherche');
      if (!ouvrir(texte)) window.location.href = form.getAttribute('action') + '?s=' + encodeURIComponent(texte);
    });
  });
})();
