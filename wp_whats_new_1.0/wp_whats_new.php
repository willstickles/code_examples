<?php

/*
Plugin Name: Whats New
Version:1.0
Description: Creates a custom post type to display new content 
Author: Will Stickles
Author URI:
Plugin URI:
*/

/* Toggle Debug During Development */
#define( 'WP_DEBUG', FALSE );

/* Load the plugin text domain */
load_plugin_textdomain('wp_whats_new', plugin_dir_path(__FILE__) . '/languages');

/* Check to see if the plugin is being accessed directly
 * If so, send a 403 Forbidden response
 */
if (!function_exists('add_action')) {

    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/* Set up the class */
if (!class_exists('WhatsNew')) {

    class WhatsNew
    {

        /* Set the minimum required version and the exit message if it isn't met */
        private $minimum_version = '3.0';
        private $minimum_message = 'Whats New requires WordPress 3.0 or greater.<a href="http://codex.wordpress.org/Upgrading_WordPress">Click here to upgrade.</a>';
        private $nonce;

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

            /* Register the post type */
            add_action('init', array(&$this, 'registerPostType'));

            /* Register Nonce */
            add_action('admin_init', array(&$this, 'registerNonce'));

            /* Register the post type meta box */
            add_action('add_meta_boxes', array(&$this, 'registerMetaBox'));

            /* Register the save method */
            add_action('save_post', array(&$this, 'savePostMeta'));

            /* Register required admin assets (JS & CSS) */
            add_action('admin_enqueue_scripts', array(&$this, 'registerAdminAssets'));

            /* Register custom column handling */
            add_action('manage_whats_new_posts_custom_column', array(&$this, 'registerCustomColumn'));
            add_filter('manage_edit-whats_new_columns', array(&$this, 'registerEditCustomColumns'));

            /* Register an ajax callback for re-sorting posts */
            add_action('wp_ajax_sort_whats_new', array(&$this, 'sortCallback'));

            /* Register a pre sort function in the admin */
            add_action('pre_get_posts', array(&$this, 'registerPreSort'));

            /* Register widget(s) */
            add_action('widgets_init', array(&$this, 'registerWidgets'));

            /* Add action to update sort order when post is moved from publish */
            add_action('publish_to_trash', array(&$this, 'syncPostSort'));
            add_action('publish_to_draft', array(&$this, 'syncPostSort'));
            add_action('wp_trash_post', array(&$this, 'syncPostSort'));
            add_action('save_post', array(&$this, 'syncPostSort'));
            add_action('edit_post', array(&$this, 'syncPostSort'));

        }

        /* Installation Procedure */
        function install()
        {
            global $wpdb;
            $query = $wpdb->query("UPDATE $wpdb->posts SET post_status='publish' WHERE post_type='whats_new' AND post_status='deactivated'");
        }

        /* Uninstallation Procedure */
        function uninstall()
        {
            global $wpdb;
            $query = $wpdb->query("UPDATE $wpdb->posts SET post_status='deactivated' WHERE post_type='whats_new' AND post_status='publish'");
        }

        /* Localize js variables */
        function LocalizeScripts()
        {
            return array(
                'Nonce' => $this->nonce
            );
        }

        /* Register the post type */
        function registerPostType()
        {

            $labels = array(
                'name' => _x("What's New", 'post type general name'),
                'singular_name' => _x("What's New", 'post type singular name'),
                'all_items' => _x("What's New Items", 'post type singular name'),
                'add_new' => _x('Add New', "What's New" . ' item'),
                'add_new_item' => __("Add New What's New item"),
                'edit' => _x('Edit', "What's New Item"),
                'edit_item' => __('Edit ' . "What's New Item"),
                'new_item' => __('New ' . "What's New Item"),
                'view_item' => __('View Item'),
                'search_items' => __('Search ' . "What's New Item"),
                'not_found' => __('Nothing found'),
                'not_found_in_trash' => __('Nothing found in Trash'),
                'parent_item_colon' => '',
                'menu_name' => _x("What's New", "What's New"),
            );

            $args = array(
                'labels' => $labels,
                'description' => __('Creates a custom whats new post type', 'wp_whats_new'),
                'supports' => array('title'),
                'capability_type' => 'post',
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'has_archive' => true,
                'query_var' => true,
                'can_export' => true,
                'rewrite' => true
            );

            register_post_type('whats_new', $args);

        }

        /* Nonce Registration */
        public function registerNonce()
        {
            $this->nonce = wp_create_nonce('whats_new_nonce');
        }

        /* Register meta information */
        function registerMetaBox()
        {
            if (function_exists('add_meta_box')) {
                add_meta_box('whats-new-meta', __('Custom Information', 'wp_whats_new'), array(&$this, 'renderMetaBox'), 'whats_new', 'normal', 'high');
            }
        }

        /* Render meta box */
        function renderMetaBox()
        {

            /* Get the post in case we are editing */
            global $post;

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
            include_once('views/entry.php');

        }

        /* Register admin assets */
        function registerAdminAssets($hook)
        {

            /* Get the post type */
            global $post;

            if ('whats_new' === $post->post_type) {

                /* Register and enqueue the css */
                wp_register_style('whats_new_admin_css', plugins_url('/assets/css/admin.css', __FILE__), false, '1.0.0');
                wp_enqueue_style('whats_new_admin_css');

                /* Register and enqueue the js */
                wp_register_script('whats_new_admin_js', plugins_url('/assets/js/admin.js', __FILE__), true, '1.0.0');
                wp_enqueue_script('whats_new_admin_js');

                /* Enqueue jquery ui for drag and drop using WordPress' version  */
                wp_enqueue_script('jquery-ui-sortable');

                /* Localize script variables */
                wp_localize_script('whats_new_admin_js', 'whats_new_nonce', $this->LocalizeScripts());

            }

        }

        /* Register the data shown in the edit view columns */
        function registerCustomColumn($column)
        {
            global $post;
            $custom = get_post_custom($post->ID);

            switch ($column) {
                case 'cb':
                    echo '<input type="checkbox"/>';
                    break;
                case 'title':
                    echo $post->post_title;
                    break;
                case 'external_link':
                    echo $custom['whats_new_External_Link'][0];
                    break;
                case 'internal_link':
                    echo $custom['whats_new_Internal_Link'][0];
                    break;
                case 'sort':
                    echo $custom['whats_new_Sort'][0];
                    break;
            }

        }

        /* Register columns that display on the edit view (list view) */
        function registerEditCustomColumns($columns)
        {

            $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => __('Title', 'wp_whats_new'),
                'external_link' => __('External Link', 'wp_whats_new'),
                'internal_link' => __('Internal Link', 'wp_whats_new'),
                'sort' => __('Sort Order', 'wp_whats_new')
            );

            return $columns;
        }

        /* Register Save functionality */
        function savePostMeta()
        {

            global $post;
            global $wpdb;
            $maxSort = 0;


            if (!empty($_POST) && 'whats_new' === $post->post_type) {
                if (empty($_POST['whats_new_nonce']) || !wp_verify_nonce($_POST['whats_new_nonce'], 'whats_new_nonce')) {
                    throw new Exception(__('Could not verify nonce', 'wp_whats_new'));
                }
            }

            if ('whats_new' === $post->post_type) {

                /* Return early if there is no post data */
                if (empty($_POST)) {
                    return;
                }

                if (empty($_POST['whats_new_Sort'])) {
                    /* This is a new item */
                    $maxSort = $wpdb->get_var("SELECT MAX(CAST(meta_value AS SIGNED)) FROM $wpdb->postmeta WHERE meta_key='whats_new_Sort'");
                    $maxSort += 1;
                }

                /* Save the meta data */
                $internal = $_POST['whats_new_Internal_Link'];
                $external = $_POST['whats_new_External_Link'];
                $sort = $_POST['whats_new_Sort'];

                (empty($internal)) ? update_post_meta($post->ID, 'whats_new_Internal_Link', null) : update_post_meta($post->ID, 'whats_new_Internal_Link', $internal);
                (empty($external)) ? update_post_meta($post->ID, 'whats_new_External_Link', null) : update_post_meta($post->ID, 'whats_new_External_Link', $external);
                (empty($sort) || '0' === $sort || 0 === $sort) ? update_post_meta($post->ID, 'whats_new_Sort', $maxSort) : update_post_meta($post->ID, 'whats_new_Sort', $sort);

            }
        }

        function registerWidgets()
        {
            register_widget('WhatsNewWidget');
        }

        /* Resorting ajax callback */
        function sortCallback()
        {

            if (empty($_POST['whats_new_nonce']) || !wp_verify_nonce($_POST['whats_new_nonce'], 'whats_new_nonce')) {
                throw new Exception(__('Could not verify nonce', 'wp_whats_new'));
            }

            if (empty($_POST['rows'])) {
                ob_end_clean();
                header('HTTP/1.1 500 Internal Server Error');
                _e('No row data provided', 'wp_whats_new');
                die();
            } else {

                $rows = stripslashes($_POST['rows']);
                $rows = json_decode($rows);

                foreach ($rows as $key => $value) {
                    $this->saveSortMeta(str_replace('post-', '', $key), $value);
                }


                ob_end_clean();
                header('HTTP/1.1 200 OK');
                die();

            }
        }

        /* Save the post sort meta */
        function saveSortMeta($post, $sort)
        {
            update_post_meta($post, 'whats_new_Sort', $sort);
        }

        /* Pre sort whats new columns in admin */
        function registerPreSort($query)
        {

            global $wp_query;

            if (is_admin() && 'whats_new' === $wp_query->query_vars['post_type']) {

                $query->set('meta_key', 'whats_new_Sort');
                $query->set('orderby', 'meta_value');
                $query->query_vars['order'] = 'asc';
                $query->query_vars['orderby'] = 'meta_value_num';

            }

        }

        function syncPostSort($post){

            global $wpdb;

            $maxQuery = "SELECT MAX(CAST(meta_value AS SIGNED)) FROM $wpdb->postmeta WHERE meta_key='whats_new_Sort'";
            $max = $wpdb->get_var($maxQuery);
            $max = $max + 1;

            $upQuery = sprintf("UPDATE $wpdb->postmeta SET meta_value=%d WHERE meta_key='whats_new_Sort' AND post_id=%d", $max, $post);
            $sort = $wpdb->query($upQuery);

            error_log($maxQuery);
            error_log($upQuery);

            $get_posts = "
                SELECT meta_value, post_id, meta_key
                FROM    $wpdb->postmeta
                WHERE   meta_key = 'whats_new_Sort'
                AND     post_id IN (
                SELECT  ID
                FROM    $wpdb->posts
                WHERE   post_status = 'publish'
                AND     post_type = 'whats_new'
                )
                ORDER BY CAST(meta_value AS SIGNED) ASC
            ";

            $posts = $wpdb->get_results($get_posts, OBJECT);

            $i = 1;

            foreach ($posts as $key => $value) {

                update_post_meta($value->post_id, $value->meta_key, $i, $value->meta_value);
                $i++;

            }
            
        }

    }

} else {

    /* Exit with a message that the PostTypeFactory class has been set up */
    _e('Whats New has already been set up.', 'wp_whats_new');

}

/* Create a new WhatsNew */
$WhatsNew = new WhatsNew();

/* If WhatsNew has been set, register the activation and deactivation hooks */
if (isset($WhatsNew)) {

    register_activation_hook(__FILE__, array(&$WhatsNew, 'install'));
    register_deactivation_hook(__FILE__, array(&$WhatsNew, 'uninstall'));
}
?>