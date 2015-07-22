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

?>

<ul>
    <?php 

    foreach( $posts as $post ) {
        setup_postdata( $post );

        $classOption = ( get_post_meta( $post->ID, 'quick_links_Classes', true ) );
        $classes = ( empty($classOption) || $classOption === '' ) ? '' : $classOption;

        /* First check if we are dealing with an external link */
        $external = get_post_meta($post->ID, 'quick_link_External_Link', true);

        /* If we have an external link, target opens a new window */
        $target = ( !empty($external) || $external != '' ) ? 'target="_blank"' : '';

        /* If we don't have an external link, the link should be an internal page */
        $link = ( !empty($external) ) ? $external : get_post_meta($post->ID, 'quick_link_Internal_Link', true);

        $thumb = get_the_post_thumbnail($post->ID);

        ?>

        <li <?php echo $classes; ?>>
            <a href="<?php echo $link; ?>" <?php echo $target; ?>>
                <?php 
                    if('' !== $thumb) { 
                        echo '<div class="outerContainer">'; 
                        echo $thumb;
                    } 
                ?>
                    <div class="innerContainer">
                        <div class="element">
                            <span><?php echo $post->post_title; ?></span>
                        </div>
                    </div>
                 <?php if('' !== $thumb) { echo '</div>'; } ?>
            </a>
        </li>

        <?php
    }
    wp_reset_postdata();
    ?>
</ul>


