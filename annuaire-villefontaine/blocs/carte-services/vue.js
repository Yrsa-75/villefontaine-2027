/* ==========================================================================
   Carte des services : Leaflet (hébergé sur le site) + fond Plan IGN.
   Filtres par catégorie, liste accessible synchronisée avec la carte.
   Le texte des bulles est inséré comme texte (jamais comme HTML).
   ========================================================================== */
(function () {
  'use strict';
  var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var cartes = document.querySelectorAll('.carte-ville[data-points]');
  if (!cartes.length) return;

  /* Performance : la bibliothèque de carte (environ 160 Ko) n'est téléchargée que lorsque la carte
     approche de l'écran, et non au chargement de la page. */
  var promesse = null;
  function chargerLeaflet() {
    if (window.L) return Promise.resolve();
    if (promesse) return promesse;
    var res = window.vfAnnuaire || {};
    promesse = new Promise(function (ok, ko) {
      var css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = res.css;
      document.head.appendChild(css);
      var js = document.createElement('script');
      js.src = res.js;
      js.onload = ok;
      js.onerror = ko;
      document.head.appendChild(js);
    });
    return promesse;
  }
  function demarrer(el) { chargerLeaflet().then(function () { initialiser(el); }).catch(function () {}); }
  if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function (entrees) {
      entrees.forEach(function (e) { if (e.isIntersecting) { obs.unobserve(e.target); demarrer(e.target); } });
    }, { rootMargin: '400px 0px' });
    cartes.forEach(function (el) { obs.observe(el); });
  } else {
    cartes.forEach(demarrer);
  }

  function initialiser(el) {
    var points, couleurs;
    try { points = JSON.parse(el.getAttribute('data-points')); couleurs = JSON.parse(el.getAttribute('data-couleurs')); } catch (e) { return; }
    if (!points.length) return;

    var carte = L.map(el, { center: [45.6135, 5.15], zoom: 14, minZoom: 11, maxZoom: 18, scrollWheelZoom: false, zoomControl: false });
    // Boutons de zoom en français (Leaflet les fournit en anglais).
    L.control.zoom({ zoomInTitle: 'Zoom avant', zoomOutTitle: 'Zoom arrière' }).addTo(carte);
    // Bouton de fermeture des bulles en français.
    carte.on('popupopen', function (e) {
      var b = e.popup.getElement() && e.popup.getElement().querySelector('.leaflet-popup-close-button');
      if (b) { b.setAttribute('aria-label', 'Fermer la bulle'); b.setAttribute('title', 'Fermer la bulle'); }
    });
    carte.once('click focus', function () { carte.scrollWheelZoom.enable(); });

    L.tileLayer(
      'https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2&STYLE=normal&TILEMATRIXSET=PM&FORMAT=image/png&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}',
      { maxZoom: 18, attribution: '&copy; <a href="https://www.ign.fr/" target="_blank" rel="noopener">IGN<span class="visuellement-masque"> (nouvelle fenêtre)</span></a> Plan IGN' }
    ).addTo(carte);
    carte.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet<span class="visuellement-masque"> (nouvelle fenêtre)</span></a>');

    function bulle(p) {
      var div = L.DomUtil.create('div', 'bulle-lieu');
      var cat = L.DomUtil.create('p', 'bulle-lieu__cat', div); cat.textContent = p.categorie;
      var h = L.DomUtil.create('h3', '', div); h.textContent = p.nom;
      if (p.adresse || p.ville) { var adr = L.DomUtil.create('p', '', div); adr.textContent = [p.adresse, p.ville].filter(Boolean).join(', '); }
      if (p.horaires) { var hr = L.DomUtil.create('p', '', div); hr.textContent = p.horaires; }
      if (p.telephone) { var t = L.DomUtil.create('a', '', div); t.href = 'tel:' + p.telephone.replace(/[^0-9+]/g, ''); t.textContent = p.telephone; }
      if (p.url) { var f = L.DomUtil.create('a', 'bulle-lieu__fiche', div); f.href = p.url; f.textContent = 'Voir la fiche'; }
      return div;
    }

    var marqueurs = {};
    var groupe = L.featureGroup().addTo(carte);
    points.forEach(function (p, i) {
      var icone = L.divIcon({
        className: 'epingle-vf',
        html: '<span style="--c: ' + (couleurs[p.categorie] || '#246170').replace(/[^#0-9a-fA-F]/g, '') + '; --d: ' + (reduit ? 0 : Math.min(i, 20) * 0.08) + 's"></span>',
        iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -38]
      });
      var m = L.marker([p.lat, p.lng], { icon: icone, title: p.nom, alt: p.nom, keyboard: true, riseOnHover: true });
      m.bindPopup(bulle(p), { maxWidth: 260 });
      m.categorie = p.categorie;
      m.addTo(groupe);
      marqueurs[p.id] = m;
    });
    carte.fitBounds(groupe.getBounds(), { padding: [50, 50], maxZoom: 15 });

    var section = el.closest('section') || document;
    var boutons = section.querySelectorAll('.filtre-carte');
    var compte = section.querySelector('[data-compte]');
    var annonce = section.querySelector('[data-annonce]');
    function appliquer(annoncer) {
      var actives = {};
      boutons.forEach(function (b) { if (b.getAttribute('aria-pressed') === 'true') actives[b.getAttribute('data-categorie')] = true; });
      var n = 0;
      Object.keys(marqueurs).forEach(function (id) {
        var m = marqueurs[id];
        if (actives[m.categorie]) { if (!groupe.hasLayer(m)) groupe.addLayer(m); n++; } else { groupe.removeLayer(m); }
      });
      section.querySelectorAll('.liste-lieux li').forEach(function (li) { li.hidden = !actives[li.getAttribute('data-categorie')]; });
      if (compte) compte.textContent = n;
      // Message de statut (RGAA 7.5) : nombre de lieux affichés après un filtre.
      if (annoncer && annonce) annonce.textContent = n + (n > 1 ? ' lieux affichés' : ' lieu affiché') + ' sur la carte et dans la liste.';
    }
    boutons.forEach(function (b) {
      b.addEventListener('click', function () {
        b.setAttribute('aria-pressed', b.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
        appliquer(true);
      });
    });
    section.querySelectorAll('.liste-lieux button[data-point]').forEach(function (b) {
      b.addEventListener('click', function () {
        var m = marqueurs[b.getAttribute('data-point')];
        if (!m) return;
        if (!groupe.hasLayer(m)) groupe.addLayer(m);
        carte.flyTo(m.getLatLng(), 16, { duration: reduit ? 0 : 0.8 });
        setTimeout(function () { m.openPopup(); }, reduit ? 0 : 850);
        el.scrollIntoView({ behavior: reduit ? 'auto' : 'smooth', block: 'center' });
      });
    });
    setTimeout(function () { carte.invalidateSize(); }, 900);
  }
})();
