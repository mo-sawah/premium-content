<?php
/**
 * Handles premium badge display for posts tagged with "premium" in Smart Mag theme
 */
class Premium_Content_Meta_Badge {

    private $badge_added = false; // Prevent multiple badges per post

    public function __construct() {
        // Hook into Smart Mag's post meta system
        add_filter('bunyad_post_meta', array($this, 'add_premium_badge_to_meta'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_badge_styles'), 99); // Changed priority to 99
        add_action('wp_footer', array($this, 'add_fallback_script'));
    }

    /**
     * Check if current post has premium tag
     */
    private function is_premium_post($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        return has_tag('premium', $post_id);
    }

    /**
     * Get premium badge HTML - larger crown, normal text
     */
    private function get_premium_badge_html() {
        return '<span class="premium-meta-badge meta-item">
                    <svg class="premium-crown-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M5 16L3 6l5.5 6L12 4l3.5 8L21 6l-2 10H5zm2.7-2h8.6l.9-5.4-2.1 2.4L12 8l-3.1 3l-2.1-2.4L7.7 14z"/>
                    </svg>
                    <span class="premium-text">Premium</span>
                </span>';
    }

    /**
     * Add premium badge to the post meta output
     */
    public function add_premium_badge_to_meta($output, $props) {
        if (!$this->is_premium_post() || strpos($output, 'premium-meta-badge') !== false) {
            return $output;
        }

        // Find the meta-below section and append the badge
        $badge_html = $this->get_premium_badge_html();
        
        // Look for meta-below container and insert the badge at the end
        if (strpos($output, 'meta-below') !== false) {
            $output = preg_replace(
                '/(<div class="[^"]*meta-below[^"]*">.*?)(<\/div>)/',
                '$1' . $badge_html . '$2',
                $output
            );
        }
        // Fallback: append to the end of post meta
        else {
            $output = preg_replace(
                '/(<div class="[^"]*post-meta[^"]*">.*?)(<\/div>)$/',
                '$1' . $badge_html . '$2',
                $output
            );
        }

        return $output;
    }

    /**
     * Inject premium badge after post meta is rendered (only once per post)
     */
    public function inject_premium_badge($post_meta_instance) {
        // This method is no longer used but kept for compatibility
    }

    /**
     * Reset badge flag for each post in loops
     */
    public function reset_badge_flag() {
        $this->badge_added = false;
    }

    /**
     * Enqueue styles for premium badge - larger crown, normal text, fix overlay sizing
     */
    public function enqueue_badge_styles() {
        // All CSS has been moved to premium-content.css for better maintenance.
        // The old inline CSS block has been removed from this function.
    }

    /**
     * Enhanced fallback script - target all Smart Mag block types
     */
    public function add_fallback_script() {
        if (!$this->is_premium_post()) {
            return;
        }
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Only run if badge isn't already present
            if (document.querySelector('.premium-meta-badge')) {
                return;
            }
            
            var premiumBadge = <?php echo json_encode($this->get_premium_badge_html()); ?>;
            
            // Enhanced Smart Mag selectors for all block types
            var smartMagSelectors = [
                // Standard post meta locations
                '.post-meta-items.meta-below',
                '.post-meta-items.meta-above', 
                '.post-meta .meta-below',
                '.post-meta .meta-above',
                '.post-meta',
                
                // Specific Smart Mag block types
                '.loop-list .post-meta',
                '.loop-list .meta-below',
                '.loop-grid .post-meta', 
                '.loop-grid .meta-below',
                '.loop-overlay .post-meta',
                '.loop-overlay .meta-below',
                '.posts-list .post-meta',
                '.posts-grid .post-meta',
                '.overlay .post-meta',
                
                // Classic and other blocks
                '.classic .post-meta',
                '.classic .meta-below',
                '.post-item .post-meta',
                '.post-card .post-meta',
                
                // Generic fallbacks
                '.entry-meta',
                '.article-meta',
                '.meta-info'
            ];
            
            var badgeInserted = false;
            
            // Try all Smart Mag specific locations
            smartMagSelectors.forEach(function(selector) {
                if (badgeInserted) return;
                
                var metaElements = document.querySelectorAll(selector);
                metaElements.forEach(function(element) {
                    if (!badgeInserted && element.innerHTML.indexOf('premium-meta-badge') === -1) {
                        // Insert inline with the existing meta items
                        element.insertAdjacentHTML('beforeend', premiumBadge);
                        badgeInserted = true;
                    }
                });
            });

            // Alternative approach: insert after specific meta items
            if (!badgeInserted) {
                var metaItemSelectors = [
                    '.meta-item.read-time',
                    '.meta-item.post-views', 
                    '.meta-item.comments',
                    '.meta-item.date',
                    '.meta-item.post-author',
                    '.post-date',
                    '.post-author',
                    '.comments a',
                    '.read-time'
                ];
                
                metaItemSelectors.forEach(function(selector) {
                    if (badgeInserted) return;
                    
                    var elements = document.querySelectorAll(selector);
                    elements.forEach(function(element) {
                        if (!badgeInserted) {
                            // Insert right after this meta item (inline)
                            element.insertAdjacentHTML('afterend', premiumBadge);
                            badgeInserted = true;
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }
}