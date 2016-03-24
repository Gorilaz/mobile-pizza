/***********************************************************************************************************************
 * Events used on product page
 * @url /home
 **********************************************************************************************************************/
$( document ).on("pageinit", "#page-home",function() {

//    if(referal){
//        $('#popup-refer2').popup('open');
//    }

});



/***********************************************************************************************************************
 * Events used on product page
 * @url /product/id
 **********************************************************************************************************************/
$( document ).on("pageinit", "#page-product",function() {

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


    /**
     * Scroll page when a category is expanded
     */
//    $(document).on("expand", 'div.ui-collapsible', function(e) {
//        var top = $(e.target).offset().top - 55;
////        $(window).scrollTop(top);
//        $("html, body").animate({scrollTop: top});
//    });

//
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
    $(document).on('change','#halfPizzaSelector', function() {

        var halfVariation = $(this).find('option').filter(':selected').val();
        var halfPizza     = halfs[$(this).data('variation')][halfVariation];

        if(halfVariation && halfVariation > 0) {

        var contentBlock  = ' \
            <div class="single-top" '+ ((halfPizza.product_image != '')?'style="background-image: url('+desktopUrl+'templates/demotest/uploads/products/thumb/'+halfPizza.product_image+')"':'') +' > \
                <div class="single-content get-space"> \
                    <h1>'+halfPizza.product_name+'</h1> \
                    <div class="single-description"> \
                            '+halfPizza.description+' \
                    </div> \
                </div> \
            </div>\
            <div class="single-options get-space clear-heights"> \
                <div class="row ingredientsHolder"> \
                    <a href="#ingredients2" class="ui-link"><i class="icon-chevron-sign-right"></i> Add/Modify Ingredients</a> \
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
        console.log(halfGroup);
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
 * Get Ingredients based on Variation
 * Used on product details page
 *
 */
function populateIngredients(variationId, pizzaNo) {

    if(variationId === undefined) {
        variationId = $('select[name=variation]:last').val();
    }


    if(pizzaNo === undefined || pizzaNo == 1) {
        pizzaNo     = 1;
        var targetBlock = '#ingredients';
    } else {
        pizzaNo     = 2;
        var targetBlock = '#ingredients2';
    }

    $.ajax({
        url: "/get/ingredients/"+variationId,
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
//            $.mobile.loading( "hide" );
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

                    $.each(items, function( key,item ) {
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
                    contentExtra += '<li data-role="list-divider" class="item-search-divider"><input type="search" name="searchIngredients" id="searchIngredientsId" value=" " data-theme="a"></li>';

                    $.each(items, function( ecategory,ingredients ) {
                        contentExtra += '<li>';
                        //contentExtra += '<div data-role="collapsible" data-inset="false" data-theme="a" data-inset="false" data-content-theme="a">';
                        //contentExtra += '<h4 class="no-margin">'+ecategory+'</h4>';
                        contentExtra += '<fieldset data-role="controlgroup">';

//                        content += '<legend>'+ecategory+'</legend>';

                        $.each(ingredients, function( key,item ) {
//                            content += '<input type="checkbox" name="ingredient['+item.ingredient_id+']" id="ingredient['+item.ingredient_id+']" value="'+item.ingredient_id+'" data-theme="a">';
//                            content += '<label for="ingredient['+item.ingredient_id+']">'+item.ingredient_name+' <span class="price">$'+item.price+'</span> </label>';

                            contentExtra += '<input type="checkbox" name="ingredient[]" ';
                            contentExtra += 'id="ingredient-'+item.ingredient_id+'" value="'+item.ingredient_id+'" ';
                                //if(item.status == 'DF') {
                                //    content += 'checked data-default="1" ';
                                //} else {
                            contentExtra += 'data-default="0" ';
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
                       // contentExtra += '</div>';
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
            content += '<p class="side-close-button"><a href="' + targetBlock +'" data-rel="close" data-role="button" class="panel-list btn btn-grey ui-link" data-inline="true" data-mini="true">Done</a></p>';
            content += '</form>';

            $(targetBlock).html(content);

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

    function discountPrice(discountpercet, type){

        if(type === undefined) {
            var type = false;
        }

        $('#has_discount').data('discountper', discountpercet);


        var total = $('.order-total-price').data('default');

        var discount = ((parseFloat(total) / 100)* parseInt(discountpercet)).toFixed(2);

        var newTotal  = (total - discount).toFixed(2);

        if($('#holiday-fee').data('fee') != 'no'){
            var feeDiscount = $('#holiday-fee').data('fee');

            var feePrice = ((parseFloat(total)/100)*parseFloat(feeDiscount)).toFixed(2);
            $('#fee-prince').html('+' + feePrice);

            newTotal = (parseFloat(newTotal) + parseFloat(feePrice)).toFixed(2);
        }
        if(type == 'low_amount'){
            newTotal = (parseFloat(newTotal) + parseFloat(rules.order_less)).toFixed(2);
            $('#low_order_fee').html('+$'+rules.order_less);
            $('#low_order').removeClass('hide');
        }




        if(type == 'online') {
            $('#icon-remove-coupon').removeClass('hide');
            $('#coupon-des').html('Online Order Discount');
        }

        if(type == 'online_low_amount') {
            newTotal = (parseFloat(newTotal) + parseFloat(rules.order_less)).toFixed(2);
            $('#low_order_fee').html('+$'+rules.order_less);
            $('#low_order').removeClass('hide');
            $('#icon-remove-coupon').removeClass('hide');
            $('#coupon-des').html('Online Order Discount');
        }

        $('.order-total-price').html('$ '+newTotal);
        $('.order-total-price').data('value',newTotal);

        $('#coupon-dis').html('-$' + discount);


    }

    /**  Coupon  */
    $(document).on('change', '.choose-coupon', function(){

        var discountpercet = $(this).data('discount');
        if(discountpercet == 'other'){

            $('#tr-coupon').removeClass('hide');
            $('#coupon-row').removeClass('hide');
            $('#coupon').prop('disabled', false);

            if($('#radio-choice-v-2a').is(':checked')){

                defaultPrice('low_amount');
            } else {

                defaultPrice();
            }

        } else {

            $('#tr-coupon').addClass('hide');
            $('#coupon').prop('disabled', true);
            $('#coupon-row').removeClass('hide');

            if($('#radio-choice-v-2a').is(':checked')){

                discountPrice(discountpercet, 'online_low_amount');
            } else {

                discountPrice(discountpercet);
            }

        }

    });


    /** other coupon */
    $(document).on('click','#voucher', function(){

        var el = $('#coupon').val();
        var request = $.ajax({
            url: '//' + location.host + '/checkout/getCoupons',
            type: "POST",
            data: { coupon : el },
            dataType: "json"
        });

        request.done(function( data ) {
            if(data != 'false'){
                $('#coupon-des').html(data.coupondescription);

                var discountpercet = parseInt(data.discountper) ;

                discountPrice(discountpercet);


                $('#icon-remove-coupon').removeClass('hide');
                $('#coupon-row').removeClass('hide');

                $('#tr-coupon').addClass('hide');
                $('#other').val(data.id);

            } else {

                defaultPrice();

                $('#icon-remove-coupon').addClass('hide');

                $('#coupon-des').html('');
                $('#coupon-dis').html('');

            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });

    /** remove coupon  */
    $(document).on('click', '#icon-remove-coupon', function(){
        var didConfirm = confirm("Remove voucher ?");
        if (didConfirm == true) {
            $('#tr-coupon').removeClass('hide');
            $('.choose-coupon').prop('checked', false).checkboxradio('refresh');
            $('#other').prop('checked', true).checkboxradio('refresh');
            $('#coupon').prop('disabled', false);

            if($('#radio-choice-v-2a').is(':checked')){
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
    $(document).off('click','#send-order');
    $(document).off('change','#credit-card');
    $(document).off('change','#form_suburb');
    $(document).off('change','.registerorlogin');
    $(document).off('click','#verify');
    $(document).off('keyup','#first_name');
    $(document).off('keyup','#form_lastname');



    /** card number format */
//    if($('#card-number').length != 0 ){
//        $('#card-number').mask("9999-9999-9999-9999");
//    }

    /** card number format */
//    if($('#cvv').length != 0 ){
//        $('#cvv').mask("999");
//    }

    /** verify mobile */
    $(document).on('click', '#verify', function(){
        var logged = $('#verify').data('verify');
        if(logged = 0){

        }

    });

    $("#cvv").attr('maxlength','3');
    /** card number inputs */
    $(document).on('click', '.card-number',function () {
        $(this).val('');
    });

    $(document).on('keyup', '.card-number',function () {

        if($(this).val().length == $(this).data('length')) {
            var id = $(this).data('id');

            if( id != 'cvv'){
                $('#'+id).val('').focus();
            } else {
                $('#cvv').focus();
            }

        }
    });

    /** Copy first and last name of the cardholder */
    if($('#cardholder-input').length) {
        $('#form_firstname, #form_lastname').on('keyup', function() {
            $('#cardholder-input').val($('#form_firstname').val() +' '+$('#form_lastname').val());
        });
    }



    /** change suburb, calculate total */
    $(document).on('change', '#form_suburb', function(){

        if(has_delivery == 1){
            var subtotal = $('#subtotal').html();
            subtotal = subtotal.replace('$','');

            var discount = $('#discount').val();
            if(discount !== undefined){
                subtotal = parseFloat(subtotal) - parseFloat(discount)
            }

            var fee = $('#form_suburb option:selected').data('fee');

            /** payment fee  */
            var payment = $('#cc').data('cc');
            if( payment != 0){
                subtotal = parseFloat(subtotal) + parseFloat(payment);
            }

            var total = parseFloat(fee) + parseFloat(subtotal) + parseFloat(low_order);

            $('#delivery-fee').html('+$'+fee);

            $('#total').html(total);
        }

    });

    $(document).on('change','.registerorlogin',function() {
        if($(this).val() == 'login') {
            $('#popupLogin').popup('open');
        }
    });

    /** end  */
    $(document).on('click','#sign-in',function(){

        var user = $('#user').val();
        var pass = $('#pass').val();

        var request = $.ajax({
            url: '//' + location.host + '/security/login',
            type: "POST",
            data: { user : user, pass : pass },
            dataType: "json"
        });

        request.done(function( data ) {

            if(data.login == 'true'){
                var page = $('#page').data('page');
                $( "#popupLogin" ).popup( "close" );
                window.location.href = '//' + location.host + '/payment';

            }else if (data.login == 'required fields'){

                $('#login-required').removeClass('hide');
                $('#login-error').addClass('hide');

            } else {

                $('#login-error').removeClass('hide');
                $('#login-required').addClass('hide');
            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });

    /** log out */
    $(document).on('click', '#log-out', function(){
        window.location.href = '//' + location.host + '/security/logout/payment';
    });

    $('#form-credit').validate({
        rules: {
            credit_card: "required",
            card_number: "required",
            expiration: {
                required: true,
                number: true,
                minlength:6,
                maxlength:6
            },
            first_name:"required",
            security: {
                required: true,
                number: true
            }
        }
    });

    $("#form-singin").validate({
        rules: {
            // simple rule, converted to {required:true}
            user: "required",
            // compound rule
            pass: "required"
        }
    });

    $('#register_form').validate({
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
                required: true,
                minlength: 10,
                maxlength: 10,
                digits: true,
                remote: '//' + location.host + '/security/checkUniqueMobile'
            }
        }
    });


    function smsVerify(){

        var mobile  = $('#form_mobile').val();
        var email   = $('#email').val();
        var fname   = $('#form_firstname').val();
        var lname   = $('#form_lastname').val();
        var request = $.ajax({
            url: '//' + location.host + '/checkout/smsMobile',
            type: "POST",
            data: { mobile : mobile, email : email, fname : fname, lname : lname }

        });

        request.done(function( data ) {

            $('#popupDialog').popup('open');
            $('#sms-verify input').prop('disabled', false);

            $('#sms-code').data('sendcode', 'yes');
            $('#form_mobile').prop('readonly', true);

        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }


    $(document).on('click', '#verify', function(){
        var sendcode = $('#sms-code').data('sendcode');
        if(sendcode == 'no'){
            var email = $('#email').val();
            var mobile = $('#form_mobile').val();
            var fname   = $('#form_firstname').val();
            var lname   = $('#form_lastname').val();

            if($.isNumeric(mobile) && IsEmail(email) && fname && lname){
                smsVerify();
            } else {
                alert('Please enter first name, last name, a valid email and mobile for verification.');
            }
        } else {
            var code = $('#sms-code').val();
            if(code !== ''){
                var request = $.ajax({
                    url: '//' + location.host + '/checkout/verifyCode',
                    type: "POST",
                    data: { code : code },
                    dataType: "json"
                });

                request.done(function( data ) {
                    if(data.valid){
                        $('#sms-code-error').addClass('hide');
                        $('#sms-code').data('final', 'yes');
                        $('#sms-verify input').prop('disabled', true);
                    } else {
                        $('#sms-code-error').removeClass('hide');
                    }
                });

                request.fail(function( jqXHR, textStatus ) {
                    alert( "Request failed: " + textStatus );
                });
            } else {
                $('#sms-code-error').removeClass('hide');
            }
        }

    });

    function IsEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }
//    function changeMobile(){
//        var mobile  = $('#form_mobile').val();
//        var email   = $('#email').val();
//        var fname   = $('#form_firstname').val();
//        var lname   = $('#form_lastname').val();
//        var request = $.ajax({
//            url: '//' + location.host + '/checkout/verifyMobile',
//            type: "POST",
//            data: { mobile : mobile, email : email, fname : fname, lname : lname, changeMobile : 1 },
//            dataType: "json"
//        });
//
//        request.done(function( data ) {
//            console.log('4');
//            $('#popupDialog').popup('open');
//            $('#sms-verify input').prop('disabled', false);
//            $('#sms-verify').removeClass('hide');
//            $('#sms-code').data('verify', 'yes');
//
//            $('#form_mobile').prop('readonly', true);
//        });
//
//        request.fail(function( jqXHR, textStatus ) {
//            alert( "Request failed: " + textStatus );
//        });
//    }

//    function sendOrder() {
//
//        var old_mobile = $('#old-mobile').val();
//        var mobile = $('#form_mobile').val();
//        var final =  $('#sms-code').data('final');
//
//        /** verify if change mobile for code verification */
//        if(old_mobile != mobile && final == 'no'){
//
//            changeMobile();
//        } else {
//
//
//            $('#sms-verify input').prop('disabled', true);
//            saveOrder();
//        }
//    }

    /** save order */
    function saveOrder(){

        if($('#register_form').valid()){

            var request = $.ajax({
                url: '//' + location.host + '/security/save',
                type: "POST",
                data:  $('#register_form').serialize()

            });

            request.done(function( data ) {

                var final = $('#sms-code').data('final');

                if(final == 'yes' || final == null){

                    var pg = $('#pg').data('pg');

                    if(pg == 'credit-card' && $('#form-credit').valid()){

                        var request = $.ajax({
                            url: '//' + location.host + '/payment/Do_direct_payment',
                            type: "POST",
                            data: $('#form-credit').serialize(),
                            dataType: "json"
                        });

                        request.done(function( data ) {

                            if(data.error){
                                $('#errors').html('');
                                var new_errors = '';

                                $.each(data.message, function( index, value ) {
                                    new_errors += value + '  ';
                                });

                                alert(new_errors);


                            } else {

                                window.location.href = '//' + location.host + '/order/save_order/credit';
                            }
                        });

                        request.fail(function( jqXHR, textStatus ) {
                            alert( "Request failed: " + textStatus );
                        });
                    } else if(pg == 'cash'){

                        window.location.href = '//' + location.host + '/order/save_order/cash';
                    } else if(pg == 'paypal'){

                        window.location.href = '//' + location.host + '/order/save_order/paypal';
                    }
                }
            });

            request.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        }

    }

    /** sms verify disabled default  */
    $('#sms-verify input').prop('disabled', true);

    $(document).on('click','#send-order',function(){


        if($('#register_form').valid() ){
            var sms = $('#sms').data('sms');
            var final = $('#sms-code').data('final');
            if(final == 'yes'){
                saveOrder();
            } else if(sms == 'enable' && logged == 0){
                var verified =  $('#sms-code').data('sendcode');
                if(verified == 'no'){
                    var verified = smsVerify();
                } else {
                    var code = $('#sms-code').val();
                    if(code !== ''){
                        var request = $.ajax({
                            url: '//' + location.host + '/checkout/verifyCode',
                            type: "POST",
                            data: { code : code },
                            dataType: "json"
                        });

                        request.done(function( data ) {
                           if(data.valid){
                               $('#sms-code').data('final', 'yes');
                               $('#sms-code-error').addClass('hide');
                               $('#sms-verify input').prop('disabled', true);
                               $('#verify').prop('disabled', true);
                                saveOrder();
                           } else {
                                $('#sms-code-error').removeClass('hide');
                           }
                        });

                        request.fail(function( jqXHR, textStatus ) {
                            alert( "Request failed: " + textStatus );
                        });

                    } else {
                        $('#sms-code-error').removeClass('hide');
                    }
                }

            } else {
                saveOrder();
            }


        }
    });
});

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

        var user = $('#user').val();
        var pass = $('#pass').val();

        var request = $.ajax({
            url: '//' + location.host + '/security/login',
            type: "POST",
            data: { user : user, pass : pass },
            dataType: "json"
        });

        request.done(function( data ) {

            if(data.login == 'true'){
                var page = $('#page').data('page');
                $( "#popupLogin" ).popup( "close" );
                window.location.href = '//' + location.host + '/orders';

            }else if (data.login == 'required fields'){

                $('#login-required').removeClass('hide');
                $('#login-error').addClass('hide');

            } else {

                $('#login-error').removeClass('hide');
                $('#login-required').addClass('hide');
            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
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
    $(document).off('click','#login');

    $(document).on('click', '#login', function(){

        var user = $('#user').val();
        var pass = $('#pass').val();

        var request = $.ajax({
            url: '//' + location.host + '/security/login',
            type: "POST",
            data: { user : user, pass : pass },
            dataType: "json"
        });

        request.done(function( data ) {

            if(data.login == 'true'){
                window.location.href = '//' + location.host + '/menu';

            }else if (data.login == 'required fields'){

                $('#login-required').removeClass('hide');
                $('#login-error').addClass('hide');

            } else {

                $('#login-error').removeClass('hide');
                $('#login-required').addClass('hide');
            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });

});


/***********************************************************************************************************************
 * Your Account
 * @url /security/edit
 **********************************************************************************************************************/
$( document ).on('pageinit', "#page-edit", function() {

    /* Unbind everything */
    $(document).off('click','#save-edit');
    $(document).off('click','#verify-btn2');


    var verified = 'no';
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
                equalTo: "#form_password1",
                minlength: 5
            },
            suburb: "required",
            state: "required",
            mobile: {
                required: true,
                maxlength: 10,
                minlength: 10,
                digits: true,
                remote: '//' + location.host + '/security/checkUniqueMobile'
            }
        }
    });

    function saveEdit(){
        var old_m = $('#old_mobile1').val();
        var m = $('#form_mobile1').val();
        var final =  $('#sms-code1').data('final');

        if( old_m != m  && final == 'no' ){
            changeMobile1();
        } else {

            if($('#register_form1').valid()){
                saveForm();
            }
        }
    }

    $(document).on('click', '#verify-btn2', function(){
        var final = $('#sms-code1').data('final');

        if(final == 'yes'){
            $('#sms-code-error').addClass('hide');
            $('#sms-verify1 input').prop('disabled', true);
            $('#verify-btn2').prop('disabled', true);
            $('#sms-code-error1').addClass('hide');
        } else {
            var code = $('#sms-code1').val();

            if(code !== ''){
                var request = $.ajax({
                    url: '//' + location.host + '/checkout/verifyCode',
                    type: "POST",
                    data: { code : code },
                    dataType: "json"
                });

                request.done(function( data ) {

                    if(data.valid){
                        $('#sms-code1').data('final', 'yes');

                        $('#sms-code-error').addClass('hide');
                        $('#sms-verify1 input').prop('disabled', true);
                        $('#verify-btn2').prop('disabled', true);
                        $('#sms-code-error1').addClass('hide');
                    } else {
                        $('#sms-code-error1').removeClass('hide');
                    }
                });

                request.fail(function( jqXHR, textStatus ) {
                    alert( "Request failed: " + textStatus );
                });

            } else {
                $('#sms-code-error1').removeClass('hide');
            }
        }
    });

    $(document).on('click', '#save-edit', function(){
        if($('#register_form1').valid()){

            if(sms == 'enable'){
                var verified =  $('#sms-code1').data('sendcode');
                var final = $('#sms-code1').data('final');

                if(final == 'yes'){
                    saveForm();
                } else if(verified == 'no'){

                    var verified = smsVerify1();
                } else {
                    var code = $('#sms-code1').val();

                    if(code !== ''){
                        var request = $.ajax({
                            url: '//' + location.host + '/checkout/verifyCode',
                            type: "POST",
                            data: { code : code },
                            dataType: "json"
                        });

                        request.done(function( data ) {
                            if(data.valid){
                                $('#sms-code1').data('final', 'yes');
                                saveForm();
                            } else {
                                $('#sms-code-error1').removeClass('hide');
                            }
                        });

                        request.fail(function( jqXHR, textStatus ) {
                            alert( "Request failed: " + textStatus );
                        });

                    } else {

                        $('#sms-code-error').removeClass('hide');

                    }
                }
            } else {
                saveForm();
            }
        }
    });


    function saveForm(){
        var request = $.ajax({
            url: '//' + location.host + '/security/save',
            type: "POST",
            data: $('#register_form1').serialize()

        });

        request.done(function( data ) {
            window.location.href = '//' + location.host + '/security/edit'
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }

    function changeMobile1(){
        var mobile  = $('#form_mobile1').val();
        var email   = $('#email1').val();
        var fname   = $('#form_firstname1').val();
        var lname   = $('#form_lastname1').val();

        var request = $.ajax({
            url: '//' + location.host + '/checkout/verifyMobile',
            type: "POST",
            data: { mobile : mobile, email : email, fname : fname, lname : lname}

        });

        request.done(function( data ) {

            $('#popupCode').popup('open');
            $('#sms-verify1 input').prop('disabled', false);
            $('#sms-verify1').removeClass('hide');
            $('#sms-code1').data('sendcode', 'yes');

            $('#form_mobile1').prop('readonly', true);
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }

    function smsVerify1(){


        var old_m = $('#old_mobile1').val();
        var m = $('#form_mobile1').val();
        var final =  $('#sms-code1').data('final');

        if( old_m != m  && final == 'no' ){
            changeMobile1();
        } else {

            if($('#register_form1').valid()){
                saveForm();
            }
        }


    }

});