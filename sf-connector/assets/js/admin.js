jQuery( function($) {
    // set your delay here, 2 seconds as an example...
    var my_delay = 2000;
    $( ".datepicker" ).datepicker();

    $('#submit').on('click',function(){
        $('.display').html();
        $('.writefile').html();
        var startdate = $('#startdate').val();
        var enddate = $('#enddate').val();

        get_orders(startdate,enddate);

    });

    function get_orders(startdate, enddate){

        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: "json",
            data: {
                action: 'get_records_to_update',
                startdate: startdate,
                enddate: enddate

            },
            beforeSend: function(){
                $('.display').html('Getting Records for ' + startdate + ' through ' + enddate);
            },
            success: function( results ){
                console.log(results);
                var order_count = results.length;
                var count = 0;
                console.log('Order count for ' + startdate + ' through ' + enddate + " = " + order_count);

                $.each( results, function( index, value ) {
                    console.log(value);
                    update_salesforce_record( value );
                    count ++;
                });
            }
        }).done( function(){
            $('.display').html('Finished');
        });
    }

    function update_salesforce_record( record ){
        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: "json",
            data: {
                action: 'update_salesforce_orders',
                order_id: record
            }
        });
    }

} );

