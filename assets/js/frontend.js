/**
 * Google Places Directory Frontend Scripts
 * For photo galleries slider functionality
 */
(function() {
    'use strict';
    
    // Initialize after the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        initPhotoSliders();
    });
    
    /**
     * Initialize photo sliders
     */
    function initPhotoSliders() {
        var sliders = document.querySelectorAll('.gpd-photo-slider');
        
        sliders.forEach(function(slider) {
            var track = slider.querySelector('.gpd-slider-track');
            var items = slider.querySelectorAll('.gpd-slider-item');
            var prevBtn = slider.querySelector('.gpd-slider-prev');
            var nextBtn = slider.querySelector('.gpd-slider-next');
            var currentSlide = 0;
            var totalSlides = items.length;
            
            if (totalSlides <= 1) return; // Don't initialize if only one slide
            
            // Set initial slide position
            updateSlidePosition();
            
            // Add event listeners to buttons
            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    navigateSlider(-1);
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    navigateSlider(1);
                });
            }
            
            // Navigation function
            function navigateSlider(direction) {
                currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
                updateSlidePosition();
            }
            
            // Update slide position
            function updateSlidePosition() {
                track.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
            }
        });
    }
})();
