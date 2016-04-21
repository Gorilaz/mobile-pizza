/***********************************************************************************************************************
 * Global functions for multiple page
 * @url none
 **********************************************************************************************************************/

/*
 * Show fancy alert
 * @param {string} title Title of popup.
 * @param {string} description Description of popup.
 * @returns none
 */
function showAlert( title, description )
{
    if( !!title )
    {
        $('#alertDialog').find('h1').empty().append(document.createTextNode(title));
    }
    else
    {
        $('#alertDialog').find('h1').empty().append(document.createTextNode(document.title));
    }

    $('#alertDialog').find('#popup-alert-text .content').empty().append(document.createTextNode(description));

    $('#alertDialog').popup('open');
}

/*
 * Show fancy confirm
 * @param {string} title Title of popup.
 * @param {string} description Description of popup.
 * @param {function} ok Ok callback.
 * @param {function} cancel Cancel callback.
 * @returns none
 */
function showConfirm( title, description, ok, cancel )
{
    if( !!title )
    {
        $('#confirmDialog').find('h1').empty().append(document.createTextNode(title));
    }
    else
    {
        $('#confirmDialog').find('h1').empty().append(document.createTextNode(document.title));
    }

    $('#confirmDialog').find('#popup-confirm-text .content').empty().append(document.createTextNode(description));

    $('#popup-confirm-btn .ok, #popup-confirm-btn .cancel').off('click');

    if( typeof(ok) === 'function' )
    {
        $('#popup-confirm-btn .ok').on('click', function() { ok(); });
    }

    if( typeof(cancel) === 'function' )
    {
        $('#popup-confirm-btn .cancel').on('click', function() { cancel(); });
    }

    $('#confirmDialog').popup('open');
}

/*
 * Select payment method and pay
 * @returns none
 */
function saveOrder()
{
    var pg = $('#pg').data('pg');
    if( pg == 'credit-card' 
        && $('#form-credit').valid() )
    {
        var request = $.ajax({
            url: '//' + location.host + '/payment/Do_direct_payment',
            type: "POST",
            data: $('#form-credit').serialize(),
            dataType: "json"
        });
        request.done(function( data ) {
            /* 
             * TODO: Need to review this strange code
             */
            if(data.error){
                $('#errors').html('');
                var new_errors = '';
                $.each(data.message, function( index, value ) {
                    new_errors += value + '  ';
                });
                showAlert( "", new_errors );
                /* -- */
            } else {
                window.location.href = '//' + location.host 
                        + '/order/save_order/credit';
            }
        });
        request.fail(function( jqXHR, textStatus ) {
            showAlert( "", "Request failed: " + textStatus );
        });

    } else if(pg == 'cash') {
        window.location.href = '//' + location.host 
                + '/order/save_order/cash';
    } else if(pg == 'paypal') {
        window.location.href = '//' + location.host 
                + '/order/save_order/paypal';
    }
} // saveOrder

/*
 * Ajax submit profile form and route
 * 
 */
function saveForm( action )
{
    action = action || '';
    $.mobile.loading( 'show', {
        textVisible: false,
        theme: 'a',
        textonly: false,
        html: ""
    });
    var request = $.ajax({
        url: '//' + location.host + '/security/save',
        type: "POST",
        data: $('#register_form').serialize()
    });
    request.done(function( data ) {
        if( data == 'login' )
        {
            window.location.href = '//' + location.host + '/menu';
        }
        else if( data == 'order' )
        {
            if( action == 'saveOrder' )
            {
                saveOrder();
            }
            else
            {
                window.location.href = '//' + location.host + '/checkout';
            }
        }
        else
        {
            window.location.href = '//' + location.host + '/security-edit'
        }
    });
    request.fail(function( jqXHR, textStatus ) {
        $.mobile.loading( 'hide' );
        showAlert( "", "Request failed: " + textStatus );
    });
} // saveForm

/*
 * Sign In process by standart login form
 * 
 * @param object obj
 * @returns false
 */
function signInRequest( obj )
{
    $.mobile.loading( 'show', {
        textVisible: false,
        theme: 'a',
        textonly: false,
        html: ""
    });
    var user = $('#user').val();
    var pass = $('#pass').val();
    var request = $.ajax({
        url: '//' + location.host + '/security/login',
        type: "POST",
        data: { 
            user : user, 
            pass : pass 
        },
        dataType: "json"
    });
    request.done(function( data ){
        if(data.login == 'true')
        {
            var type = $(obj).attr('data-position');
            if( type == 'order' )
            {
                window.location.href = '//' + location.host + '/payment';
            } else {
                window.location.href = '//' + location.host + '/menu';
            }
        } else if(data.login == 'required fields')
        {
            $('#login-error').addClass('hide');
            $('#login-required').removeClass('hide');
        } else {
            $('#login-error').removeClass('hide');
            $('#login-required').addClass('hide');
        }
        $.mobile.loading( 'hide' );
    });
    request.fail(function( jqXHR, textStatus ) {
        $.mobile.loading( 'hide' );
        showAlert( "", "Request failed: " + textStatus );
    });
    return false;
} // signInRequest

/*
 * Apply for login form validation schem
 * 
 * @returns bool
 */
function prepareLoginFormValidation()
{
    if( typeof $.validator == 'function' )
    {
        $("#form-singin").validate({
            rules: {
                user: {
                    required: true,
                    email: true
                },
                pass: {
                    required: true,
                    minlength: 5
                }
            }
        });
        return true;
    }
    return false;
} // prepareLoginFormValidation
   
/*
 * Apply for profile form validation schem
 * 
 * @returns bool
 */
function prepareProfileFormValidation()
{
    if( typeof $.validator == 'function' )
    {
        /*
         * Custom validate function for check mobile number
         */
        $.validator.addMethod('smsVerification', function (value, element) {
            var sms = $('#sms').data('sms');
            if( sms == 'enable')
            {
                if( $('#form_mobile').data('current') != $('#form_mobile').val() )
                {
                    $('#verify-div').show();
                    return false;
                } else {
                    verifyClean();
                }
            }
            return true;
        }, 'You need to verify this mobile number');

        /*
         * Activate validation for profile form
         */
        $('#register_form').validate({
            rules: {
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
                    required: true,
                    maxlength: 10,
                    minlength: 10,
                    digits: true,
                    remote: '//' + location.host + '/security/checkUniqueMobile',
                    smsVerification: true,
                }
            },
            messages: {
                mobile: {
                    remote: "The mobile number already used",
                }
            }
        });
        return true;
    }
    return false;
} // prepareProfileFormValidation

/*
 * Function for sent verify code
 */
function changeMobile()
{
    var mobile  = $('#form_mobile').val();
    var email   = $('#email').val();
    var fname   = $('#form_firstname').val();
    var lname   = $('#form_lastname').val();
    var request = $.ajax({
        url: '//' + location.host + '/checkout/verifyMobile',
        type: "POST",
        data: { 
            mobile : mobile, 
            email : email, 
            fname : fname, 
            lname : lname
        }
    });
    request.done(function( data ) {
        $('#form_mobile').attr('readonly', 'readonly');
        $('#popupDialog').popup('open');
        $('#sms-code').data('final', 'yes');
        $('#sms-code').show();
        $('#verify-btn')
             .find('.ui-btn-text').html($('#verify-btn').data('titlefinal'));
    });
    request.fail(function( jqXHR, textStatus ) {
        showAlert( "", "Request failed: " + textStatus );
    });
} // changeMobile

