document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const nav = document.getElementById('homepageNav');
  
    hamburger.addEventListener('click', function () {
      nav.classList.toggle('active');
    });
  });
  