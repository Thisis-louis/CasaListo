const navToggle = document.querySelector('.nav-toggle');
const mainNav = document.querySelector('.main-nav');
const serviceFilter = document.querySelector('#service-filter');
const serviceItems = document.querySelectorAll('.catalog-item');

if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    const isOpen = mainNav.classList.toggle('is-open');
    navToggle.setAttribute('aria-expanded', String(isOpen));
  });

  mainNav.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      mainNav.classList.remove('is-open');
      navToggle.setAttribute('aria-expanded', 'false');
    });
  });
}

if (serviceFilter) {
  serviceFilter.addEventListener('change', () => {
    const selectedCategory = serviceFilter.value;

    serviceItems.forEach((item) => {
      const matches = selectedCategory === 'todos' || item.dataset.category === selectedCategory;
      item.classList.toggle('is-hidden', !matches);
    });
  });
}
