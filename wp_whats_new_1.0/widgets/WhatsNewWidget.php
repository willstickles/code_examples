<?php

/*
 * Class:
 * Whats New Widget
 * 
 * Description:
 * This class is a WordPress widget that displays a listing of "What's New" posts on the public pages
 * based on arguments defined in the WordPress admin.
 * 
 */

class WhatsNewWidget extends WP_Widget
{

    public function __construct()
    {
        /* Tap into the parent constructor to set the widget options */
        parent::__construct(
            'whats_new',
            'Whats New',
            array( 'description' => __( "Displays list of what's new items", 'soe_whats_new' ), ) // Args
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

        /* Set up the query to sort on the meta key whats_new_Sort and tell
         * it to treat the sort value as a number (meta_value_num)
         */
        $args = array(
                'post_type' => 'whats_new',
                'post_status' => 'publish',
                'posts_per_page' => $count,
                'meta_key' => 'whats_new_Sort',
                'orderby' => 'meta_value_num',
                'order' => 'ASC');

        $query = new WP_Query( $args );

        /* If there are posts, set a $posts collection and include the widget view */
        if($query->have_posts()) {

            $posts = $query->posts;

            include_once( plugin_dir_path(__FILE__) . '../views/whats_new.widget.php' );

        }
        else {
            printf( __( 'There are no new items', 'soe_whats_new' ) );
        }

        wp_reset_postdata();

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
            $title = __( 'New Title', 'soe_whats_new' );
        }

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'soe_whats_new' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'item_count' ); ?>"><?php _e( 'Number of items to display:', 'soe_whats_new'); ?></label>
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