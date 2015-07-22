<?php

	/* 
	 * This view has a "model" attached
	 * The model is a collection of pages in the $pages variable
	 */
?>

<div class="wp-meta-wrapper">

    <div class="wp-meta-field">
        <label for="wp_voter_stats_Date"><?php _e( 'Date', 'wp_voter_stats' ); ?></label>
        <input type="text" name="wp_voter_stats_Date" id="wp_voter_stats_Date" value="<?php echo (!empty($custom['wp_voter_stats_Date'])) ? $custom['wp_voter_stats_Date'][0] : '';?>"/>

    </div>

    <ul id="voter_stats_list">
        <li class="party_row">
            <div class="wp-meta-field">
                <label for="wp_voter_stats_Party"><?php _e( 'Party', 'wp_voter_Party' ); ?></label>
                <input type="text" name="wp_voter_stats_Party" id="wp_voter_stats_Party" value="<?php echo (!empty($custom['wp_voter_stats_Party'])) ? $custom['wp_voter_stats_Party'][0] : '';?>"/>
            </div>
            <div class="wp-meta-field">
                <label for="wp_voter_stats_Vote_Count"><?php _e( 'Vote Count','wp_voter_stats' ); ?></label>
                <input type="text" name="wp_voter_stats_Vote_Count" id="wp_voter_stats_Vote_Count" value="<?php echo (!empty($custom['wp_voter_stats_Vote_Count'])) ? $custom['wp_voter_stats_Vote_Count'][0] : ''; ?>" />

            </div>
                <a href="" id="remove">remove</a>
        </li>
    </ul>
    <div class="wp-meta-field">
        <a href="" id="add">Add</a>
    </div>

    <div class="wp-meta-field">
        <label for="wp_voter_stats_total"><?php _e('Total', 'wp_voter_stats');?></label>
        <input type="text" name="wp_voter_stats_Count_Total" id="wp_voter_stats_Count_Total" value="<?php echo (!empty($custom['wp_voter_stats_Count_Total'])) ? $custom['wp_voter_stats_Count_Total'][0] : ''; ?>" />

    </div>


    <input type="hidden" name="wp_voter_stats-nonce" id="wp_voter_stats-nonce" value="<?php echo $nonce; ?>"/>
	<input type="hidden" name="wp_voter_stats_Sort" id="wp_voter_stats_Sort" value="<?php echo (!empty($custom['wp_voter_stats_Sort'])) ? $custom['wp_voter_stats_Sort'][0] : '';?>"/>
</div>