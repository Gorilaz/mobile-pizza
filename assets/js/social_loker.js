
// -
// Notice: read the documentation that comes with the plugins to get more details

// IMPORTANT: this is an obligatory wrapper, it runs after loading a page
jQuery(document).ready(function ($) {


    /**
     * Need to check is present global vars
     * @type String|FACEBOOKAPPID
     */
    var FBAppIDLock = '';
    if( typeof FACEBOOKAPPID == 'string' )
    {
        FBAppIDLock = FACEBOOKAPPID;
    }
    /* use after for Facebook App ID - FBAppIDLock */

    // --
    // Secrets style
    // --

    $("#to-lock-2").sociallocker({

        // common url for every social button
        url: "http://codecanyon.net/item/social-locker-for-jquery/3408941?ref=onepress&" + Math.random(),

        // a theme name that will be used
        theme: "secrets",

        buttons: {
            order: [
                "twitter-tweet", "facebook-share"
            ]
        },

        // text that appears above the social buttons
        text: {
            header: "",
            message: "Free coke? Like us and it's yours!"
            },

        facebook: {
            share: {
            // url to share
            url: "http://pizzaboy.y11.in/"
            },
            appId: FBAppIDLock
        },

        twitter: {
            tweet: {
            url: 'http://pizzaboy.y11.in/',
            text: 'I am just having a dinner delivered from'
            }
        }
    });
});
