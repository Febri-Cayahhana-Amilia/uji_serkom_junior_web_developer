(function(){
  var links = document.querySelectorAll('[data-nav]');
  var targets = Array.from(links).map(function(a){
    return document.querySelector(a.getAttribute('href'));
  }).filter(Boolean);

  function setActive(id){
    links.forEach(function(a){
      var isMatch = a.getAttribute('href') === '#' + id;
      if(isMatch){ a.setAttribute('aria-current','true'); }
      else{ a.removeAttribute('aria-current'); }
    });
  }

  if('IntersectionObserver' in window){
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){ setActive(entry.target.id); }
      });
    }, { rootMargin: '-40% 0px -50% 0px' });
    targets.forEach(function(t){ observer.observe(t); });
  }

  // ---------- Lightbox foto sertifikat ----------
  var lightbox = document.getElementById('lightbox');
  var lightboxImg = document.getElementById('lightboxImg');
  var lightboxClose = document.getElementById('lightboxClose');
  var triggers = document.querySelectorAll('[data-lightbox]');

  function openLightbox(src, alt){
    lightboxImg.setAttribute('src', src);
    lightboxImg.setAttribute('alt', alt || '');
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
    lightboxClose.focus();
  }

  function closeLightbox(){
    lightbox.hidden = true;
    lightboxImg.setAttribute('src', '');
    document.body.style.overflow = '';
  }

  triggers.forEach(function(btn){
    btn.addEventListener('click', function(){
      var img = btn.querySelector('img');
      openLightbox(btn.getAttribute('data-lightbox'), img ? img.getAttribute('alt') : '');
    });
  });

  if(lightboxClose){ lightboxClose.addEventListener('click', closeLightbox); }
  if(lightbox){
    lightbox.addEventListener('click', function(e){
      if(e.target === lightbox){ closeLightbox(); }
    });
  }
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && lightbox && !lightbox.hidden){ closeLightbox(); }
  });
})();