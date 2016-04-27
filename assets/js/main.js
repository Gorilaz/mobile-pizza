/***********************************************************************************************************************
 * Global functions for multiple page
 * @url none
 **********************************************************************************************************************/

/*
 * Show fancy alert
 * 
 * @param {string} title Title of popup.
 * @param {string} description Description of popup.
 * @returns none
 */
function showAlert( title, description )
{
    if( !!title )
    {
        $('#alertDialog')
            .find('h1')
            .empty()
            .append(document.createTextNode(title));
    }
    else
    {
        $('#alertDialog')
            .find('h1')
            .empty()
            .append(document.createTextNode(document.title));
    }

    $('#alertDialog')
        .find('#popup-alert-text .content')
        .empty()
        .append(document.createTextNode(description));

    $('#alertDialog').popup('open');
}

/*
 * Show fancy confirm
 * 
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
        $('#confirmDialog')
            .find('h1')
            .empty()
            .append(document.createTextNode(title));
    }
    else
    {
        $('#confirmDialog')
            .find('h1')
            .empty()
            .append(document.createTextNode(document.title));
    }

    $('#confirmDialog')
        .find('#popup-confirm-text .content')
        .empty()
        .append(document.createTextNode(description));

    if( typeof(ok) === 'function' )
    {
        $('#popup-confirm-btn .ok')
            .off('click')
            .on('click', function() {
                ok();
            });
    }

    if( typeof(cancel) === 'function' )
    {
        $('#popup-confirm-btn .cancel')
            .off('click')
            .on('click', function() {
                cancel();
            });
    }

    $('#confirmDialog').popup('open');
}

/*
 * Select payment method and pay
 * 
 * @returns none
 */
function saveOrder()
{
    var pg = $('#pg').data('pg');

    if( pg === 'credit-card' && 
        $('#form-credit').valid() )
    {
        var request = $.ajax({
            data: $('#form-credit').serialize(), 
            dataType: 'json', 
            url: '//' + window.location.host + '/payment/Do_direct_payment', 
            type: 'POST'
        });

        request.done(function(data) {
            /* 
             * TODO: Need to review this strange code
             */
            if( !!data.error )
            {
                var message, messageIndex, 
                    errors = '';

                for( messageIndex in data.message )
                {
                    if( data.message.hasOwnProperty(messageIndex) )
                    {
                        message = data.message[messageIndex];

                        errors += ( !!errors ? ', ' + message : message );
                    }
                }

                $('#errors').empty();

                showAlert('', errors);
            }
            else
            {
                window.location.href = '//' + window.location.host + '/order/save_order/credit';
            }
        });

        request.fail(function(jqXHR, textStatus) {
            showAlert('', 'Request failed: ' + textStatus);
        });
    }
    else if( pg === 'cash' )
    {
        window.location.href = '//' + window.location.host + '/order/save_order/cash';
    }
    else if( pg === 'paypal' )
    {
        window.location.href = '//' + window.location.host + '/order/save_order/paypal';
    }
}
// saveOrder

/*
 * Ajax submit profile form and route
 * 
 * @returns none
 */
function saveForm(action)
{
    action = action || '';

    $.mobile.loading('show', {
        textVisible: false, 
        theme: 'a', 
        textonly: false, 
        html: ''
    });

    var request = $.ajax({
        data: $('#register_form').serialize(), 
        type: 'POST', 
        url: '//' + window.location.host + '/security/save'
    });

    request.done(function(data) {
        if( data === 'login' )
        {
            window.location.href = '//' + window.location.host + '/menu';
        }
        else if( data === 'order' )
        {
            if( action === 'saveOrder' )
            {
                saveOrder();
            }
            else
            {
                window.location.href = '//' + window.location.host + '/checkout';
            }
        }
        else
        {
            window.location.href = '//' + window.location.host + '/security-edit'
        }
    });

    request.fail(function(jqXHR, textStatus) {
        $.mobile.loading('hide');

        showAlert('', 'Request failed: ' + textStatus);
    });
}
// saveForm

/*
 * Sign In process by standart login form
 * 
 * @param object obj
 * @returns none
 */
function signInRequest(obj)
{
    $.mobile.loading('show', {
        textVisible: false, 
        theme: 'a', 
        textonly: false, 
        html: ''
    });

    var user = $('#user').val(), 
        pass = $('#pass').val(), 
        type = $(obj).attr('data-position'), 
        request = $.ajax({
            data: {
                user: user, 
                pass: pass
            }, 
            dataType: 'json', 
            type: 'POST', 
            url: '//' + window.location.host + '/security/login'
        });

    request.done(function(data) {
        if( data.login === 'true' )
        {
            if( type === 'order' )
            {
                window.location.href = '//' + window.location.host + '/payment';
            }
            else
            {
                window.location.href = '//' + window.location.host + '/menu';
            }
        }
        else if( data.login === 'required fields' )
        {
            $('#login-error').addClass('hide');
            $('#login-required').removeClass('hide');
        }
        else
        {
            $('#login-error').removeClass('hide');
            $('#login-required').addClass('hide');
        }

        $.mobile.loading('hide');
    });

    request.fail(function(jqXHR, textStatus) {
        $.mobile.loading('hide');

        showAlert('', 'Request failed: ' + textStatus);
    });
}
// signInRequest

/*
 * Apply for login form validation schem
 * 
 * @returns bool
 */
