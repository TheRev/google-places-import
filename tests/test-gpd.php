<?php
/**
 * Class GPDTest
 *
 * @package GooglePlacesDirectory
 */

/**
 * Sample test case.
 */
class GPDTest extends WP_UnitTestCase {

    /**
     * Test instance creation
     */
    public function test_instance() {
        $this->assertInstanceOf('GPD_CPT', GPD_CPT::instance());
    }
    
    /**
     * Test post type registration
     */
    public function test_post_type_registration() {
        GPD_CPT::instance(); // Initialize
        
        // Check if post type exists
        $this->assertTrue(post_type_exists('business'));
        
        // Check if taxonomies exist
        $this->assertTrue(taxonomy_exists('destination'));
        $this->assertTrue(taxonomy_exists('region'));
    }
    
    /**
     * Test meta registration
     */
    public function test_meta_registration() {
        GPD_CPT::instance(); // Initialize
        
        // Get registered meta keys
        $registered_meta = get_registered_meta_keys('post', 'business');
        
        // Check if our meta keys are registered
        $this->assertArrayHasKey('_gpd_place_id', $registered_meta);
        $this->assertArrayHasKey('_gpd_rating', $registered_meta);
    }
}
