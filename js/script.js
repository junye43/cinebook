document.addEventListener("DOMContentLoaded", function () {

    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".dot");

    if (slides.length === 0) {
        return;
    }

    let current = 0;
    let timer = null;
    const INTERVAL = 5000;   // auto-advance every 5 seconds

    function showSlide(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach(function (s) { s.classList.remove("active"); });
        dots.forEach(function (d) { d.classList.remove("active"); });
        slides[current].classList.add("active");
        if (dots[current]) {
            dots[current].classList.add("active");
        }
    }

    function nextSlide() {
        showSlide(current + 1);
    }

    function startAuto() {
        stopAuto();
        if (slides.length > 1) {
            timer = setInterval(nextSlide, INTERVAL);
        }
    }

    function stopAuto() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Dots: jump to a slide and restart the timer
    dots.forEach(function (dot, index) {
        dot.addEventListener("click", function () {
            showSlide(index);
            startAuto();
        });
    });

    // Pause auto-scroll while the user hovers the carousel
    const carousel = document.querySelector(".hero-carousel");
    if (carousel) {
        carousel.addEventListener("mouseenter", stopAuto);
        carousel.addEventListener("mouseleave", startAuto);
    }

    startAuto();
});