function prepareLoginFormValidation()
{
    if( typeof($.validator) === 'function' )
    {
        $('#form-singin').validate({
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
}
// prepareLoginFormValidation

/*
 * Apply for profile form validation schem
 * 
 * @returns bool
 */
function prepareProfileFormValidation()
{
    if( typeof($.validator) === 'function' )
    {
        /*
         * Custom validate function for check mobile number
         */
        $.validator.addMethod('smsVerification', function(value, element) {
            var sms = $('#sms').data('sms');

            if( sms === 'enable' )
            {
                if( $('#form_mobile').data('current') !== $('#form_mobile').val() )
                {
                    $('#verify-div').show();

                    return false;
                }
                else
                {
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
                first_name: 'required', 
                last_name: 'required', 
                address: 'required', 
                email: {
                    required: true, 
                    email: true, 
                    remote: '//' + window.location.host + '/security/checkUniqueEmail'
                }, 
                password: {
                    required: true, 
                    minlength: 5
                }, 
                conf_password: {
                    required: true, 
                    equalTo: '#form_password', 
                    minlength: 5
                }, 
                suburb: 'required', 
                state: 'required', 
                mobile: {
                    required: true, 
                    maxlength: 10, 
                    minlength: 10, 
                    digits: true, 
                    remote: '//' + window.location.host + '/security/checkUniqueMobile', 
                    smsVerification: true
                }
            }, 
            messages: {
                mobile: {
                    remote: 'The mobile number already used'
                }
            }
        });

        return true;
    }

    return false;
}
// prepareProfileFormValidation

/*
 * Function for sent verify code
 * @returns none
 */
function changeMobile()
{
    var fname   = $('#form_firstname').val(), 
        lname   = $('#form_lastname').val(), 
        email   = $('#email').val(), 
        mobile  = $('#form_mobile').val(), 
        request = $.ajax({
            data: {
                mobile : mobile, 
                email : email, 
                fname : fname, 
                lname : lname
            }, 
            type: 'POST', 
            url: '//' + window.location.host + '/checkout/verifyMobile'
        });

    request.done(function(data) {
        $('#form_mobile').attr('readonly', 'readonly');

        $('#popupDialog').popup('open');

        $('#sms-code').data('final', 'yes');

        $('#sms-code').show();

        $('#verify-btn').find('.ui-btn-text').html($('#verify-btn').data('titlefinal'));
    });

    request.fail(function(jqXHR, textStatus) {
        showAlert('', 'Request failed: ' + textStatus);
    });
}
// changeMobile

/*
 * Clear profile template to default point
 * 
 * @returns true
 */
function verifyClean()
{
    $('#form_mobile').removeAttr('readonly');

    if( $('#form_mobile').data('current') === '' )
    {
        $('#verify-div').show();
    }
    else
    {
        $('#verify-div').hide();
    }

    $('#sms-error-label').hide();

    $('#sms-code').hide();

    $('#sms-code').data('final', 'no');

    $('#sms-code').val('');

    $('#verify-btn').find('.ui-btn-text').html($('#verify-btn').data('titlestart'));

    return true;
}
// verifyClean

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

    if( final === 'no' )
    {
        if( $('#form_mobile').val() !== '' )
        {
            changeMobile();
        }
    }
    else
    {
        var code = $('#sms-code').val();

        if( code !== '' )
        {
            var request = $.ajax({
                data: {
                    code: code
                }, 
                dataType: 'json', 
                type: 'POST', 
                url: '//' + window.location.host + '/checkout/verifyCode'
            });

            request.done(function(data) {
                if( data.valid )
                {
                    verifyClean();

                    $('#form_mobile').data('current', $('#form_mobile').val());

                    $('#register_form').valid();
                }
                else
                {
                    $('#sms-error-label').show();
                }
            });

            request.fail(function(jqXHR, textStatus) {
                showAlert('', 'Request failed: ' + textStatus);
            });
        }
        else
        {
            $('#sms-error-label').show();
        }
    }
}
// verifyMobileBySMS

helpIntervalForFooterLine = null;

/*
 * Show / Hide help box for footer buttons
 * 
 * @param jquery obj lineObj
 * @returns true - show or false - hide
 */
function manageHelpFooterLine(lineObj)
{
    if( lineObj )
    {
        if( lineObj.data('title') )
        {
            if( helpIntervalForFooterLine !== null )
            {
                window.clearInterval(helpIntervalForFooterLine);
            }

            $('#id-footer-help-line')
                .empty()
                .append(document.createTextNode(lineObj.data('title')))
                .show()
                .stop()
                .animate({ bottom: 60 }, 500);

            helpIntervalForFooterLine = window.setInterval(function() {
                $('#id-footer-help-line')
                    .stop()
                    .animate({ bottom: 0 }, 200, function() {
                        $('#id-footer-help-line').hide();
                    });
            }, 2500);

            return true;
        }
    }

    if( helpIntervalForFooterLine !== null )
    {
        window.clearInterval(helpIntervalForFooterLine);

        helpIntervalForFooterLine = null;
    }

    $('#id-footer-help-line')
        .stop()
        .animate({ bottom: 0 }, 200, function() {
            $('#id-footer-help-line').hide();
        });

    return false;
}
// manageHelpFooterLine

$(document)
    .off('pageinit', '#page-home')
    /***********************************************************************************************************************
     * Events used on product page
     * @url /home
     **********************************************************************************************************************/
    .on('pageinit', '#page-home', function() {
        // if( referal )
        // {
        //     $('#popup-refer2').popup('open');
        // }
    })
    .off('pageinit', '#page-product')
    /***********************************************************************************************************************
     * Events used on product page
     * @url /product/id
     **********************************************************************************************************************/
    .on('pageinit', '#page-product', function() {
        /**
         * Resize ingredients panels
         * 
         * @param string id
         * @returns none
         */
        function resizeIngredients(id)
        {
            id = id || 'ingredients';

            $('#' + id + ' .ingredients-list:not(.fixed)')
                .outerHeight($(window).outerHeight() - 
                    $('#' + id + ' .ingredients-list.fixed').outerHeight() - 
                    $('#' + id).next('[id*="doneBtnForRightPanelingredients"]').find('.side-close-button').height());
        }
        // resizeIngredients

        $(window)
            .off('resize')
            .on('resize', function() {
                if( $('#ingredients').is(':visible') )
                {
                    resizeIngredients();
                }

                if( $('#ingredients2').is(':visible') )
                {
                    resizeIngredients('ingredients2');
                }

                if( ( $(window).height() > window.before_resize ) && 
                    $(document.activeElement).is('.searchIngredientsId') )
                {
                    var event; // The custom event that will be created

                    if( document.createEvent )
                    {
                        event = document.createEvent('HTMLEvents');

                        event.initEvent('hidekeyboard', true, true);

                        event.eventName = 'HTMLEvents';

                        document.dispatchEvent(event);
                    }
                    else
                    {
                        event = document.createEventObject();

                        event.eventType = 'hidekeyboard';
                        event.eventName = 'HTMLEvents';

                        document.fireEvent('on' + event.eventType, event);
                    }
                }

                window.before_resize = $(window).height();
            });

        window.before_resize = $(window).height();

        /**
         * Show/Hide footer panel for Done button
         * 
         * @returns true
         */
        function manageDoneButtonForRightPanel()
        {
            var position = 0;

            for( position = 1; position <= 2; position++ )
            {
                if( $('#doneBtnForRightPanelingredients' + position).hasClass('ui-panel-closed') )
                {
                    $('#doneBtnForRightPanelingredients' + position).removeClass('ui-panel-closed');
                    $('#doneBtnForRightPanelingredients' + position).addClass('ui-panel-open');

                    $('#doneBtnForRightPanelingredients' + position).show();
                }
                else if( $('#doneBtnForRightPanelingredients' + position).hasClass('ui-panel-open') )
                {
                    $('#doneBtnForRightPanelingredients' + position).removeClass('ui-panel-open');
                    $('#doneBtnForRightPanelingredients' + position).addClass('ui-panel-closed');

                    $('#doneBtnForRightPanelingredients' + position).hide();
                }
            }

            return true;
        }
        // manageDoneButtonForRightPanel

        /**
         * Deal with half / half pizza
         **/
        function initHalfOrder()
        {
            var sizeOption = $('select[data-type="Size"]').find('option').filter(':selected'), 
                halfGroup = sizeOption.data('half-group'), 
                halfPrice = sizeOption.data('half-fee');

            if( typeof(halfGroup) === 'undefined' )
            {
                $('select[name="halfPizza"]').val('').trigger('change');

                $('input[name="half-group-id"]').val('');

                $('input[name="half-fee"]').val('');

                $('input[name="isHalf"]').val('0');

                $('.halfHolder').addClass('hide');
            }
            else
            {
                $('input[name="half-group-id"]').val(halfGroup);

                $('input[name="half-fee"]').val(halfPrice);

                $('.halfHolder').removeClass('hide');

                $('.halfHolder h2 small')
                    .empty()
                    .append(document.createTextNode('$' + halfPrice + ' fee applies'));

                var halfPizzaSelector = $('<select>')
                    .addClass('halfSelector')
                    .html((function() {
                        var options = new Array;

                        options.push(
                            $('<option>')
                                .append(document.createTextNode('No Half Pizza'))
                                .attr({
                                    'value': ''
                                })
                        );

                        if( typeof(halfs[halfGroup]) !== 'undefined' )
                        {
                            var halfItem, halfItemIndex, halfItems = halfs[halfGroup], 
                                halfItemPrice;

                            for( halfItemIndex in halfItems )
                            {
                                if( halfItems.hasOwnProperty(halfItemIndex) )
                                {
                                    halfItem = halfItems[halfItemIndex];

                                    halfItemPrice = ( parseFloat(halfItem.product_price) + parseFloat(halfItem.variation_price) );

                                    options.push(
                                        $('<option>')
                                            .append(document.createTextNode(halfItem.product_name + ' - ($' + ( halfItemPrice / 2 ) + ')'))
                                            .attr({
                                                'data-price': halfItemPrice, 
                                                'value': halfItem.variation_id
                                            })
                                    );
                                }
                            }
                        }

                        return options;
                    })())
                    .attr({
                        'data-mini': 'true', 
                        'data-type': 'halfoption', 
                        'data-variation': halfGroup, 
                        'id': 'halfPizzaSelector', 
                        'name': 'halfPizza'
                    });

                $('.halfHolder .ui-block-b').html(halfPizzaSelector);

                $('#halfPizzaSelector').val('').trigger('change');

                $('#halfPizzaSelector').selectmenu();
            }
        }

        /**
         * Calculate order total
         * - price can be in dollars - for normal order
         * or in points - for loyalty program
         */
        function calculateOrderPrice()
        {
            if( parseFloat($('#buyWithPoints').val()) === 1 )
            {
                /* Payment via loyalty points */
                var initialPrice = parseFloat($('#p-footer').data('price')), 
                    quantity = parseFloat($('#p-quantity').val()), 
                    total = ( initialPrice * quantity );

                $('#p-total')
                    .empty()
                    .append(document.createTextNode(total));
            }
            else
            {
                /* Normal Payment */
                var initialPrice = parseFloat($('#p-footer').data('price')), 
                    quantity = parseFloat($('#p-quantity').val()), 
                    calculate, calculateIndex, calculates = $('.calculate').get(), 
                    p_ingredient, p_ingredientIndex, p_ingredients = $('.p-ingredient').get();

                for( calculateIndex in calculates )
                {
                    if( calculates.hasOwnProperty(calculateIndex) )
                    {
                        calculate = calculates[calculateIndex];

                        var calculate_price = $(calculate).find('option').filter(':selected').data('price');

                        if( typeof(calculate_price) !== 'undefined' && !!calculate_price )
                        {
                            initialPrice += parseFloat(calculate_price);
                        }
                    }
                }

                for( p_ingredientIndex in p_ingredients )
                {
                    if( p_ingredients.hasOwnProperty(p_ingredientIndex) )
                    {
                        p_ingredient = p_ingredients[p_ingredientIndex];

                        var value = parseFloat($(p_ingredient).data('price'));

                        if( parseFloat($(p_ingredient).data('default')) === 1 )
                        {
                            // 
                        }
                        else
                        {
                            if( $(p_ingredient).is(':checked') )
                            {
                                initialPrice += value;
                            }
                        }
                    }
                }

                /* if its half order */
                var halfVariationPrice = parseFloat($('.halfSelector').find('option').filter(':selected').data('price'));

                if( halfVariationPrice )
                {
                    initialPrice += halfVariationPrice;
                    initialPrice = ( initialPrice / 2 );
                    initialPrice += parseFloat($('select[data-type="Size"]').find('option').filter(':selected').data('half-fee'));

                    // Set all ingredients for 2nd pizza visible
                    if( $('#ingredients').find('.i-half-price:first').hasClass('hide') )
                    {
                        $('#ingredients').find('.i-full-price').addClass('hide');
                        $('#ingredients').find('.i-half-price').removeClass('hide');
                    }
                }
                else
                {
                    if( $('#ingredients').find('.i-full-price:first').hasClass('hide') )
                    {
                        $('#ingredients').find('.i-full-price').removeClass('hide');
                        $('#ingredients').find('.i-half-price').addClass('hide');
                    }
                }

                var total = ( initialPrice * quantity );

                $('#p-total')
                    .empty()
                    .append(document.createTextNode(total));
            }
        }

        /**
         * Get Ingredients based on Variation
         * Used on product details page
         */
        function populateIngredients(variationId, pizzaNo)
        {
            if( typeof(variationId) === 'undefined' )
            {
                variationId = $('select[name="variation"]:last').val();
            }

            if( typeof(pizzaNo) === 'undefined' || 
                parseFloat(pizzaNo) === 1 )
            {
                pizzaNo = 1;
                var targetBlock = '#ingredients';
            }
            else
            {
                pizzaNo = 2;
                var targetBlock = '#ingredients2';
            }

            $.ajax({
                context: document.body, 
                url: '/get/ingredients/' + variationId
            })
            .complete(function() {
                $(document)
                    .off('keyup', '.searchIngredientsId')
                    /* for symbol keys */
                    .on('keyup', '.searchIngredientsId', function() {
                        var self = this;

                        searchItemsForRightPanel($(self).val());
                    })
                    .off('keypress', '.searchIngredientsId')
                    /* disable enter press */
                    .on('keypress', '.searchIngredientsId', function(event) {
                        if( event.keyCode === 13 )
                        {
                            event.preventDefault();

                            return false;
                        }
                    })
                    .off('click', '.ui-input-clear')
                    /* clear button in search field */
                    .on('click', '.ui-input-clear', function() {
                        var self = this;

                        searchItemsForRightPanel($(self).val());
                    })
                    .off('focus', '#ingredients .searchIngredientsId')
                    .on('focus', '#ingredients .searchIngredientsId', function() {
                        $('#ingredients')
                            .find('.ingredients-list.fixed')
                            .find('.included-header, .included-content')
                            .slideUp(800, function() {
                                resizeIngredients();
                            });
                    })
                    .off('blur', '#ingredients .searchIngredientsId')
                    .on('blur', '#ingredients .searchIngredientsId', function() {
                        $('#ingredients')
                            .find('.ingredients-list.fixed')
                            .find('.included-header, .included-content')
                            .slideDown(800, function() {
                                resizeIngredients();
                            });
                    })
                    .off('focus', '#ingredients2 .searchIngredientsId')
                    .on('focus', '#ingredients2 .searchIngredientsId', function() {
                        $('#ingredients2')
                            .find('.ingredients-list.fixed')
                            .find('.included-header, .included-content')
                            .slideUp(800, function() {
                                resizeIngredients('ingredients2');
                            });
                    })
                    .off('blur', '#ingredients2 .searchIngredientsId')
                    .on('blur', '#ingredients2 .searchIngredientsId', function() {
                        $('#ingredients2')
                            .find('.ingredients-list.fixed')
                            .find('.included-header, .included-content')
                            .slideDown(800, function() {
                                resizeIngredients('ingredients2');
                            });
                    })
                    .off('hidekeyboard')
                    .on('hidekeyboard', function() {
                        if( $(document.activeElement).is('.searchIngredientsId') )
                        {
                            $(document.activeElement).trigger('blur');
                        }
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
                            })
                    )
                    .append(
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
                    }), 
                    contentFixed = new Array, 
                    contentExtra = new Array;

                if( !!data )
                {
                    if( 'included' in data )
                    {
                        /**
                         * Included items comes as a single array
                         */
                        var items = data['included'], 
                            type = 'included';

                        contentFixed.push(
                            $('<li>')
                                .addClass('included-header')
                                .append(document.createTextNode('Included'))
                                .attr({
                                    'data-role': 'list-divider'
                                })
                        );

                        contentFixed.push(
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
                                                                })
                                                        );

                                                        returnedBy.push(
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
                    }

                    if( 'extra' in data )
                    {
                        /**
                         * Extra ingredients comes grouped by subcategory
                         */
                        var items = data['extra'], 
                            type = 'extra', 
                            ecategory, ingredients;

                        contentFixed.push(
                            $('<li>')
                                .append(document.createTextNode('Extra'))
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

                    content
                        .find('.ingredients-list.fixed')
                        .append(contentFixed);

                    content
                        .find('.ingredients-list:not(.fixed)')
                        .append(contentExtra);
                }
                else
                {
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

                if( data )
                {
                    $('.ingredientsHolder').removeClass('hide');
                }

                /**
                 * Refresh the layout - dynamic content injected
                 */
                $('.ingredients-list')
                    .listview()
                    .listview('refresh');

                $('.ingredients-list')
                    .trigger('create');
            });
        }

        /**
         * Hide or show list elements for search string
         * 
         * @param string searchString
         * @returns true
         */
        function searchItemsForRightPanel(searchString)
        {
            $('.ui-checkbox').show();

            if( typeof(searchString) !== 'undefined' && 
                !!searchString )
            {
                $('.order-ingredients input[type=checkbox]').each(function() {
                    var contentString = $(this).attr('data-value');

                    if( typeof(contentString) !== 'undefined' )
                    {
                        var checked = $(this).parent().find('.ui-icon-checkbox-on');

                        if( ( contentString.toLowerCase().indexOf(searchString.toLowerCase()) < 0 ) && 
                            $(checked).length == 0 )
                        {
                            $(this).parent().hide();
                        }
                    }
                });
            }

            return true;
        }

        $(document)
            .off('change', '.calculate, #p-quantity, #half-order, .p-ingredient')
            .on('change', '.calculate, #p-quantity, #half-order, .p-ingredient', function() {
                calculateOrderPrice();
            })
            .off('change', 'select[data-type="Size"]')
            .on('change', 'select[data-type="Size"]', function() {
                initHalfOrder();
            })
            .off('change', 'select[name="variation"]')
            .on('change', 'select[name="variation"]', function() {
                populateIngredients();
            })
            .off('change', '#halfPizzaSelector')
            /**
             * Handle half pizza change
             * Populates ingredients for half pizza
             */
            .on('change', '#halfPizzaSelector', function() {
                var self = this, 
                    half_pizza_group_id = parseFloat($(self).data('variation')), 
                    variation_id = parseFloat($(self).val()), 
                    halfPizza = halfs[half_pizza_group_id][variation_id];

                if( !!halfPizza )
                {
                    var halfPizzaBlock = [
                        $('<div>')
                            .addClass('single-top')
                            .append(
                                $('<div>')
                                    .addClass('single-content get-space')
                                    .append(
                                        $('<h1>')
                                            .append(document.createTextNode(halfPizza.product_name))
                                    )
                                    .append(
                                        $('<div>')
                                            .addClass('single-description')
                                            .append(document.createTextNode(halfPizza.description))
                                    )
                            )
                            .attr((function() {
                                var attributes = new Object;

                                if( halfPizza.product_image !== '' )
                                {
                                    attributes.style = 'background-image: url(\'' + desktopUrl + 'templates/demotest/uploads/products/thumb/' + halfPizza.product_image + '\')';
                                }

                                return attributes;
                            })()), 
                        $('<div>')
                            .addClass('single-options get-space clear-heights')
                            .append(
                                $('<div>')
                                    .addClass('row ingredientsHolder')
                                    .append(
                                        $('<a>')
                                            .addClass('ui-link add-modify-btn')
                                            .append(
                                                $('<i>')
                                                    .addClass('icon-chevron-sign-right')
                                            )
                                            .append(document.createTextNode(' Add/Modify Ingredients'))
                                            .attr({ 'href': '#ingredients2' })
                                    )
                            )
                    ];

                    $('.halfPizzaBlock').html(halfPizzaBlock);

                    $('input[name="isHalf"]').val('1');

                    populateIngredients(variation_id, 2);

                    calculateOrderPrice();
                }
                else
                {
                    $('.halfPizzaBlock').empty();

                    $('#ingredients2').empty();

                    $('input[name="isHalf"]').val('0');

                    calculateOrderPrice();
                }
            })
            .off('click', '.submit-order')
            /**
             * Send order
             * If is half, another popup is shown for selecting the 2nd pizza
             * else, user is redirected to cart/checkout
             */
            .on('click', '.submit-order', function(event) {
                event.preventDefault();

                /* In case is a loyalty add to cart, check if user is logged in and that he
                 * has enough points
                 */
                if( parseFloat($('#buyWithPoints').val()) === 1 && 
                    ( parseFloat($('#buyWithPoints').data('user')) !== 1 || 
                        parseFloat($('#buyWithPoints').data('points')) < parseFloat($('#p-total').html()) ) )
                {
                    showAlert('', 'You have to be logged in and have enough points to buy this item!');

                    return false;
                }
                else
                {
                    var form = $('<form>')
                        .append(
                            $('<input>')
                                .attr({
                                    'name': 'general', 
                                    'type': 'hidden', 
                                    'value': $('form#order-form').serialize()
                                })
                        )
                        .append(
                            $('<input>')
                                .attr({
                                    'name': 'ingredients', 
                                    'type': 'hidden', 
                                    'value': $('#ingredients form.order-ingredients').serialize()
                                })
                        )
                        .append(
                            $('<input>')
                                .attr({
                                    'name': 'ingredients2', 
                                    'type': 'hidden', 
                                    'value': $('#ingredients2 form.order-ingredients').serialize()
                                })
                        )
                        .attr({
                            'action': '/menu', 
                            'method': 'post'
                        });

                    $(form).trigger('submit');
                }
            })
            .off('click', '.ui-header, .ui-content, .ui-footer')
            .on('click', '.ui-header, .ui-content, .ui-footer', function() {
                $('#ingredients').panel('close');
            })
            .off('panelclose', '#ingredients')
            .on('panelclose', '#ingredients', function() {
                manageDoneButtonForRightPanel();

                $('[data-role="footer"]').fixedtoolbar({ tapToggle: true });
            })
            .off('panelopen', '#ingredients')
            .on('panelopen', '#ingredients', function() {
                manageDoneButtonForRightPanel();

                resizeIngredients();

                $('[data-role="footer"]').fixedtoolbar({ tapToggle: false });
            })
            .off('panelclose', '#ingredients2')
            .on('panelclose', '#ingredients2', function() {
                manageDoneButtonForRightPanel();

                $('[data-role="footer"]').fixedtoolbar({ tapToggle: true });
            })
            .off('panelopen', '#ingredients2')
            .on('panelopen', '#ingredients2', function() {
                manageDoneButtonForRightPanel();

                resizeIngredients('ingredients2');

                $('[data-role="footer"]').fixedtoolbar({ tapToggle: false });
            });

        /* Trigger actions on page init */
        calculateOrderPrice();

        if( parseFloat($('#hasIngredients').val()) === 1 )
        {
            populateIngredients();
        }

        if( parseFloat($('#hasHalf').val()) === 1 )
        {
            initHalfOrder();
        }
    })
    .off('pageinit', '#page-menu')
    /***********************************************************************************************************************
     * Events used on menu page
     * @url /menu
     **********************************************************************************************************************/
    .on('pageinit', '#page-menu', function() {
        $(document)
            .off('click', '#click-checkout')
            .on('click', '#click-checkout', function() {
                window.location.href = '//' + window.location.host + '/checkout';
            });
    })
    .off('pageinit', '#page-checkout')
    /***********************************************************************************************************************
     * Events used on page review page
     * @url /checkout
     **********************************************************************************************************************/
    .on('pageinit', '#page-checkout', function() {
        var firstPanel = $('.control-1');

        firstPanel
            .show()
            .fadeOut(250)
            .fadeIn(250);

        manageHelpFooterLine(firstPanel);

        $('#form-checkout').trigger('reset');

        $(document)
            .off('click', '#id-footer-help-line')
            .on('click', '#id-footer-help-line', function() {
                manageHelpFooterLine(null);
            })
            .off('change', '.footer-change')
            .on('change', '.footer-change', function() {
                var self = this, 
                    elem = $(self).closest('.checkout-footer'), 
                    next = elem.next(), 
                    totalAmount = parseFloat($('.order-total-price').data('value'));

                if( $(self).attr('name') === 'payment' )
                {
                    /**
                     * Payment Tab
                     * Based on selected payment processor, check if minimum amount is meet
                     */
                    if( parseFloat($(self).val()) === 3 || 
                        parseFloat($(self).val()) === 2 )
                    {
                        // in case its online payment
                        if( totalAmount < parseFloat(rules.cc) )
                        {
                            showAlert('', 'Minimum amount for Credit Card payments is $' + rules.cc);
                        }
                        else
                        {
                            elem.hide();

                            next
                                .show()
                                .fadeOut(250)
                                .fadeIn(250);

                            manageHelpFooterLine(next);
                        }
                    }
                    else if( parseFloat($(self).val()) === 4 )
                    {
                        //paypal
                        if( totalAmount < parseFloat(rules.paypal) )
                        {
                            showAlert('', 'Minimum amount for Paypal payments is $' + rules.paypal);
                        }
                        else
                        {
                            elem.hide();

                            next
                                .show()
                                .fadeOut(250)
                                .fadeIn(250);

                            manageHelpFooterLine(next);
                        }
                    }
                    else
                    {
                        elem.hide();

                        next
                            .show()
                            .fadeOut(250)
                            .fadeIn(250);

                        manageHelpFooterLine(next);
                    }
                }
                else if( $(self).attr('name') === 'delivery' )
                {
                    /**
                     * Home/Pickup Delivery
                     */
                    var a_process_for_preparing = $(self).val(), 
                        date = $('#date').val(), 
                        time, timeIndex;

                    $('#time')
                        .empty()
                        .append(
                            $('<option>')
                                .append(document.createTextNode('Select Time'))
                                .attr({
                                    'selected': 'selected', 
                                    'value': ''
                                })
                        );

                    for( timeIndex in schedule[date][a_process_for_preparing] )
                    {
                        if( schedule[date][a_process_for_preparing].hasOwnProperty(timeIndex) )
                        {
                            time = schedule[date][a_process_for_preparing][timeIndex];

                            $('#time')
                                .append(
                                    $('<option>')
                                        .append(document.createTextNode(time))
                                        .attr({
                                            'value': timeIndex
                                        })
                                );
                        }
                    }

                    $('#time').selectmenu('refresh');

                    if( !Object.keys(schedule[date][a_process_for_preparing]).length )
                    {
                        manageHelpFooterLine($('<div>').data('title', 'Sorry, the shop is closed for today'));

                        return undefined;
                    }

                    if( a_process_for_preparing === 'D' )
                    {
                        if( totalAmount < parseFloat(rules.min_order_amt) )
                        {
                            if( parseFloat(rules.order_less) > 0 )
                            {
                                showConfirm('', 'There is a $' + rules.order_less + ' fee for order less than $' + rules.min_order_amt + '. Click Ok for proceed or Cancel for keep shoping.', function() {
                                    var discountpercet = $('#has_discount').attr('data-discountper');

                                    if( discountpercet === 'no' )
                                    {
                                        defaultPrice('low_amount');
                                    }
                                    else
                                    {
                                        discountPrice(discountpercet, 'low_amount');
                                    }

                                    elem.hide();

                                    next
                                        .show()
                                        .fadeOut(250)
                                        .fadeIn(250);

                                    manageHelpFooterLine(next);
                                }, function() {
                                    window.location.href = '//' + location.host + '/menu';
                                });
                            }
                            else
                            {
                                elem.hide();

                                next
                                    .show()
                                    .fadeOut(250)
                                    .fadeIn(250);

                                manageHelpFooterLine(next);
                            }
                        }
                        else
                        {
                            elem.hide();

                            next
                                .show()
                                .fadeOut(250)
                                .fadeIn(250);

                            manageHelpFooterLine(next);
                        }
                    }
                    else if( a_process_for_preparing === 'P' )
                    {
                        var current_date = $('#date').val(), 
                            first_time = Object.keys(schedule[date][a_process_for_preparing])[0], 
                            first_datetime = new Date(current_date + ' ' + first_time);

                        if( Date.now() < first_datetime.getTime() )
                        {
                            manageHelpFooterLine($('<div>').data('title', 'Sorry, this option will be available in the ' + schedule[date][a_process_for_preparing][first_time]));

                            return false;
                        }
                        else
                        {
                            elem.hide();

                            next
                                .show()
                                .fadeOut(250)
                                .fadeIn(250);

                            manageHelpFooterLine(next);
                        }
                    }
                }
                else if( $(self).attr('name') === 'when' )
                {
                    if( $(self).val() === 'ASAP' )
                    {
                        submitOrder();
                    }
                    else
                    {
                        if( !!$(next).length )
                        {
                            elem.hide();

                            next
                                .show()
                                .fadeOut(250)
                                .fadeIn(250);

                            manageHelpFooterLine(next);
                        }
                        else
                        {
                            submitOrder();
                        }
                    }
                }
                else
                {
                    if( !!$(next).length )
                    {
                        elem.hide();

                        next
                            .show()
                            .fadeOut(250)
                            .fadeIn(250);

                        manageHelpFooterLine(next);
                    }
                    else
                    {
                        submitOrder();
                    }
                }
            })
            .off('click tap', '.checkout-footer a')
            .on('click tap', '.checkout-footer a', function() {
                var self = this, 
                    prev = $(self).closest('.checkout-footer').hide().prev();

                manageHelpFooterLine(prev);

                if( !!$(prev).length )
                {
                    prev
                        .show()
                        .fadeOut(250)
                        .fadeIn(250);
                }
                else
                {
                    window.location.href = '//' + window.location.host+ '/menu';
                }

                return false; // fix double event
            })
            .off('click', '#keep-shoping')
            /**
             * Click Keep Shoping
             */
            .on('click', '#keep-shoping', function() {
                window.location.href = '//' + location.host + '/menu';
            })
            .off('click', '#proceed')
            /**
             * Click Procced
             */
            .on('click', '#proceed', function() {
                var discountpercet = $('#has_discount').attr('data-discountper');

                if( discountpercet === 'no' )
                {
                    defaultPrice('low_amount');
                }
                else
                {
                    discountPrice(discountpercet, 'low_amount');
                }

                var elem = $('#dialog').data('elem');

                elem.hide();

                elem
                    .next()
                    .show()
                    .fadeOut(250)
                    .fadeIn(250);

                $('#dialog').dialog('close');
            })
            .off('change', '.choose-coupon')
            /**  Coupon  */
            .on('change', '.choose-coupon', function() {
                var self = this, 
                    discountpercet = $(self).data('discount');

                if( discountpercet === 'other' )
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
                        $(self).attr('to-applying', 'to-applying');

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
            })
            .off('click', '#voucher')
            /** other coupon */
            .on('click', '#voucher', function() {
                var coupon = $('#coupon').val(), 
                    request = $.ajax({
                        data: {
                            coupon: coupon
                        }, 
                        dataType: 'json', 
                        type: 'POST', 
                        url: '//' + window.location.host + '/checkout/getCoupons'
                    });

                request.done(function(data) {
                    if( data === 'false' )
                    {
                        defaultPrice();

                        $('#icon-remove-coupon').addClass('hide');

                        $('#coupon-des').empty();
                        $('#coupon-dis').empty();
                    }
                    else
                    {
                        $('#coupon-des')
                            .empty()
                            .append(document.createTextNode(data.coupondescription));

                        var discountpercet = parseInt(data.discountper, 10);

                        discountPrice(discountpercet);

                        $('#icon-remove-coupon').removeClass('hide');

                        $('#coupon-row').removeClass('hide');
                        
                        $('#tr-coupon').addClass('hide');

                        $('#other').val(data.id);
                    }
                });

                request.fail(function(jqXHR, textStatus) {
                    showAlert('', 'Request failed: ' + textStatus);
                });
            })
            .off('click', '#icon-remove-coupon')
            /** remove coupon  */
            .on('click', '#icon-remove-coupon', function() {
                showConfirm('', 'Remove voucher?', function() {
                    if( !!$('[to-applying="to-applying"]').length )
                    {
                        var self = $('[to-applying="to-applying"]'), 
                            discountpercet = $(self).data('discount');

                        $(self).removeAttr('to-applying');

                        $('#coupon').prop('disabled', true).val('');

                        $('#tr-coupon').addClass('hide');

                        $('#icon-remove-coupon').addClass('hide');

                        $('#coupon-row').removeClass('hide');

                        $('#coupon-des').empty();

                        if( $('#radio-choice-v-2a').is(':checked') )
                        {
                            discountPrice(discountpercet, 'online_low_amount');
                        }
                        else
                        {
                            discountPrice(discountpercet);
                        }
                    }
                    else
                    {
                        $('#coupon').prop('disabled', false).val('');

                        $('#tr-coupon').removeClass('hide');

                        $('#icon-remove-coupon').addClass('hide');

                        $('#coupon-des').empty();
                        $('#coupon-dis').empty();

                        if( $('#radio-choice-v-2a').is(':checked') )
                        {
                            defaultPrice('low_amount');
                        }
                        else
                        {
                            defaultPrice();
                        }
                    }

                    $('#other').val('');
                }, function() {
                    var self = $('[to-applying="to-applying"]');

                    $(self).removeAttr('to-applying');
                });
            })
            .off('click', '.remove-order-item')
            .on('click', '.remove-order-item', function(event) {
                event.preventDefault();

                var self = this;

                showConfirm('', 'Remove ' + $(self).data('title') + ' from your order?', function() {
                    var hideItems = $(self).data('id'), 
                        totalItem = $('.order-total-price'), 
                        newTotal = ( parseFloat(totalItem.data('default')) - parseFloat($(self).data('value')) );

                    $('.item-' + hideItems).hide();

                    totalItem
                        .data({
                            'default': newTotal, 
                            'value': newTotal
                        })
                        .empty()
                        .append(document.createTextNode('$ ' + newTotal));

                    var discountpercet = $('#has_discount').attr('data-discountper');

                    if( discountpercet === 'no' )
                    {
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
                        if( $('#radio-choice-v-2a').is(':checked') )
                        {
                            discountPrice(discountpercet, 'low_amount');
                        }
                        else
                        {
                            discountPrice(discountpercet, 'online');
                        }
                    }

                    /**
                     * Ajax call to remove 
                     * the item from session
                     */
                    var request = $.ajax({
                        context: document.body, 
                        url: '/remove/' + hideItems
                    });

                    request.done(function(data) {
                        // done!
                    });

                    if( newTotal === 0 )
                    {
                        $('.notice-holder').removeClass('hide');

                        $('.order-holder').hide();

                        $('.checkout-footer').hide();
                    }
                });
            });

        /**
         * Apply percent
         * 
         * @param integer amountPercent
         * @param float price
         * @returns string
         */
        function applyPercentForPrice(amountPercent, price)
        {
            return ( ( parseFloat(price) / 100 ) * parseInt(amountPercent, 10) ).toFixed(2);
        }

        function defaultPrice(type)
        {
            if( typeof(type) === 'undefined' )
            {
                type = false;
            }

            $('#coupon-row').addClass('hide');

            $('#has_discount').data('discountper', 'no');

            var total = parseFloat($('.order-total-price').data('default'));

            if( $('#holiday-fee').data('fee') !== 'no' )
            {
                var feeDiscount = parseFloat($('#holiday-fee').data('fee')), 
                    feePrice = ( ( total / 100 ) * feeDiscount );

                total = ( total + feePrice );
            }

            if( type === 'low_amount' )
            {
                total = ( total + parseFloat(rules.order_less) );

                $('#low_order_fee')
                    .empty()
                    .append(document.createTextNode('+$' + rules.order_less));

                $('#low_order').removeClass('hide');
            }

            $('.order-total-price')
                .data({
                    'value': total.toFixed(2)
                })
                .empty()
                .append(document.createTextNode('$ ' + total.toFixed(2)));
        }

        /**
         * Apply discounts for price
         * 
         * @param integer discountpercet
         * @param string type
         * @returns none
         */
        function discountPrice(discountpercet, type)
        {
            type = type || '';

            $('#has_discount').attr('data-discountper', discountpercet);

            var defaultTotal = parseFloat($('.order-total-price').data('default')), 
                total = 0, totalDiscount = 0, 
                orderPrice, orderPriceIndex, 
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
                            if( parseFloat($(orderPrice).data('coupon')) === 1 )
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

                total = parseFloat(prepareMathFloatValues(total, feePrice));

                $('#fee-prince').html('+$' + feePrice.toFixed(2));
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

            $('.order-total-price')
                .data({
                    'value': total.toFixed(2)
                })
                .empty()
                .append(document.createTextNode('$ ' + total.toFixed(2)));

            $('#coupon-dis').html('-$' + totalDiscount.toFixed(2));
        }

        /**
         * Sum two values
         * 
         * @param float operandOne
         * @param float operandTwo
         * @param string operation
         * @returns string or false
         */
        function prepareMathFloatValues(operandOne, operandTwo, operation)
        {
            operation = operation || '+';

            if( operation === '+' )
            {
                return ( parseFloat(operandOne) + parseFloat(operandTwo) ).toFixed(2);
            }
            else if( operation === '-' )
            {
                return ( parseFloat(operandOne) - parseFloat(operandTwo) ).toFixed(2);
            }
            else
            {
                return false;
            }
        }

        function submitOrder()
        {
            if( $('.later').is(':checked') )
            {
                if( !$('#date').val() || 
                    !$('#time').val() )
                {
                    $('#date-error').removeClass('hide');
                }
                else
                {
                    $('#form-checkout').submit();
                }
            }
            else
            {
                if( $('[name="when"]:checked').val() === 'ASAP' )
                {
                    $('#form-checkout').submit();
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
                        var date = $('#date').val(), 
                            last_time = $('#time').find('option:last').attr('value');

                        if( Date.now() > (new Date(date + ' ' + last_time)).getTime() )
                        {
                            manageHelpFooterLine($('<div>').data('title', 'Sorry, the shop is closed for today'));

                            return false;
                        }
                        else
                        {
                            $('#form-checkout').submit();
                        }
                    }
                }
            }
        }

        /**
         * Show Date-time picker
         */
        if( $('#isopen').data('open') === 'close' )
        {
            $('#date-time :input').attr('disabled', false);
            $('#date-time').removeClass('hide');
        }
        else
        {
            $('#date-time :input').attr('disabled', true);
        }

        $(document)
            .off('click', '.show-date')
            .on('click', '.show-date', function() {
                $('#date-time :input').attr('disabled', false);
                $('#date-time').removeClass('hide');
            })
            .off('click', '.asap')
            .on('click', '.asap', function() {
                $('#date-time :input').attr('disabled', true);
                $('#date-time').addClass('hide');
            });
        /** END Date-time picker */

        /**
         * Social Locker
         */
        // $(document)
        //     .off('click', '#td-social a')
        //     .on('click', '#td-social a', function() {
        //         $('#show-social-loker').removeClass('hide');
        //     });

        /**
         * Need to check is present global vars
         * @type String|FACEBOOKAPPID
         */
        // var FBAppID = '';

        // if( typeof(FACEBOOKAPPID) === 'string' )
        // {
        //     FBAppID = FACEBOOKAPPID;
        // }
        /* use after for Facebook App ID - FBAppID */

        // $('#social-loker').sociallocker({
        //     buttons: { order: [ 'twitter-tweet', 'facebook-share' ] }, 
        //     // a theme name that will be used
        //     theme: 'secrets', 
        //     // text that appears above the social buttons
        //     text: { header: ' ', message: 'Free coke? Like us and it\'s yours!' }, 
        //     facebook: {
        //         appId: FBAppID, 
        //         share: { title: 'share it', url: 'http://m.pizzaboy.bywmds.us/' }
        //     }, 
        //     twitter: {
        //         tweet: {
        //             title: 'tweet me', 
        //             text: 'Tweet this message', 
        //             url: 'http://m.pizzaboy.bywmds.us/'
        //         }
        //     }
        // });

        // $('.onp-sociallocker-text').remove();
    })
    .off('pageinit', '#page-payment')
    /***********************************************************************************************************************
     * Payment/Send Order
     * @url /payment
     **********************************************************************************************************************/
    .on('pageinit', "#page-payment", function() {
        verifyClean();

        $(document)
            .off('click', '#sign-in')
            /*
             * Bind click action for standart login form
             */
            .on('click', '#sign-in', function() {
                var self = this;

                if( $('#form-singin').valid() )
                {
                    signInRequest(self);
                }
            })
            .off('click', '#log-out')
            /*
             * Bind click action for log out
             */
            .on('click', '#log-out', function() {
                window.location.href = '//' + window.location.host + '/logout/payment';
            })
            .off('click', '#verify-btn')
            /*
             * Bind click action for Verify button on profile page
             */
            .on('click', '#verify-btn', function() {
                verifyMobileBySMS();
            })
            .off('change', '#form_suburb')
            /*
             * Change suburb, calculate total
             */
            .on('change', '#form_suburb', function() {
                if( typeof(has_delivery) !== 'undefined' && 
                    has_delivery === '1' )
                {
                    var subtotal = parseFloat($('#subtotal').text().replace('$', '')), 
                        discount = parseFloat($('#discount').val()), 
                        fee = parseFloat($('#form_suburb').find('option').filter(':selected').data('fee')), 
                        payment = parseFloat($('#cc').data('cc'));

                    if( !!discount )
                    {
                        subtotal -= discount;
                    }

                    if( !!fee )
                    {
                        subtotal += fee;
                    }

                    if( !!payment )
                    {
                        subtotal += payment;
                    }

                    var total = subtotal + parseFloat(low_order);

                    $('#delivery-fee')
                        .empty()
                        .append('+$' + (fee || 0));

                    $('#total')
                        .empty()
                        .append(document.createTextNode(total.toFixed(2)));
                }
            })
            .off('click', '.card-number')
            /*
             * Card number inputs
             */
            .on('click', '.card-number', function() {
                var self = this;

                $(self).val('');
            })
            .off('keydown', '.card-number')
            /*
             * Check if number and limit by 4 digit
             */
            .on('keydown', '.card-number', function(event) {
                var self = this, 
                    lengthStr = $(self).val().length;

                if( lengthStr <= parseFloat($(self).attr('data-length')) )
                {
                    if( $.inArray(event.keyCode, [ 48, 49, 50, 51, 52, 53, 54, 55, 56, 57 ]) !== -1 )
                    {
                        if( lengthStr === parseFloat($(self).attr('data-length')) )
                        {
                            var id = $(self).data('id');

                            $('#' + id).focus();
                        }

                        return true;
                    }
                }

                event.preventDefault();

                return false;
            })
            .off('click', '#send-order')
            /*
             * Bind action click for Order now
             */
            .on('click', '#send-order', function() {
                if( $('#register_form').valid() )
                {
                    saveForm('saveOrder');
                }
            });

        if( !!$('#cardholder-input').length )
        {
            $(document)
                .off('keyup', '#form_firstname, #form_lastname')
                /*
                 * Copy first and last name of the cardholder
                 */
                .on('keyup', '#form_firstname, #form_lastname', function() {
                    $('#cardholder-input')
                        .val($('#form_firstname').val() + ' ' + $('#form_lastname').val());
                });
        }

        prepareProfileFormValidation();
        prepareLoginFormValidation();
    })
    .off('pageinit', '#page-recover')
    /***********************************************************************************************************************
     * Recovery Password
     * @url /reset
     **********************************************************************************************************************/
    .on('pageinit', '#page-recover', function() {
        $(document)
            .off('click', '#recover')
            .on('click', '#recover', function() {
                var email = $('#email').val();

                if( !!email )
                {
                    var request = $.ajax({
                        data: {
                            email: email
                        }, 
                        dataType: 'json', 
                        type: 'POST', 
                        url: '//' + window.location.host + '/security/checkValidEmail'
                    });

                    request.done(function(data) {
                        if( data === 'valid' )
                        {
                            $('#popupRecover').popup('open');
                        }
                        else
                        {
                            $('#error-required')
                                .empty()
                                .append(document.createTextNode('Email address not found in database!'));
                        }
                    });

                    request.fail(function(jqXHR, textStatus) {
                        showAlert('', 'Request failed: ' + textStatus);
                    });
                }
                else
                {
                    $('#error-valid')
                        .empty()
                        .append(document.createTextNode('Please input valid email address!'));
                }
            });
    })
    .off('pageinit', '#page-change')
    /***********************************************************************************************************************
     * Change Password
     * @url /change-password
     **********************************************************************************************************************/
    .on('pageinit', '#page-change', function() {
        $(document)
            .off('click', '#save')
            .on('click', '#save', function() {
                $('#error-valid').empty();

                var pass = $('#pass').val(), 
                    conf = $('#conf').val(), 
                    code = $('#code').attr('data-code');

                if( pass === conf )
                {
                    var request = $.ajax({
                        data: {
                            code: code, 
                            pass: pass
                        }, 
                        type: 'POST', 
                        url: '//' + window.location.host + '/security/savePassword'
                    });

                    request.done(function(data) {
                       window.location.href = '//' + window.location.host + '/login_page';
                    });

                    request.fail(function(jqXHR, textStatus) {
                        showAlert('', 'Request failed: ' + textStatus);
                    });
                }
                else
                {
                    $('#error-required')
                        .empty()
                        .append(document.createTextNode('Verification must be the same with the Password!'));
                }
            });
    })
    .off('pageinit', '#your-orders')
    /***********************************************************************************************************************
     * Your Orders
     * @url /order/yourOrders
     **********************************************************************************************************************/
    .on('pageinit', '#your-orders', function() {
        $(document)
            .off('click', '#order-signin')
            .on('click', '#order-signin', function() {
                var self = this;

                signInRequest(self);
            })
            .off('click', '.change-page')
            .on('click', '.change-page', function() {
                var self = this, 
                    count = $('#page').data('count'), 
                    page = $(self).data('change'), 
                    request = $.ajax({
                        data: {
                            count: count, 
                            page: page
                        }, 
                        dataType: 'json', 
                        type: 'POST', 
                        url: '//' + window.location.host + '/order/getAjaxOrders'
                    });

                request.done(function(data) {
                    $('#tbody-orders').empty();

                    var order, orderIndex, 
                        orders = data.orders;

                    for( orderIndex in orders )
                    {
                        if( orders.hasOwnProperty(orderIndex) )
                        {
                            order = orders[orderIndex];

                            if( !!order.order_description )
                            {
                                $('#tbody-orders')
                                    .append(
                                        $('<tr>')
                                            .addClass('tr-your-order')
                                            .append(
                                                $('<td>')
                                                    .append(document.createTextNode(order.order_placement_date))
                                            )
                                            .append(
                                                $('<td>')
                                                    .html($(order.order_description))
                                            )
                                            .append(
                                                $('<td>')
                                                    .append(document.createTextNode(order.payment_amount))
                                            )
                                            .append(
                                                $('<td>')
                                                    .append(document.createTextNode(order.points_used))
                                            )
                                            .append(
                                                $('<td>')
                                                    .append(document.createTextNode(order.points_earned))
                                            )
                                            .append(
                                                $('<td>')
                                                    .append(
                                                        $('<a>')
                                                            .append(document.createTextNode('Order This Again'))
                                                            .attr({
                                                                'data-inline': 'true', 
                                                                'data-role': 'button', 
                                                                'href': base_url + 'order-again/' + order.order_id
                                                            })
                                                    )
                                            )
                                    );
                            }
                        }
                    }

                    $('#page').data('count', data.count);

                    var total = $('#total').attr('data-total');

                    if( data.count === 0 || 
                        data.count === 5 )
                    {
                        $('#div-both').addClass('hide');
                        $('#div-preview').addClass('hide');

                        $('#div-next').removeClass('hide');
                    }
                    else if( data.count >= total )
                    {
                        $('#div-both').addClass('hide');
                        $('#div-next').addClass('hide');

                        $('#div-preview').removeClass('hide');
                    }
                    else
                    {
                        $('#div-preview').addClass('hide');
                        $('#div-next').addClass('hide');

                        $('#div-both').removeClass('hide');
                    }

                    $('#your-orders').trigger('create');
                });

                request.fail(function(jqXHR, textStatus) {
                    showAlert('', 'Request failed: ' + textStatus);
                });
            });
    })
    .off('pageinit', '#security-login')
    /***********************************************************************************************************************
     * Your Account
     * @url /login_page
     **********************************************************************************************************************/
    .on('pageinit', '#security-login', function() {
        $(document)
            .off('click', '#sign-in')
            /*
             * Bind click action for standart login form
             */
            .on('click', '#sign-in', function() {
                var self = this;

                if( $('#form-singin').valid() )
                {
                    signInRequest(self);
                }
            });
    })
    .off('pageshow', '#page-edit')
    /***********************************************************************************************************************
     * Your Account
     * @url /security-edit
     **********************************************************************************************************************/
    .on('pageshow', '#page-edit', function() {
        

        $(document)
            .off('click', '#verify-btn')
            /*
             * Bind click action for Verify button on profile page
             */
            .on('click', '#verify-btn', function() {
                verifyMobileBySMS();
            })
            .off('click', '#save-edit')
            /*
             * Bind click action for save button for edit profile
             */
            .on('click', '#save-edit', function() {
                if( $('#register_form').valid() )
                {
                    saveForm();
                }
            });

        verifyClean();

        prepareProfileFormValidation();
    });