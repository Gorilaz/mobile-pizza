
window.fbAsyncInit = function() {
    
    /**
     * Need to check is present global vars
     * @type String|FACEBOOKAPPID
     */
    var FBAppIDAsync = '';
    if( typeof FACEBOOKAPPID == 'string' )
    {
        FBAppIDAsync = FACEBOOKAPPID;
    }
    /* use after for Facebook App ID - FBAppIDLock */
    
    // init the FB JS SDK
    FB.init({
        appId      : FBAppIDAsync,                   // App ID from the app dashboard
        status     : true                                 // Check Facebook Login status
    });

    // Additional initialization code such as adding Event Listeners goes here
};

// Load the SDK asynchronously
(function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/all.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

$(function(){
    $('#loginButton').click(function(){
        authFBUser();
    });
    $('#googleLoginButton').click(function(){
        document.location.href = '/security/googleplus_page';
    });
});

function authFBUser() {

    FB.login(checkLoginStatus, {scope:'email'});

}


// Check the result of the user status and display login button if necessary

function checkLoginStatus(response) {
    if(response && response.status == 'connected') {
        FB.api('/me', function(me) {
            $.ajax({

                url: '//' + location.host + '/security/facebook_login',

                data: me,

                type: "POST",

                success: function(result) {
                    window.location.href = '//' + location.host + '/menu';

                },

                error: function(e){

                    console.error(e);

                }

            });

        });



        // Hide the login button

//        document.getElementById('loginButton').style.display = 'none';

    } else {

//        alert('User is not authorized');



        // Display the login button

//        document.getElementById('loginButton').style.display = 'block';

    }

}

/** By default country is Australia - get states */