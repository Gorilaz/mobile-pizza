
if( typeof SOCIALRETURNURL == 'string' )
{
    if( SOCIALRETURNURL != '' )
    {
        SOCIALRETURNURL = '//' + location.host + '/' + SOCIALRETURNURL;
    }
} else {
    SOCIALRETURNURL = '';
}


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
    console.log(response);

    if(response && response.status == 'connected') {
        FB.api('/me?fields=id,name,first_name,last_name,email', function(me) {
            console.log(me); return undefined;

            $.ajax({
                url: '//' + location.host + '/security/facebook-login',
                data: me,
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    if(
                        typeof result.fields != 'undefined'
                        && result.fields == 'requare'
                       )
                    {
                        window.location.href = '//' + location.host + '/security-edit';
                    } else {
                        if( result.error != '' ) {
                            showAlert( 'Authorization', result.error );
                        } else {
                            if( !!SOCIALRETURNURL )
                            {
                                window.location.href = SOCIALRETURNURL;
                            }
                            else
                            {
                                window.location.reload();
                            }
                        }
                    }
                },
                error: function(e){
                    console.log(e);
                }
            });
        });
    }
}

/** By default country is Australia - get states */

/** Google Plus --------------------------------------------------- **/
function googlePlusloginCallback(result)
{
    if(result['status']['signed_in'])
    {
        var request = gapi.client.plus.people.get(
        {
            'userId': 'me'
        });

        request.execute(function(resp)
        {
            var email = '';
            if( typeof resp['emails'] == "object" )
            {
                for(i = 0; i < resp['emails'].length; i++)
                {
                    if(resp['emails'][i]['type'] == 'account')
                    {
                        email = resp['emails'][i]['value'];
                    }
                }
            }

            var firstName = '';
            var lastName = '';
            if( typeof resp['name'] == "object" )
            {
                var tmp = resp['name'];
                firstName = tmp.familyName;
                lastName = tmp.givenName;
            }
            var formdata = [
                            { name: "first_name", value: firstName },
                            { name: "last_name", value: lastName },
                            { name: "email", value: email}
                           ];
            $.ajax({
                url: '//' + location.host + '/security/googleplus-login',
                data: formdata,
                type: "POST",
                dataType: 'json',
                success: function(result) {
                    if(
                        typeof result.fields != 'undefined'
                        && result.fields == 'requare'
                       )
                    {
                        window.location.href = '//' + location.host + '/security-edit';
                    } else {
                        if( result.error != '' ) {
                            showAlert( 'Authorization', result.error );
                        } else {
                            if( !!SOCIALRETURNURL )
                            {
                                window.location.href = SOCIALRETURNURL;
                            }
                            else
                            {
                                window.location.reload();
                            }
                        }
                    }
                },
                error: function(e){
                    console.error(e);
                }
            });
        });
     }
}

/**
 * Need to check is present global vars
 * @type String|GOOGLEPLUSAPPKEY
 * @type String|GOOGLEPLUSCLIENTID
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
        'cookiepolicy' : document.location.origin,
        'callback' : 'googlePlusloginCallback',
        'approvalprompt':'force',
        'scope' : 'https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read'
    };
    gapi.auth.signIn(myParams);
}

function googleOnLoadCallback()
{
    gapi.client.setApiKey(googleappkey);
    gapi.client.load('plus', 'v1', function(){});
}

/** --------------------------------------------------------------- **/
