/* Formulaire de contact : vérification accessible puis envoi sans rechargement. */
(function () {
  'use strict';
  var MESSAGES = {
    nom: 'Indiquez votre nom et prénom.',
    courriel: 'Indiquez une adresse électronique valide, par exemple nom@exemple.fr.',
    telephone: 'Indiquez un numéro de téléphone valide, par exemple 06 12 34 56 78.',
    objet: 'Indiquez l’objet de votre message.',
    message: 'Écrivez votre message.',
    service: 'Choisissez le service à contacter.',
    accord: 'Cochez la case pour accepter l’utilisation de vos informations.'
  };

  function erreurDe(champ) {
    var v = champ.type === 'checkbox' ? champ.checked : champ.value.trim();
    if (champ.required && !v) return MESSAGES[champ.name];
    if (champ.name === 'courriel' && v && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return MESSAGES.courriel;
    if (champ.name === 'telephone' && v && !/^[+0-9 ().-]{6,20}$/.test(v)) return MESSAGES.telephone;
    return '';
  }

  function marque(champ, texte) {
    var zone = document.getElementById(champ.id + '-erreur');
    if (!zone) return;
    zone.textContent = texte;
    zone.hidden = !texte;
    if (texte) champ.setAttribute('aria-invalid', 'true'); else champ.removeAttribute('aria-invalid');
  }

  function init(form) {
    var jeton = form.querySelector('[name="jeton"]');
    var resume = form.querySelector('.vf-form__resume');
    var statut = form.querySelector('.vf-form__statut');
    var bouton = form.querySelector('button[type="submit"]');
    var champs = Array.prototype.slice.call(form.querySelectorAll('input:not([type="hidden"]):not([name="site_web"]), select, textarea'));

    // Jeton anti-robots demandé au chargement (jamais mis en cache).
    fetch(form.dataset.jeton, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) { jeton.value = d.jeton || ''; })
      .catch(function () {});

    // Une erreur signalée disparaît dès que la saisie la corrige (pendant la frappe, et non à la sortie
    // du champ : la mise en page ne bouge donc pas au moment où l'on clique sur le champ suivant).
    champs.forEach(function (ch) {
      var verifie = function () { if (ch.getAttribute('aria-invalid') && !erreurDe(ch)) marque(ch, ''); };
      ch.addEventListener('input', verifie);
      ch.addEventListener('change', verifie);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var erreurs = [];
      champs.forEach(function (ch) {
        var t = erreurDe(ch);
        marque(ch, t);
        if (t) erreurs.push({ champ: ch, texte: t });
      });
      if (erreurs.length) {
        resume.innerHTML = '';
        var h = document.createElement('p');
        h.className = 'vf-form__resume-titre';
        h.textContent = erreurs.length === 1 ? 'Le formulaire contient 1 erreur :' : 'Le formulaire contient ' + erreurs.length + ' erreurs :';
        var ul = document.createElement('ul');
        erreurs.forEach(function (er) {
          var li = document.createElement('li');
          var a = document.createElement('a');
          a.href = '#' + er.champ.id;
          a.textContent = er.texte;
          a.addEventListener('click', function (ev) { ev.preventDefault(); er.champ.focus(); });
          li.appendChild(a);
          ul.appendChild(li);
        });
        resume.appendChild(h);
        resume.appendChild(ul);
        resume.hidden = false;
        resume.focus();
        return;
      }
      resume.hidden = true;
      bouton.disabled = true;
      statut.textContent = 'Envoi en cours…';
      fetch(form.dataset.route, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          if (!res.ok) throw new Error(res.d && res.d.message ? res.d.message : '');
          var merci = document.createElement('div');
          merci.className = 'vf-form__merci';
          merci.setAttribute('tabindex', '-1');
          merci.innerHTML = '<p class="vf-form__merci-titre"></p><p></p>';
          merci.children[0].textContent = 'Votre message a bien été envoyé.';
          merci.children[1].textContent = 'Le service concerné vous répondra dans les meilleurs délais.';
          form.replaceWith(merci);
          merci.focus();
        })
        .catch(function (err) {
          bouton.disabled = false;
          statut.textContent = err.message || 'L’envoi n’a pas abouti. Réessayez dans quelques instants ou appelez le 04 74 96 00 00.';
        });
    });
  }

  document.querySelectorAll('.vf-form__formulaire').forEach(init);
})();
