let slideIndex = 1;

function showSlides(n) {
    const slides = document.getElementsByClassName("carousel-slide");
    const indicators = document.getElementsByClassName("indicator");

    if (n > slides.length) { slideIndex = 1; }
    if (n < 1) { slideIndex = slides.length; }

    for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove("active");
    }

    for (let i = 0; i < indicators.length; i++) {
        indicators[i].classList.remove("active");
    }

    slides[slideIndex - 1].classList.add("active");
    indicators[slideIndex - 1].classList.add("active");
}

function changeSlide(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

// Initialize the carousel
showSlides(slideIndex);