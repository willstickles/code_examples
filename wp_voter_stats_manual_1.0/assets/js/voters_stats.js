jQuery(document).ready(function($){

    /* Add items to unordered list for Voter Parties and Vote Counts */

    var list_size = $('#voter_stats_list li').size();
    var id = 'wp_voters_stats_Party';
    var incName = 'wp_voters_stats_Party';

    if(list_size = 1){
        $('.remove').attr('disabled',true);

    }
        $('.add').click(function(e){

            e.preventDefault();

            $('.remove').removeAttr('disabled');

            //$(new_row).appendTo('ul#voter_stats_list');
//            $('.party_row:first').clone(true, true).appendTo('ul#voter_stats_list').find("input").each(function(){
//                $(this).attr('name', this.name + "-" +list_size);
//            });

            $('.party_row:first').clone(true, true).appendTo('ul#voter_stats_list').find("input").each(function(){
                $(this).attr('name', this.name + "-" +list_size);
                $(this).attr('value', "");
            });

            list_size++;

            console.log("Rows Added: "+list_size);

        });

        $('.remove').click(function(e){

            if(list_size <= 2){
                $('.remove').attr('disabled',true);

            }

            e.preventDefault();

            if(list_size > 1){

                $(this).parent().remove();
                list_size--;

                console.log(list_size);

            }

        });

    /* Init Event Datepickers */

    if($('#voters_stats_date').length > 0) {
        $('#voters_stats_date').datepicker({
            dateFormat : 'mm/dd/yy',
            onSelect: function(dateStr) {
                if($('#voters_stats_date').val() === ''){
                    $('#voters_stats_date').val($('voters_stats_Date').val());
                }
            },
            showOn: "button",
            buttonImage: "../wp-content/plugins/wp_voter_stats_manual_1.0/assets/img/calendar.jpg",
            buttonImageOnly: true

        });

        $('.datepicker').datepicker({
            dateFormat : 'mm/dd/yy'
        });
    }

});
