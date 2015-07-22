<?php

class QuickLinksWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'quick_links',
            'Quick Links',
            array( 'description' => __( "Displays list of quick links", 'wp_quick_links' ), ) // Args
        );
    }

    public function widget( $args, $instance ) {
        extract( $args );
        $title = apply_filters( 'widget_title', $instance['title'] );
        $count = apply_filters( 'widget_count', $instance['item_count'] );
        $view = '';
        $viewTemplate = '';
        $viewOutput = '';
        $viewTokens = array('{{link}}', '{{target}}', '{{title}}');
        $viewValues;

        echo $before_widget;

        if ( ! empty( $title ) ) {
            echo $before_title . $title . $after_title;
        }

        /* Add code to display what's new items */

        $args = array(
                'post_type' => 'quick_link',
                'post_status' => 'publish',
                'meta_key' => 'quick_link_Sort',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
                'posts_per_page' => $count
                );

        $query = new WP_Query($args);

        if( $query->have_posts() ){

            $posts = $query->posts;

            include( plugin_dir_path(__FILE__) . '../views/quick_link.widget.php' );

        } else {
            printf(__('There are no quick links', 'wp_quick_link'));
        }

        echo $after_widget;

    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['item_count'] = strip_tags( $new_instance['item_count'] );

        return $instance;
    }

    public function form( $instance ) {
        $defaults = array( 'title' => 'Example', 'item_count' => '5');
        $instance = wp_parse_args( (array) $instance, $defaults );

        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New Title', 'wp_quick_link' );
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp_quick_link' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'item_count' ); ?>"><?php _e( 'Number of items to display:', 'wp_quick_link'); ?></label>
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

    private function get_sub_template($view, $opening, $closing)
    {
        $start = strpos($view, $opening) + strlen($closing);
        $end = strpos($view, $closing, $start);
        $sub_template = substr($view, $start, $end - $start);

        return $sub_template;
    }

    private function remove_sub_template($view, $output, $opening, $closing)
    {
        $start = strpos($view, $opening) + strlen($opening);
        $end = strpos($view, $closing, $start);
        $sub_template = substr($view, $start, $end - $start);
        
        $view = str_replace($opening, '', $view);
        $view = str_replace($closing, '', $view);
        $view = str_replace($sub_template, $output, $view);

        return $view;
    }

}

?>