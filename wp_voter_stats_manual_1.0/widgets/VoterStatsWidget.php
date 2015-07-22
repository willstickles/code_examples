<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wstickles
 * Date: 5/16/13
 * Time: 1:10 PM
 * To change this template use File | Settings | File Templates.
 */

class VoterStatsWidget extends WP_Widget{

    public function __construct()
    {
        /* Tap into the parent constructor to set the widget options */
        parent::__construct(
            'voters_stats_widget',
            'Voter Statistics',
            array('description' => __("Displays list of Voter Stats", 'soe_voters_stats'),) // Args
        );
    }

    public function widget($args, $instance)
    {

        global $wpdb;

        $vote_stats = get_option('voters_stats_settings');

        echo $before_widget;

        $vote_total = '';

        echo '<aside class="widget manual-voterstats-widget">';
        echo '<ul>';

            foreach ($vote_stats[0] as $key => $value) {

                switch($key){
                    case (strpos($key, 'voters_stats_title')):
                        $vote_title = $value;
                        echo '<h3 class="widget-title">'.$vote_title.'</h3>';
                        break;
                    case (strpos($key, 'voters_stats_date')):
                        $vote_date = $value;
                        echo '<li class="voter-meta"> Registered Voters as of <span class="voter-date">'.$vote_date . '</span></li>';
                        break;
                    case (strpos($key, 'voters_stats_party')):
                        $vote_party = $value;

                        break;
                    case (strpos($key, 'voters_stats_vote_count')):
                        $vote_count += str_replace(',', '',$value);
                        echo '<li class="voter-data"><b>'.$vote_party . "</b>" . '<span class="voter-count"> '.str_replace(',', '',$value) . "</span></li>";
                        $vote_total += $vote_count;
                        break;
                    case(strpos($key, 'display_total_label')):
                        $display = $value;
                        break;
                }
            }

            if($display == 'on'){
                echo '<li class="voter-data"><b>Total </b>'. $vote_count."</li>";
            }
        echo '</ul>';
        echo '</aside>';
        echo $after_widget;

    }

}