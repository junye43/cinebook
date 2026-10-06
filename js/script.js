/* ============================================================
   Custom error message box (replaces the browser's default alert)
   Usage: showErrorBox("msg one\nmsg two")  or  showErrorBox(["a","b"])
   ============================================================ */
function showErrorBox(messages, title) {
    var list = Array.isArray(messages) ? messages : String(messages).split("\n");
    list = list.filter(function (s) { return s.trim() !== ""; });
    if (list.length === 0) return;

    var esc = function (s) {
        return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    };
    var items = list.map(function (m) { return "<li>" + esc(m) + "</li>"; }).join("");

    var overlay = document.createElement("div");
    overlay.className = "modal-overlay";
    overlay.innerHTML =
        '<div class="modal-box" role="alertdialog" aria-modal="true" aria-label="Error">' +
            '<h3>&#9888; ' + esc(title || "Please check the form") + '</h3>' +
            '<ul>' + items + '</ul>' +
            '<button type="button" class="btn modal-ok">OK</button>' +
        '</div>';

    function close() {
        overlay.remove();
        document.removeEventListener("keydown", onKey);
    }
    function onKey(e) { if (e.key === "Escape") close(); }

    overlay.addEventListener("click", function (e) { if (e.target === overlay) close(); });
    overlay.querySelector(".modal-ok").addEventListener("click", close);
    document.addEventListener("keydown", onKey);

    document.body.appendChild(overlay);
    overlay.querySelector(".modal-ok").focus();
}
window.showErrorBox = showErrorBox;

document.addEventListener("DOMContentLoaded", function () {

    // Clickable table rows (e.g. My Bookings → ticket view)
    document.querySelectorAll(".clickable-row").forEach(function (row) {
        row.addEventListener("click", function (e) {
            if (e.target.closest("a, button")) return; // let links/buttons work normally
            var href = row.getAttribute("data-href");
            if (href) window.location.href = href;
        });
    });

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
