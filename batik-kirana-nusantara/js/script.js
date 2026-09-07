// Reveal product cards as they scroll into view.
document.addEventListener('DOMContentLoaded', () => {
  const items = document.querySelectorAll('.reveal');

  if (!items.length) return;

  if (!('IntersectionObserver' in window)) {
    items.forEach((el) => el.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  items.forEach((el) => observer.observe(el));
});


// Toast notifications: auto-dismiss after a few seconds, or on manual close.
document.addEventListener('DOMContentLoaded', () => {
  const toasts = document.querySelectorAll('.toast');

  const hideToast = (toast) => {
    toast.classList.add('toast-hide');
    setTimeout(() => toast.remove(), 300);
  };

  toasts.forEach((toast) => {
    const timer = setTimeout(() => hideToast(toast), 5000);

    const closeBtn = toast.querySelector('.toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        clearTimeout(timer);
        hideToast(toast);
      });
    }
  });
});