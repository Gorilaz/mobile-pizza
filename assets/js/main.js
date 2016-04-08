/***********************************************************************************************************************
 * Global functions for multiple page
 * @url none
 **********************************************************************************************************************/


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
                alert(new_errors);
                /* -- */
            } else {
                window.location.href = '//' + location.host 
                        + '/order/save_order/credit';
            }
        });
        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
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
        } else if( data == 'order' )
        {
            if( action == 'saveOrder' )
            {
                saveOrder();
            } else {
                window.location.href = '//' + location.host + '/payment/socialLoker';
            }
        } else {
            window.location.href = '//' + location.host + '/security/edit'
        }
    });
    request.fail(function( jqXHR, textStatus ) {
        $.mobile.loading( 'hide' );
        alert( "Request failed: " + textStatus );
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
        alert( "Request failed: " + textStatus );
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
        alert( "Request failed: " + textStatus );
    });
} // changeMobile

/*
 * Clear profile template to default point
 */
function verifyClean()
{
    $('#verify-div').hide();
    $('#form_mobile').removeAttr('readonly');
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
                alert( "Request failed: " + textStatus );
            });
        } else {
            $('#sms-error-label').show();
        }
    }
} // verifyMobileBySMS




/***********************************************************************************************************************
 * Events used on product page
 * @url /home
 **********************************************************************************************************************/
$( document ).on("pageinit", "#page-home", function() {

//    if(referal){
//        $('#popup-refer2').popup('open');
//    }

});

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
}

/***********************************************************************************************************************
 * Events used on product page
 * @url /product/id
 **********************************************************************************************************************/
