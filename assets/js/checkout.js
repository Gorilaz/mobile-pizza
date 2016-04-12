var base_url = '{{ base_url }}';
$(function(){

    /** discount  */

    $('#checkout-page .discount').change(function(){
        var el = $(this).data('btn');
        if (el == 'voucher'){
            $("#checkout-page #voucher").prop('disabled', false);
        } else {
            $('#checkout-page #voucher').val('');
            $("#checkout-page #voucher").prop('disabled', true);
        }
    });

    $("#checkout-page #voucher").prop('disabled', true);
    /**  end discount  */

    function save_preordered(){
        var request = $.ajax({
            url: '//' + location.host + '/save_preordered_account',
            type: "POST",
            data: $('#checkout-page #acount-form').serialize(),
            dataType: "json"
        });

        request.done(function( data ) {
            if(data.valid == false){
                $('#error_note').html(data.errors);
            } else {
                var request = $.ajax({
                    url: '//' + location.host + '/save_preordered',
                    type: "POST",
                    data: $('#checkout-page #form-order').serialize(),
                    dataType: "json"
                });

                request.done(function( data) {

                });

                request.fail(function( jqXHR, textStatus ) {
                    showAlert( "", "Request failed: " + textStatus );
                });
            }

        });

        request.fail(function( jqXHR, textStatus ) {
            showAlert( "", "Request failed: " + textStatus );
        });

    }

    $('#checkout-page .choose-later').click(function(){
        $('#checkout-page #later').removeClass('hide');
    });
    $('#checkout-page .choose-asap').click(function(){
        $('#checkout-page #later').addClass('hide');
    });

    $(document).on('click','#checkout',function(){

        if(($('#checkout-page .choose-later').is(':checked') && (!$('#checkout-page #order_placement').val()))){
            showAlert( "", "Enter order placement date." );
        } else if (($('#checkout-page .choose-later').is(':checked') && ($('#checkout-page #order_placement').val()))) {
            var date_default = $('#checkout-page #order_placement').val();
            var d = new Date();
            var date = new Date(date);
            if(date-d > 0){
                showAlert( "", "Delivery date must be greater than the date now" );
            } else {
                if($('#checkout-page .choose-delivery').is(':checked')){
                    var delivery = 'D';
                } else {
                    var delivery = 'P';
                }
                var request = $.ajax({
                    url: '//' + location.host + '/check-date',
                    type: "POST",
                    data: { date : date_default, delivery : delivery },
                    dataType: "json"
                });

                request.done(function( data ) {
                    if(data.valid){

                        var request = $.ajax({
                            url: '//' + location.host + '/save_preordered_account',
                            type: "POST",
                            data: $('#checkout-page #acount-form').serialize(),
                            dataType: "json"
                        });

                        request.done(function( data ) {
                            if(data.valid == false){
                                $('#error_note').html(data.errors);
                            } else {
                                var request = $.ajax({
                                    url: '//' + location.host + '/save_preordered',
                                    type: "POST",
                                    data: $('#checkout-page #form-order').serialize(),
                                    dataType: "json"
                                });

                                request.done(function( data) {
                                    if(data.valid){
                                        if($('.choose-credit').is(':checked')){
                                            window.location.href = '//' + location.host + '/payment';
                                        } else {
                                            showAlert( "", "is not checked" );
                                        }
                                    }
                                });

                                request.fail(function( jqXHR, textStatus ) {
                                    showAlert( "", "Request failed: " + textStatus );
                                });
                            }

                        });

                        request.fail(function( jqXHR, textStatus ) {
                            showAlert( "", "Request failed: " + textStatus );
                        });

                    } else {
                        showAlert( "", "The time you are selected is out our schedule" );
                    }
                });

                request.fail(function( jqXHR, textStatus ) {
                    showAlert( "", "Request failed: " + textStatus );
                });
            }

        } else {
            var request = $.ajax({
                url: '//' + location.host + '/save_preordered_account',
                type: "POST",
                data: $('#checkout-page #acount-form').serialize(),
                dataType: "json"
            });

            request.done(function( data ) {
                if(data.valid == false){
                    $('#error_note').html(data.errors);
                } else {
                    var request = $.ajax({
                        url: '//' + location.host + '/save_preordered',
                        type: "POST",
                        data: $('#checkout-page #form-order').serialize(),
                        dataType: "json"
                    });

                    request.done(function( data) {
                        if(data.valid){
                            if($('#checkout-page .choose-credit').is(':checked')){
                                window.location.href = '//' + location.host + '/payment';
                            } else {
                                window.location.href = '//' + location.host + '/save-order';
                            }
                        }
                    });

                    request.fail(function( jqXHR, textStatus ) {
                        showAlert( "", "Request failed: " + textStatus );
                    });
                }

            });

            request.fail(function( jqXHR, textStatus ) {
                showAlert( "", "Request failed: " + textStatus );
            });

        }

    });
})