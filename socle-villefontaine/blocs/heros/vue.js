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

/* Diaporama du grand visuel : les photos suivantes sont chargées une fois la page affichée, puis se succèdent
   en fondu. Un bouton permet de le mettre en pause (RGAA 13.8). Il ne démarre pas tout seul si la personne a
   demandé à son appareil de réduire les animations, et s'arrête quand l'onglet n'est pas affiché.
   Sans JavaScript, seule la première photo est affichée. */
(function () {
  'use strict';
  var section = document.querySelector('.immersif[data-diaporama]');
  var calque = section && section.querySelector('.immersif__photos');
  var premiere = calque && calque.querySelector('.immersif__photo');
  if (!premiere) return;
  var sources;
  try { sources = JSON.parse(section.getAttribute('data-diaporama')); } catch (e) { return; }
  if (!sources || !sources.length) return;
  var duree = Math.max(3, parseInt(section.getAttribute('data-duree'), 10) || 5) * 1000;
  var photos = [premiere];          // Photos prêtes à être affichées (la première l'est déjà).
  var courante = 0, minuterie = null, nettoyage = null;
  var reduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var enPause = reduit;             // Choix de la personne (ou réglage « moins d'animations »).
  var bouton = null;
  var ICONES = {
    pause: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>',
    lecture: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>'
  };

  function suivante() {
    if (photos.length < 2) return;
    var avant = photos[courante];
    courante = (courante + 1) % photos.length;
    var apres = photos[courante];
    photos.forEach(function (p) { if (p !== avant && p !== apres) { p.classList.remove('est-affichee', 'est-dessous'); p.style.zIndex = 0; } });
    avant.classList.remove('est-affichee'); avant.classList.add('est-dessous'); avant.style.zIndex = 1;
    apres.style.zIndex = 2; apres.classList.remove('est-dessous');
    void apres.offsetWidth;         // Force le navigateur à partir d'une photo invisible, pour que le fondu se voie.
    apres.classList.add('est-affichee');
    clearTimeout(nettoyage);
    nettoyage = setTimeout(function () { avant.classList.remove('est-dessous'); }, 1300);
  }
  function arreter() { clearInterval(minuterie); minuterie = null; }
  function lancer() { if (!minuterie && !enPause && !document.hidden && photos.length > 1) minuterie = setInterval(suivante, duree); }
  function majBouton() {
    if (!bouton) return;
    bouton.innerHTML = (enPause ? ICONES.lecture : ICONES.pause) + '<span class="visuellement-masque">' + (enPause ? 'Lancer le diaporama des photos' : 'Mettre en pause le diaporama des photos') + '</span>';
  }
  function creerBouton() {
    if (bouton) return;
    bouton = document.createElement('button');
    bouton.type = 'button';
    bouton.className = 'immersif__pause';
    bouton.addEventListener('click', function () { enPause = !enPause; majBouton(); if (enPause) arreter(); else { suivante(); lancer(); } });
    majBouton();
    section.appendChild(bouton);
  }
  function charger() {
    premiere.classList.add('est-affichee');
    calque.classList.add('est-diaporama');
    sources.forEach(function (s) {
      var img = new Image();
      img.alt = '';
      img.decoding = 'async';
      img.className = 'immersif__photo immersif__photo--suite';
      img.sizes = '100vw';
      if (s.srcset) img.srcset = s.srcset;
      if (s.largeur) { img.width = s.largeur; img.height = s.hauteur; }
      img.onload = function () {
        calque.appendChild(img);
        photos.push(img);
        creerBouton();
        lancer();
      };
      img.src = s.src;
    });
  }
  document.addEventListener('visibilitychange', function () { if (document.hidden) arreter(); else lancer(); });
  // Après le chargement complet de la page, pour ne rien retirer à l'affichage de la première photo.
  function apresChargement() { (window.requestIdleCallback || function (f) { setTimeout(f, 600); })(charger); }
  if (document.readyState === 'complete') apresChargement(); else window.addEventListener('load', apresChargement);
})();
