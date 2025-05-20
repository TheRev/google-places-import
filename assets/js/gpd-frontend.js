/**
 * Google Places Directory - Frontend Script
 * Version: 2.5.0
 * Date: 2025-05-20
 */
(function($) {
    'use strict';
    
    // Initialize the plugin
    function init() {
        initCarousels();
        initMasonry();
        initBusinessSearch();
    }
    
    // Initialize carousels
    function initCarousels() {
        $('.gpd-layout-carousel').each(function() {
            const $carousel = $(this);
            const $track = $carousel.find('.gpd-carousel-track');
            const $slides = $carousel.find('.gpd-carousel-slide');
            const slideCount = $slides.length;
            
            if (slideCount <= 1) {
                $carousel.find('.gpd-carousel-prev, .gpd-carousel-next').hide();
                return;
            }
            
            let currentIndex = 0;
            
            // Set initial position
            $track.css('width', `${slideCount * 100}%`);
            $slides.css('width', `${100 / slideCount}%`);
            
            // Previous button
            $carousel.find('.gpd-carousel-prev').on('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                } else {
                    currentIndex = slideCount - 1; // Loop to the end
                }
                updateCarousel();
            });
            
            // Next button
            $carousel.find('.gpd-carousel-next').on('click', function() {
                if (currentIndex < slideCount - 1) {
                    currentIndex++;
                } else {
                    currentIndex = 0; // Loop to the beginning
                }
                updateCarousel();
            });
            
            // Update carousel position
            function updateCarousel() {
                const translateX = -currentIndex * (100 / slideCount);
                $track.css('transform', `translateX(${translateX}%)`);
            }
            
            // Swipe support for touch devices
            let touchStartX = 0;
            let touchEndX = 0;
            
            $carousel[0].addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });
            
            $carousel[0].addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, { passive: true });
            
            function handleSwipe() {
                const swipeThreshold = 50;
                if (touchEndX < touchStartX - swipeThreshold) {
                    // Swipe left - next slide
                    $carousel.find('.gpd-carousel-next').trigger('click');
                } else if (touchEndX > touchStartX + swipeThreshold) {
                    // Swipe right - previous slide
                    $carousel.find('.gpd-carousel-prev').trigger('click');
                }
            }
        });
    }
    
    // Initialize masonry layout
    function initMasonry() {
        // For browsers that support CSS columns, we've handled this with CSS
        // If you need a JavaScript masonry library (like Masonry.js), add it here
    }
    
    // Initialize business search functionality
    function initBusinessSearch() {
        const $forms = $('.gpd-business-search-form');
        
        $forms.each(function() {
            const $form = $(this);
            const $locationBtn = $form.find('.gpd-location-button');
            const $latInput = $form.find('input[name="gpd_lat"]');
            const $lngInput = $form.find('input[name="gpd_lng"]');
            const $ajaxResults = $form.find('.gpd-ajax-results');
            const $resultsList = $form.find('.gpd-results-list');
            const $resultsCount = $form.find('.gpd-results-count');
            const $loading = $form.find('.gpd-results-loading');
            const $error = $form.find('.gpd-results-error');
            
            // Location button click
            $locationBtn.on('click', function() {
                if (navigator.geolocation) {
                    $locationBtn.attr('disabled', true);
                    
                    navigator.geolocation.getCurrentPosition(
                        // Success
                        function(position) {
                            $latInput.val(position.coords.latitude);
                            $lngInput.val(position.coords.longitude);
                            $locationBtn.addClass('active');
                            $locationBtn.attr('disabled', false);
                        },
                        // Error
                        function(error) {
                            let errorMessage;
                            
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMessage = "Location permission denied.";
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMessage = "Location information unavailable.";
                                    break;
                                case error.TIMEOUT:
                                    errorMessage = "Location request timed out.";
                                    break;
                                default:
                                    errorMessage = "Unknown location error.";
                            }
                            
                            alert(errorMessage);
                            $locationBtn.attr('disabled', false);
                        },
                        // Options
                        {
                            enableHighAccuracy: true,
                            timeout: 5000,
                            maximumAge: 0
                        }
                    );
                } else {
                    alert("Geolocation is not supported by your browser.");
                }
            });
            
            // Form submission
            $form.on('submit', function(e) {
                // Skip AJAX handling if results_page is set (will redirect)
                if (!$form.find('input[name="gpd_ajax_search"]').length) {
                    return true; // Allow form submission
                }
                
                e.preventDefault();
                
                // Show loading and hide previous results/errors
                $loading.show();
                $resultsList.hide();
                $error.hide();
                $ajaxResults.show();
                
                // Collect form data
                const formData = {
                    action: 'gpd_business_search',
                    query: $form.find('input[name="gpd_query"]').val(),
                    radius: $form.find('select[name="gpd_radius"]').val(),
                    limit: $form.find('select[name="gpd_limit"]').val(),
                    lat: $form.find('input[name="gpd_lat"]').val(),
                    lng: $form.find('input[name="gpd_lng"]').val(),
                    nonce: gpdFrontendVars.nonce
                };
                
                // Send AJAX request
                $.ajax({
                    url: gpdFrontendVars.ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $loading.hide();
                        
                        if (response.success && response.data) {
                            // Update results count
                            const count = response.data.businesses ? response.data.businesses.length : 0;
                            $resultsCount.text(count + ' businesses found');
                            
                            // Update results list
                            if (count > 0) {
                                $resultsList.html(renderBusinessCards(response.data.businesses));
                                $resultsList.show();
                                
                                // Initialize map if it exists
                                if ($form.find('#gpd-results-map').length && typeof gpdInitMap === 'function') {
                                    initResultsMap(response.data.businesses);
                                }
                                
                                // Trigger contentLoaded event for other scripts
                                $(document).trigger('gpd:contentLoaded');
                            } else {
                                $resultsList.html('<div class="gpd-no-results">No businesses found matching your search criteria.</div>');
                                $resultsList.show();
                            }
                        } else {
                            // Show error message
                            let errorMsg = 'Error performing search.';
                            if (response.data && response.data.message) {
                                errorMsg = response.data.message;
                            }
                            $error.text(errorMsg);
                            $error.show();
                        }
                    },
                    error: function() {
                        $loading.hide();
                        $error.text('Error connecting to server. Please try again.');
                        $error.show();
                    }
                });
            });
            
            // Initialize Google Map for results
            function initResultsMap(businesses) {
                const mapElement = document.getElementById('gpd-results-map');
                
                if (!mapElement || !businesses || !businesses.length) {
                    return;
                }
                
                // Calculate center of map
                let lat = 0;
                let lng = 0;
                
                businesses.forEach(function(business) {
                    lat += parseFloat(business.latitude);
                    lng += parseFloat(business.longitude);
                });
                
                lat /= businesses.length;
                lng /= businesses.length;
                
                // Initialize map with businesses
                gpdInitMap('gpd-results-map', {
                    center: {
                        lat: lat,
                        lng: lng
                    },
                    zoom: 12,
                    clustering: true,
                    businesses: businesses.map(function(business) {
                        return {
                            id: business.id,
                            title: business.title,
                            lat: business.latitude,
                            lng: business.longitude,
                            url: business.permalink,
                            address: business.address,
                            thumbnail: business.thumbnail
                        };
                    })
                });
            }
            
            // Render business cards for results
            function renderBusinessCards(businesses) {
                let html = '';
                
                businesses.forEach(function(business) {
                    const thumbnail = business.thumbnail || 'https://via.placeholder.com/300x180?text=No+Image';
                    const rating = business.rating ? business.rating : 0;
                    const distance = business.distance ? formatDistance(business.distance) : '';
                    
                    html += `
                        <div class="gpd-business-card">
                            <div class="gpd-business-thumbnail" style="background-image: url('${thumbnail}')"></div>
                            <div class="gpd-business-info">
                                <h3 class="gpd-business-title">${business.title}</h3>
                                <p class="gpd-business-address">${business.address || 'Address not available'}</p>
                    `;
                    
                    if (rating > 0) {
                        html += `
                            <div class="gpd-business-rating">
                                <div class="gpd-stars">
                                    ${renderStars(rating)}
                                </div>
                                <span>${rating.toFixed(1)}</span>
                            </div>
                        `;
                    }
                    
                    if (distance) {
                        html += `<div class="gpd-business-distance">${distance} away</div>`;
                    }
                    
                    html += `
                                <div class="gpd-business-buttons">
                                    <a href="${business.permalink}" class="gpd-business-link">View Details</a>
                    `;
                    
                    if (business.latitude && business.longitude) {
                        html += `
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${business.latitude},${business.longitude}" 
                               class="gpd-business-directions" target="_blank" rel="noopener">Directions</a>
                        `;
                    }
                    
                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                return html;
            }
            
            // Render star rating
            function renderStars(rating) {
                const fullStars = Math.floor(rating);
                const halfStar = rating % 1 >= 0.5;
                const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
                
                let html = '';
                
                // Add full stars
                for (let i = 0; i < fullStars; i++) {
                    html += '★';
                }
                
                // Add half star if needed
                if (halfStar) {
                    html += '⯪';
                }
                
                // Add empty stars
                for (let i = 0; i < emptyStars; i++) {
                    html += '☆';
                }
                
                return html;
            }
            
            // Format distance in km or m
            function formatDistance(meters) {
                if (meters < 1000) {
                    return Math.round(meters) + ' m';
                } else {
                    return (meters / 1000).toFixed(1) + ' km';
                }
            }
        });
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        init();
    });
    
    // Re-initialize when new content is loaded
    $(document).on('gpd:contentLoaded', function() {
        init();
    });
    
})(jQuery);