/*
 * Clear profile template to default point
 */
function verifyClean()
{
    $('#form_mobile').removeAttr('readonly');
    if( $('#form_mobile').data('current') != '' ) {
        $('#verify-div').hide();
    } else {
        $('#verify-div').show();
    }
    $('#sms-error-label').hide();
    $('#sms-code').hide();
    $('#sms-code').data('final', 'no');
    $('#sms-code').val('');
    $('#verify-btn')
         .find('.ui-btn-text').html($('#verify-btn').data('titlestart'));
    return true;
} // verifyClean

/*
 * Two ways 
 * - one for get sms code,
 * - two for validate mobile number
 * 
 * @returns none
 */
function verifyMobileBySMS()
{
    var final = $('#sms-code').data('final');
    if( final == 'no' )
    {
        if( $('#form_mobile').val() != '' )
        {
            changeMobile();
        }
    } else {
        var code = $('#sms-code').val();
        if( code !== '' )
        {
            var request = $.ajax({
                url: '//' + location.host + '/checkout/verifyCode',
                type: "POST",
                data: { code : code },
                dataType: "json"
            });
            request.done(function( data ) {
                if( data.valid )
                {
                    verifyClean();
                    $('#form_mobile').data('current', $('#form_mobile').val());                        
                    $('#register_form').valid();
                } else {
                    $('#sms-error-label').show();
                }
            });
            request.fail(function( jqXHR, textStatus ) {
                showAlert( "", "Request failed: " + textStatus );
            });
        } else {
            $('#sms-error-label').show();
        }
    }
} // verifyMobileBySMS

/**
 * Show/Hide footer panel for Done button
 * @returns true
 */
function manageDoneButtonForRightPanel()
{
    var position = 0;
    for( position = 1; position < 3; position++ )
    {
        if( $('#doneBtnForRightPanelingredients' + position).hasClass('ui-panel-closed') )
        {
            $('#doneBtnForRightPanelingredients' + position).removeClass('ui-panel-closed');
            $('#doneBtnForRightPanelingredients' + position).addClass('ui-panel-open');
        } else {
            $('#doneBtnForRightPanelingredients' + position).removeClass('ui-panel-open');
            $('#doneBtnForRightPanelingredients' + position).addClass('ui-panel-closed');
        }
    }
    return true;
} // manageDoneButtonForRightPanel

/*
 * Show / Hide help box for footer buttons
 * @param jquery obj lineObj
 * @returns true - show or false - hide
 */
helpIntervalForFooterLine = null;
function manageHelpFooterLine( lineObj )
{
    if( lineObj ) {
        if( lineObj.data('title') )
        {
            if( helpIntervalForFooterLine != null ) {
                clearInterval(helpIntervalForFooterLine);
            }
            $('#id-footer-help-line').html(lineObj.data('title'))
                    .show().stop().animate({ bottom: "60px" }, 500);
            helpIntervalForFooterLine = setInterval(function(){
                $('#id-footer-help-line').stop().animate({ bottom: "0px" }, 200, function() {
                    $('#id-footer-help-line').hide();
                });
            }, 2500);
            return true;
        }
    }
    if( helpIntervalForFooterLine != null ) {
        clearInterval(helpIntervalForFooterLine);
    }
    $('#id-footer-help-line').stop().animate({ bottom: "0px" }, 200, function() {
        $('#id-footer-help-line').hide();
    });
    return false;
} // manageHelpFooterLine

/***********************************************************************************************************************
 * Events used on product page
 * @url /home
 **********************************************************************************************************************/
$( document ).on("pageinit", "#page-home", function() {

//    if(referal){
//        $('#popup-refer2').popup('open');
//    }

});

$(window).on('resize', function() {
    if( !!$('#ingredients').length )
    {
        resizeIngredients();
    }

    if( !!$('#ingredients2').length )
    {
        resizeIngredients('ingredients2');
    }
});

function resizeIngredients(selector) {
    var selector = !!selector ? selector : 'ingredients';

    $('#' + selector + ' .ingredients-list:not(.fixed)')
        .outerHeight($(window).outerHeight() - 
            $('#' + selector + ' .ingredients-list.fixed').outerHeight() - 
            $('#' + selector).next('[id*="doneBtnForRightPanelingredients"]').find('.side-close-button').height());
}

/***********************************************************************************************************************
 * Events used on product page
 * @url /product/id
 **********************************************************************************************************************/
$( document ).on("pageinit", "#page-product", function() {
    
    
    $( document ).on("panelclose", "#ingredients", function(){
        manageDoneButtonForRightPanel();

        $('#doneBtnForRightPanelingredients1').hide();

        $('[data-role="footer"]').fixedtoolbar({ tapToggle: true });
    });
    $( document ).on("panelopen", "#ingredients", function(){
        manageDoneButtonForRightPanel();

        $('#doneBtnForRightPanelingredients1').show();

        resizeIngredients();

        $('[data-role="footer"]').fixedtoolbar({ tapToggle: false });
    });
    $( document ).on("panelclose", "#ingredients2", function(){
        manageDoneButtonForRightPanel();

        $('#doneBtnForRightPanelingredients2').hide();

        $('[data-role="footer"]').fixedtoolbar({ tapToggle: true });
    });
    $( document ).on("panelopen", "#ingredients2", function(){
        manageDoneButtonForRightPanel();

        $('#doneBtnForRightPanelingredients2').show();

        resizeIngredients('ingredients2');

        $('[data-role="footer"]').fixedtoolbar({ tapToggle: false });
    });

    /* Unbind everything */
    $(document).off('change','.calculate');
    $(document).off('change','#p-quantity');
    $(document).off('change','#half-order');
    $(document).off('change','.p-ingredient');
    $(document).off('change','select[data-type=Size]');
    $(document).off('change','select[name=variation]');
    $(document).off('change','#halfPizzaSelector');
    $(document).off('click','.submit-order');
    $(document).off('click','.ui-header');
    $(document).off('click','.ui-content');
    $(document).off('click','.ui-footer');

    $(document).on('click','.ui-header, .ui-content, .ui-footer',function() {
        $( "#ingredients" ).panel( "close");
    });

    /* Trigger actions on page init */
    calculateOrderPrice();

    if($('#hasHalf').val() == 1) {
        initHalfOrder();
    }

    if($('#hasIngredients').val() == 1) {
        populateIngredients();
    }

    $(document).on('change','select[data-type=Size]', function(e) {
        initHalfOrder();
    });
    $(document).on('change','select[name=variation]',function(e) {
        populateIngredients();
    });
    $(document).on('change','.calculate, #p-quantity, #half-order', function(e) {
        calculateOrderPrice();
    });
    $(document).on('change','.p-ingredient', function(e) {
        calculateOrderPrice();
    });

    /**
     * Send order
     * If is half, another popup is shown for selecting the 2nd pizza
     * else, user is redirected to cart/checkout
     */
    $(document).on('click','.submit-order', function(e) {
        e.preventDefault();

        /* In case is a loyalty add to cart, check if user is logged in and that he
            has enough points
         */
        if($('#buyWithPoints').val() == 1 && ($('#buyWithPoints').data('user') != 1 || $('#buyWithPoints').data('points') < $('#p-total').html())) {
            showAlert( "", "You have to be logged in and have enough points to buy this item!" );
            return false;
        } else {
            var form = $('<form>')
                .append(
                    $('<input>')
                        .attr({
                            'name': 'general', 
                            'type': 'hidden', 
                            'value': $( "form#order-form" ).serialize()
                        }), 
                    $('<input>')
                        .attr({
                            'name': 'ingredients', 
                            'type': 'hidden', 
                            'value': $( "#ingredients form.order-ingredients" ).serialize()
                        }), 
                    $('<input>')
                        .attr({
                            'name': 'ingredients2', 
                            'type': 'hidden', 
                            'value': $( "#ingredients2 form.order-ingredients" ).serialize()
                        })
                )
                .attr({
                    'action': '/menu', 
                    'method': 'post'
                });

            $(form).trigger('submit');
        }
    });


    /**
     * Handle half pizza change
     * Populates ingredients for half pizza
     */
    $(document).on('change', '#halfPizzaSelector', function() {

        var halfVariation = $(this).find('option').filter(':selected').val();
        var halfPizza     = halfs[$(this).data('variation')][halfVariation];

        if(halfVariation && halfVariation > 0) {
            var contentBlock  = ' \
            <div class="single-top" '+ ((halfPizza.product_image != '')?'style="background-image: url(' +
                    desktopUrl + 'templates/demotest/uploads/products/thumb/' + 
                    halfPizza.product_image+')"':'') +' > \
                <div class="single-content get-space"> \
                    <h1>'+halfPizza.product_name+'</h1> \
                    <div class="single-description"> \
                            '+halfPizza.description+' \
                    </div> \
                </div> \
            </div>\
            <div class="single-options get-space clear-heights"> \
                <div class="row ingredientsHolder"> \
                    <a href="#ingredients2" class="ui-link add-modify-btn">\n\
                    <i class="icon-chevron-sign-right"></i> Add/Modify Ingredients</a> \
                </div> \
            </div> \
            ';
            $('.halfPizzaBlock').html(contentBlock);
            populateIngredients(halfVariation, 2);
            calculateOrderPrice();
            $('input[name=isHalf]').val(1);
        } else {
            $('.halfPizzaBlock').html('');
            $('#ingredients2').html('');
            calculateOrderPrice();
            $('input[name=isHalf]').val(0);
        }
    });
});


