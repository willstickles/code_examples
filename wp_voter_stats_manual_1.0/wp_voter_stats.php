<?php
/*
Plugin Name: Voter Stats
Version:1.0
Description: Creates a custom post type to display new content 
Author: Will Stickles
Author URI:
Plugin URI:
*/


/* Toggle Debug During Development */
#define( 'WP_DEBUG', FALSE );

/* Load the plugin text domain */
load_plugin_textdomain('wp_voter_stats', plugin_dir_path(__FILE__) . '/languages');

/* Check to see if the plugin is being accessed directly
 * If so, send a 403 Forbidden response
 */
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/* Setup the class */
if (!class_exists('VoterStats')) {
    class VoterStats
    {
        /* Set the minimum required version and the exit message if it isn't met */
        private
            $minimum_version = '3.0';
        private
            $minimum_message = 'SOE Voter Stats requires Wordpress 3.0 or greater.<a href="http://codex.wordpress.org/Upgrading_Wordpress">Click here to upgrade.</a>';
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

            /* Include any widgets */
            foreach (glob(plugin_dir_path(__FILE__) . 'widgets/*.php') as $widget) {
                include_once($widget);
            }

            /* Register Nonce */
            add_action('admin_init', array(&$this, 'registerNonce'));

            /* Register required admin assets (JS & CSS) */
            add_action('admin_enqueue_scripts', array(&$this, 'registerAdminAssets'));

            /* Register an ajax callback for re-sorting posts */
            add_action('wp_ajax_sort_wp_voter_stats', array(&$this, 'sortCallback'));

            /* Register a pre sort function in the admin */
            add_action('pre_get_posts', array(&$this, 'registerPreSort'));

            // add the admin menu page
            add_action('admin_menu', array(&$this, 'soe_voters_stats_admin_add_page'));

            /* Register widget(s) */
            add_action('widgets_init', array(&$this, 'registerWidgets'));
        }

        /* Installation Procedure */
        function install()
        {
            global $wpdb;
            $query = $wpdb->query("UPDATE wp_posts SET post_status='publish' WHERE post_type='wp_voter_stats' and post_status='draft'");
        }

        /* Uninstallation Procedure */
        function uninstall()
        {
            global $wpdb;
            $query = $wpdb->query("UPDATE wp_posts SET post_status='draft' WHERE post_type='wp_voter_stats'");
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
            $this->nonce = wp_create_nonce('wp_voter_stats-nonce');
        }

        /* Register admin assets */
        function registerAdminAssets($hook)
        {

            /* Get the post type */
            global $post;

            if ('wp_voter_stats' === $post->post_type) {

                /* Register and enqueue the css */
                wp_register_style('wp_voter_stats_admin_css', plugins_url('/assets/css/admin.css', __FILE__), false, '1.0.0');
                wp_enqueue_style('wp_voter_stats_admin_css');

                /* Register and enqueue the js */
                wp_register_script('wp_voter_stats_admin_js', plugins_url('/assets/js/admin.js', __FILE__), true, '1.0.0');
                wp_enqueue_script('wp_voter_stats_admin_js');

                /* Enqueue jquery ui for drag and drop using wordpress' version */
                wp_enqueue_script('jquery-ui-sortable');

                /* Localize script variables */
                wp_localize_script('wp_voter_stats_admin_js', 'wp_voter_stats_nonce', $this->LocalizeScripts());

            }

            /* Register and enqueue the css */
            wp_register_style('wp_voter_stats_css', plugins_url('/assets/css/voters_stats.css', __FILE__), false, '1.0.0');
            wp_enqueue_style('wp_voter_stats_css');

            /* Register and enqueue the js */
            wp_register_script('wp_voter_stats_js', plugins_url('/assets/js/voters_stats.js', __FILE__), true, '1.0.0');
            wp_enqueue_script('wp_voter_stats_js');

            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

        }

        function soe_voters_stats_admin_add_page()
        {
            add_menu_page('SOE Voters Stats', 'SOE Voters Stats', 'manage_options', 'voters_stats', array(&$this, 'soe_voters_stats_options_page'), '' , 21);

        }

        // display the admin options page
        function soe_voters_stats_options_page()
        {

            include_once('views/voters_stats.php');

            if (isset($_POST["update_settings"])) {
                // Do the saving
                update_option('voters_stats_settings', array($_POST));

            }
        }

        /* Register Widgets */
        function registerWidgets()
        {
            register_widget('VoterStatsWidget');
        }

        /* Resorting ajax callback */
        function sortCallback()
        {

            if (empty($_POST['soe-voter-stats-nonce']) || !wp_verify_nonce($_POST['soe-voter-stats-nonce'], 'soe-voter-stats-nonce')) {
                throw new Exception(__('Could not verify nonce', 'wp_voter_stats'));
            }

            if (empty($_POST['rows'])) {
                ob_end_clean();
                header('HTTP/1.1 500 Internal Server Error');
                _e('No row data provided', 'wp_voter_stats');
                die();
            } else {
                $rows = stripslashes($_POST['rows']);
                $rows = json_decode($rows);

                foreach ($rows as $key => $value) {
                    $this->saveSortMeta(str_replace('post-', '', $key), $value);
                }

                ob_end_clean();
                header('Http/1.1 200 OK');
                die();
            }

        }

        /* Save the post sort meta */
        function saveSortMeta($post, $sort)
        {
            update_post_meta($post, 'wp_voter_stats_Sort', $sort);
        }

        /* Pre sort soe example columns in admin */
        function registerPreSort($query)
        {

            global $wp_query;

            if (is_admin() && 'wp_voter_stats' === $wp_query->query_vars['post_type']) {
                $query->set('meta_key', 'wp_voter_stats_Sort');
                $query->set('orderby', 'meta_value');
                $query->query_vars['order'] = 'asc';
                $query->query_vars['orderby'] = 'meta_value_num';
            }
        }

    }
} else {
    /* Exit wit a message that the PostTypeFactory class has been setup */
    exit(__('SOE Voter Stats has already been setup.', 'SOE Voter Stats'));
}

/* Create a new VoterStats */
$VoterStats = new VoterStats();

/* If VoterStats has been set, register the activation and deactivation hooks */
if (isset($VoterStats)) {
    register_activation_hook(__FILE__, array(&$VoterStats, 'install'));
    register_deactivation_hook(__FILE__, array(&$VoterStats, 'uninstall'));
}


