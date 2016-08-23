$(document).ready(function() {    $.ajaxSetup({ cache: true });    $.getScript('//connect.facebook.net/en_UK/all.js', function(){        FB.init({            appId: '501653813264138',            channelUrl: '//' + location.host + 'facebook_channel'        });        //FB.getLoginStatus(checkLoginStatus);    });});function authFBUser() {    FB.login(checkLoginStatus, {scope:'email'});}jQuery.fn.simpleValidate = function() {    var valid = true;    this.find('input').each(function(){        if (($(this).val()).length == 0) {            $(this).parent().css({'border': '1px solid red'});            valid = false;        } else {            $(this).parent().css({'border': '1px solid #AAAAAA'});        }    });    return valid;}function recoverPassword() {    if ($('#account-register #recover_form').simpleValidate()) {        $.ajax({            url: '//' + location.host + '/account/recover_password',            data: $('#recover_form').serialize(),            type: "POST",            success: function(result) {                result = $.parseJSON(result);                if (result.status == 'fail') {                    $('#erro_note').html(result.error);                } else {                    $('#form_email').val('');                    $("#alertSuccess").popup("open");                }            },            error: function(e){                console.error(e);            }        });    }}function changeRecoverPassword() {    if ($('#account-register #change_password_form').simpleValidate()) {        $.ajax({            url: '//' + location.host + '/account/change_recover_password',            data: $('#change_password_form').serialize(),            type: "POST",            success: function(result) {                result = $.parseJSON(result);                if (result.status == 'fail') {                    $('#erro_note').html(result.error);                } else {                    $('#erro_note').html('');                    $('#form_password').val('');                    $('#form_repassword').val('');                    $("#alertSuccess").popup("open");                    setTimeout(function(){                        window.location.href = '//' + location.host + '/account/login';                    }, 5000);                }            },            error: function(e){                console.error(e);            }        });    }}function authNormalUser() {    if ($('#account-register #login_form').simpleValidate()) {        $.ajax({            url: '//' + location.host + '/user_login',            data: $('#login_form').serialize(),            type: "POST",            success: function(result) {                result = $.parseJSON(result);                if (result.status == 'error') {                    $('#acount-page #form_password').val('');                    $('#acount-page #erro_note').html(result.error);                } else {                    var order = $('#acount-page #order').data('order');                    var checkout = $('#acount-page #checkout').data('order');                    if(order == 'yes'){//                        window.location.href = '//' + location.host + '/payment';                        $.mobile.changePage( '//' + location.host + '/payment', { transition: 'slide' });                    } else if(checkout == 'yes'){                        $.mobile.changePage( '//' + location.host + '/cart/popup', { transition: 'slide' });//                        window.location.href = '//' + location.host + '/checkout';                    }else {//                        $.mobile.changePage( '//' + location.host + '/menu/logged', { transition: 'slide' });                        window.location.href = '//' + location.host + '/menu/logged';                    }                }            },            error: function(e){                console.error(e);            }        });    }}// Check the result of the user status and display login button if necessaryfunction checkLoginStatus(response) {    if(response && response.status == 'connected') {        FB.api('/me?fields=id,name,first_name,last_name,email,location', function(me) {            $.ajax({                url: '//' + location.host + '/facebook_login',                data: me,                type: "POST",                success: function(result) {                    console.log(result);                },                error: function(e){                    console.error(e);                }            });        });        // Hide the login button//        document.getElementById('loginButton').style.display = 'none';    } else {//        alert('User is not authorized');        // Display the login button//        document.getElementById('loginButton').style.display = 'block';    }}/** By default country is Australia - get states *///    $(document).on('change', '#form_country', function(){//        var el = $(this).val();//        var request = $.ajax({//            url: '//' + location.host + '/states',//            type: "POST",//            data: { id : el },//            dataType: "json"//        });////        request.done(function( data ) {//            if(data.valid){//                var state = data.states;//                if($('#account-register #div-state').hasClass('hide')){//                    $('#account-register #div-state').removeClass('hide');//                }//                $(state).each(function(){//                    var new_state = $('<option value="' + this.id + '">' + this.state_name + ' (' + this.code + ')</option>');//                    $('#account-register #form_state').append(new_state);//                });////            } else {////                if(!$('#account-register #div-state').hasClass('hide')){//                    $('#account-register #div-state').addClass('hide');//                }//                if(!$('#account-register #div-city').hasClass('hide')){//                    $('#account-register #div-city').addClass('hide');//                }//                alert('This Country has not entered states.')////            }//        });////        request.fail(function( jqXHR, textStatus ) {//            alert( "Request failed: " + textStatus );//        });//    });    /** When choose state get cities *///    $(document).on('change', '#form_state', function(){//        var el = $(this).val();//        var request = $.ajax({//            url: '//' + location.host + '/cities',//            type: "POST",//            data: { id : el },//            dataType: "json"//        });////        request.done(function( data ) {//            if(data.valid){//                var cities = data.cities;//                if($('#account-register #div-city').hasClass('hide')){//                    $('#account-register #div-city').removeClass('hide');//                }//                $(cities).each(function(){//                    var new_city = $('<option value="' + this.city_id + '">' + this.city_name + '</option>');//                    $('#account-register #form_city').append(new_city);//                });//                $('#account-register #form_city').rules('add', {//                    required:true//                });//////            } else {//                if(!$('#account-register #div-city').hasClass('hide')){//                    $('#account-register #div-city').addClass('hide');//                }//                alert('This State has not entered cities.')////            }//        });////        request.fail(function( jqXHR, textStatus ) {//            alert( "Request failed: " + textStatus );//        });////    });    /** Register */    $('#account-register #register_form').validate({        rules: {            first_name: "required",            last_name: "required",            email: {                required: true,                email: true,                remote: '//' + location.host + '/check_email'            },            password: {                required: true,                minlength: 5            },            conf_password:{                required: true,                equalTo: "#form_password"            },            mobile:{                required: true,                number: true            },            address:{                required: true            },            state:{                required: true            }        },messages:{            email: {                remote: jQuery.format("{0} is already in use!")            }        }    });    $(document).on('click', '#register-btn', function(){        if( $('#account-register #register_form').valid() ){            var request = $.ajax({                url: '//' + location.host + '/register',                type: "POST",                data: $('#register_form').serialize(),                dataType: "json"            });            request.done(function( data ) {                if(!data.valid){                    $('#account-register #error_note').html('');                    $('#account-register #error_note').append(data.errors);                } else {                    window.location.href = '//' + location.host + '/menu';                }            });            request.fail(function( jqXHR, textStatus ) {                showAlert( "", "ACC_Request failed: " + textStatus );            });        }    });    /** Edit  */    $('#account-edit #edit_form').validate({        rules: {            first_name: "required",            last_name: "required",            email: {                required: true,                email: true,                remote: '//' + location.host + '/check_email_edit'            },            password: {                required: true,                minlength: 5            },            conf_password:{                required: true,                equalTo: "#form_password"            },            mobile:{                required: true,                number: true            },            address:{                required: true            },            country_id:{                required: true            },            state:{                required: true            },            city:{                required: true            }        },messages:{            email: {                remote: jQuery.format("{0} is already in use!")            }        }    });    $(document).on('click', '#edit-btn', function(){        if( $('#account-edit #edit_form').valid() ){            var request = $.ajax({                url: '//' + location.host + '/save',                type: "POST",                data: $('#edit_form').serialize(),                dataType: "json"            });            request.done(function( data ) {                if(!data.valid){                    $('#account-edit #error_note').html('');                    $('#account-edit #error_note').append(data.errors);                } else {                    window.location.href = '//' + location.host + '/menu';                }            });            request.fail(function( jqXHR, textStatus ) {                showAlert( "", "ACC2_Request failed: " + textStatus );            });        }    });//    $(document).on('keyup','#form_password', function(e){//        if(e.keyCode == 13){//            alert('you pressed enter ^_^');//        }//    });