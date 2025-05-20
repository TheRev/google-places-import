/**
 * Google Places Directory - Lightbox Script
 * Version: 2.5.0
 * Date: 2025-05-20
 */
(function($) {
    'use strict';
    
    // Create the lightbox element if it doesn't exist
    function createLightbox() {
        if ($('.gpd-lightbox-overlay').length === 0) {
            const lightbox = `
                <div class="gpd-lightbox-overlay">
                    <div class="gpd-lightbox-content">
                        <img class="gpd-lightbox-img" src="" alt="">
                        <div class="gpd-lightbox-caption"></div>
                        <button class="gpd-lightbox-close">&times;</button>
                        <button class="gpd-lightbox-prev">&lsaquo;</button>
                        <button class="gpd-lightbox-next">&rsaquo;</button>
                    </div>
                </div>
            `;
            $('body').append(lightbox);
            
            // Close lightbox when clicking outside the image
            $('.gpd-lightbox-overlay').on('click', function(e) {
                if (e.target === this) {
                    closeLightbox();
                }
            });
            
            // Close button
            $('.gpd-lightbox-close').on('click', closeLightbox);
            
            // Navigate through images
            $('.gpd-lightbox-prev').on('click', showPrevImage);
            $('.gpd-lightbox-next').on('click', showNextImage);
            
            // Keyboard navigation
            $(document).on('keydown.gpdLightbox', function(e) {
                if ($('.gpd-lightbox-overlay').hasClass('active')) {
                    if (e.key === 'Escape') {
                        closeLightbox();
                    } else if (e.key === 'ArrowLeft') {
                        showPrevImage();
                    } else if (e.key === 'ArrowRight') {
                        showNextImage();
                    }
                }
            });
        }
    }
    
    // Current gallery and image index
    let currentGallery = [];
    let currentIndex = 0;
    
    // Open lightbox with the clicked image
    function openLightbox(imgSrc, caption) {
        createLightbox();
        
        // Set image and caption
        const $lightboxImg = $('.gpd-lightbox-img');
        const $lightboxCaption = $('.gpd-lightbox-caption');
        
        // Preload image
        const img = new Image();
        img.onload = function() {
            $lightboxImg.attr('src', imgSrc);
            $lightboxCaption.text(caption || '');
            
            // Show lightbox with animation
            $('.gpd-lightbox-overlay').addClass('active');
            
            // Show/hide navigation based on gallery length
            if (currentGallery.length <= 1) {
                $('.gpd-lightbox-prev, .gpd-lightbox-next').hide();
            } else {
                $('.gpd-lightbox-prev, .gpd-lightbox-next').show();
            }
        };
        img.src = imgSrc;
    }
    
    // Close the lightbox
    function closeLightbox() {
        $('.gpd-lightbox-overlay').removeClass('active');
    }
    
    // Show the previous image in the gallery
    function showPrevImage() {
        if (currentGallery.length <= 1) return;
        
        currentIndex = (currentIndex - 1 + currentGallery.length) % currentGallery.length;
        const prevItem = currentGallery[currentIndex];
        openLightbox(prevItem.src, prevItem.caption);
    }
    
    // Show the next image in the gallery
    function showNextImage() {
        if (currentGallery.length <= 1) return;
        
        currentIndex = (currentIndex + 1) % currentGallery.length;
        const nextItem = currentGallery[currentIndex];
        openLightbox(nextItem.src, nextItem.caption);
    }
    
    // Initialize lightbox functionality for all galleries
    function initLightbox() {
        // Find all elements with gpd-lightbox class
        $('.gpd-lightbox').each(function() {
            $(this).on('click', function(e) {
                e.preventDefault();
                
                // Find all images in this gallery
                const $gallery = $(this).closest('.gpd-photos-gallery');
                const $links = $gallery.find('.gpd-lightbox');
                
                // Create gallery array
                currentGallery = [];
                $links.each(function(i) {
                    currentGallery.push({
                        src: $(this).attr('href'),
                        caption: $(this).data('caption') || ''
                    });
                    
                    // If this is the clicked image, set the current index
                    if (this === e.currentTarget) {
                        currentIndex = i;
                    }
                });
                
                // Open lightbox with clicked image
                openLightbox($(this).attr('href'), $(this).data('caption'));
            });
        });
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initLightbox();
    });
    
    // Re-initialize if new content is added to the page
    $(document).on('gpd:contentLoaded', function() {
        initLightbox();
    });
    
})(jQuery);
