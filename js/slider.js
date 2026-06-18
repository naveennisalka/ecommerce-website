/* =========================================
   SLIDERS — Hero auto-slide + Brands carousel
   ========================================= */

(function () {
  'use strict';

  /* ── HERO SLIDER ── */
  const heroSlider = document.querySelector('.hero-slider');
  const heroPrev   = document.querySelector('.hero-prev');
  const heroNext   = document.querySelector('.hero-next');
  const heroDots   = document.querySelectorAll('.hero-dot');

  if (heroSlider) {
    let current   = 0;
    let autoTimer = null;
    const slides  = heroSlider.querySelectorAll('.hero-slide');
    const total   = slides.length;

    function goTo(index) {
      current = (index + total) % total;
      heroSlider.style.transform = `translateX(-${current * 100}%)`;
      heroDots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function startAuto() {
      clearInterval(autoTimer);
      autoTimer = setInterval(() => goTo(current + 1), 4500);
    }

    heroPrev && heroPrev.addEventListener('click', () => { goTo(current - 1); startAuto(); });
    heroNext && heroNext.addEventListener('click', () => { goTo(current + 1); startAuto(); });
    heroDots.forEach((dot, i) => {
      dot.addEventListener('click', () => { goTo(i); startAuto(); });
    });

    // Pause on hover
    heroSlider.closest('.hero-section')?.addEventListener('mouseenter', () => clearInterval(autoTimer));
    heroSlider.closest('.hero-section')?.addEventListener('mouseleave', () => startAuto());

    // Touch swipe
    let touchStartX = 0;
    heroSlider.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    heroSlider.addEventListener('touchend', e => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) { goTo(current + (diff > 0 ? 1 : -1)); startAuto(); }
    }, { passive: true });

    goTo(0);
    startAuto();
  }

  /* ── BRANDS CAROUSEL ── */
  const brandsTrack = document.querySelector('.brands-carousel');
  const brandsPrev  = document.querySelector('.brands-prev');
  const brandsNext  = document.querySelector('.brands-next');

  if (brandsTrack) {
    const scrollAmt = 220;
    brandsPrev && brandsPrev.addEventListener('click', () => {
      brandsTrack.scrollBy({ left: -scrollAmt, behavior: 'smooth' });
    });
    brandsNext && brandsNext.addEventListener('click', () => {
      brandsTrack.scrollBy({ left: scrollAmt, behavior: 'smooth' });
    });

    // Active brand pill
    brandsTrack.querySelectorAll('.brand-pill').forEach(pill => {
      pill.addEventListener('click', function () {
        brandsTrack.querySelectorAll('.brand-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        const bid = this.dataset.brandId;
        if (bid) {
          // Fire brand filter event
          window.dispatchEvent(new CustomEvent('brandFilter', { detail: { brandId: bid } }));
        }
      });
    });
  }

  /* ── PRODUCTS ROW (horizontal scroll with drag) ── */
  document.querySelectorAll('.products-row').forEach(row => {
    let isDown = false, startX, scrollLeft;
    row.addEventListener('mousedown', e => {
      isDown = true; row.style.cursor = 'grabbing';
      startX = e.pageX - row.offsetLeft;
      scrollLeft = row.scrollLeft;
    });
    row.addEventListener('mouseleave', () => { isDown = false; row.style.cursor = 'default'; });
    row.addEventListener('mouseup',    () => { isDown = false; row.style.cursor = 'default'; });
    row.addEventListener('mousemove',  e => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - row.offsetLeft;
      const walk = (x - startX) * 1.5;
      row.scrollLeft = scrollLeft - walk;
    });
  });

})();
