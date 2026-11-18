/* Bloc « Vidéo » : le lecteur externe n'est inséré qu'après un clic du visiteur. */
(function () {
  'use strict';
  document.querySelectorAll('.vf-video[data-src]').forEach(function (fig) {
    var bouton = fig.querySelector('button');
    if (!bouton) return;
    bouton.addEventListener('click', function () {
      var f = document.createElement('iframe');
      f.src = fig.getAttribute('data-src');
      f.title = fig.getAttribute('data-titre') || 'Vidéo';
      f.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
      f.setAttribute('allowfullscreen', '');
      fig.textContent = '';
      fig.appendChild(f);
      f.focus();
    });
  });
})();