$( document ).on("pageinit", "#page-product", function() {
    
    $( "#ingredients" ).on( "panelclose", function( event, ui ){
        manageDoneButtonForRightPanel();
    });
    $( "#ingredients" ).on( "panelopen", function( event, ui ){
        manageDoneButtonForRightPanel();
    });
    $( "#ingredients2" ).on( "panelclose", function( event, ui ){
        manageDoneButtonForRightPanel();
    });
    $( "#ingredients2" ).on( "panelopen", function( event, ui ){
        manageDoneButtonForRightPanel();
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

    $(document).on('change','.calculate, #p-quantity, #half-order', function(e) {
        calculateOrderPrice();
    })
    $(document).on('change','.p-ingredient', function(e) {
        calculateOrderPrice();
    })
    $(document).on('change','select[data-type=Size]', function(e) {
        initHalfOrder();
    });
    $('document').on('change','select[name=variation]',function() {
        populateIngredients();
    })

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
            alert("You have to be logged in and have enough points to buy this item!");
            return false;
        } else {

            var target = '/menu';

            $.mobile.changePage( target, {
                type: "post",
                data: {
                    general:        $( "form#order-form" ).serialize(),
                    ingredients:    $( "#ingredients form.order-ingredients" ).serialize(),
                    ingredients2:   $( "#ingredients2 form.order-ingredients" ).serialize()
                },
                changeHash: true,
                transition: 'none'
            });
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
        $(document).on('focus', '.searchIngredientsId', function( env ){
            $('html,body').animate({scrollTop: $(this).offset().top}, 800);
        });

    })
    .done(function(data) {

        var content = '<form class="order-ingredients" data-pizza="'+ pizzaNo +'">';
        content += '<ul data-role="listview" data-inset="true" data-divider-theme="c" class="ingredients-list">';

        var contentIncluded = '';
        var contentExtra    = '';

        if(data) {

            $.each(data, function( type, items ) {
                /**
                 * Included items comes as a single array
                 */
                if(type == 'included') {
                    contentIncluded += '<li data-role="list-divider">Included</li>';
                    contentIncluded += '<li>';
                    contentIncluded += '<fieldset data-role="controlgroup">';
                    $.each(items, function( key, item ) {
                        contentIncluded += '<input type="checkbox" name="ingredient[]" ';
                        contentIncluded += 'id="ingredient-'+item.ingredient_id+'" value="'+item.ingredient_id+'" ';
                            //if(item.status == 'DF') {
                        contentIncluded += 'checked data-default="1" ';
                            //} else {
                            //    content += 'data-default="1" ';
                            //}
                        contentIncluded += 'data-price="'+ item.price +'" data-theme="a" class="p-ingredient">';
                        contentIncluded += '<label for="ingredient-'+item.ingredient_id+'">'+item.ingredient_name+' ';
                        contentIncluded += '</label>';
//                        if(pizzaNo == 2) {
//                            content += ' <span class="price">$'+parseFloat(item.price)/2+'</span> </label>';
//                        } else {
//                            content += ' <span class="price">$'+item.price+'</span> </label>';
//                        }
                    });

                    contentIncluded += '</fieldset>';
                    contentIncluded += '</li>';
                }
                /**
                 * Extra ingredients comes grouped by subcategory
                 */
                else {
                    contentExtra += '<li data-role="list-divider">Extra</li>';
                    contentExtra += '<li data-role="list-divider" class="item-search-divider"><input type="search" name="searchIngredients" class="searchIngredientsId" value="" data-mini="true" data-theme="a" /></li>';

                    $.each(items, function( ecategory, ingredients ) {
                        contentExtra += '<li>';
                        //contentExtra += '<div data-role="collapsible" data-inset="false" data-theme="a" data-inset="false" data-content-theme="a">';
                        //contentExtra += '<h4 class="no-margin">'+ecategory+'</h4>';
                        contentExtra += '<fieldset data-role="controlgroup">';

//                        content += '<legend>'+ecategory+'</legend>';

                        $.each(ingredients, function( key, item ) {
//                            content += '<input type="checkbox" name="ingredient['+item.ingredient_id+']" id="ingredient['+item.ingredient_id+']" value="'+item.ingredient_id+'" data-theme="a">';
//                            content += '<label for="ingredient['+item.ingredient_id+']">'+item.ingredient_name+' <span class="price">$'+item.price+'</span> </label>';

                            contentExtra += '<input type="checkbox" name="ingredient[]" ';
                            contentExtra += 'id="ingredient-'+item.ingredient_id+'" value="'+item.ingredient_id+'" ';
                                //if(item.status == 'DF') {
                                //    content += 'checked data-default="1" ';
                                //} else {
                            contentExtra += 'data-default="0" ';
                            var str = item.ingredient_name;
                            contentExtra += 'data-value="' + str.replace(/"/gi, '\"') + '" ';
                                //}
                            contentExtra += 'data-price="'+ item.price +'" data-theme="a" class="p-ingredient">';
                            contentExtra += '<label for="ingredient-'+item.ingredient_id+'">'+item.ingredient_name+' ';
                            if(pizzaNo == 2) {
                                contentExtra += ' <span class="price">$'+parseFloat(item.price)/2+'</span>';
                            } else {
                                contentExtra += ' <span class="price i-full-price">$'+item.price+'</span>';
                                contentExtra += ' <span class="price i-half-price hide">$'+parseFloat(item.price)/2+'</span>';
                            }
                            contentExtra += '</label>';
                        });


                        contentExtra += '</fieldset>';
                        //contentExtra += '</div>';
                        contentExtra += '</li>';
                    });
                    
                }
            });
            content += contentIncluded;
            content += contentExtra;

        } else {
            content += '<li>This product doesn\'t have ingredients that you can modify</li>';
        }

        content += '</ul>';
        //content += '<p class="side-close-button"><a href="' + targetBlock +'" data-rel="close" data-role="button" class="panel-list btn btn-grey ui-link" data-inline="true" data-mini="true">Done</a></p>';
        content += '</form>';
       // content += '<div class="right-panel-footer"><a href="' + targetBlock +'" data-rel="close" data-role="button" class="panel-list btn btn-grey ui-link" data-inline="true" data-mini="true">Done</a></div>';

        $(targetBlock).html(content);
        $(targetBlock).after('<div id="doneBtnForRightPanelingredients'+pizzaNo+'" class="right-panel-footer ui-panel-closed"><p class="side-close-button"><a href="' + targetBlock +
                '" data-rel="close" data-role="button" class="panel-list btn btn-blue ui-link done-btn-right-panel"\n\
                     data-inline="true" data-mini="true">Done</a></p></div>');
        
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
    $('.control-1').show().addClass('animated bounce');

    var count = 1;

    $(document).on('change','.footer-change', function() {

       count++;

       var elem         = $(this).closest('.checkout-footer');
       var next         = elem.next();
       var totalAmount  = $('.order-total-price').data('value');

       /**
        * Payment Tab
        * Based on selected payment processor, check if minimum amount is meet
        */
       if($(this).attr('name') == 'payment') {

           // in case its online payment
            if($(this).val() == 3 || $(this).val() == 2) {
                if(totalAmount < rules.cc) {
                    alert('Minimum amount for Credit Card payments is $'+rules.cc);
                } else {
//                    elem.addClass('hide');
//                    elem.next().removeClass('hide');
                    elem.hide();//.addClass('animated flip');
                    elem.next().show().addClass('animated bounce');
                }
            }
            //paypal
            else if($(this).val() == 4) {
                if(totalAmount < rules.paypal) {
                    alert('Minimum amount for Paypal payments is $'+rules.paypal);
                } else {
                    elem.hide();//.addClass('animated flip');
                    elem.next().show().addClass('animated bounce');
                }
            } else {
                elem.hide();//.addClass('animated flip');
                elem.next().show().addClass('animated bounce');
            }
       }

       /**
        * Home/Pickup Delivery
        */
       else if($(this).attr('name') == 'delivery') {

           if($(this).val() == "D") {
               if(totalAmount < rules.min_order_amt) {
                   if(rules.order_less > 0){

                       var r=confirm("There is a $"+rules.order_less+" fee for order less than $"+rules.min_order_amt+". Click Ok for proceed or Cancel for keep shoping  .");
                       if (r==true)
                       {
                           if($('#has_discount').data('discountper') == 'no'){
                               defaultPrice('low_amount');
                           } else {
                               var discountpercet = $('#has_discount').data('discountper');
                               discountPrice(discountpercet,'low_amount');
                           }
                           r = null;
                           elem.hide();//.addClass('animated flip');
                           elem.next().show().addClass('animated bounce');
                       }
                       else
                       {
                           window.location.href = '//' + location.host + '/menu';
                       }
                   } else {
//                          alert('Minimum amount for Delivery is $'+rules.min_order_amt);
                          $( "#popupMinOrderValueNotMet" ).popup("open");
                   }

               } else {
                   elem.hide();//.addClass('animated flip');
                   elem.next().show().addClass('animated bounce');
               }
           } else {
               elem.hide();//.addClass('animated flip');
               elem.next().show().addClass('animated bounce');
           }
       }
       else if($(this).attr('name') == 'when') {
           if($(this).val() == 'ASAP') {
               submitOrder();
           } else {
               if(elem.next().length) {
                   elem.hide();//.addClass('animated flip');
                   elem.next().show().addClass('animated bounce');
               } else {
                   submitOrder();
               }
           }
       } else {
           if(elem.next().length) {
               elem.hide();//.addClass('animated flip');
               elem.next().show().addClass('animated bounce');
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
        elem.next().show().addClass('animated bounce');
        $('#dialog').dialog('close');
    });



    $(document).on('click tap', '.checkout-footer a', function() {

       var prev = $(this).closest('.checkout-footer').hide().prev();
       if(prev.length) {
           prev.show().addClass('animated bounce');
       } else {
           $.mobile.back();
       }
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
    $(document).off('click','#td-social a');

    function submitOrder() {
        if($('.later').is(':checked')){
            var date = $('#date').val();
            if(date){
                $('#form-checkout').submit();
            } else {
                $('#date-error').removeClass('hide');
            }
        } else {
            $('#form-checkout').submit();
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
        type = type || '';
        $('#has_discount').data('discountper', discountpercet);
        var total = $('.order-total-price').data('default');

        var couponPriceTotal = 0;
        $('.order-subtotal').find('.order-price').each(function(){
            if( $(this).css('display') != 'none' )
            {
                var subTotal = $(this).data('value');
                if( $(this).data('coupon') == 0 )
                {
                    total = total - subTotal;
                    couponPriceTotal = couponPriceTotal + subTotal;
                }
            }
        });
        var discount = applyPercentForPrice(discountpercet, total);
        var newTotal = prepareMathFloatValues(total, discount, '-');
        newTotal = prepareMathFloatValues(newTotal, couponPriceTotal);
       
        console.log(newTotal);
       
        // add holiday fee
        if( $('#holiday-fee').data('fee') != 'no' )
        {
            var feePrice = applyPercentForPrice( $('#holiday-fee').data('fee'), total );
            $('#fee-prince').html( '+' + feePrice );
            newTotal = prepareMathFloatValues(newTotal, feePrice);
        }

        if( type == 'low_amount' || type == 'online_low_amount' )
        {
            newTotal = prepareMathFloatValues(newTotal, rules.order_less);
            $('#low_order_fee').html( '+$' + rules.order_less );
            $('#low_order').removeClass('hide');
        }
        if( type == 'online' || type == 'online_low_amount' )
        {
            $('#icon-remove-coupon').removeClass('hide');
            $('#coupon-des').html('Online Order Discount');
        }

        $('.order-total-price').html( '$ ' + newTotal );
        $('.order-total-price').data( 'value', newTotal );
        $('#coupon-dis').html( '-$' + discount );
    }

    /**  Coupon  */
    $(document).on( 'change', '.choose-coupon', function(){
        var discountpercet = $(this).data('discount');
        if( discountpercet == 'other' )
        {
            $('#tr-coupon').removeClass('hide');
            $('#coupon-row').removeClass('hide');
            $('#coupon').prop('disabled', false);
            if( $('#radio-choice-v-2a').is(':checked') )
            {
                defaultPrice('low_amount');
            } else {
                defaultPrice();
            }
        } else {
            if( $('#coupon-des').html() != '' )
            {
                $('#icon-remove-coupon').click();
            }
            
            $('#tr-coupon').addClass('hide');
            $('#coupon').prop('disabled', true);
            $('#coupon-row').removeClass('hide');
            if( $('#radio-choice-v-2a').is(':checked') )
            {
                discountPrice(discountpercet, 'online_low_amount');
            } else {
                discountPrice(discountpercet, '');
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
            alert( "Request failed: " + textStatus );
        });
    });

    /** remove coupon  */
    $(document).on('click', '#icon-remove-coupon', function(){
        var didConfirm = confirm("Remove voucher ?");
        if( didConfirm == true )
        {
            $('#tr-coupon').removeClass('hide');
          //  $('.choose-coupon').prop('checked', false).checkboxradio('refresh');
          //  $('#other').prop('checked', true).checkboxradio('refresh');
            $('#coupon').prop('disabled', false);
            if($('#radio-choice-v-2a').is(':checked'))
            {
                defaultPrice('low_amount');
            } else {
                defaultPrice();
            }
            $('#icon-remove-coupon').addClass('hide');
            $('#coupon-des').html('');
            $('#coupon-dis').html('');
            $('#coupon').val('');
        }
    });
    /**  END Coupon  */

    $(document).on('click','.remove-order-item', function(e) {
        e.preventDefault();


        var didConfirm = confirm("Remove "+ $(this).data('title') +" from your order?");
        if (didConfirm == true) {

            var valueToSubstract = $(this).data('value');
            var hideItems        = $(this).data('id');

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
        }
    });


    /**
     * Social Locker
     */


    $(document).on('click', '#td-social a', function(){

        $('#show-social-loker').removeClass('hide');
    });

    /**
     * Need to check is present global vars
     * @type String|FACEBOOKAPPID
     */
    var FBAppID = '';
    if( typeof FACEBOOKAPPID == 'string' )
    {
        FBAppID = FACEBOOKAPPID;
    }
    /* use after for Facebook App ID - FBAppID */

    $("#social-loker").sociallocker({

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
    $('.onp-sociallocker-text').remove();

});


/***********************************************************************************************************************
 * Payment/Send Order
 * @url /payment
 **********************************************************************************************************************/
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
    
    verifyClean();
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
        window.location.href = '//' + location.host + '/security/logout/payment';
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
 * @url /security/reset
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
                alert( "Request failed: " + textStatus );
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
               window.location.href = '//' + location.host + '/security/login_page';
            });

            request.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
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
            alert( "Request failed: " + textStatus );
        });
    });

});



/***********************************************************************************************************************
 * Your Account
 * @url /security/login_page
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

}); // /security/login_page


/***********************************************************************************************************************
 * Your Account
 * @url /security/edit
 **********************************************************************************************************************/
$( document ).on('pageinit', "#page-edit", function() {

    /* Unbind everything */
    $(document).off('click','#save-edit');
    $(document).off('click','#verify-btn');
    
    verifyClean();
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

}); // /security/edit