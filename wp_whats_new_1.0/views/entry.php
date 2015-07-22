<?php

	/* 
	 * This view has a "model" attached
	 * The model is a collection of pages in the $pages variable
	 */
?>
<div class="wp-meta-wrapper">
	<div class="wp-meta-field">
		<label for="whats_new_External_Link"><?php _e( 'Link to external page', 'wp_whats_new' ); ?></label>
		<input type="text" name="whats_new_External_Link" id="whats_new_External_Link" value="<?php echo (!empty($custom['whats_new_External_Link'])) ? $custom['whats_new_External_Link'][0] : '';?>"/>
	</div>
	<div class="wp-meta-field">
		<label for="whats_new_Internal_Link"><?php _e( 'Link to internal page','wp_whats_new' ); ?></label>
		<select name="whats_new_Internal_Link" id="whats_new_Internal_Link">
			<option value="0">Select an internal page</option>
			<?php
				foreach($pages as $page) {
                    echo $page->ID . ' - ' . $custom['whats_new_Internal_Link'][0];
					?>
					<option value="<?php echo get_permalink($page->ID); ?>" <?php echo ( !empty($custom['whats_new_Internal_Link'][0]) && ( $custom['whats_new_Internal_Link'][0] === get_permalink($page->ID) ) ) ? 'selected="selected"' : '';?>><?php echo $page->post_title; ?></option>
					<?php
				}
			?>
		</select>
	</div>
    <input type="hidden" name="whats_new_nonce" id="whats_new_nonce" value="<?php echo $nonce; ?>"/>
	<input type="hidden" name="whats_new_Sort" id="whats_new_Sort" value="<?php echo (!empty($custom['whats_new_Sort'])) ? $custom['whats_new_Sort'][0] : '';?>"/>
</div>