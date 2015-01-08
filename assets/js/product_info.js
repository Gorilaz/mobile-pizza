(function(window, PhotoSwipe){
    if (window.document.querySelectorAll('#photo_swipe').length > 0) {
        document.addEventListener('DOMContentLoaded', function(){

            var
                options = {},
                instance = PhotoSwipe.attach( window.document.querySelectorAll('#photo_swipe'), options );

        }, false);
    }


}(window, window.Code.PhotoSwipe));

$(function(){
    $('#add-to-cart-trigger').bind('click',function(){
        jQuery.ajax({
            url: base_url + 'cart_add_item/' + $(this).data('product-id'),
            type: "POST",
            data: $('.cartable').serialize(),
            success: function(result)
            {
                $.mobile.hidePageLoadingMsg();
                result = $.parseJSON(result);
                window.location.href = result.redirect;
            },
            error: function(e){
                $.mobile.hidePageLoadingMsg();
                console.log(e);
            }
        });
    });
});