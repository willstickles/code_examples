<?php
/*
Plugin Name: Sitemap
Version:1.0
Description: Creates a custom post type to display a sitemap on client pages
Author: Will Stickles
Author URI: 
Plugin URI: 
*/

/* Include Class File */
//include( plugin_dir_path( __FILE__ ) . 'inc/Option.class.php');

/* Toggle Debug During Development */
#define( 'WP_DEBUG', FALSE );

/* Load the plugin text domain */
load_plugin_textdomain('wp_sitemap', plugin_dir_path(__FILE__) . '/languages');

/* Check to see if hte plugin is being accessed directly
 * If so, send a 403 Forbidden response
 */
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/* Setup the class */
if (!class_exists('SiteMap')) {
    class SiteMap
    {

        /* Set Variables for plugin */

        private $plugin_name = 'Sitemap';
        private $plugin_textdomain = 'wp_sitemap';
        private $plugin_classname = 'SiteMap';
        private $plugin_nonce_name = 'wp-sitemap-nonce';
        private $plugin_widget_name = 'SiteMapWidget';

        /* Set the minimum required version and the exit message if it isn't met */
        private
            $minimum_version = '3.0';
        private
            $minimum_message = 'Sitemap requires Wordpress 3.0 or greater.<a href="http://codex.wordpress.org/Upgrading_Wordpress">Click here to upgrade.</a>';
        private
            $nonce;

        /* Constructor */
        function __construct()
        {
            /* Get the current wp version */
            global $wp_version;

            if (version_compare($wp_version, $this->minimum_version, '<')) {
                exit($this->minimum_message);
            }

            /* Register Nonce */
            add_action('admin_init', array(&$this, 'registerNonce'));

            /* Register required admin assets (JS & CSS) */
            add_action('admin_enqueue_scripts', array(&$this, 'registerAdminAssets'));

            /* Register the post type meta box */
            add_action('add_meta_boxes', array(&$this, 'registerMetaBox'));

            /* Register the save method */
            add_action('save_post', array(&$this, 'savePostMeta'));

            /* Register Sitemap Shortcode */
            add_shortcode('sitemap', array(&$this, 'render_sitemap'));
        }

        /* Installation Procedure */
        function install()
        {
            // NA
        }

        /* Uninstallation Procedure */
        function uninstall()
        {
            // NA
        }

        /* Localize js variables */
        function LocalizeScripts()
        {
            return array(
                'Nonce' => $this->nonce
            );
        }

        /* Nonce Registration */
        public function registerNonce()
        {
            $this->nonce = wp_create_nonce($this->plugin_nonce_name);
        }

        /* Register admin assets */
        function registerAdminAssets($hook)
        {

            /* Get the post type */
            global $post;

            if ($this->plugin_textdomain === $post->post_type) {

                /* Register and enqueue the css */
                wp_register_style('' . $this->plugin_textdomain . '_admin_css', plugins_url('/assets/css/admin.css', __FILE__), false, '1.0.0');
                wp_enqueue_style('' . $this->plugin_textdomain . '_admin_css');

                /* Register and enqueue the js */
                wp_register_script('' . $this->plugin_textdomain . '_admin_js', plugins_url('/assets/js/admin.js', __FILE__), true, '1.0.0');
                wp_enqueue_script('' . $this->plugin_textdomain . '_admin_js');

                /* Enqueue jquery ui for drag and drop using wordpress' version */
                wp_enqueue_script('jquery-ui-sortable');

                /* Localize script variables */
                wp_localize_script('' . $this->plugin_textdomain . '_admin_js', '' . $this->plugin_textdomain . '_nonce', $this->LocalizeScripts());

            }
        }

        /* Register Meta Box */

        function registerMetaBox()
        {
            /* Define the custom box */

            if (function_exists('add_meta_box')) {
                add_meta_box('sitemap-meta', __('Exclude from Sitemap', 'wp_sitemap'), array(&$this, 'renderMetaBox'), 'page', 'side', 'high');
            }
        }

        function renderMetaBox()
        {
            /* Get the post in case we are editing */
            global $post;

            $display_pages = get_post_meta($post->ID);

            $display = $display_pages['wp_display_pages'][0];

            if($display == 'on'){
                $checked = 'checked=checked';
            }else{
                $checked = '';
            }

            /* Get the post meta information
             * Used in the included view !! DO NOT DELETE !!
             */
            $custom = get_post_custom($post->ID);

            /* Set a pages variable for the view
             * Used in the included view !! DO NOT DELETE !!
             */
            $pages = get_pages();

            /* Set the nonce value
             * Used in the included view !! DO NOT DELETE !!
             */
            $nonce = $this->nonce;
            echo '<input type="checkbox" name="wp_display_pages" id="wp_display_pages" '. $checked .' /> ';
            echo 'Exclude';
        }

        // load post_meta_data
        function load_post_meta($id) {

            return get_post_meta($id, 'wp_display_pages', true);
        }

        /* Sitemap */
        function render_sitemap($atts = null)
        {

            global $wpdb;

            $query = "SELECT *
                        FROM
                        $wpdb->postmeta
                        WHERE
                        meta_key = 'wp_display_pages'
                        AND
                        meta_value = 'on'
            ";

            $posts = $wpdb->get_results($query, OBJECT);

            $test = '';
            foreach ($posts as $post) {
                $val = $post->post_id;

                $test .= $val . ',';
            }

            if (substr($test, -1) == ",") {
                $include = substr_replace($test, "", -1);
            }

            $title_li = 'Sitemap';
            $link_before = '';
            $link_after = '';


            if (isset($atts)) {
                extract(shortcode_atts(array('title_li' => 'Sitemap', 'link_before' => '', 'link_after' => ''), $atts));
            }

            $output = '<ul>';
//            $output .= wp_list_pages('&include=' . $include, ',&title_li=&link_before=' . $link_before . '&link_after=' . $link_after . '&echo=0');
            $output .= wp_list_pages('&exclude=' . $include.'&title_li=' . $title_li.'&link_before=' . $link_before . '&link_after=' . $link_after . '&echo=0'  );
            $output .= '</ul>';

            return $output;
        }

        /* Register Save functionality */
        function savePostMeta()
        {

            global $post;
            global $wpdb;
            $maxSort = 0;


//            if (!empty($_POST) && 'page' === $post->post_type) {
//                if (empty($_POST[$this->plugin_nonce_name]) || !wp_verify_nonce($_POST[$this->plugin_nonce_name], $this->plugin_nonce_name)) {
//                    throw new Exception(__('Could not verify nonce', $this->plugin_textdomain));
//                }
//            }

            if ('page' === $post->post_type) {

                /* Return early if there is no post data */
                if (empty($_POST)) {
                    return;
                }

                /* Save the meta data */
                $internal = $_POST['wp_display_pages'];

                (empty($internal)) ? update_post_meta($post->ID, 'wp_display_pages', null) : update_post_meta($post->ID, 'wp_display_pages', $internal);

            }

        }

    }
} else {
    /* Exit wit a message that the PostTypeFactory class has been setup */
    _e($this->plugin_name . ' has already been setup.', $this->plugin_name);
}

/* Create a new SoeCustomHeader */
$SiteMap = new SiteMap();

/* If SoeExample has been set, register the activation and deactivation hooks */
if (isset($SiteMap)) {
    register_activation_hook(__FILE__, array(&$SiteMap, 'install'));
    register_deactivation_hook(__FILE__, array(&$SiteMap, 'uninstall'));
}