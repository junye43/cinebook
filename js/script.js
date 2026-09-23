document.addEventListener("DOMContentLoaded", function () {

    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".dot");
    const nextBtn = document.querySelector(".hero-next");
    const prevBtn = document.querySelector(".hero-prev");

    if (slides.length === 0) {
        return;
    }

    let current = 0;

    function showSlide(index){
        slides.forEach((slide)=>{
            slide.classList.remove("active");
        });
        dots.forEach((dot)=>{
            dot.classList.remove("active");
        });
        slides[index].classList.add("active");
        if(dots[index]){
            dots[index].classList.add("active");
        }
    }

    nextBtn.addEventListener("click", function(){
        current++;
        if(current >= slides.length){
            current = 0;
        }
        showSlide(current);
    });

    prevBtn.addEventListener("click", function(){
        current--;
        if(current < 0){
            current = slides.length - 1;
        }
        showSlide(current);
    });

    dots.forEach((dot,index)=>{
        dot.addEventListener("click",function(){
            current=index;
            showSlide(current);
        });
    });
});