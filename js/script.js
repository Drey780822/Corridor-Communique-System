// script.js
$(document).ready(function() {
    // Newsletter form submission
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();
        const email = $('#newsletterEmail').val();
        if (email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            $.post('index.php', $(this).serialize(), function(response) {
                $('#newsletterResponse').html(response.message).addClass(response.success ? 'text-success' : 'text-error');
            }, 'json');
        } else {
            $('#newsletterResponse').html('Please enter a valid email.').addClass('text-error');
        }
    });

    // Contact form submission (client-side validation)
    $('#contactForm').on('submit', function(e) {
        e.preventDefault();
        const name = $('#contactName').val();
        const email = $('#contactEmail').val();
        const message = $('#contactMessage').val();
        if (name && email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/) && message) {
            $('#contactResponse').html('Thank you for your message! We’ll get back to you soon.').addClass('text-success');
            this.reset();
        } else {
            $('#contactResponse').html('Please fill in all fields with valid information.').addClass('text-error');
        }
    });

    // Smooth scrolling for "Explore Updates" button
    $('.btn-explore').on('click', function(e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: $('#communiques').offset().top
        }, 800);
    });

    // Pause videos after 20 seconds and set poster
    $('.residence-video').each(function() {
        const video = this;
        $(video).on('play', function() {
            setTimeout(() => {
                video.pause();
                if (video.dataset.poster) {
                    video.setAttribute('poster', video.dataset.poster);
                }
            }, 20000); // 20 seconds
        });
    });

    // Slideshow controls
    let currentSlide = 0;
    const slides = $('.slideshow-image');
    const dots = $('.slideshow-dot');
    
    function showSlide(index) {
        slides.removeClass('active').eq(index).addClass('active');
        dots.removeClass('active').eq(index).addClass('active');
    }
    
    dots.on('click', function() {
        currentSlide = $(this).index();
        showSlide(currentSlide);
    });
    
    showSlide(currentSlide);
    
    // Auto-advance slideshow
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }, 4000);

    // Back to top button
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 300) {
            $('.back-to-top').addClass('visible');
        } else {
            $('.back-to-top').removeClass('visible');
        }
    });

    // Hamburger menu toggle
    $('.hamburger').on('click', function() {
        $('.nav-menu').toggleClass('active');
    });

    // Search icon click (placeholder)
    $('.nav-search').on('click', function() {
        alert('Search functionality coming soon!');
    });
});