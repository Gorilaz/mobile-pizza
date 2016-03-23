
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
        goolePluslogin();
    });
});

function authFBUser() {
    FB.login(checkLoginStatus, {scope:'email'});
}

// Check the result of the user status and display login button if necessary
function checkLoginStatus(response) {
    if(response && response.status == 'connected') {
        FB.api('/me?fields=id,name,first_name,last_name,email,location', function(me) {
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

/** Google Plus --------------------------------------------------- **/
function loginCallback(result)
{
    if(result['status']['signed_in'])
    {
        var request = gapi.client.plus.people.get(
        {
            'userId': 'me'
        });
        request.execute(function (resp)
        {
           console.log(resp);
           alert('stop');
        });
     }
}

/**
 * Need to check is present global vars
 * @type String|GOOGLEPLUSAPPKEY
 */
var googleappkey = '';
if( typeof GOOGLEPLUSAPPKEY == 'string' )
{
    googleappkey = GOOGLEPLUSAPPKEY;
    googleclientid = GOOGLEPLUSCLIENTID;
}
var googleclientid = '';
if( typeof GOOGLEPLUSCLIENTID == 'string' )
{
    googleclientid = GOOGLEPLUSCLIENTID;
}
/* use after for GooglePlus - var googleappkey and googleclientid */

function goolePluslogin()
{
    var myParams = {
        'clientid' : googleclientid,
        'cookiepolicy' : 'http://mobile.bluestarpizza.com.au',
        'callback' : 'loginCallback',
        'approvalprompt':'force',
        'scope' : 'https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read'
    };
    gapi.auth.signIn(myParams);
}

function onLoadCallback()
{
    gapi.client.setApiKey(googleappkey);
    gapi.client.load('plus', 'v1', function(){});
}

/** --------------------------------------------------------------- **/
