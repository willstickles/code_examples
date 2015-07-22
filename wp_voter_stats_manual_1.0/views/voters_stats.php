<?php screen_icon('themes'); ?><h2>Voter Stats Options</h2>
<?php echo $save_success; ?>
<form method="post" action="">
    <div>
        <label for="title"><?php _e('Title', 'voters_stats'); ?></label>
        <input type="text" name="voters_stats_title" id="voters_stats_title" />
    </div>

    <div>
        <label for="voters_stats_date"><?php _e('Date', 'voters_stats'); ?></label>
        <input type="text" name="voters_stats_date" id="voters_stats_date" class="datepicker" value="" />
    </div>

    <div>
        <ul id="voter_stats_list">
            <li class="party_row" id="voter_stats_row">

                <label for="wp_voters_stats_Party"><?php _e('Party', 'wp_voters_Party'); ?></label>
                <input type="text" name="voters_stats_party" id="voters_stats_party" value="" />

                <label for="wp_voters_stats_vote_count"><?php _e('Vote Count', 'wp_voters_stats'); ?></label>
                <input type="text" name="voters_stats_vote_count" id="voters_stats_vote_count" value="" />

                <a href="" id="remove" class="button-primary remove">remove</a>
            </li>
        </ul>
    </div>

    <div>
        <a href="" id="add" class="button-primary add">add party</a>
    </div>
    <hr>
    <h3>Total Display</h3>

    <div>
        <label for="wp_voters_stats_total"><?php _e('Total Label', 'wp_voters_stats'); ?></label>
        <input type="text" name="wp_voters_stats_Count_Total" id="wp_voters_stats_Count_Total"
               value="<?php echo (!empty($custom['wp_voters_stats_Count_Total'])) ? $custom['wp_voters_stats_Count_Total'][0] : ''; ?>"/>
        <input type="checkbox" name="display_total_label" id="display_total_label"/> <label for="display_total_label">
            Display</label>
    </div>
    <input type="hidden" name="update_settings" value="Y" />
    <div>
        <p><?php submit_button(); ?></p>
    </div>

</form>