function initHalfOrder() {
    /**
     * Deal with half/half pizza
     **/
    var sizeOption = $('select[data-type=Size] option').filter(':selected');
    if(sizeOption) {

        var halfGroup = sizeOption.data( "half-group" );
        var halfPrice = sizeOption.data( "half-fee" );

        if(halfGroup === undefined) {
            $('select[name=halfPizza]').val('').trigger('change');
            $('input[name=half-group-id]').val('');
            $('input[name=half-fee]').val('');
            $('input[name=isHalf]').val(0);
            $('.halfHolder').addClass('hide');
        } else {
            $('input[name=half-group-id]').val(halfGroup);
            $('input[name=half-fee]').val(halfPrice);

            $('.halfHolder').removeClass('hide');
            $('.halfHolder h2 small').html('$'+halfPrice+' fee applies');

            var options = '<select name="halfPizza" id="halfPizzaSelector" data-mini="true" data-type="halfoption" class="halfSelector" data-variation="'+ halfGroup +'">';
            options += '<option value="">No Half Pizza</option>';

            if(halfs[halfGroup]) {
                $.each(halfs[halfGroup], function( key, item ) {

                    var halfItemPrice = parseFloat(item.product_price) + parseFloat(item.variation_price);
                    options += '<option value="'+item.variation_id+'" data-price="'+halfItemPrice+'">'+item.product_name+' - ($'+halfItemPrice/2+')</option>';
                });

            }

            options += '</select>';

            $('.halfHolder .ui-block-b').html(options);

            $('#halfPizzaSelector').val('');
            $('#halfPizzaSelector').trigger('change');

            $('#halfPizzaSelector').selectmenu();

        }

    }
}


/**
 * Calculate order total
 * - price can be in dollars - for normal order
 *   or in points - for loyalty program
 */
function calculateOrderPrice() {

    /* Payment via loyalty points */
    if($('#buyWithPoints').val() == 1) {

        var initialPrice = parseFloat($('#p-footer').data('price'));
        var quantity     = parseFloat($('#p-quantity').val());

        var total = initialPrice*quantity;


        $('#p-total').html(total);
    }

    /* Normal Payment */
    else {
        var initialPrice = parseFloat($('#p-footer').data('price'));
        var quantity     = parseFloat($('#p-quantity').val());


        $( ".calculate" ).each(function() {
            var cprice = $(this).find('option:selected').data( "price" );

            if(cprice) {
                initialPrice+=parseFloat(cprice);
            }
        });

        $( ".p-ingredient" ).each(function() {

            var value = parseFloat($( this ).data( "price" ));

            /* Default values */
            if($(this).data('default') == 1) {

                /**
                 * Do Nothing for default ingredients
                 */
//            if ($(this).is(':checked')) {
//
//            } else {
//                initialPrice-=value;
//            }

                /* Optional values */
            } else {
                if ($(this).is(':checked')) {
                    initialPrice+=value;
                }
            }
        });

        /* if its half order */
        var halfVariationPrice = $('.halfSelector').find('option').filter(':selected').data('price');
//    console.log(halfVariationPrice);
        if(halfVariationPrice) {

            initialPrice+=halfVariationPrice;
            initialPrice = initialPrice/2;
            initialPrice+=parseFloat($('select[data-type=Size] option').filter(':selected').data('half-fee'));

            // Set all ingredients for 2nd pizza visible
            if($('#ingredients').find('.i-half-price:first').hasClass('hide')) {
                $('#ingredients').find('.i-full-price').addClass('hide');
                $('#ingredients').find('.i-half-price').removeClass('hide');
            }
        } else {
            if($('#ingredients').find('.i-full-price:first').hasClass('hide')) {
                $('#ingredients').find('.i-full-price').removeClass('hide');
                $('#ingredients').find('.i-half-price').addClass('hide');
            }
        }


        var total = initialPrice*quantity;


        $('#p-total').html(total);
    }
}

/**
 * Hide or show list elements for search string
 * @param string searchString
 * @returns true
 */
function searchItemsForRightPanel( searchString )
{
    $( '.ui-checkbox' ).show();
    if( 
        typeof searchString != 'undefined' 
        && searchString != '' 
      )
    {
        $('.order-ingredients input[type=checkbox]').each(function(){
            var contentString = $(this).attr('data-value');
            if( typeof contentString != 'undefined' )
            {
                var checked = $(this).parent().find('.ui-icon-checkbox-on');
                if ( 
                    (contentString.toLowerCase().indexOf(searchString.toLowerCase()) < 0) 
                    && checked.length == 0
                   ) {
                    $(this).parent().hide();
                }
            }
        });
    }
    return true;
}

/**
 * Get Ingredients based on Variation
 * Used on product details page
 *
 */
