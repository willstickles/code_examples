<?php

	/* 
	 * This view has a "model" attached
	 * The model is a collection of pages in the $pages variable
	 */
?>
<div class="wp-meta-wrapper">
	<div class="wp-meta-field">
		<label for="social_link_External_Link"><?php _e( 'Link to external page', 'wp_social_links' ); ?></label>
		<input type="text" name="social_link_External_Link" id="social_link_External_Link" value="<?php echo (!empty($custom['social_link_External_Link'])) ? $custom['social_link_External_Link'][0] : '';?>"/>
	</div>
	<div class="wp-meta-field">
		<label for="social_link_Internal_Link"><?php _e( 'Link to internal page','wp_social_links' ); ?></label>
		<select name="social_link_Internal_Link" id="social_link_Internal_Link">
			<option value="0">Select an internal page</option>
			<?php
				foreach($pages as $page) {
                    echo $page->ID . ' - ' . $custom['social_link_Internal_Link'][0];
					?>
					<option value="<?php echo get_permalink($page->ID); ?>" <?php echo ( !empty($custom['social_link_Internal_Link'][0]) && ( $custom['social_link_Internal_Link'][0] === get_permalink($page->ID) ) ) ? 'selected="selected"' : '';?>><?php echo $page->post_title; ?></option>
					<?php
				}
			?>
		</select>
	</div>
    <input type="hidden" name="social_link_nonce" id="social_link_nonce" value="<?php echo $nonce; ?>"/>
	<input type="hidden" name="social_link_Sort" id="social_link_Sort" value="<?php echo (!empty($custom['social_link_Sort'])) ? $custom['social_link_Sort'][0] : '';?>"/>
</div>