<?php
/**
 * @package WP-Max-Submit-Protect
 */
/*
Plugin Name: WP Max Submit Protect
Plugin URI: https://github.com/academe/wp-max-submit-protect
Description: Protect admin forms from being submitted with too many POST parameters, e.g. a WooCommerce variable product with many variations.
Version: 1.1.2
Author: Academe Computing
Author URI: http://www.academe.co.uk/
License: GPLv2 or later
*/


class WP_Max_Submit_Protect
{
    /**
     * Singleton instance.
     */

    private static $instance = null;

    /**
     * The default limit we will use if no server settings can be found.
     */

    protected $default_limit = 1000;

    /**
     * The limit currently set on the server.
     */

    protected $current_limit = null;

    /**
     * The filename of the plugin, needed to add info to the admin plugin links.
     */

    protected $plugin_basename = null;

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

        // Add some dyanamic information to the plugins page links for this plugin.
        add_filter('plugin_action_links', array($this, 'plugin_action_links_filter'), 10, 2);
    }

    /**
     * List the field limit in the plugins page, along with the links.
     */
    public function plugin_action_links_filter($links, $file)
    {
        if ( ! isset($this->plugin_basename)) {
            $this->plugin_basename = plugin_basename(__FILE__);
        }

        if ($file == $this->plugin_basename) {
            // Only an anchor is correctly formatted, otherwise the text is too light.
            $links[] = sprintf(
                __('<a title="The current limit set by the server">Field limit: %s</a>'),
                (!empty($this->current_limit) ? $this->current_limit : __('unknown'))
            );
        }

        return $links;
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
        $ini[] = ini_get('suhosin.post.max_vars');
        $ini[] = ini_get('suhosin.request.max_vars');

        // Strip out the blanks - ini options not set.
        $ini = array_filter($ini, 'is_numeric');

        // Find the smallest of them all.
        // Ticket #7: A PHP warning will be issued if min() is run against an empty array.
        if ( ! empty($ini)) {
            $lowest_limit = min($ini);
        } else {
            $lowest_limit = $default;
        }

        return ($lowest_limit === false ? $default : $lowest_limit);
    }

    /**
     * Add assets to the page.
     */
    public function wp_enqueue_scripts_action()
    {
        // Get the plugin version for attaching to the assets.
        // We do this to blow away any browser/proxy caches when the plugin is updated.
        $plugin_metadata = get_plugin_data(__FILE__);
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
    }

    /**
     * Add inline JS to the head.
     * So far as I can tell, there is no way to enqueue this.
     */
    public function wp_head_action()
    {
        // Translate the message the administrator will see if they submit a big form.
        // Also encode it to a JavaScript inline string, except for the newline in the middle.
        // Don't translate any of the {fields}.
        $too_many_message = str_replace('{newline}', '\n', json_encode(__(
            'This form has too many fields ({form_count}) for the server to accept (max {max_count})'
            . "{newline}"
            . 'Data may be lost if you submit. Are you sure you want to go ahead?'
        )));

        // Apply the limit checker to all POST forms on the page.
        $script = <<<ENDHTML
            <script type="text/javascript">
                /* Plugin: WP Max Submit Protect */
                jQuery(document).ready(function($) {
                    $('form[method*=post]').maxSubmit({
                        max_count: {$this->current_limit},
                        max_exceeded_message: {$too_many_message}
                    });
                })
            </script>
ENDHTML;

        // Send the script out to the browser in the head.
        // Trim each line to keep it shorter.
        echo implode("\n", array_map('trim', explode("\n", $script)));
    }
}

// Initialise and instantiate the plugin class.
WP_Max_Submit_Protect::getInstance();