function populateIngredients( variationId, pizzaNo )
{

    if( variationId === undefined )
    {
        variationId = $('select[name=variation]:last').val();
    }

    if( pizzaNo === undefined || pizzaNo == 1 )
    {
        pizzaNo     = 1;
        var targetBlock = '#ingredients';
    } else {
        pizzaNo     = 2;
        var targetBlock = '#ingredients2';
    }
    $.ajax({
        url: '/get/ingredients/' + variationId,
        context: document.body
//        beforeSend: function() {
//            $.mobile.loading( 'show', {
//                text: "Loading...",
//                textVisible: true,
//                theme: 'a',
//                textonly: false,
//                html: ""
//            });
//        }
    })
    .complete(function() {

        // for symbol keys
        $(document).on('keyup', '.searchIngredientsId', function( env ){
            searchItemsForRightPanel(this.value);
        });
        
        // disable enter press
        $(document).on('keypress', '.searchIngredientsId', function( env ){
            if( env.keyCode == 13 )
            {
                env.preventDefault();
                return false;
            }
        });
        
        // clear button in search field
        $(document).on('click', '.ui-input-clear', function( env ){
            searchItemsForRightPanel(this.value);
        });

        // move to top search fields
        $(document)
            .on('focus', '#ingredients .searchIngredientsId', function( event ) {
                $('#ingredients')
                    .find('.ingredients-list.fixed')
                    .find('.included-header, .included-content')
                    .slideUp(800, function() {
                        resizeIngredients();
                    });
            })
            .on('blur', '#ingredients .searchIngredientsId', function( event ) {
                $('#ingredients')
                    .find('.ingredients-list.fixed')
                    .find('.included-header, .included-content')
                    .slideDown(800, function() {
                        resizeIngredients();
                    });
            })
            .on('focus', '#ingredients2 .searchIngredientsId', function( event ) {
                $('#ingredients2')
                    .find('.ingredients-list.fixed')
                    .find('.included-header, .included-content')
                    .slideUp(800, function() {
                        resizeIngredients('ingredients2');
                    });
            })
            .on('blur', '#ingredients2 .searchIngredientsId', function( event ) {
                $('#ingredients2')
                    .find('.ingredients-list.fixed')
                    .find('.included-header, .included-content')
                    .slideDown(800, function() {
                        resizeIngredients('ingredients2');
                    });
            });

        calculateOrderPrice();
    })
    .done(function(data) {

        var content = $('<form>')
            .addClass('order-ingredients')
            .append(
                $('<ul>')
                    .addClass('ingredients-list fixed')
                    .attr({
                        'data-divider-theme': 'c', 
                        'data-inset': 'true', 
                        'data-role': 'listview'
                    }), 
                $('<ul>')
                    .addClass('ingredients-list')
                    .attr({
                        'data-divider-theme': 'c', 
                        'data-inset': 'true', 
                        'data-role': 'listview'
                    })
            )
            .attr({
                'data-pizza': pizzaNo
            });

        var contentFixed = new Array;
        var contentIncluded = new Array;
        var contentExtra = new Array;

        if(data) {

            $.each(data, function( type, items ) {

                /**
                 * Included items comes as a single array
                 */
                if( type === 'included' )
                {
                    contentFixed.unshift(
                        $('<li>')
                            .addClass('included-content')
                            .append(
                                $('<fieldset>')
                                    .attr({
                                        'data-role': 'controlgroup'
                                    })
                                    .append(
                                        (function() {
                                            var item, key, 
                                                returnedBy = new Array;

                                            for( key in items )
                                            {
                                                if( items.hasOwnProperty(key) )
                                                {
                                                    item = items[key];

                                                    returnedBy.push(
                                                        $('<input>')
                                                            .addClass('p-ingredient')
                                                            .attr({
                                                                'checked': 'checked', 
                                                                'data-default': '1', 
                                                                'data-price': item.price, 
                                                                'data-theme': 'a', 
                                                                'id': 'ingredient-' + item.ingredient_id, 
                                                                'name': 'ingredient[]', 
                                                                'type': 'checkbox', 
                                                                'value': item.ingredient_id
                                                            }), 
                                                        $('<label>')
                                                            .append(
                                                                document.createTextNode(item.ingredient_name)
                                                            )
                                                            .attr({
                                                                'for': 'ingredient-' + item.ingredient_id
                                                            })
                                                    );
                                                }
                                            }

                                            return returnedBy;
                                        })()
                                    )
                            )
                    );

                    contentFixed.unshift(
                        $('<li>')
                            .addClass('included-header')
                            .append(
                                document.createTextNode('Included')
                            )
                            .attr({
                                'data-role': 'list-divider'
                            })
                    );
                }

                /**
                 * Extra ingredients comes grouped by subcategory
                 */
                else
                {
                    contentFixed.push(
                        $('<li>')
                            .append(
                                document.createTextNode('Extra')
                            )
                            .attr({
                                'data-role': 'list-divider'
                            })
                    );

                    contentFixed.push(
                        $('<li>')
                            .append(
                                $('<input>')
                                    .addClass('searchIngredientsId')
                                    .attr({
                                        'data-mini': 'true', 
                                        'data-theme': 'a', 
                                        'name': 'searchIngredients', 
                                        'type': 'search', 
                                        'value': '',
                                        'autocomplete': 'off'
                                    })
                            )
                            .addClass('item-search-divider')
                            .attr({
                                'data-role': 'list-divider'
                            })
                    );

                    var ecategory, ingredients;

                    for( ecategory in items )
                    {
                        if( items.hasOwnProperty(ecategory) )
                        {
                            ingredients = items[ecategory];

                            contentExtra.push(
                                $('<li>')
                                    .append(
                                        $('<fieldset>')
                                            .append(
                                                (function() {
                                                    var item, key, 
                                                        returnedBy = new Array;

                                                    for( key in ingredients )
                                                    {
                                                        if( ingredients.hasOwnProperty(key) )
                                                        {
                                                            item = ingredients[key];

                                                            returnedBy.push(
                                                                $('<input>')
                                                                    .addClass('p-ingredient')
                                                                    .attr({
                                                                        'data-default': '0', 
                                                                        'data-price': item.price, 
                                                                        'data-theme': 'a', 
                                                                        'data-value': item.ingredient_name.replace(/"/gi, '\"'), 
                                                                        'id': 'ingredient-' + item.ingredient_id, 
                                                                        'name': 'ingredient[]', 
                                                                        'type': 'checkbox', 
                                                                        'value': item.ingredient_id
                                                                    }), 
                                                                $('<label>')
                                                                    .append(
                                                                        (function() {
                                                                            var _returnedBy = new Array;

                                                                            _returnedBy.push(
                                                                                document.createTextNode(item.ingredient_name + ' ')
                                                                            );

                                                                            if( pizzaNo == 2 )
                                                                            {
                                                                                _returnedBy.push(
                                                                                    $('<span>')
                                                                                        .append(
                                                                                            document.createTextNode('$' + (parseFloat(item.price) / 2))
                                                                                        )
                                                                                        .addClass('price')
                                                                                );
                                                                            }
                                                                            else
                                                                            {
                                                                                _returnedBy.push(
                                                                                    $('<span>')
                                                                                        .append(
                                                                                            document.createTextNode('$' + item.price)
                                                                                        )
                                                                                        .addClass('price i-full-price')
                                                                                );

                                                                                _returnedBy.push(
                                                                                    $('<span>')
                                                                                        .append(
                                                                                            document.createTextNode('$' + (parseFloat(item.price) / 2))
                                                                                        )
                                                                                        .addClass('price i-half-price hide')
                                                                                );
                                                                            }

                                                                            return _returnedBy;
                                                                        })()
                                                                    )
                                                                    .attr({
                                                                        'for': 'ingredient-' + item.ingredient_id
                                                                    })
                                                            );
                                                        }
                                                    }

                                                    return returnedBy;
                                                })()
                                            )
                                            .attr({
                                                'data-role': 'controlgroup'
                                            })
                                    )
                            );
                        }
                    }                    
                }
            });

            content
                .find('.ingredients-list.fixed')
                .append(contentFixed);

            content
                .find('.ingredients-list:not(.fixed)')
                .append(contentExtra);
        } else {
            content.append(
                $('<li>')
                    .append(
                        document.createTextNode('This product doesn\'t have ingredients that you can modify')
                    )
            );
        }

        $(targetBlock).html(content);

        $(targetBlock)
            .after(
                $('<div>')
                    .addClass('right-panel-footer ui-panel-closed')
                    .append(
                        $('<p>')
                            .addClass('side-close-button')
                            .append(
                                $('<a>')
                                    .addClass('panel-list btn btn-blue ui-link done-btn-right-panel')
                                    .append(
                                        document.createTextNode('Done')
                                    )
                                    .attr({
                                        'data-inline': 'true', 
                                        'data-mini': 'true', 
                                        'data-rel': 'close', 
                                        'data-role': 'button', 
                                        'href': targetBlock
                                    })
                            )
                    )
                    .attr({
                        'id': 'doneBtnForRightPanelingredients' + pizzaNo
                    })
                    .hide()
            );

            content
                .find('.ingredients-list:not(.fixed)')
                .outerHeight($(window).outerHeight() - 
                    content.find('.ingredients-list.fixed').outerHeight() - 
                    $('#doneBtnForRightPanelingredients' + pizzaNo + ' .side-close-button').height());
        
        if(data) {
            $('.ingredientsHolder').removeClass('hide');
        }

        /**
         * Refresh the layout - dynamic content injected
         */
        $( ".ingredients-list" ).listview().listview('refresh');
        $( ".ingredients-list" ).trigger('create');
    });

}

window.addEventListener('native.hidekeyboard', function() {
    alert('123123');
});

/***********************************************************************************************************************
 * Events used on menu page
 * @url /menu
 **********************************************************************************************************************/
$( document ).on('pageinit', '#page-menu', function() {
    $(document).on('click', '#click-checkout', function(){
        window.location.href = '//' + location.host + '/checkout';
    });
});

/***********************************************************************************************************************
 * Events used on page review page
 * @url /checkout
 **********************************************************************************************************************/
$( document ).on('pageinit', '#page-checkout', function() {
    $(document).off('change','.footer-change');
    $(document).off('click','.checkout-footer a');
    $(document).off('change','#date');
    
    
    $(document).on('click','#id-footer-help-line', function() {
        manageHelpFooterLine(null);
    });
    

    //$('.control-1').show().addClass('animated bounce');
    var firstPanel = $('.control-1');
    firstPanel.show().fadeOut(250).fadeIn(250);
    manageHelpFooterLine(firstPanel);
   

    var count = 1;

    $(document).on('change','.footer-change', function() {

       count++;

       var elem         = $(this).closest('.checkout-footer');
       var next         = elem.next();
       var totalAmount  = $('.order-total-price').data('value');
       
       manageHelpFooterLine(next);

       /**
        * Payment Tab
        * Based on selected payment processor, check if minimum amount is meet
        */
       if($(this).attr('name') == 'payment') {

           // in case its online payment
            if($(this).val() == 3 || $(this).val() == 2) {
                if(totalAmount < rules.cc) {
                    showAlert( "", "Minimum amount for Credit Card payments is $"+rules.cc );
                } else {
//                    elem.addClass('hide');
//                    elem.next().removeClass('hide');
                    elem.hide();//.addClass('animated flip');
                    //elem.next().show().addClass('animated bounce');
                    elem.next().show().fadeOut(250).fadeIn(250);
                }
            }
            //paypal
            else if($(this).val() == 4) {
                if(totalAmount < rules.paypal) {
                    showAlert( "", "Minimum amount for Paypal payments is $"+rules.paypal );
                } else {
                    elem.hide();//.addClass('animated flip');
                    //elem.next().show().addClass('animated bounce');
                    elem.next().show().fadeOut(250).fadeIn(250);
                }
            } else {
                elem.hide();//.addClass('animated flip');
                //elem.next().show().addClass('animated bounce');
                elem.next().show().fadeOut(250).fadeIn(250);
            }
       }

       /**
        * Home/Pickup Delivery
        */
       else if($(this).attr('name') == 'delivery') {

           if($(this).val() == "D") {
               if(totalAmount < rules.min_order_amt) {
                   if(rules.order_less > 0){

                       showConfirm( "", "There is a $"+rules.order_less+" fee for order less than $"+rules.min_order_amt+". Click Ok for proceed or Cancel for keep shoping  .", function() {
                           if($('#has_discount').data('discountper') == 'no'){
                               defaultPrice('low_amount');
                           } else {
                               var discountpercet = $('#has_discount').data('discountper');
                               discountPrice(discountpercet,'low_amount');
                           }
                           r = null;
                           elem.hide();//.addClass('animated flip');
                           //elem.next().show().addClass('animated bounce');
                           elem.next().show().fadeOut(250).fadeIn(250);
                       }, function() {
                           window.location.href = '//' + location.host + '/menu';
                       } );
                   } else {
//                          alert('Minimum amount for Delivery is $'+rules.min_order_amt);
                          $( "#popupMinOrderValueNotMet" ).popup("open");
                   }

               } else {
                   elem.hide();//.addClass('animated flip');
                   //elem.next().show().addClass('animated bounce');
                   elem.next().show().fadeOut(250).fadeIn(250);
               }
           } else {
               elem.hide();//.addClass('animated flip');
               //elem.next().show().addClass('animated bounce');
               elem.next().show().fadeOut(250).fadeIn(250);
           }
       }
       else if($(this).attr('name') == 'when') {
           if($(this).val() == 'ASAP') {
               submitOrder();
           } else {
               if(elem.next().length) {
                   elem.hide();//.addClass('animated flip');
                   //elem.next().show().addClass('animated bounce');
                   elem.next().show().fadeOut(250).fadeIn(250);
               } else {
                   submitOrder();
               }
           }
       } else {
           if(elem.next().length) {
               elem.hide();//.addClass('animated flip');
               //elem.next().show().addClass('animated bounce');
               elem.next().show().fadeOut(250).fadeIn(250);
           } else {
               submitOrder();
           }
       }

   });

    /**
     * Click Keep Shoping
     */
    $(document).on('click', '#keep-shoping', function(){
        window.location.href = '//' + location.host + '/menu';
    });


    /**
     * Click Procced
     */
    $(document).on('click', '#proceed', function(){
        if($('#has_discount').data('discountper') == 'no'){
           defaultPrice('low_amount');
        } else {
           var discountpercet = $('#has_discount').data('discountper');
           discountPrice(discountpercet,'low_amount');
        }
        var elem = $('#dialog').data('elem');
        elem.hide();//.addClass('animated flip');
        //elem.next().show().addClass('animated bounce');
        elem.next().show().fadeOut(250).fadeIn(250);
        $('#dialog').dialog('close');
    });



    $(document).on('click tap', '.checkout-footer a', function() {
       var prev = $(this).closest('.checkout-footer').hide().prev();
       
       manageHelpFooterLine(prev);

       if( prev.length ) {
           //prev.show().addClass('animated bounce');
           prev.show().fadeOut(250).fadeIn(250);
       } else {
            /*
             * Placeholder
             * Overwrite Back button
             */
           //$.mobile.back(); // old code
           window.location.href = '//' + location.host+ '/menu';
       }
       return false; // fix double event
   });

    populateOrderAvailableHours();
    $(document).on('change', '#date', function() {
        populateOrderAvailableHours();
    });



    function populateOrderAvailableHours() {
        var selected = $('#date').val();

        if(selected === undefined || selected == '') {
            var selected = $('#date').find('option:nth-child(2)').val();
        }


        if(selected !== undefined) {
            var availableHours = schedule[selected]["P"];
            var $timeEl = $('#time');
            $timeEl.empty();

            var firstVal = 'Select Time';

            $timeEl.append($("<option></option>")
                .attr("value", '').attr("selected", true).text("Select Time"));

            $.each(availableHours, function(key, value) {
                if(!firstVal) {
                    firstVal = value;
                }
                $timeEl.append($("<option></option>")
                    .attr("value", key).text(value));
            });

            $timeEl.parent().find('.ui-btn-text span').html(firstVal);
        }


    }

    /* Unbind everything */
    $(document).off('click','#cart-button');
    $(document).off('click','.show-date');
    $(document).off('click','.asap');
    $(document).off('click','#voucher');
    $(document).off('click','.remove-order-item');
    $(document).off('change','.choose-coupon');
    // $(document).off('click','#td-social a');

    function submitOrder() {
        if( $('.later').is(':checked') )
        {
            var date = $('#date').val();

            if( !!date )
            {
                $('#form-checkout').submit();
            }
            else
            {
                $('#date-error').removeClass('hide');
            }
        }
        else
        {
            if( !$('#date').val() || 
                !$('#time').val() )
            {
                return false;
            }
            else
            {
                $('#form-checkout').submit();
            }
        }
    }


    /**
     * Show Date-time picker
     */


    if($('#isopen').data('open') == 'close'){
        $("#date-time :input").attr("disabled", false);
        $('#date-time').removeClass('hide');
    } else {
        $("#date-time :input").attr("disabled", true);
    }



    $(document).on('click','.show-date', function(){

        $("#date-time :input").attr("disabled", false);
        $('#date-time').removeClass('hide');
    });

    $(document).on('click','.asap', function(){
        $("#date-time :input").attr("disabled", true);
        $('#date-time').addClass('hide');
    });

    /** END Date-time picker */


    function defaultPrice(type){
        if(type === undefined) {
            var type = false;
        }

        $('#coupon-row').addClass('hide');
        $('#has_discount').data('discountper', 'no');

        var total = $('.order-total-price').data('default');
        if($('#holiday-fee').data('fee') != 'no'){
            var feeDiscount = $('#holiday-fee').data('fee');
            var feePrice = ((parseFloat(total)/100)*parseFloat(feeDiscount)).toFixed(2);

            total = (parseFloat(total) + parseFloat(feePrice)).toFixed(2);
        }

        if(type == 'low_amount'){
            total = (parseFloat(total) + parseFloat(rules.order_less)).toFixed(2);
            $('#low_order_fee').html('+$'+rules.order_less);
            $('#low_order').removeClass('hide');
        }

        $('.order-total-price').html('$ ' + total).data('value',total);
    }
    
    /**
     * Apply percent
     * @param int amountPercent
     * @param float price
     * @returns float
     */
    function applyPercentForPrice( amountPercent, price )
    {
        return ( (parseFloat(price) / 100) * parseInt(amountPercent) ).toFixed(2);
    }
    
    /**
     * Sum two values
     * @param float value1
     * @param float value2
     * @returns float or false
     */
    function prepareMathFloatValues( operandOne, operandTwo, operation )
    {
        operation = operation || '+';
        if( operation == '+' )
        {
            return (parseFloat(operandOne) + parseFloat(operandTwo)).toFixed(2);            
        } else {
        if( operation == '-' )
        {
            return (parseFloat(operandOne) - parseFloat(operandTwo)).toFixed(2);
        }}
        return false;
    }

    /**
     * Apply discounts for price
     * @param int discountpercet
     * @param string type
     * @returns null
     */
    function discountPrice( discountpercet, type )
    {
        type = !!type ? type : '';

        $('#has_discount').attr('data-discountper', discountpercet);

        var defaultTotal = parseFloat($('.order-total-price').data('default')), 
            total = 0, totalDiscount = 0;

        var orderPrice, orderPriceIndex, 
            orderPrices = $('.order-subtotal').find('.order-price').get();

        for( orderPriceIndex in orderPrices )
        {
            if( orderPrices.hasOwnProperty(orderPriceIndex) )
            {
                orderPrice = orderPrices[orderPriceIndex];

                if( $(orderPrice).is(':visible') )
                {
                    var subTotal = parseFloat($(orderPrice).data('value')), 
                        qty = parseInt($(orderPrice).data('qty'), 10);

                    if( !isNaN(qty) )
                    {
                        subTotal *= qty;
                    }

                    if( isNaN(subTotal) )
                    {
                        continue;
                    }
                    else
                    {
                        if( $(orderPrice).data('coupon') == 1 )
                        {
                            var discount = parseFloat(applyPercentForPrice(discountpercet, subTotal));

                            totalDiscount += discount;

                            total += (subTotal - discount);
                        }
                        else
                        {
                            total += subTotal;
                        }
                    }
                }
            }
        }

        // add holiday fee
        if( $('#holiday-fee').data('fee') !== 'no' )
        {
            var feePrice = parseFloat(applyPercentForPrice(parseFloat($('#holiday-fee').data('fee')), total));

            $('#fee-prince').html('+$' + feePrice.toFixed(2));

            total = parseFloat(prepareMathFloatValues(total, feePrice));
        }

        if( type == 'low_amount' || 
            type == 'online_low_amount' )
        {
            total = parseFloat(prepareMathFloatValues(total, rules.order_less));

            $('#low_order_fee').html('+$' + rules.order_less);

            $('#low_order').removeClass('hide');
        }

        if( type == 'online' || 
            type == 'online_low_amount' )
        {
            $('#icon-remove-coupon').removeClass('hide');

            $('#coupon-des').html('Online Order Discount');
        }

        $('.order-total-price').html( '$ ' + total.toFixed(2) );
        $('.order-total-price').attr( 'data-value', total.toFixed(2) );
        $('#coupon-dis').html( '-$' + totalDiscount.toFixed(2) );
    }

    /**  Coupon  */
    $(document).on( 'change', '.choose-coupon', function() {
        var _this = this, 
            discountpercet = $(_this).data('discount');

        if( discountpercet == 'other' )
        {
            $('#coupon').prop('disabled', false);
            $('#tr-coupon').removeClass('hide');

            $('#coupon-row').removeClass('hide');

            if( $('#radio-choice-v-2a').is(':checked') )
            {
                defaultPrice('low_amount');
            }
            else
            {
                defaultPrice();
            }
        }
        else
        {
            if( !!$.trim($('#coupon-des').text()) )
            {
                $(_this).attr('to-applying', 'to-applying');

                $('#icon-remove-coupon')[0].click();
            }
            else
            {
                $('#coupon').prop('disabled', true);
                $('#tr-coupon').addClass('hide');

                $('#coupon-row').removeClass('hide');

                if( $('#radio-choice-v-2a').is(':checked') )
                {
                    discountPrice(discountpercet, 'online_low_amount');
                }
                else
                {
                    discountPrice(discountpercet, '');
                }
            }
        }
    });


    /** other coupon */
    $(document).on('click','#voucher', function()
    {
        var el = $('#coupon').val();
        var request = $.ajax({
            url: '//' + location.host + '/checkout/getCoupons',
            type: "POST",
            data: { 
                coupon : el 
            },
            dataType: "json"
        });
        request.done(function( data )
        {
            if( data != 'false' )
            {
                $('#coupon-des').html(data.coupondescription);
                var discountpercet = parseInt(data.discountper) ;
                discountPrice(discountpercet);
                $('#icon-remove-coupon').removeClass('hide');
                $('#coupon-row').removeClass('hide');
                $('#tr-coupon').addClass('hide');
                $('#other').val( data.id );
            } else {
                defaultPrice();
                $('#icon-remove-coupon').addClass('hide');
                $('#coupon-des').html('');
                $('#coupon-dis').html('');
            }
        });
        request.fail(function( jqXHR, textStatus )
        {
            showAlert( "", "Request failed: " + textStatus );
        });
    });

    /** remove coupon  */
    $(document).on('click', '#icon-remove-coupon', function() {
        showConfirm( "", "Remove voucher ?", function() {
            if( !!$('[to-applying="to-applying"]').length )
            {
                var _this = $('[to-applying="to-applying"]'), 
                    discountpercet = $(_this).data('discount');

                $('#coupon').prop('disabled', true);
                $('#tr-coupon').addClass('hide');

                $('#coupon-row').removeClass('hide');

                if( $('#radio-choice-v-2a').is(':checked') )
                {
                    discountPrice(discountpercet, 'online_low_amount');
                }
                else
                {
                    discountPrice(discountpercet, '');
                }

                $('#icon-remove-coupon').addClass('hide');

                $('#coupon-des').html('');

                $('#coupon').val('');
            }
            else
            {
                $('#coupon').prop('disabled', false);
                $('#tr-coupon').removeClass('hide');

                if( $('#radio-choice-v-2a').is(':checked') )
                {
                    defaultPrice('low_amount');
                }
                else
                {
                    defaultPrice();
                }

                $('#icon-remove-coupon').addClass('hide');

                $('#coupon-des').html('');
                $('#coupon-dis').html('');

                $('#coupon').val('');
            }
        }, function() {
            var _this = $('[to-applying="to-applying"]');

            $(_this).removeAttr('to-applying');
        } );
    });
    /**  END Coupon  */

    $(document).on('click','.remove-order-item', function(e) {
        e.preventDefault();

        var _this = this;

        showConfirm( "", "Remove "+ $(_this).data('title') +" from your order?", function() {

            var valueToSubstract = $(_this).data('value');
            var hideItems        = $(_this).data('id');

            $('.item-'+hideItems).hide();
            var totalItem = $('.order-total-price');
            var newTotal  = totalItem.data('default')-valueToSubstract;

            $('.order-total-price').html('$ '+newTotal).data('value',newTotal).data('default', newTotal);


            if($('#has_discount').data('discountper') == 'no'){
                if($('#radio-choice-v-2a').is(':checked')){
                    defaultPrice('low_amount');
                } else {
                    defaultPrice();
                }

            } else {
                var discountpercet = $('#has_discount').data('discountper');
                if($('#radio-choice-v-2a').is(':checked')){
                    discountPrice(discountpercet, 'low_amount');
                } else {
                    discountPrice(discountpercet, 'online');
                }

            }

//            totalItem.html('$ '+newTotal).data('value',newTotal);
//            totalItem.html('$ '+newTotal).data('value',newTotal);



            /* Ajax call to remove the item from session */
            $.ajax({
                url: "/remove/"+hideItems,
                context: document.body
            }).done(function(data) {
                    //done!
                });

            if(newTotal == 0) {
                $('.notice-holder').removeClass('hide');
                $('.order-holder').hide();
                $('.checkout-footer').hide();
            }
        } );
    });


    /**
     * Social Locker
     */


    /* $(document).on('click', '#td-social a', function(){

        $('#show-social-loker').removeClass('hide');
    }); */

    /**
     * Need to check is present global vars
     * @type String|FACEBOOKAPPID
     */
    /* var FBAppID = '';
    if( typeof FACEBOOKAPPID == 'string' )
    {
        FBAppID = FACEBOOKAPPID;
    } */
    /* use after for Facebook App ID - FBAppID */

    /* $("#social-loker").sociallocker({

        buttons: {
            order: [
                "twitter-tweet", "facebook-share"
            ]
        },

        // a theme name that will be used
        theme: "secrets",

        // text that appears above the social buttons
        text: {
            header: " ",
            message: "Free coke? Like us and it's yours!"
        },
        facebook: {
            appId: FBAppID,
            share: {
                title: 'share it',
                url: "http://m.pizzaboy.bywmds.us/"
            }
        },

        twitter: {
            tweet: {
                title: "tweet me",
                text: 'Tweet this message',
                url: "http://m.pizzaboy.bywmds.us/"
            }
        }
    });
    $('.onp-sociallocker-text').remove(); */

});


/***********************************************************************************************************************
 * Payment/Send Order
 * @url /payment
 **********************************************************************************************************************/
$( document ).on('pageshow', "#page-payment", function() {
    verifyClean();    
});
$( document ).on('pageinit', "#page-payment", function() {

    /* Unbind everything */
    $(document).off('click','#sign-in');
    $(document).off('click','#verify-btn');
    $(document).off('click','#log-out');
    $(document).off('change','#form_suburb');
    $(document).off('click','#send-order');
    $(document).off('click','.card-number');
    $(document).off('keyup','.card-number');
    $(document).off('keyup','#form_firstname');
    $(document).off('keyup','#form_lastname');
    
    prepareProfileFormValidation();
    prepareLoginFormValidation();

    /*
     * Bind click action for standart login form
     */
    $(document).on('click','#sign-in',function(){
        if($('#form-singin').valid())
        {
            signInRequest( this );
        }
    });

    /*
     * Bind click action for log out 
     */
    $(document).on('click', '#log-out', function(){
        window.location.href = '//' + location.host + '/logout/payment';
    });

    /*
     * Bind click action for Verify button on profile page
     */
    $(document).on('click', '#verify-btn', function(){
        verifyMobileBySMS();
    });

    /*
     *  change suburb, calculate total 
     */
    $(document).on('change', '#form_suburb', function(){
        if( typeof has_delivery != 'undefined' 
            && has_delivery == '1'
           )
        {
            var subtotal = $('#subtotal').html();
            subtotal = subtotal.replace('$','');

            var discount = $('#discount').val();
            if( discount !== 'undefined' )
            {
                subtotal = parseFloat(subtotal) - parseFloat(discount);
            }
            var fee = $('#form_suburb option:selected').data('fee');

            /** payment fee  */
            var payment = $('#cc').data('cc');
            if( payment != 0 )
            {
                subtotal = parseFloat(subtotal) + parseFloat(payment);
            }
            var total = parseFloat(fee) + parseFloat(subtotal) + parseFloat(low_order);
            $('#delivery-fee').html('+$' + fee);
            $('#total').html(total);
        }
    }); // onchange form_suburb

    /** Copy first and last name of the cardholder */
    if($('#cardholder-input').length)
    {
        $('#form_firstname, #form_lastname').on('keyup', function(){
            $('#cardholder-input').val($('#form_firstname').val()
                    + ' ' + $('#form_lastname').val());
        });
    }

    /** card number inputs */
    $(document).on('click', '.card-number', function() {
        $(this).val('');
    });

    /*
     * Check if number and limit by 4 digit
     */
    $(document).on('keydown', '.card-number', function( event ) {
        var lengthStr = $(this).val().length;
        if( lengthStr <= $(this).data('length') )
        {
            if( $.inArray( event.keyCode, 
                            [48, 49, 50, 51, 52, 53, 54, 55, 56, 57] ) > -1 )
            {
                if( lengthStr == $(this).data('length') )
                {
                    var id = $(this).data('id');
                    $('#' + id).focus();
                }
                return true;
            }
        }
        event.preventDefault();
        return false;
    });
    
    /** Bind action click for Order now  */
    $(document).on('click','#send-order', function(){
        if($('#register_form').valid())
        {
            saveForm( 'saveOrder' );
        }
    }); // send-order
    
}); // /payment


/***********************************************************************************************************************
 * Recovery Password
 * @url /reset
 **********************************************************************************************************************/
$( document ).on('pageinit', "#page-recover", function() {
    /* Unbind everything */
    $(document).off('click','#recover');

    $(document).on('click', '#recover', function(){

        var email = $('#email').val();
        if(email){
            var request = $.ajax({
                url: '//' + location.host + '/security/checkValidEmail',
                type: "POST",
                data: { email : email },
                dataType: "json"
            });

            request.done(function( data ) {
                if(data == 'valid'){
                    $('#popupRecover').popup('open');
                } else {
                    $('#error-required').html('Email address not found in database!');
                }
            });

            request.fail(function( jqXHR, textStatus ) {
                showAlert( "", "Request failed: " + textStatus );
            });
        } else {
            $('#error-valid').html('Please input valid email address!');
        }
    });
});

/***********************************************************************************************************************
 * Change Password
 * @url /change-password
 **********************************************************************************************************************/
$( document ).on('pageinit', "#page-change", function() {
    /* Unbind everything */
    $(document).off('click','#save');

    $(document).on('click', '#save', function(){

        $('#error-valid').html('');
        var pass = $('#pass').val();
        var conf = $('#conf').val();

        if(pass == conf){

            var code = $('#code').data('code');
            var request = $.ajax({
                url: '//' + location.host + '/security/savePassword',
                type: "POST",
                data: { code : code, pass : pass }
            });

            request.done(function( data ) {
               window.location.href = '//' + location.host + '/login_page';
            });

            request.fail(function( jqXHR, textStatus ) {
                showAlert( "", "Request failed: " + textStatus );
            });
        } else {
            $('#error-required').html('Verification must be the same with the Password!');
        }
    });
});

/***********************************************************************************************************************
 * Your Orders
 * @url /order/yourOrders
 **********************************************************************************************************************/
$( document ).on('pageinit', "#your-orders", function() {

    /* Unbind everything */
    $(document).off('click','#order-signin');
    $(document).on('click','#order-signin',function(){
        signInRequest( this );
    });


    $(document).on('click', '.change-page', function(){

        var count = $('#page').data('count');
        var page = $(this).data('change');


        var request = $.ajax({
            url: '//' + location.host + '/order/getAjaxOrders',
            type: "POST",
            data: { count : count, page : page },
            dataType: "json"
        });

        request.done(function( data ) {

            $('#tbody-orders').html('');

            var html = '';
            $(data.orders).each(function(){
                if(this.order_description){
                    html += '<tr class="tr-your-order">' +
                        '<td>' + this.order_placement_date + '</td>' +
                        '<td>' + this.order_description + '</td>' +
                        '<td>' + this.payment_amount + '</td>' +
                        '<td>' + this.points_used + '</td>' +
                        '<td>' + this.points_earned + '</td>' +
                        '<td><a href="'+ base_url + 'order-again/'+ this.order_id +'" data-role="button" data-inline="true">Order This Again</a></td>' +
                        '</tr>';

                }

            });


            $('#tbody-orders').html(html);
            $('#page').data('count', data.count);
            var total = $('#total').data('total');

            if(data.count == 0 || data.count == 5) {
                $('#div-both').addClass('hide');
                $('#div-preview').addClass('hide');

                $('#div-next').removeClass('hide');

            } else if(data.count >= total){
                $('#div-both').addClass('hide');
                $('#div-next').addClass('hide');

                $('#div-preview').removeClass('hide');
            } else {
                $('#div-preview').addClass('hide');
                $('#div-next').addClass('hide');

                $('#div-both').removeClass('hide');
            }

            $('#your-orders').trigger('create');
        });

        request.fail(function( jqXHR, textStatus ) {
            showAlert( "", "Request failed: " + textStatus );
        });
    });

});



/***********************************************************************************************************************
 * Your Account
 * @url /login_page
 **********************************************************************************************************************/

$( document ).on('pageinit', "#security-login", function() {

    /* Unbind everything */
    $(document).off('click','#sign-in');
    
    prepareLoginFormValidation();
    
    /*
     * Bind click action for standart login form
     */
    $(document).on('click', '#sign-in', function(){
        if($('#form-singin').valid())
        {
            signInRequest( this );
        }
    });

}); // /login_page


/***********************************************************************************************************************
 * Your Account
 * @url /security-edit
 **********************************************************************************************************************/
$( document ).on('pageshow', "#page-edit", function() {
    console.log('pageshow#page-edit');
    verifyClean();    
});

$( document ).on('pageinit', "#page-edit", function() {
    console.log('pageinit#page-edit');
    /* Unbind everything */
    $(document).off('click','#save-edit');
    $(document).off('click','#verify-btn');
    
    prepareProfileFormValidation();
    
    /*
     * Bind click action for Verify button on profile page
     */
    $(document).on('click', '#verify-btn', function(){
        verifyMobileBySMS();
    });

    /*
     * Bind click action for save button for edit profile
     */
    $(document).on('click', '#save-edit', function(){
        if($('#register_form').valid())
        {
            saveForm();
        }
    });

}); // /security-edit