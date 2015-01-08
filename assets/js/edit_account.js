$(function(){
    $(document).on('blur', '#account-edit', function(){
        var number = $(this).val();
        $.post('//' + location.host + '/mobileverify', {'number': number}, function(data){
            var data = $.parseJSON(data);
            if (data.status == 'send_verify') {
                $('#account-edit #popup-confirm').popup("open");
                $('#account-edit #popup-confirm').find('.ui-title').eq(1).html(
                    ($('#account-edit #popup-confirm').find('.ui-title').eq(1).html()).replace('{phone_number}', number)
                );

                $('#account-edit #popup-confirm').find('a[data-go]').unbind('click');
                $('#account-edit #popup-confirm').find('a[data-go]').bind('click',function(){
                    if ($(this).data('go') == 'yes') {
                        $.ajax({
                            url: '//' + location.host + '/mobileverify_send/',
                            type: "POST",
                            data: {'number': number},
                            success: function(result)
                            {
                                $.mobile.hidePageLoadingMsg();
                                result = $.parseJSON(result);
                                /*if (result.redirect) {
                                    window.location.href = result.redirect;
                                } else {
                                    window.location.reload();
                                }*/
                            },
                            error: function(e){
                                $.mobile.hidePageLoadingMsg();
                                console.error(e);
                            }
                        });
                    }
                });
            }
        });
    });

    /** By default country is Australia - get states */
    if ($.mobile.activePage.attr("id") == 'account-edit') {
        var el =  $('#account-edit #form_country').val();
        var request = $.ajax({
            url: '//' + location.host + '/states',
            type: "POST",
            data: { id : el },
            dataType: "json"
        });

        request.done(function( data ) {
            if(data.valid){
                var state = data.states;
                if($('#account-edit #div-state').hasClass('hide')){
                    $('#account-edit #div-state').removeClass('hide');
                }
                $(state).each(function(){
                    var new_state = $('<option value="' + this.id + '">' + this.state_name + ' (' + this.code + ')</option>');
                    $('#account-edit #form_state').append(new_state);
                });

                $('#account-edit #form_state').rules('add', {
                    required:true
                });
            } else {

                if(!$('#account-edit #div-state').hasClass('hide')){
                    $('#account-edit #div-state').addClass('hide');
                }
                if(!$('#account-edit #div-city').hasClass('hide')){
                    $('#account-edit #div-city').addClass('hide');
                }
                alert('This Country has not entered states.')

            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
        /** When choose country get states*/
    }

    $(document).on('change', '#form_country', function(){
        $('#account-edit #form_state').html('');
        $('#account-edit #form_city').html('');
        var el = $(this).val();
        var request = $.ajax({
            url: '//' + location.host + '/states',
            type: "POST",
            data: { id : el },
            dataType: "json"
        });

        request.done(function( data ) {
            if(data.valid){

                var state = data.states;
                if($('#account-edit #div-state').hasClass('hide')){
                    $('#account-edit #div-state').removeClass('hide');
                }
                var new_state = $('<option value="">Choose State</option>');
                $('#account-edit #form_state').append(new_state);
                $(state).each(function(){
                    var new_state = $('<option value="' + this.id + '">' + this.state_name + ' (' + this.code + ')</option>');
                    $('#account-edit #form_state').append(new_state);
                });

            } else {

                if(!$('#account-edit #div-state').hasClass('hide')){
                    $('#account-edit #div-state').addClass('hide');
                }
                if(!$('#account-edit #div-city').hasClass('hide')){
                    $('#account-edit #div-city').addClass('hide');
                }
                alert('This Country has not entered states.')

            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });


    /** When choose state get cities */
    $(document).on('change', '#form_state', function(){
        var el = $(this).val();
        var request = $.ajax({
            url: '//' + location.host + '/cities',
            type: "POST",
            data: { id : el },
            dataType: "json"
        });

        request.done(function( data ) {
            if(data.valid){
                $('#account-edit #form_city').html('');
                var cities = data.cities;
                if($('#account-edit #div-city').hasClass('hide')){
                    $('#account-edit #div-city').removeClass('hide');
                }
                $(cities).each(function(){
                    var new_city = $('<option value="' + this.city_id + '">' + this.city_name + '</option>');
                    $('#account-edit #form_city').append(new_city);
                });
                $('#account-edit #form_city').rules('add', {
                    required:true
                });


            } else {
                if(!$('#account-edit #div-city').hasClass('hide')){
                    $('#account-edit #div-city').addClass('hide');
                }
                alert('This State has not entered cities.')

            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });

    });


    $('#account-edit').find('#register_form').validate({
        rules: {
            first_name: "required",
            last_name: "required",
            email: {
                required: true,
                email: true,
                remote: '//' + location.host + '/check_email_edit'
            },
            mobile:{
                required: true,
                number: true
            },
            address:{
                required: true
            },
            country_id:{
                required: true
            },
            state:{
                required: true
            },
            city:{
                required: true
            }

        },messages:{
            email: {
                remote: jQuery.format("{0} is already in use!")
            }
        }
    });
    $(document).on('click', '#register-btn', function(){
        if( $('#account-edit #register_form').valid() ){
            var request = $.ajax({
                url: '//' + location.host + '/save',
                type: "POST",
                data: $('#account-edit').find('#register_form').serialize(),
                dataType: "json"
            });

            request.done(function( data ) {
                if(!data.valid){
                    $('#account-edit #error_note').html('');
                    $('#account-edit #error_note').append(data.errors);
                } else {
                    window.location.href = '//' + location.host + '/menu';
                }
            });

            request.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        }
    });
});