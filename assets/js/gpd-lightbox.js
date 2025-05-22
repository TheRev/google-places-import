/**
 * Google Places Directory - Lightbox Script
 * Version: 2.5.1
 * Date: 2025-05-22
 */
(function($) {
    'use strict';
    
    // Lightbox component
    class GPDLightbox {
        constructor() {
            this.isOpen = false;
            this.currentIndex = 0;
            this.images = [];
            this.overlay = null;
            this.content = null;
            this.img = null;
            this.caption = null;
            this.closeBtn = null;
            this.prevBtn = null;
            this.nextBtn = null;
            this.isAnimating = false;
            
            this.initialize();
        }
        
        initialize() {
            // Create lightbox elements
            this.createLightbox();
            // Bind event handlers
            this.bindEvents();
        }
        
        createLightbox() {
            // Create overlay
            this.overlay = $('<div class="gpd-lightbox-overlay"></div>');
            
            // Create content container
            this.content = $('<div class="gpd-lightbox-content"></div>');
            
            // Create image element
            this.img = $('<img class="gpd-lightbox-img" alt="">');
            
            // Create caption
            this.caption = $('<div class="gpd-lightbox-caption"></div>');
            
            // Create control buttons
            this.closeBtn = $('<button type="button" class="gpd-lightbox-close" aria-label="Close">&times;</button>');
            this.prevBtn = $('<button type="button" class="gpd-lightbox-prev" aria-label="Previous">&#10094;</button>');
            this.nextBtn = $('<button type="button" class="gpd-lightbox-next" aria-label="Next">&#10095;</button>');
            
            // Assemble the lightbox
            this.content.append(this.img);
            this.content.append(this.caption);
            this.content.append(this.closeBtn);
            this.content.append(this.prevBtn);
            this.content.append(this.nextBtn);
            this.overlay.append(this.content);
            
            // Add to body
            $('body').append(this.overlay);
        }
        
        bindEvents() {
            const self = this;
            
            // Open lightbox on click
            $(document).on('click', '.gpd-lightbox', function(e) {
                e.preventDefault();
                self.open(this);
                return false;
            });
            
            // Close button
            this.closeBtn.on('click', function() {
                self.close();
            });
            
            // Navigation buttons
            this.prevBtn.on('click', function() {
                if (!self.isAnimating) {
                    self.navigate(-1);
                }
            });
            
            this.nextBtn.on('click', function() {
                if (!self.isAnimating) {
                    self.navigate(1);
                }
            });
            
            // Close on overlay click
            this.overlay.on('click', function(e) {
                if (e.target === self.overlay[0]) {
                    self.close();
                }
            });
            
            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if (!self.isOpen) return;
                
                switch (e.keyCode) {
                    case 27: // ESC
                        self.close();
                        break;
                    case 37: // Left arrow
                        if (!self.isAnimating) {
                            self.navigate(-1);
                        }
                        break;
                    case 39: // Right arrow
                        if (!self.isAnimating) {
                            self.navigate(1);
                        }
                        break;
                }
            });
            
            // Touch swipe support
            let touchStartX = 0;
            let touchEndX = 0;
            
            this.overlay[0].addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });
            
            this.overlay[0].addEventListener('touchend', function(e) {
                if (!self.isOpen) return;
                
                touchEndX = e.changedTouches[0].screenX;
                const swipeThreshold = 50;
                
                if (touchEndX < touchStartX - swipeThreshold) {
                    // Swipe left - next image
                    if (!self.isAnimating) {
                        self.navigate(1);
                    }
                } else if (touchEndX > touchStartX + swipeThreshold) {
                    // Swipe right - previous image
                    if (!self.isAnimating) {
                        self.navigate(-1);
                    }
                }
            }, { passive: true });
        }
        
        open(el) {
            const self = this;
            
            // Find all lightbox links in the same gallery
            const $gallery = $(el).closest('.gpd-photos-gallery');
            this.images = $gallery.find('.gpd-lightbox').toArray();
            
            // Get the index of the clicked image
            this.currentIndex = this.images.findIndex(img => img === el);
            
            // Set image
            this.showImage(this.currentIndex);
            
            // Show the lightbox with animation
            this.overlay.fadeIn(300, function() {
                self.overlay.addClass('active');
                self.isOpen = true;
                
                // Check for only one image (hide navigation)
                if (self.images.length <= 1) {
                    self.prevBtn.hide();
                    self.nextBtn.hide();
                } else {
                    self.prevBtn.show();
                    self.nextBtn.show();
                    
                    // Preload adjacent images
                    self.preloadAdjacentImages();
                }
                
                // Set focus for accessibility
                self.overlay.attr('tabindex', '-1').focus();
            });
            
            // Prevent body scrolling
            $('body').addClass('gpd-lightbox-open');
        }
        
        close() {
            const self = this;
            
            // Hide with animation
            this.overlay.fadeOut(300, function() {
                self.overlay.removeClass('active');
                self.isOpen = false;
            });
            
            // Re-enable body scrolling
            $('body').removeClass('gpd-lightbox-open');
        }
        
        navigate(direction) {
            if (this.images.length <= 1) return;
            
            this.isAnimating = true;
            
            // Calculate new index
            let newIndex = this.currentIndex + direction;
            
            // Handle wrapping
            if (newIndex < 0) {
                newIndex = this.images.length - 1;
            } else if (newIndex >= this.images.length) {
                newIndex = 0;
            }
            
            // Fade transition
            this.img.fadeOut(200, () => {
                this.showImage(newIndex);
                this.img.fadeIn(200, () => {
                    this.isAnimating = false;
                });
            });
        }
        
        showImage(index) {
            const el = this.images[index];
            const $el = $(el);
            
            // Update current index
            this.currentIndex = index;
            
            // Get image source
            const src = $el.attr('href');
            
            // Update image
            this.img.attr('src', src);
            this.img.attr('alt', $el.attr('data-caption') || '');
            
            // Update caption
            const caption = $el.attr('data-caption') || '';
            if (caption) {
                this.caption.text(caption).show();
            } else {
                this.caption.hide();
            }
            
            // Preload adjacent images
            this.preloadAdjacentImages();
        }
        
        /**
         * Preload adjacent images for smoother navigation
         */
        preloadAdjacentImages() {
            if (this.images.length <= 1) return;
            
            // Preload next image
            const nextIndex = (this.currentIndex + 1) % this.images.length;
            const nextImage = new Image();
            nextImage.src = $(this.images[nextIndex]).attr('href');
            
            // Preload previous image
            const prevIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            const prevImage = new Image();
            prevImage.src = $(this.images[prevIndex]).attr('href');
            
            // Smooth transition enhancements
            this.img.css({
                'will-change': 'opacity',
                'image-rendering': 'auto'
            });
        }
    }
    
    // Add CSS to body for preventing scroll when lightbox is open
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            body.gpd-lightbox-open {
                overflow: hidden;
                padding-right: ${getScrollbarWidth()}px;
            }
            @media (prefers-reduced-motion: reduce) {
                .gpd-lightbox-img {
                    transition: none !important;
                }
            }
        `)
        .appendTo('head');
        
    // Calculate scrollbar width to prevent page jump
    function getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.visibility = 'hidden';
        outer.style.overflow = 'scroll';
        document.body.appendChild(outer);
        
        const inner = document.createElement('div');
        outer.appendChild(inner);
        
        const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
        outer.parentNode.removeChild(outer);
        
        return scrollbarWidth;
    }
    
    // Initialize GPD Lightbox
    let lightbox;
    
    // Initialize on DOM ready
    $(document).ready(function() {
        lightbox = new GPDLightbox();
    });
    
    // Expose to global scope
    window.GPDLightbox = GPDLightbox;
    
})(jQuery);
