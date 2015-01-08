$(function(){

    if($('#menu-page #log_ord-popup').data('popup') == 'logged'){
        $('#menu-page #popup-logged').popup("open");
    }

    /******************************************  Verify if add to cart  ****************************************/
    $(document).on('click', '#start-order',function(){
        if($('#menu-page #start-order').hasClass('add-to-card')){
//            add_to_card();

        } else {
        /**  Half pizza group */
            half_pizza_group();
        }
    });

    function half_pizza_group(){

        $('#menu-page #half-group').html('');

        var half_pizza = $('#menu-page #half_pizza').val();
        var variation = $('#menu-page #half_pizza_variation').val();
        var request = $.ajax({
            url: '//' + location.host + '/half-group',
            type: "POST",
            data: { var_id : variation, hpg_id : half_pizza },
            dataType: "json"
        });

        request.done(function( data ) {
            //** verify if has pizza group */
            if(!data.data){
                $('#menu-page #div-different-half').addClass('hide');
                $('#menu-page #div-add-to-card').removeClass('half-right');
            } else {
                var half_pizza_group = data.half_pizza_group;
                $(half_pizza_group).each(function(){
                    var li = $('<a href="#half-pizza-product" class="different-half" data-var_id="' + this.variation_id + '" data-rel="popup" data-position-to="window"><li>' +this.product_name + '</li></a>');
                    $('#menu-page #half-group').append(li);
                });

            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }

    /************************************* Half pizza details ******************************************************/
    $( document ).on('click', '.different-half', function(){

        $('#menu-page #product_right_id').val('');
//      $('.different-half').click(function(){
        var var_id = $(this).data('var_id');
        var request = $.ajax({
            url: '//' + location.host + '/half-pizza',
            type: "POST",
            data: { var_id : var_id },
            dataType: "json"
        });

        request.done(function( data ) {
            if(!data.data){
                alert('No result.');
            } else {

                /** dialog title */
                $('#menu-page #dialog4-title').html(data.right_product_name);

                /** product1 price, product2 price, total price  */
                var price = $('#menu-page #left-total').val();

                var product_left_price = parseFloat(price) / 2;
//                product_left_price = Math.ceil(product_left_price * 10) / 10;
                $('#menu-page #product-left-price').text(product_left_price);

                $('#menu-page #product-right-name').text(data.right_product_name);
                $('#menu-page #right-name').val(data.right_product_name);
                var var_id_value = data.var_id_value;
                var split = var_id_value.split('_');
                var var_id = split[0];
                /**  if product is family price = default price + falimy price  */
                var size_price = split[1];
                var right_price = data.right_product_price;
                $('#menu-page #right-price-product').val(parseFloat(right_price)/2);

                var product_right_price = (parseFloat(size_price) + parseFloat(right_price)) / 2;
//                product_right_price = Math.ceil(product_right_price * 10) / 10;

                $('#menu-page #right-price').val(product_right_price);

                var quantity = parseFloat($('#menu-page #quantity').val());
                var right_total = parseFloat(product_right_price) * quantity;
                $('#menu-page #right-total').val(right_total);
                $('#menu-page #product-right-price').text(right_total);
                $('#menu-page #product_right_id').val(data.right_product_id);

                var total_price = product_left_price + product_right_price;
                $('#menu-page .total').val(total_price);
                $('#menu-page .price-span').text(total_price);

                /** produs 2 description  */
                $('#menu-page #right-description').html(data.right_description);

//                calculate();

                get_ingredients_right(var_id);
            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });

    /***************************************  Get ingredinets for right product *********************************/
    function get_ingredients_right(var_id){

        var request = $.ajax({
            url: '//' + location.host + '/half-ingredients',
            type: "POST",
            data: { var_id : var_id },
            dataType: "json"
        });

        request.done(function( data ) {
            /**  current ingredients */
            var current_ing = data.current_ingredients;
            $('#menu-page #right-current_ing').html('');
            $(current_ing).each(function(){
                var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ this.price +'" selected>' + this.ingredient_name + '</option>');
                $('#menu-page #right-current_ing').append(option);
            });

            /** optional ingredients  */
            var optional_ing = data.optional_ingredients;
            $('#menu-page #right-optional_ing').html('');
            $(optional_ing).each(function(){
                var option_price = parseFloat(this.price) / 2;
                var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ option_price +'">' + this.ingredient_name + ' '+ option_price +'</option>');
                $('#menu-page #right-optional_ing').append(option);
            });

            /** optional ingredients  */
            var optional_ing = data.optional_ingredients;
            $('#menu-page #right_optional_ing_condiment').html('');
            $('#menu-page #right_optional_ing_meat').html('');
            $('#menu-page #right_optional_ing_veggie').html('');
            $('#menu-page #right_optional_ing_common').html('');

            $(optional_ing).each(function(){
                if(this.group_id == 3){
                    var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ parseFloat(this.price)/2 +'">' + this.ingredient_name + ' '+ parseFloat(this.price)/2 +'</option>');
                    $('#menu-page #right_optional_ing_condiment').append(option);
                } else if(this.group_id == 1){
                    var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ parseFloat(this.price)/2 +'">' + this.ingredient_name + ' '+ parseFloat(this.price)/2 +'</option>');
                    $('#menu-page #right_optional_ing_meat').append(option);
                } else if(this.group_id == 2){
                    var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ parseFloat(this.price)/2 +'">' + this.ingredient_name + ' '+ parseFloat(this.price)/2 +'</option>');
                    $('#menu-page #right_optional_ing_veggie').append(option);
                } else {
                    var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ parseFloat(this.price)/2 +'">' + this.ingredient_name + ' '+ parseFloat(this.price)/2 +'</option>');
                    $('#menu-page #right_optional_ing_common').append(option);
                }

            });

        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }

    /******************************************* Choose product (get product) **************************************/
    $(document).on('click', '.product',function(){

        $('#menu-page .select-menu').each(function(){
            var span = $(this).parent().find('.ui-btn-text span');
            $(span).text('');
        });

//        alert( $('#left_current_ing').val());
        $('#menu-page #product_right_id').val('');
        $('#menu-page #product_left_id').val('');
        $('#menu-page #quantity').val('1');
        $('#menu-page .comment').val('');
        $('#menu-page #product_left_id').val('');

        $('#menu-page #product-left-name').html('');
        $('#menu-page #product-right-name').html('');


        /** default different half and add to card */
        if($('#menu-page #div-different-half').hasClass('hide')){
            $('#menu-page #div-different-half').removeClass('hide');
        }
        if(!($('#menu-page #div-add-to-card').hasClass('half-right'))){
            $('#menu-page #div-add-to-card').addClass('half-right');
        }

        $('#menu-page #size').html('');

        $('#menu-page #dialog-title').html('');
        $('#menu-page #description').html('');

        /** remove all old pricies */
        $('#menu-page .dialog-price').each(function(){
            $(this).html('')
        });

        /** create price div  */

        var product_id = $(this).data('id');
        var request = $.ajax({
            url: '//' + location.host + '/product',
            type: "POST",
            data: { id : product_id },
            dataType: "json"
        });

        request.done(function(data) {
            if(data.deal == 1){
                /***********  RESET DEAL FIELDS **************************/
                $('#menu-page #deal-total-span').text('');
                $('#menu-page #product-deal-name').text('');
                $('#menu-page #deal-description').html('');
                $('#menu-page #deal-pizza1').val('');
                $('#menu-page #deal-pizza2').val('');
                $('#menu-page #deal-meal1').val('');
                $('#menu-page #deal-meal2').val('');
                $('#menu-page #deal-drink').val('');

                $('#menu-page #start-deal').click();
                var product = data.product;
                $('#menu-page .deal-total-span').text(product.product_price);
                $('#menu-page #product-deal-name').text(product.product_name);
                $('#menu-page #deal-product-name').val(product.product_name);
                $('#menu-page #deal-description').append(product.description);
                $('#menu-page #deal-total').val(product.product_price);
                $('#menu-page #deal-price').val(product.product_price);

                $('#menu-page #deal-product-id').val(data.product_id);
                var variations = data.variations;
                $(variations).each(function(){
                    var option = $('<option value="'+ this.variation_id +'" data-price="' + this.variation_price + '">' + this.variation_name + ' (' + this.variation_price + ')</option>');
                    if(this.variation_group_id == 21){
                        $('#menu-page #deal-pizza1-div').removeClass('hide');
                        $('#menu-page #deal-pizza1').append(option);
                    } else if(this.variation_group_id == 22){
                        $('#menu-page #deal-pizza2-div').removeClass('hide');
                        $('#menu-page #deal-pizza2').append(option);
                    } else if(this.variation_group_id == 23){
                        $('#menu-page #deal-meal1-div').removeClass('hide');
                        $('#menu-page #deal-meal1').append(option);
                    } else if(this.variation_group_id == 24){
                        $('#menu-page #deal-meal2-div').removeClass('hide');
                        $('#menu-page #deal-meal2').append(option);
                    } else if(this.variation_group_id == 12){
                        $('#menu-page #deal-drink-div').removeClass('hide');
                        $('#menu-page #deal-drink').append(option);
                    }
                });
                /** select first option each select */
                var text = $("#menu-page #deal-pizza1 option:eq(1)").html();
                $("#menu-page #deal-pizza1 option:eq(1)").attr('selected','selected');
                var span = $('#menu-page #deal-pizza1').parent().find('.ui-btn-text span');
                $(span).html(text);

                var text2 = $("#menu-page #deal-pizza2 option:eq(1)").html();
                $("#deal-pizza2 option:eq(1)").attr('selected','selected');
                var span2 = $('#menu-page #deal-pizza2').parent().find('.ui-btn-text span');
                $(span2).html(text2);

                var text3 = $("#menu-page #deal-meal1 option:eq(1)").html();
                $("#menu-page #deal-meal1 option:eq(1)").attr('selected','selected');
                var span3 = $('#deal-meal1').parent().find('.ui-btn-text span');
                $(span3).html(text3);

                var text4 = $("#menu-page #deal-meal2 option:eq(1)").html();
                $("#menu-page #deal-meal2 option:eq(1)").attr('selected','selected');
                var span4 = $('#menu-page #deal-meal2').parent().find('.ui-btn-text span');
                $(span4).html(text4);

                var text5 = $("#menu-page #deal-drink option:eq(1)").html();
                $("#menu-page #deal-drink option:eq(1)").attr('selected','selected');
                var span5 = $('#menu-page #deal-drink').parent().find('.ui-btn-text span');
                $(span5).html(text5);

//                $("#deal-pizza2").val($("#deal-pizza2 option:first").val());
//                $("#deal-meal1").val($("#deal-meal1 option:first").val());
//                $("#deal-meal2").val($("#deal-meal2 option:first").val());
//                $("#deal-drink").val($("#deal-drink option:first").val());

                calculate_total_deal();

            } else {
                $('#menu-page #start-order').prop("href", "#item-option");
                var product = data.product;
                $('#menu-page #popupDialog-popup').css({'width' : '100%', 'margin' : 'auto', 'max-width' : '', 'left' : ''});

                /** image  */
                $('#menu-page #img-thumb').html('');
                $('#menu-page #full-image').html('');
                if(product.product_image){
                    var header = '<a href="#dialog-image" data-rel="popup" data-position-to="window">' +
                                 '<img style="margin: auto;width: 40%;" src="'+'//' + location.host + '/templates/demotest/uploads/products/thumb/' + product.product_image + '"/>' +
                                 '</a>' +
                                 '<span>'+product.description+'</span>';
                    $('#menu-page #img-thumb').html(header);

                    var full_name = product.product_image.slice(0, -4);
                    var full_image = '<img src="'+'//' + location.host + '/templates/demotest/uploads/products/' + full_name + '"/>' +
                                    '<span>'+product.description+'</span>';
                    $('#menu-page #full-image').html(full_image);
                }
                /** end image */

                /** product name */
                $('#menu-page #dialog-title').text(product.product_name);
                $('#menu-page #product-left-name').text(product.product_name);
                $('#menu-page #left-name').val(product.product_name);
                $('#menu-page #product_left_id').val(product.product_id);

                /** product description */
                $('#menu-page #description').append(product.description);
                /** product price */
                $('#menu-page .price-span').text(product.product_price);
                $('#menu-page #left-price').val(product.product_price);
                $('#menu-page #left-total').val(product.product_price);
                $('#menu-page #total').val(product.product_price);

                /** variation  (medium/large/family)  */
                var variation = data.variation_product;
                $(variation).each(function(){
                    var option = $('<option value="'+ this.variation_id +'_' + this.variation_price + '">' + this.variation_name + ' (' + this.variation_price + ')</option>');
                    $('#menu-page #size').append(option);
                });

                /** set default variation  */
                var text = $("#menu-page #size option:eq(1)").html();
                $("#menu-page #size option:eq(1)").attr('selected','selected');
                var span = $('#menu-page #size').parent().find('.ui-btn-text span');
                span.html(text);
                $('#menu-page #size-input').val(text);
                var id = $("#size option:eq(1)").val();

                get_ingredients(id);
            }

        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });
    /******************************  Calculate left total  ******************************************************/
    function left_total(){
        var split = $('#menu-page #size').val().split('_');
        var size = split[1];
        var quantity = $('#menu-page #quantity').val();
        var left_product =  $('#menu-page #left-price').val();
        var extra =  0;
        $('#menu-page #optional_ing_common option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });
        $('#menu-page #optional_ing_meat option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });
        $('#menu-page #optional_ing_veggie option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });
        $('#menu-page #optional_ing_condiment option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });

        var total = ((parseFloat(left_product) + parseFloat(size)) + parseFloat(extra)) * parseFloat(quantity);

        return total;
    }

    function total(left_total){
        var right_product = $('#menu-page #right-price').val();
        var sub_left =parseFloat(left_total)/2;

        var quantity = $('#menu-page #quantity').val();
        var extra =  0;
        $('#menu-page #right_optional_ing_common option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });
        $('#menu-page #right_optional_ing_meat option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });
        $('#menu-page #right_optional_ing_veggie option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });
        $('#menu-page #right_optional_ing_condiment option').each(function(){
            if($(this).is(':selected')){
                extra = parseFloat($(this).val()) + parseFloat(extra);
            }
        });


        var right_total =(parseFloat(right_product) + parseFloat(extra)) * parseFloat(quantity);
        $('#menu-page #product-right-price').text(right_total);

        var total = (parseFloat(right_total)+ parseFloat(sub_left));

        return total;
    }

    function calculate(){
        var left_total2 = left_total();

        if($('#menu-page #product_right_id').val()){
            var total2 = total(left_total2);
            $('#menu-page #total').val(total2);
            $('#menu-page .price-span').text(total2);
            $('#menu-page #product-left-price').text(parseFloat(left_total2)/2);
        } else {
            $('#menu-page #total').val(left_total2);
            $('#menu-page #left-total').val(left_total2);
            $('#menu-page .price-span').text(left_total2);
        }
    }

    $(document).on('change', '.calculate',function(){
        calculate();
    });

    /**************************************** Get ingredients **************************************************/
    function get_ingredients(id){

        var request = $.ajax({
            url: '//' + location.host + '/ingredients',
            type: "POST",
            data: { var_id : id },
            dataType: "json"
        });

        request.done(function( data ) {

            if(data.have_ing == 0){
                $('#menu-page  #div-size').addClass('hide');
                $('#menu-page  #size').val('');
                $('#menu-page  #start-order').addClass('add-to-card');
                $("#menu-page  #start-order").attr("href","#");

                $('#menu-page #add-to-cart-trigger').attr('class', '');
                $('#menu-page #add-to-cart-trigger').addClass('button-add-to-cart');
                $('#menu-page #add-to-cart-trigger span').text('Add to cart').css("color", "#FFFFFF");
                $('#menu-page #start-order').css("text-decoration", "none");
                var icon = '<i class="icon icon-shopping-cart" style="color: #FFFFFF;margin-right: 5px;"></i>';
                $("#menu-page #add-to-cart-trigger span").prepend(icon);

            } else {

                $('#menu-page #add-to-cart-trigger').attr('class', '');
                $('#menu-page #add-to-cart-trigger').addClass('button-continue');
                $('#menu-page #add-to-cart-trigger span').text('Start to cart');
                var icon = '<i class="icon icon-shopping-cart" style="color: #FFFFFF;margin-right: 5px;"></i>';
                $("#menu-page #add-to-cart-trigger span").prepend(icon);
//


                /**  current ingredients */
                var current_ing = data.current_ing;
                $('#menu-page #current_ing').html('');
                $(current_ing).each(function(){
                    var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ this.price +'" selected>' + this.ingredient_name + '</option>');
                    $('#menu-page #current_ing').append(option);
                });

                /** optional ingredients  */
                var optional_ing = data.optional_ing;
                $('#menu-page #optional_ing_condiment').html('');
                $('#menu-page #optional_ing_meat').html('');
                $('#menu-page #optional_ing_veggie').html('');
                $('#menu-page #optional_ing_common').html('');

                $(optional_ing).each(function(){
                    if(this.group_id == 3){
                        var option = $('<option data-id="'+ this.ingredient_id +'" value="'+  this.price +'">' + this.ingredient_name + ' '+ this.price +'</option>');
                        $('#menu-page #optional_ing_condiment').append(option);
                    } else if(this.group_id == 1){
                        var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ this.price +'">' + this.ingredient_name + ' '+ this.price +'</option>');
                        $('#menu-page #optional_ing_meat').append(option);
                    } else if(this.group_id == 2){
                        var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ this.price +'">' + this.ingredient_name + ' '+ this.price +'</option>');
                        $('#menu-page #optional_ing_veggie').append(option);
                    } else {
                        var option = $('<option data-id="'+ this.ingredient_id +'" value="'+ this.price +'">' + this.ingredient_name + ' '+ this.price +'</option>');
                        $('#menu-page #optional_ing_common').append(option);
                    }

                });

                /** save in input half_pizza_group and variation */
                $('#menu-page #half_pizza').val(data.half_pizza_group);
                $('#menu-page #half_pizza_variation').val(data.half_pizza_variation_id);
            }


        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }

    /******************************************** Choose optional ingredients  ****************************************/
//    $('#optional_ing').change(function(){
//    $(document).on('change', '#optional_ing',function(){
//
//        var split = $('#menu-page #size').val().split('_');
//        /** variation id */
//        var id = split[0];
//        /** variation price */
//        var price = split[1];
//
//        var el = parseFloat(price);
//        var price = parseFloat($('#menu-page #left-price').val());
//        var new_price = el + price;
//        var quantity = parseFloat($('#menu-page #quantity').val());
//
//        new_price = new_price * quantity;
//        var select = $('#menu-page #optional_ing').val();
//        var ing_price = 0;
//        if(select){
//            for (var i=0;i<select.length;i++)
//            {
//                var op = quantity * parseFloat(select[i]);
//                ing_price = ing_price + op;
//                new_price = new_price + op;
//            }
//        }
//        $('#menu-page #left-ing-price').val(ing_price);
//        $('#menu-page #left-total').val(new_price);
//        $('#menu-page #total').val(new_price);
//        $('#menu-page .price-span').text(new_price);
//
//    });

    /*************************** Choose optional ingredients for right product ****************************************/
//    $('#right-optional_ing').change(function(){
    $(document).on('change', '#right-optional_ing',function(){
//        var left_price = $('#product-left-price').val();
//        var right_price = $('#right-price-default').val();
//        var quantity = parseFloat($('#menu-page #quantity').val());
//        var left_total = $('#menu-page #left-total').val();
//        var left_half = parseFloat(left_total)/2;
//        var right_price = $('#menu-page #right-price').val();
//        var right_subtotal = parseFloat(right_price) * parseFloat(quantity);
//
////        var sub_total = parseFloat(left_total) + right_total;
////        var new_price = parseFloat(left_price) + parseFloat(right_price);
//        var select = $('#menu-page #right-optional_ing').val();
//        if(select){
//            for (var i=0;i<select.length;i++)
//            {
//                var op = parseFloat(quantity) * parseFloat(select[i]);
//                right_subtotal = parseFloat(right_subtotal) + op;
//
//            }
//        }
//
//        $('#menu-page #right-total').val(right_subtotal);
//        $('#menu-page #product-right-price').text(right_subtotal);
//        var total = parseFloat(right_subtotal) + parseFloat(left_half);
//        $('#menu-page #total').val(total);
//        $('#menu-page .price-span').text(total);

        calculate();

    });

    /************************************************* Change size ****************************************************/
//    $('#size').change(function(){
    $(document).on('change', '#size',function(){
        var split = $(this).val().split('_');
        /** variation id */
        var id = split[0];
        /** variation price */
        var price = split[1];
        var el = parseFloat(price);
        var price = parseFloat($('#menu-page #left-price').val());
        var new_price = el + price;
        var quantity = parseFloat($('#menu-page #quantity').val());

        new_price = new_price * quantity;

        $('#menu-page .price-span').text(new_price);
        $('#menu-page #left-total').val(new_price);

        $('#menu-page #size-input').val(split[0]);

        get_ingredients(id);

    });

    /********************************************** Change qunatity  **************************************************/
//    $('#quantity').change(function(){
    $(document).on('change', '#quantity',function(){

        var size = $('#menu-page #size').val();
        if(size){
            var split = $('#menu-page #size').val().split('_');
            /** variation id */
            var id = split[0];
            /** variation price */
            var el = split[1];
            var price = $('#menu-page #left-price').val();
            var new_price = parseFloat(el) + parseFloat(price);
        } else {
            var new_price = $('#menu-page #left-price').val();
        }

        var quantity = $('#menu-page #quantity').val();
        new_price = parseFloat(new_price) * parseFloat(quantity);

        $('#menu-page .price-span').text(new_price);
        $('#menu-page #left-total').val(new_price);
    });

    /***********************************************  Comment  *******************************************************/
    $(document).on('keyup', '.comment',function(){
        var text = $(this).val();
        $('#menu-page .comment').val(text);
    });

    /******************************************  Add to card  ********************************************************/
//    $('.add-to-card').on('click',function(){
    $(document).on('click', '.add-to-card',function(){
        add_to_card();

    });

    function add_to_card(){
        var total = $('#menu-page #total').val();
        var quantity = $('#menu-page #quantity').val();
        var size = $('#size option:selected').text();
        var variation_id = $('#menu-page #half_pizza_variation').val();
        var half_pizza_group_id = $('#menu-page #half_pizza').val();
        var comment = $('#menu-page .comment').val();
        /***************  Left produs *******************/
        var left_id =  $('#menu-page #product_left_id').val();
        /** produs name */
        var left_produs = $('#menu-page #product-left-name').html();
        var left_price =  $('#menu-page #left-price').val();

        /** get left produs current ingredients */
        var left_current_ing = '';
        $('#menu-page #current_ing option').each(function(){
            if($(this).is(':selected')){
            } else {
                var id = $(this).data('id');
                left_current_ing = left_current_ing + id + ',';
            }
        });
        var left_current_ing = left_current_ing.slice(0,-1);
        /******* get left produs option ingredients *********/
        /**** common  *******/
        var left_optional_ing_common = '';
        $('#menu-page #optional_ing_common option:selected').each(function(){
            var id = $(this).data('id');
            left_optional_ing_common = left_optional_ing_common + id + ',';
        });
        var left_optional_ing_common = left_optional_ing_common.slice(0,-1);
        /****  meal  *****/
        var left_optional_ing_meal = '';
        $('#menu-page #optional_ing_meat option:selected').each(function(){
            var id = $(this).data('id');
            left_optional_ing_meal = left_optional_ing_meal + id + ',';
        });
        var left_optional_ing_meal = left_optional_ing_meal.slice(0,-1);

        /*****  veggie  ****/
        var left_optional_ing_veggie = '';
        $('#menu-page #optional_ing_veggie option:selected').each(function(){
            var id = $(this).data('id');
            left_optional_ing_veggie = left_optional_ing_veggie + id + ',';
        });
        var left_optional_ing_veggie = left_optional_ing_veggie.slice(0,-1);

        /*********  condiments  *******/
        var left_optional_ing_condiment = '';
        $('#menu-page #optional_ing_condiment option:selected').each(function(){
            var id = $(this).data('id');
            left_optional_ing_condiment = left_optional_ing_condiment + id + ',';
        });
        var left_optional_ing_condiment = left_optional_ing_condiment.slice(0,-1);

        /*************  Right produs  **********************/
        var right_id =  $('#menu-page #product_right_id').val();
        /** produs name  */
        var right_produs = $('#menu-page #product-right-name').html();

        var right_price = $('#menu-page #right-price-product').val();

        /** get right produs current ingredients */
        var right_current_ing = '';
        $('#menu-page #right-current_ing option').each(function(){
            if($(this).is(':selected')){
            } else {
                var id = $(this).data('id');
                right_current_ing = right_current_ing + id + ',';
            }
        });
        var right_current_ing = right_current_ing.slice(0,-1);

        /******* get right produs option ingredients *********/
        /**** common  *******/
        var right_optional_ing_common = '';
        $('#menu-page #right_optional_ing_common option:selected').each(function(){
            var id = $(this).data('id');
            right_optional_ing_common = right_optional_ing_common + id + ',';
        });
        var right_optional_ing_common = right_optional_ing_common.slice(0,-1);

        /****  meal  *****/
        var right_optional_ing_meal = '';
        $('#menu-page #right_optional_ing_meat option:selected').each(function(){
            var id = $(this).data('id');
            right_optional_ing_meal = right_optional_ing_meal + id + ',';
        });
        var right_optional_ing_meal = right_optional_ing_meal.slice(0,-1);

        /*****  veggie  ****/
        var right_optional_ing_veggie = '';
        $('#menu-page #right_optional_ing_veggie option:selected').each(function(){
            var id = $(this).data('id');
            right_optional_ing_veggie = right_optional_ing_veggie + id + ',';
        });
        var right_optional_ing_veggie = right_optional_ing_veggie.slice(0,-1);

        /*********  condiments  *****/
        var right_optional_ing_condiment = '';
        $('#menu-page #right_optional_ing_condiment option:selected').each(function(){
            var id = $(this).data('id');
            right_optional_ing_condiment = right_optional_ing_condiment + id + ',';
        });
        var right_optional_ing_condiment = right_optional_ing_condiment.slice(0,-1);

        /****************************************************/

//        var form1 = $( document).find("#form1").serialize();
//        var form2 = $( document).find("#form2").serializeArray();
//        var form3 = $( document).find("#form3").serializeArray();
//        var form4 = $( document).find("#form4").serializeArray();
        var request = $.ajax({
            url: '//' + location.host + '/add-cart',
            type: "POST",
//            data: { form1 : form1, form2 : form2, form3: form3, form4 : form4 },
            data: { left_id : left_id, left_name : left_produs, left_current_ing : left_current_ing, left_optional_ing_common: left_optional_ing_common,
                left_optional_ing_meal :left_optional_ing_meal, left_optional_ing_veggie : left_optional_ing_veggie, left_optional_ing_condiment : left_optional_ing_condiment,
                right_id : right_id, right_produs : right_produs,right_current_ing : right_current_ing, right_optional_ing_common : right_optional_ing_common,
                right_optional_ing_meal : right_optional_ing_meal, right_optional_ing_veggie : right_optional_ing_veggie, right_optional_ing_condiment : right_optional_ing_condiment,
                variation_id : variation_id, size : size, quantity :quantity, total : total, half_pizza_group_id : half_pizza_group_id, comment : comment, left_price : left_price,
                right_price : right_price},
            dataType: "json"
        });

        request.done(function( data ) {
            if(data.succes){
//               if use transition, the next page dialog don't work
                window.location.href = '//' + location.host + '/cart';
            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    }

    /******************************  DEAL  ********************************/

    /**  Change quantity  */
//    $('#deal-quantity').change(function(){
    $(document).on('change', '#deal-quantity',function(){
        var el = $(this).val();
        var single_price = $('#menu-page #deal-price').val();

        var total = parseFloat(el) * parseFloat(single_price);

        $('#menu-page #deal-total').val(total);
        $('#menu-page .deal-total-span').text(total);
    });

    function calculate_total_deal(){

        var quantity = $('#menu-page #deal-quantity').val();
        var total = parseFloat($('#menu-page #deal-price').val()) * parseFloat(quantity);
        var pizza1 = $('#menu-page #deal-pizza1').find('option:selected').data('price');
        var pizza2 = $('#menu-page #deal-pizza2').find('option:selected').data('price');
        var meal1 = $('#menu-page #deal-meal1').find('option:selected').data('price');
        var meal2 = $('#menu-page #deal-meal2').find('option:selected').data('price');
        var drink = $('#menu-page #deal-drink').find('option:selected').data('price');

        if(pizza1 != null){

            total = parseFloat(total)  + parseFloat(pizza1)* parseFloat(quantity);
        }
        if(pizza2 != null){
            total = parseFloat(total)  + parseFloat(pizza2)* parseFloat(quantity);
        }
        if(meal1 != null){
            total = parseFloat(total)  + parseFloat(meal1)* parseFloat(quantity);
        }
        if(meal2 != null){
            total = parseFloat(total)  + parseFloat(meal2)* parseFloat(quantity);
        }
        if(drink != null){
            total = parseFloat(total)  + parseFloat(drink)* parseFloat(quantity);
        }

        $('#menu-page #deal-total').val(total);
        $('#menu-page .deal-total-span').text(total);
    }

    $('#menu-page #deal-pizza1').change(function(){
        calculate_total_deal();
    });

    $('#menu-page #deal-pizza2').change(function(){
        calculate_total_deal();
    });

    $('#menu-page #deal-meal1').change(function(){
        calculate_total_deal();
    });

    $('#menu-page #deal-meal2').change(function(){
        calculate_total_deal();
    });

    $('#menu-page #deal-drink').change(function(){
        calculate_total_deal();
    });


    /*********************** Change deal comment ********************************/
//    $('.deal-comment').keyup(function(){
    $(document).on('keyup', '.deal-comment',function(){
        var text = $(this).val();
        $('#menu-page .deal-comment').val(text);
    });

    /******************** Back to deal  ********************/
//    $('#back-to-deal').click(function(){
//        alert('asdsa');
//        $('#form5').find('#deal-pizza1').val('');
//        $('#form5').find('#deal-pizza2').val('');
//        $('#form5').find('#deal-meal1').val('');
//        $('#form5').find('#deal-meal2').val('');
//        $('#form5').find('#deal-drink').val('');
//    });

//    $('#add-deal').on('click',function(){
    $(document).on('click', '#add-deal',function(){
        var pizza1 =$('#menu-page #deal-pizza1').val();
        var pizza2 =$('#menu-page #deal-pizza2').val();
        var meal1 =$('#menu-page #deal-meal1').val();
        var meal2 =$('#menu-page #deal-meal2').val();
        var drink =$('#menu-page #deal-drink').val();
        var quantity = $('#menu-page #deal-quantity').val();
        var product_id = $('#menu-page #deal-product-id').val();
        var product_name = $('#menu-page #deal-product-name').val();
        var split = $('#menu-page #deal-pizza1').find('option:selected').html();
        if (split){
            split = split.split('(')
        } else {
            var split = new Array();
            split[0] = '';
        }
        var split2 = $('#menu-page #deal-pizza2').find('option:selected').html();
        if (split2){
            split2 = split2.split('(')
        } else {
            var split2 = new Array();
            split2[0] = '';
        }
        var split3 = $('#menu-page #deal-drink').find('option:selected').html();
        if (split3){

            split3 = split3.split('(')
        } else {
            var split3 = new Array();
            split3[0] = '';
        }
        var split4 = $('#menu-page #deal-meal1').find('option:selected').html();
        if (split4){
            split4 = split4.split('(')
        } else {
            var split4 = new Array();
            split4[0] = '';
        }
        var split5 = $('#menu-page #deal-meal2').find('option:selected').html();
        if (split5){
            split5 = split5.split('(')
        } else {
            var split5 = new Array();
            split5[0] = '';
        }
        var comment = $('#menu-page .deal-comment').val();
        var total = $('#menu-page #deal-total').val();


        var request = $.ajax({
            url: '//' + location.host + '/add-cart-deal',
            type: "POST",
            data: { pizza1 : pizza1, pizza2 : pizza2, meal1 : meal1, meal2 : meal2, drink : drink,
                quantity : quantity, product_id : product_id, product_name : product_name, pizza1_name : split[0],
                pizza2_name : split2[0], drink_name : split3[0],  comment:comment, total:total, meal1_name : split4[0],  meal2_name : split5[0]},
            dataType: "json"
        });

        request.done(function( data ) {
            if(data.succes){
                window.location.href = '//' + location.host + '/menu/order';
//                $.mobile.changePage( '//' + location.host + '/menu', { transition: 'flip' });
            }
        });

        request.fail(function( jqXHR, textStatus ) {
            alert( "Request failed: " + textStatus );
        });
    });
});