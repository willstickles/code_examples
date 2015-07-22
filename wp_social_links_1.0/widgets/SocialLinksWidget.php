<?php

/*
 * Class:
 * Social Media Links Widget
 * 
 * Description:
 * This class is a WordPress widget that displays a listing of Social Media Links posts on the public pages
 * based on arguments defined in the WordPress admin.
 * 
 */

class SocialLinksWidget extends WP_Widget
{

    public function __construct()
    {
        /* Tap into the parent constructor to set the widget options */
        parent::__construct(
            'social_links',
            'Social Media Links',
            array( 'description' => __( "Displays list of social media links", 'wp_social_links' ), ) // Args
        );
    }

    /* This function displays the widget output on the public pages */
    public function widget( $args, $instance ) {
        
        extract( $args );
        $title = apply_filters( 'widget_title', $instance['title'] );
        $count = apply_filters( 'widget_count', $instance['item_count'] );

        echo $before_widget;

        if ( ! empty( $title ) ) {
            echo $before_title . $title . $after_title;
        }

        $args = array(
            'post_type' => 'social_link',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'meta_key' => 'social_link_Sort',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        );

        $query = new WP_Query( $args );

         if($query->have_posts()) {

            $posts = $query->posts;

            include( plugin_dir_path(__FILE__) . '../views/social_links.widget.php' );

        }
        else {
            printf( __( 'There are no new items', 'wp_social_links' ) );
        }

        echo $after_widget;
    }

    /* This is the function that saves any admin setting changes */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['item_count'] = strip_tags( $new_instance['item_count'] );

        return $instance;
    }

    /* This function renders the widget admin form */
    public function form( $instance ) {

        $title;
        $defaults = array( 'title' => 'Example', 'item_count' => '5');
        $instance = wp_parse_args( (array) $instance, $defaults );
        $widget = $this;
        
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New Title', 'wp_social_links' );
        }

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp_social_links' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'item_count' ); ?>"><?php _e( 'Number of items to display:', 'wp_social_links'); ?></label>
            <select id="<?php echo $this->get_field_id( 'item_count' ); ?>" name="<?php echo $this->get_field_name( 'item_count' ); ?>" class="widefat">
                <?php
                $numOfOptions = '10';
                for ($i = 1; $i <= $numOfOptions; $i++) {
                    ?>
                    <option value="<?php echo $i ?>" <?php echo ( $instance['item_count'] == $i ) ? 'selected="selected"' : '' ; ?>><?php echo $i ?></option>
                <?php
                } ?>
            </select>
        </p>
        <?php
    }

}

?>