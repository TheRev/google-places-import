/**
 * Google Places Directory Frontend Scripts
 */
(function($) {
    'use strict';

    // Collection of all initialized sliders
    var gpdSliders = {};

    /**
     * Initialize photo slider for a specific gallery
     * @param {string} sliderId - The unique ID of the slider to initialize
     */
    window.gpdInitSlider = function(sliderId) {
        var $slider = $('#' + sliderId + ' .gpd-photo-slider');
        if (!$slider.length) return;

        var $track = $slider.find('.gpd-slider-track');
        var $items = $slider.find('.gpd-slider-item');
        var $prevBtn = $slider.find('.gpd-slider-prev');
        var $nextBtn = $slider.find('.gpd-slider-next');
        var $dots = $slider.find('.gpd-pagination-dot');
        var totalSlides = $items.length;
        var currentSlide = 0;

        // Don't initialize if only one slide
        if (totalSlides <= 1) return;

        // Store slide instance
        gpdSliders[sliderId] = {
            currentSlide: 0,
            totalSlides: totalSlides,
            animating: false
        };

        // Add event listeners to buttons
        $prevBtn.on('click', function(e) {
            e.preventDefault();
            navigateSlider(-1);
        });

        $nextBtn.on('click', function(e) {
            e.preventDefault();
            navigateSlider(1);
        });

        // Add event listeners to dots
        $dots.on('click', function(e) {
            e.preventDefault();
            var slideIndex = $(this).data('slide');
            if (slideIndex !== currentSlide) {
                goToSlide(slideIndex);
            }
        });

        // Add touch swipe support
        var startX, startY, distX, distY;
        var threshold = 50; // Minimum distance for swipe
        var restraint = 100; // Maximum distance for up/down movement

        $slider.on('touchstart', function(e) {
            var touch = e.originalEvent.touches[0];
            startX = touch.pageX;
            startY = touch.pageY;
        });

        $slider.on('touchmove', function(e) {
            if (!startX || !startY) return;

            var touch = e.originalEvent.touches[0];
            distX = touch.pageX - startX;
            distY = touch.pageY - startY;
            
            // Prevent vertical scrolling when swiping
            if (Math.abs(distX) > Math.abs(distY)) {
                e.preventDefault();
            }
        });

        $slider.on('touchend', function(e) {
            if (!distX || !distY) return;

            if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
                if (distX > 0) {
                    navigateSlider(-1); // Right swipe = previous
                } else {
                    navigateSlider(1); // Left swipe = next
                }
            }

            // Reset values
            startX = startY = distX = distY = null;
        });

        // Navigation function
        function navigateSlider(direction) {
            if (gpdSliders[sliderId].animating) return;
            
            var newSlide = (currentSlide + direction + totalSlides) % totalSlides;
            goToSlide(newSlide);
        }

        // Go to specific slide
        function goToSlide(slideIndex) {
            if (gpdSliders[sliderId].animating) return;
            
            gpdSliders[sliderId].animating = true;
            currentSlide = slideIndex;
            gpdSliders[sliderId].currentSlide = currentSlide;

            // Update slide position with animation
            $track.css({
                'transition': 'transform 0.3s ease',
                'transform': 'translateX(-' + (currentSlide * 100) + '%)'
            });

            // Update pagination dots
            $dots.removeClass('gpd-active');
            $dots.eq(currentSlide).addClass('gpd-active');

            // Reset animating flag after transition
            setTimeout(function() {
                gpdSliders[sliderId].animating = false;
            }, 350);
        }
    };

    /**
     * Initialize lightbox functionality
     * Basic lightbox implementation - for a production plugin, consider using an established lightbox library
     */
    window.gpdInitLightbox = function(selector) {
        var $links = $(selector + ' .gpd-photo-link[data-lightbox="gpd-gallery"]');
        
        if (!$links.length) return;
        
        // Check if lightbox container exists already
        var $lightbox = $('#gpd-lightbox');
        
        // Create lightbox if it doesn't exist
        if (!$lightbox.length) {
            $('body').append(
                '<div id="gpd-lightbox" class="gpd-lightbox">' +
                    '<div class="gpd-lightbox-overlay"></div>' +
                    '<div class="gpd-lightbox-content">' +
                        '<img class="gpd-lightbox-image" src="" alt="">' +
                        '<div class="gpd-lightbox-caption"></div>' +
                    '</div>' +
                    '<button class="gpd-lightbox-close" aria-label="Close">&times;</button>' +
                    '<button class="gpd-lightbox-prev" aria-label="Previous">&#10094;</button>' +
                    '<button class="gpd-lightbox-next" aria-label="Next">&#10095;</button>' +
                '</div>'
            );
            
            $lightbox = $('#gpd-lightbox');
            
            // Add lightbox styles if not already added
            if (!$('#gpd-lightbox-styles').length) {
                $('head').append(
                    '<style id="gpd-lightbox-styles">' +
                        '.gpd-lightbox { display: none; position: fixed; z-index: 999999; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; }' +
                        '.gpd-lightbox-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }' +
                        '.gpd-lightbox-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%; }' +
                        '.gpd-lightbox-image { display: block; max-width: 100%; max-height: 90vh; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.5); }' +
                        '.gpd-lightbox-caption { color: #fff; text-align: center; padding: 10px; font-size: 14px; }' +
                        '.gpd-lightbox-close, .gpd-lightbox-prev, .gpd-lightbox-next { background: none; border: none; color: white; font-size: 30px; cursor: pointer; opacity: 0.8; position: absolute; }' +
                        '.gpd-lightbox-close:hover, .gpd-lightbox-prev:hover, .gpd-lightbox-next:hover { opacity: 1; }' +
                        '.gpd-lightbox-close { top: 20px; right: 20px; font-size: 40px; }' +
                        '.gpd-lightbox-prev { left: 20px; top: 50%; transform: translateY(-50%); }' +
                        '.gpd-lightbox-next { right: 20px; top: 50%; transform: translateY(-50%); }' +
                    '</style>'
                );
            }
            
            // Close lightbox on overlay click
            $lightbox.find('.gpd-lightbox-overlay, .gpd-lightbox-close').on('click', function() {
                closeLightbox();
            });
            
            // Close on escape key
            $(document).keydown(function(e) {
                if (e.key === "Escape" && $lightbox.is(':visible')) {
                    closeLightbox();
                }
                
                // Navigate with arrow keys
                if ($lightbox.is(':visible')) {
                    if (e.key === "ArrowRight") {
                        showNextImage();
                    } else if (e.key === "ArrowLeft") {
                        showPrevImage();
                    }
                }
            });
            
            // Next/Previous buttons
            $lightbox.find('.gpd-lightbox-next').on('click', function(e) {
                e.stopPropagation();
                showNextImage();
            });
            
            $lightbox.find('.gpd-lightbox-prev').on('click', function(e) {
                e.stopPropagation();
                showPrevImage();
            });
        }
        
        // Lightbox variables
        var currentIndex = 0;
        var gallery = [];
        
        // Collect all images in this gallery
        $links.each(function() {
            gallery.push({
                src: $(this).attr('href'),
                title: $(this).data('title') || ''
            });
        });
        
        // Open lightbox on click
        $links.on('click', function(e) {
            e.preventDefault();
            
            // Find index of clicked image
            var clickedHref = $(this).attr('href');
            for (var i = 0; i < gallery.length; i++) {
                if (gallery[i].src === clickedHref) {
                    currentIndex = i;
                    break;
                }
            }
            
            showImage(currentIndex);
        });
        
        // Show image at specified index
        function showImage(index) {
            if (!gallery[index]) return;
            
            var $image = $lightbox.find('.gpd-lightbox-image');
            var $caption = $lightbox.find('.gpd-lightbox-caption');
            
            // Preload image
            var img = new Image();
            img.onload = function() {
                $image.attr('src', gallery[index].src);
                $caption.text(gallery[index].title);
                
                // Show lightbox if not already visible
                if (!$lightbox.is(':visible')) {
                    $lightbox.fadeIn(300);
                }
                
                // Update navigation visibility
                updateNavigation();
            };
            img.src = gallery[index].src;
        }
        
        // Show next image
        function showNextImage() {
            if (currentIndex < gallery.length - 1) {
                currentIndex++;
                showImage(currentIndex);
            }
        }
        
        // Show previous image
        function showPrevImage() {
            if (currentIndex > 0) {
                currentIndex--;
                showImage(currentIndex);
            }
        }
        
        // Update navigation button visibility
        function updateNavigation() {
            var $prev = $lightbox.find('.gpd-lightbox-prev');
            var $next = $lightbox.find('.gpd-lightbox-next');
            
            if (gallery.length <= 1) {
                $prev.hide();
                $next.hide();
                return;
            }
            
            $prev.toggle(currentIndex > 0);
            $next.toggle(currentIndex < gallery.length - 1);
        }
        
        // Close lightbox
        function closeLightbox() {
            $lightbox.fadeOut(300);
        }
    };

    // Initialize all sliders when the DOM is ready
    $(document).ready(function() {
        // Find all sliders with data-slider-id and initialize them
        $('.gpd-photo-slider[data-slider-id]').each(function() {
            var sliderId = $(this).data('slider-id');
            if (sliderId) {
                gpdInitSlider(sliderId);
            }
        });
    });

})(jQuery);
