jQuery(document).ready(function($){

    /* External/Internal link validation */
    $('#whats_new_External_Link').blur(function(){
        if($(this).val() !== '') {
            if($('#whats_new_Internal_Link').val() !== '0'){
                if(confirm('You have an internal page selected. Clicking ok will remove the internal page link and use this link')){
                    $('#whats_new_Internal_Link').val(0);

                }
            }

            if( $(this).val().substring(0, 7) !== 'http://' ) {
                $(this).val('http://' + $(this).val() );
            }
        }
    });

    $('#whats_new_Internal_Link').change(function(){
        if($(this).val() !== '0' && $('#whats_new_External_Link').val() !== '') {
            if(confirm('You have an external link set. Clicking ok will remove the external link and use this page.')){
                $('#whats_new_External_Link').val('');
            }
        }
    });

    $('form#post').submit(function(e){

        if($('#whats_new_External_Link').val() === '' && $('#whats_new_Internal_Link').val() === '0'){
            alert('Please enter an external link or select an internal link');
            $('#publish').removeClass('button-primary-disabled');
            $('.spinner').hide();
            return false;
        }

    });

    /* Table sorting */
	if( $('.wp-list-table th.column-sort').length > 0 ) {

		$('.wp-list-table tbody').sortable({
			containment:'parent',
			cursor:'move',
			helper:function(event,ui){
				var helper = [];
		        helper.push('<table class="sortable-helper">');
		        helper.push($(ui).html());
		        helper.push('</table>');
		        return helper.join('');
			},
			opacity:1,
			stop: function(event, ui){
                var key;
                var rows = {};
                var range = [];

                /* Cache the needed dom elements */
                var tableRows = $('#the-list tr');

                /* Get the sort range to account for paging */
                $(tableRows).each(function (index, row) {
                   range.push( $(this).find('td.column-sort').html() );
                });

                /* Sort the range */
                range.sort(function(a,b){ return (a - b) });

				/* Get the list of table rows and re sort them */
				$(tableRows).each(function(index, row){
                    rows[ $(this).attr('id')] = ( range[index] );
				});

				/* Send the sorted rows to the server */
				$.ajax({
					data: "&rows=" + JSON.stringify(rows) + "&action=sort_whats_new&whats-new-nonce=" + whats_new_nonce.Nonce,
					url: ajaxurl,
					type:'POST',
					statusCode: {
						200: function() {
                            for (key in rows) {
							   if(rows.hasOwnProperty(key)) {
							   		$('#' + key + ' td.column-sort').html(rows[key]);
							   }
							}
                            /* Remove zebra stripes and reapply them */
                            $(tableRows).each(function(index, row){
                                ( index % 2 ) ? $(row).addClass('alternate') : $(row).removeClass('alternate');
                            });
						},
						500 : function(xhr) {
							alert( 'Sorting error : ' + xhr.responseText);
						}
					}
				});


				/* Send the sorted rows to the server */
				$.ajax({
					data: "&rows=" + JSON.stringify(rows) + "&action=wp_example_sort&wp-example-nonce=" + wp_example_nonce.Nonce,
					url: ajaxurl,
					type:'POST',
					statusCode: {
						200: function() {
                            for (key in rows) {
							   if(rows.hasOwnProperty(key)) {
							   		$('#' + key + ' td.column-sort').html(rows[key]);
							   }
							}
                            /* Remove zebra stripes and reapply them */
                            $(tableRows).each(function(index, row){
                                ( index % 2 ) ? $(row).addClass('alternate') : $(row).removeClass('alternate');
                            });
						},
						500 : function(xhr) {
							alert( 'Sorting error : ' + xhr.responseText);
						}
					}
				});
			},
			zindex:9999
		});

	}



    /* Init Event Datepickers */

    if($('#event_Start_Date').length > 0) {
        $('#event_Start_Date').datepicker({
            dateFormat : 'mm/dd/yy',
            onSelect: function(dateStr) {
                if($('#event_End_Date').val() === ''){
                    $('#event_End_Date').val($('#event_Start_Date').val());
                }
            }
        });

        $('#event-meta .datepicker').datepicker({
            dateFormat : 'mm/dd/yy'
        });
    }


});