/**
 * Google Places Directory - Leaflet Maps Integration
 * Version: 2.3.0
 * Date: 2025-05-26
 * 
 * Replaces Google Maps with Leaflet for displaying business locations
 */
(function($) {
    'use strict';
    
    // Global map instances storage
    window.gpdMaps = window.gpdMaps || {};
    
    /**
     * Initialize Leaflet map - replaces gpdInitMap function
     * 
     * @param {string} mapId - ID of the map container
     * @param {object} options - Map configuration options
     */
    window.gpdInitMap = function(mapId, options) {
        // Default options
        const defaults = {
            center: { lat: 40.7128, lng: -74.0060 }, // Default to NYC
            zoom: 12,
            clustering: true,
            businesses: [],
            showSurrounding: true,
            maxSurroundingDistance: 10000, // 10km in meters
            tileLayer: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '© OpenStreetMap contributors'
        };
        
        const config = Object.assign({}, defaults, options);
        
        // Initialize map
        const map = L.map(mapId, {
            center: [config.center.lat, config.center.lng],
            zoom: config.zoom,
            zoomControl: true,
            scrollWheelZoom: true
        });
        
        // Add tile layer
        L.tileLayer(config.tileLayer, {
            attribution: config.attribution,
            maxZoom: 18
        }).addTo(map);
        
        // Store map instance
        window.gpdMaps[mapId] = map;
        
        // Initialize markers
        if (config.businesses && config.businesses.length > 0) {
            addBusinessMarkers(map, config.businesses, config);
        }
        
        return map;
    };
    
    /**
     * Add business markers to the map
     */
    function addBusinessMarkers(map, businesses, config) {
        const markers = [];
        
        // Create marker cluster group if clustering is enabled
        let markerGroup;
        if (config.clustering && window.L && L.markerClusterGroup) {
            markerGroup = L.markerClusterGroup({
                chunkedLoading: true,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 50
            });
        } else {
            markerGroup = L.featureGroup();
        }
        
        // Add each business marker
        businesses.forEach(function(business) {
            if (!business.lat || !business.lng) return;
            
            const lat = parseFloat(business.lat);
            const lng = parseFloat(business.lng);
            
            if (isNaN(lat) || isNaN(lng)) return;
            
            // Create custom icon
            const icon = createBusinessIcon(business);
            
            // Create marker
            const marker = L.marker([lat, lng], { icon: icon });
            
            // Create popup content
            const popupContent = createPopupContent(business);
            marker.bindPopup(popupContent, {
                maxWidth: 300,
                className: 'gpd-leaflet-popup'
            });
            
            // Add click event to marker
            marker.on('click', function() {
                // Track analytics if available
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'business_marker_click', {
                        business_id: business.id,
                        business_name: business.title
                    });
                }
            });
            
            markerGroup.addLayer(marker);
            markers.push(marker);
        });
        
        // Add marker group to map
        map.addLayer(markerGroup);
        
        // Fit map to show all markers
        if (markers.length > 1) {
            map.fitBounds(markerGroup.getBounds(), { padding: [20, 20] });
        }
        
        // Load surrounding businesses if enabled
        if (config.showSurrounding && businesses.length > 0) {
            loadSurroundingBusinesses(map, businesses, config);
        }
        
        return markers;
    }
    
    /**
     * Create custom business icon
     */
    function createBusinessIcon(business) {
        const iconHtml = `
            <div class="gpd-marker-icon">
                <div class="gpd-marker-pin"></div>
                <div class="gpd-marker-content">
                    <i class="gpd-icon-business"></i>
                </div>
            </div>
        `;
        
        return L.divIcon({
            html: iconHtml,
            className: 'gpd-custom-marker',
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -40]
        });
    }
    
    /**
     * Create popup content for business marker
     */
    function createPopupContent(business) {
        const title = business.title || 'Business';
        const address = business.address || '';
        const rating = business.rating || 0;
        const url = business.url || business.permalink || '#';
        const thumbnail = business.thumbnail || '';
        
        let content = `
            <div class="gpd-popup-content">
                <div class="gpd-popup-header">
                    ${thumbnail ? `<img src="${thumbnail}" alt="${title}" class="gpd-popup-image">` : ''}
                    <h3 class="gpd-popup-title">${title}</h3>
                </div>
                <div class="gpd-popup-body">
                    ${address ? `<p class="gpd-popup-address">${address}</p>` : ''}
                    ${rating > 0 ? `
                        <div class="gpd-popup-rating">
                            <span class="gpd-rating-stars">${renderStars(rating)}</span>
                            <span class="gpd-rating-value">${rating.toFixed(1)}</span>
                        </div>
                    ` : ''}
                </div>
                <div class="gpd-popup-actions">
                    <a href="${url}" class="gpd-popup-btn gpd-popup-btn-primary" target="_blank">
                        View Details
                    </a>
        `;
        
        // Add Google Maps link if coordinates are available
        if (business.lat && business.lng) {
            const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${business.lat},${business.lng}`;
            content += `
                    <a href="${googleMapsUrl}" class="gpd-popup-btn gpd-popup-btn-secondary" target="_blank" rel="noopener">
                        <i class="gpd-icon-directions"></i> Directions
                    </a>
            `;
        }
        
        content += `
                </div>
            </div>
        `;
        
        return content;
    }
    
    /**
     * Render star rating
     */
    function renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';
        
        for (let i = 0; i < 5; i++) {
            if (i < fullStars) {
                stars += '<i class="gpd-star gpd-star-full">★</i>';
            } else if (i === fullStars && hasHalfStar) {
                stars += '<i class="gpd-star gpd-star-half">☆</i>';
            } else {
                stars += '<i class="gpd-star gpd-star-empty">☆</i>';
            }
        }
        
        return stars;
    }
    
    /**
     * Load surrounding businesses from the server
     */
    function loadSurroundingBusinesses(map, centerBusinesses, config) {
        if (!centerBusinesses.length || !window.gpdFrontendVars) {
            return;
        }
        
        // Get center point
        const centerBusiness = centerBusinesses[0];
        const centerLat = parseFloat(centerBusiness.lat);
        const centerLng = parseFloat(centerBusiness.lng);
        
        if (isNaN(centerLat) || isNaN(centerLng)) {
            return;
        }
        
        // AJAX request to get surrounding businesses
        $.ajax({
            url: window.gpdFrontendVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'gpd_get_surrounding_businesses',
                lat: centerLat,
                lng: centerLng,
                radius: config.maxSurroundingDistance,
                exclude_ids: centerBusinesses.map(b => b.id),
                nonce: window.gpdFrontendVars.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.businesses) {
                    addSurroundingBusinesses(map, response.data.businesses);
                }
            },
            error: function(xhr, status, error) {
                console.log('Failed to load surrounding businesses:', error);
            }
        });
    }
    
    /**
     * Add surrounding businesses with different styling
     */
    function addSurroundingBusinesses(map, businesses) {
        const surroundingGroup = L.featureGroup();
        
        businesses.forEach(function(business) {
            if (!business.lat || !business.lng) return;
            
            const lat = parseFloat(business.lat);
            const lng = parseFloat(business.lng);
            
            if (isNaN(lat) || isNaN(lng)) return;
            
            // Create icon for surrounding business (different style)
            const icon = createSurroundingBusinessIcon(business);
            
            // Create marker
            const marker = L.marker([lat, lng], { icon: icon });
            
            // Create popup content
            const popupContent = createPopupContent(business);
            marker.bindPopup(popupContent, {
                maxWidth: 300,
                className: 'gpd-leaflet-popup gpd-surrounding-popup'
            });
            
            surroundingGroup.addLayer(marker);
        });
        
        // Add to map
        map.addLayer(surroundingGroup);
        
        // Store reference for potential removal
        map.gpdSurroundingGroup = surroundingGroup;
    }
    
    /**
     * Create icon for surrounding businesses
     */
    function createSurroundingBusinessIcon(business) {
        const iconHtml = `
            <div class="gpd-marker-icon gpd-marker-surrounding">
                <div class="gpd-marker-pin"></div>
                <div class="gpd-marker-content">
                    <i class="gpd-icon-business-small"></i>
                </div>
            </div>
        `;
        
        return L.divIcon({
            html: iconHtml,
            className: 'gpd-custom-marker gpd-surrounding-marker',
            iconSize: [20, 25],
            iconAnchor: [10, 25],
            popupAnchor: [0, -25]
        });
    }
    
    /**
     * Initialize maps when document is ready
     */
    $(document).ready(function() {
        // Auto-initialize any maps that were created before this script loaded
        $('.gpd-map-canvas').each(function() {
            const mapId = this.id;
            if (mapId && !window.gpdMaps[mapId]) {
                // Try to initialize with default settings if no specific config was provided
                const $mapContainer = $(this).parent();
                if ($mapContainer.data('gpd-businesses')) {
                    window.gpdInitMap(mapId, {
                        businesses: $mapContainer.data('gpd-businesses')
                    });
                }
            }
        });
    });
    
    /**
     * Public API for programmatic control
     */
    window.gpdLeafletMaps = {
        /**
         * Get map instance by ID
         */
        getMap: function(mapId) {
            return window.gpdMaps[mapId] || null;
        },
        
        /**
         * Add business to existing map
         */
        addBusiness: function(mapId, business) {
            const map = this.getMap(mapId);
            if (map && business) {
                addBusinessMarkers(map, [business], { clustering: false });
            }
        },
        
        /**
         * Clear surrounding businesses
         */
        clearSurrounding: function(mapId) {
            const map = this.getMap(mapId);
            if (map && map.gpdSurroundingGroup) {
                map.removeLayer(map.gpdSurroundingGroup);
                delete map.gpdSurroundingGroup;
            }
        },
        
        /**
         * Refresh surrounding businesses
         */
        refreshSurrounding: function(mapId) {
            this.clearSurrounding(mapId);
            const map = this.getMap(mapId);
            if (map && map.gpdCenterBusinesses) {
                loadSurroundingBusinesses(map, map.gpdCenterBusinesses, {
                    showSurrounding: true,
                    maxSurroundingDistance: 10000
                });
            }
        }
    };
    
})(jQuery);
