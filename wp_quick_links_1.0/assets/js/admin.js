jQuery(document).ready(function($){

    /* External/Internal link validation */
    $('#quick_link_External_Link').blur(function(){
        if($(this).val() !== '') {
            if($('#quick_link_Internal_Link').val() !== '0'){
                if(confirm('You have an internal page selected. Clicking ok will remove the internal page link and use this link')){
                    $('#quick_link_Internal_Link').val(0);
                }
            }

            if( $(this).val().substring(0, 7) !== 'http://' ) {
                $(this).val('http://' + $(this).val() );
            }
        }
    });

    $('#quick_link_Internal_Link').change(function(){
        if($(this).val() !== '0' && $('#quick_link_External_Link').val() !== '') {
            if(confirm('You have an external link set. Clicking ok will remove the external link and use this page.')){
                $('#quick_link_External_Link').val('');
            }
            else{
                $(this).val(0);
            }
        }
    });

    $('form#post').submit(function(e){

        if($('#quick_link_External_Link').val() === '' && $('#quick_link_Internal_Link').val() === '0'){
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
					data: "&rows=" + JSON.stringify(rows) + "&action=sort_quick_link&quick_link_nonce=" + quick_link_nonce.Nonce,
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

});