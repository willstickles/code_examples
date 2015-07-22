<?php

    /*-------------------------------------------------------------------------------------------
		Horizontal Quicklink Output
    -------------------------------------------------------------------------------------------*/

    function QuickLinkOutput($output_type = 'horizontal'){

    	if($output_type == 'horizontal'){

	    	$output='';

			$args = array(
				'post_type' => 'quick_link',
				'post_status' => 'publish',
				'meta_key' => 'quick_link_Sort',
                'orderby' => 'meta_value_num',
                'order' => 'ASC'
				);

			$quick_links = new WP_Query( $args );

			if( $quick_links->have_posts() ){

				$output .= '<nav role="navigation" id="quicklinks">';
				$output .= '<ul>';

				while( $quick_links->have_posts() ){

					$quick_links->the_post();

					if(get_post_meta(get_the_id(), 'quick_links_Classes', true)){
						$classes = ' class="' . get_post_meta(get_the_id(), 'quick_links_Classes', true) . '" ';
					}else{
						$classes = '';
					}

					if(get_post_meta(get_the_id(), 'quick_link_Internal_Link', true)){
						$link = ' href="' . get_post_meta(get_the_id(), 'quick_link_Internal_Link', true) . '"';
					}else if(get_post_meta(get_the_id(), 'quick_link_External_Link', true)){
						$link = ' href="' . get_post_meta(get_the_id(), 'quick_link_External_Link', true) . '" target="_blank"';
					}else{
						$link = '';
					}

					$output .= '<li' . $classes . '>';
					$output .= '<a' . $link . '>';
					$output .= get_the_post_thumbnail();
					$output .= '<div class="ql-title-wrapper"><span class="ql-title">' . get_the_title() . '</span></div>';
					$output .= '</a>';
					$output .= '</li>';

				}

				$output .= '</ul>';
				$output .= '</nav>';

			}

			else{

				$output .= 'No quick links defined.';

			}

			echo $output;

		}

	}



?>
