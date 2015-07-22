jQuery( function($) {

    var order_id = $('#order_id').val();

    send_order_to_salesforce( order_id );

});

function send_order_to_salesforce( order_id ){

    jQuery.ajax({
        url: ajaxurl,
        type: 'post',
        dataType: 'json',
        data: {
            action: 'create_salesforce_order',
            order_id: order_id
        },
        success: function(){
            console.log("SUCCESS");
        }

    })

}