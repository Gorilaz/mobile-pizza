/*
 * 
$(function(){
    $('#register_form1').validate({
        rules: {
            // simple rule, converted to {required:true}
            first_name: "required",
            last_name: "required",
            address: "required",
            email: {
                required: true,
                email: true,
                remote: '//' + location.host + '/security/checkUniqueEmail'
            },
            password: {
                required: true,
                minlength: 5
            },
            conf_password: {
                required: true,
                equalTo: "#form_password",
                minlength: 5
            },
            suburb: "required",
            state: "required",
            mobile: {
                required: true
            }
        }
    });


    $('#save').click(function(){

        var old_m = $('#old_mobile1').val();
        var m = $('#form_mobile1').val();
        if( old_m != m ){
            alert('yes');
        } else {
            alert('no');
        }

        if($('#register_form1').valid()){
            var request = $.ajax({
                url: '//' + location.host + '/security/save',
                type: "POST",
                data: $('#register_form1').serialize(),
                dataType: "json"
            });

            request.done(function( data ) {
                window.location.href = '//' + location.host + '/security-edit'
            });

            request.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        }
    });




});

*/