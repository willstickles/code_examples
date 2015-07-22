<?php

/* 
 * Description:
 * This view accepts a collection of posts and renders an unordered list
 * of post titles. Each post should link to either an internal page or 
 * external site link.
 * To access the posts via the WordPress api's you will need to call
 * setup_postdata in the loop
 *
 * Flow:
 * Take the posts collection and loop through them.
 * If the link is to an external site, then target should be blank to open
 * the link in a new window. If it is to an interal page within the site
 * target should be empty
 * 
 */

    $counter = 0;
    $icon = "";
    $items = "";
    $link_text = "";

    foreach($posts as $post) {
        setup_postdata($post);

        /* First check if we are dealing with an external link */
        $external = get_post_meta($post->ID, 'social_link_External_Link', true);

        /* If we have an external link, target opens a new window */
        $target = ( !empty($external) || $external != '' ) ? 'target="_blank"' : '';

        /* If we don't have an external link, the link should be an internal page */
        $link = ( !empty($external) ) ? $external : get_post_meta($post->ID, 'social_link_Internal_Link', true);

        $icon = get_the_post_thumbnail( $post->ID );
        $link_text = ( empty($icon) ) ? $post->post_title : $icon;

        $items .= "<li>";
        $items .= ( !empty($link) ) ? "<a href=\"$link\" title=\"$post->post_title\" $target>". $link_text ."</a>" : $post->post_title; 
        $items .= "</li>";

        $counter++;
    }

    wp_reset_postdata();

?>

<ul class="social-links item-count-<?php echo $counter; ?>">
    <?php echo $items; ?>
</ul>



