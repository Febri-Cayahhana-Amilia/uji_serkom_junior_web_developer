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
})();