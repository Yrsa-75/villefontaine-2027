/* ==========================================================================
   Kiosque : lecture en ligne dans une fenêtre.
   RGPD : le lecteur Calaméo (service externe) n'est chargé qu'après un clic
   explicite sur « Afficher la publication ». Le choix peut être retenu
   pour la durée de la visite (stockage de session, effacé à la fermeture).
   ========================================================================== */
(function () {
  'use strict';
  var liens = document.querySelectorAll('[data-kiosque] a[data-calameo]');
  if (!liens.length || typeof HTMLDialogElement === 'undefined') return;

  function accordRetenu() { try { return sessionStorage.getItem('vf-calameo') === 'ok'; } catch (e) { return false; } }
  function retenirAccord() { try { sessionStorage.setItem('vf-calameo', 'ok'); } catch (e) { /* stockage indisponible : on redemandera */ } }

  var d = document.createElement('dialog');
  d.className = 'liseuse';
  d.setAttribute('aria-labelledby', 'liseuse-titre');
  d.innerHTML = '<div class="liseuse__tete"><h2 id="liseuse-titre"></h2><button type="button" class="bouton-rond" data-fermer aria-label="Fermer la publication"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div><div class="liseuse__corps"></div>';
  document.body.appendChild(d);
  var titre = d.querySelector('#liseuse-titre');
  var corps = d.querySelector('.liseuse__corps');
  var declencheur = null;

  function afficherLecteur(code, nom) {
    corps.textContent = '';
    var f = document.createElement('iframe');
    f.src = 'https://v.calameo.com/?bkcode=' + encodeURIComponent(code) + '&mode=viewer&showsharemenu=false';
    f.title = 'Publication : ' + nom;
    f.setAttribute('allowfullscreen', '');
    f.setAttribute('loading', 'eager');
    corps.appendChild(f);
  }

  function demanderAccord(code, nom, pdf) {
    corps.textContent = '';
    var bloc = document.createElement('div');
    bloc.className = 'liseuse__accord';
    var p1 = document.createElement('p');
    p1.textContent = 'Cette publication est affichée avec Calaméo, un service externe qui peut déposer des cookies sur votre appareil.';
    var p2 = document.createElement('p');
    p2.textContent = pdf ? 'Vous pouvez aussi télécharger le PDF, sans service externe.' : 'Rien n’est chargé tant que vous n’avez pas accepté.';
    var actions = document.createElement('div'); actions.className = 'liseuse__actions';
    var ok = document.createElement('button'); ok.type = 'button'; ok.className = 'bouton'; ok.textContent = 'Afficher la publication';
    actions.appendChild(ok);
    if (pdf) { var a = document.createElement('a'); a.className = 'bouton bouton--contour'; a.href = pdf; a.setAttribute('download', ''); a.textContent = 'Télécharger le PDF'; actions.appendChild(a); }
    var lab = document.createElement('label'); lab.className = 'liseuse__souvenir';
    var cb = document.createElement('input'); cb.type = 'checkbox';
    lab.appendChild(cb); lab.appendChild(document.createTextNode('Ne plus me demander pendant cette visite'));
    bloc.appendChild(p1); bloc.appendChild(p2); bloc.appendChild(actions); bloc.appendChild(lab);
    corps.appendChild(bloc);
    ok.addEventListener('click', function () { if (cb.checked) retenirAccord(); afficherLecteur(code, nom); });
    ok.focus();
  }

  liens.forEach(function (lien) {
    lien.addEventListener('click', function (e) {
      e.preventDefault();
      declencheur = lien;
      var nom = lien.getAttribute('data-titre');
      titre.textContent = nom;
      d.showModal();
      if (accordRetenu()) afficherLecteur(lien.getAttribute('data-calameo'), nom);
      else demanderAccord(lien.getAttribute('data-calameo'), nom, lien.getAttribute('data-pdf'));
    });
  });
  d.querySelector('[data-fermer]').addEventListener('click', function () { d.close(); });
  d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
  d.addEventListener('close', function () { corps.textContent = ''; if (declencheur) declencheur.focus(); });
})();
