<?php

	/* 
	 * This view has a "model" attached
	 * The model is a collection of pages in the $pages variable
	 */
?>
<div class="wp-meta-wrapper">
	<div class="wp-meta-field">
		<label for="quick_link_External_Link"><?php _e( 'Link to external page', 'wp_quick_link' ); ?></label>
		<input type="text" name="quick_link_External_Link" id="quick_link_External_Link" value="<?php echo (!empty($custom['quick_link_External_Link'])) ? $custom['quick_link_External_Link'][0] : '';?>"/>
	</div>
	<div class="wp-meta-field">
		<label for="quick_link_Internal_Link"><?php _e( 'Link to internal page','wp_quick_link' ); ?></label>
		<select name="quick_link_Internal_Link" id="quick_link_Internal_Link">
			<option value="0">Select an internal page</option>
			<?php
				foreach($pages as $page) {
                    echo $page->ID . ' - ' . $custom['quick_link_Internal_Link'][0];
					?>
					<option value="<?php echo get_permalink($page->ID); ?>" <?php echo ( !empty($custom['quick_link_Internal_Link'][0]) && ( $custom['quick_link_Internal_Link'][0] === get_permalink($page->ID) ) ) ? 'selected="selected"' : '';?>><?php echo $page->post_title; ?></option>
					<?php
				}
			?>
		</select>
	</div>
    <input type="hidden" name="quick_link_nonce" id="quick_link_nonce" value="<?php echo $nonce; ?>"/>
	<input type="hidden" name="quick_link_Sort" id="quick_link_Sort" value="<?php echo (!empty($custom['quick_link_Sort'])) ? $custom['quick_link_Sort'][0] : '';?>"/>
</div>