<?php
/**
 * @package WordPress-Max-Submit-Protect
 * Similar to http://docs.woothemes.com/document/catalog-visibility-options/
 */
/*
Plugin Name: WordPress Max Submit Protect
Plugin URI: http://www.academe.co.uk/
Description: Protect admin forms from being submitted with too many GET or POST parameters, e.g. a WooCommerce variable product with many variations.
Version: 1.0.0
Author: Academe Computing
Author URI: http://www.academe.co.uk/
License: GPLv2 or later
*/

/*
 * TODO:
 */

class WordPress_Max_Submit_Protect
{
    /**
     * Singleton instance.
     */

    private static $instance = null;

    /**
     * The default limit we will use if no server settings can be found.
     */

    protected static $default_limit = 1000;

    /**
     * The limit currently set on the server.
     */

    protected static $current_limit = null;

    /**
     * Return the singleton.
     * PHP 5.2 compatible version.
     * Yes, it is an anti-pattern, but we can't fight the WP ecosystem
     * if we are to live happily within it.
     */

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Plugin constructor.
     * Register all the hooks.
     */

    public function __construct()
    {
        // Only used in admin pages for now.
        // There may be an option later to enable it in other areas.
        if ( ! is_admin()) {
            return;
        }

        // Get the limit.
        $this->current_limit = $this->getFormSubmissionLimit($this->default_limit);

        // If we have no limit (no ini settings retrieved and no default) then go
        // no further.
        if (empty($this->current_limit)) return;

        // Enqueue the JS scripts (add the assets).
        //add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts_action'));
        add_action('admin_enqueue_scripts', array($this, 'wp_enqueue_scripts_action'));

        // Add inline JS to the head.
        //add_action('wp_head', 'wp_head_action');
        add_action('admin_head', array($this, 'wp_head_action'));
    }

    /**
     * Get the submission limit.
     * Returns the lowest limit or false if no limit can be found.
     * An alternate default can be provided if required.
     * CHECKME: do we need to separate GET and POST limits, as they may apply
     * to different forms. The larger number of parameters is like to only
     * apply to POST forms, so POST is important. The REQUEST max vars is 
     * another thing to consider, as it will be the sum of GET and POST parameters.
     */
    public function getFormSubmissionLimit($default = false)
    {
        // All these ini settings will affect the number of parameters that can be
        // processed. Check them all to find the lowest.
        $ini = array();
        $ini[] = ini_get('max_input_vars');
        $ini[] = ini_get('suhosin.get.max_vars');
        $ini[] = ini_get('suhosin.post.max_vars');
        $ini[] = ini_get('suhosin.request.max_vars');

        // Strip out the blanks - ini options not set.
        $ini = array_filter($ini, 'is_numeric');

        // Find the smallest of them all.
        $lowest_limit = min($ini);

        return ($lowest_limit === false ? $default : $lowest_limit);
    }

    /**
     * Add assets to the page.
     */
    public function wp_enqueue_scripts_action()
    {
        // Get the plugin version for attaching to the assets.
        // We do this to blow away any browser/proxy caches when the plugin is updated.
        $plugin_metadata = get_plugin_data( __FILE__);
        $version = (!empty($plugin_metadata['Version']) ? $plugin_metadata['Version'] : '1.0.0');

        // Enqueue the jQuery plugin.
        // TODO: introduce a minimised version of this script.
        wp_register_script(
            'jquery.maxsubmit',
            plugins_url('js/jquery-maxsubmit/jquery.maxsubmit.js', __FILE__),
            array('jquery'),
            $version,
            false
        );
        wp_enqueue_script('jquery.maxsubmit');

        // Enqueue the initialisation of the form checker.
        // Apply it to all forms.
    }

    /**
     * Add inline JS to the head.
     */
    public function wp_head_action()
    {
        // Apply the limit checker to all forms on the page.
        $script = <<<ENDHTML
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('form').maxSubmit({max_count: {$this->current_limit}});
                })
            </script>
ENDHTML;

        // Send the script out to the browser in the head.
        // Trim each line to keep it shorter.
        echo implode("\n", array_map('trim', explode("\n", $script)));
    }
}

// Initialise and instantiate the plugin class.
WordPress_Max_Submit_Protect::getInstance();

