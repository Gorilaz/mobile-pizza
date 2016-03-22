/**
 * Preset resources for Social Locker
 * for jQuery: http://onepress-media.com/plugin/social-locker-for-jquery/get
 * for Wordpress: http://onepress-media.com/plugin/social-locker-for-wordpress/get
 *
 * Copyright 2013, OnePress, http://onepress-media.com/portfolio
 * Help Desk: http://support.onepress-media.com/
 */

(function ($) {

    /**
    * Text resources.
    */

    if (!$.onepress) $.onepress = {};
    if (!$.onepress.sociallocker) $.onepress.sociallocker = {};
    if (!$.onepress.sociallocker.lang) $.onepress.sociallocker.lang = {};

    $.onepress.sociallocker.lang = {

        defaultHeader:      "This content is locked!",
        defaultMessage:     "Please support us, use one of the buttons below to unlock the content.",
        orWait:             'or wait',
        seconds:            's',   
        close:              'Close',
        
        // default button labels
        facebook_like:      'like us',
        facebook_share:     'share',
        twitter_tweet:      'tweet',  
        twitter_follow:     'follow us on twitter',  
        google_plus:        '+1 us',  
        google_share:       'share',
        linkedin_share:     'share'
    };
    
    /**
    * Available buttons
    */
   
    if (!$.onepress.sociallocker.buttons) $.onepress.sociallocker.buttons = {};
    $.onepress.sociallocker.buttons = [
        "facebook-like",
        "facebook-share",
        "google-plus",
        "google-share",
        "twitter-tweet",
        "twitter-follow",
        "linkedin-share",    
        "#"
    ]

    /**
    * Presets options for styles.
    * You can add some options that will be applied when a specified theme is used.
    */

    if (!$.onepress.sociallocker.presets) $.onepress.sociallocker.presets = {};
    
    /* starter theme */

    $.onepress.sociallocker.presets['starter'] = {
        
        buttons: {
            layout: 'horizontal',
            counter: true
        },
        effects: {
            flip: false
        }
    };
    
    /* secrets theme */
    
    $.onepress.sociallocker.presets['secrets'] = {

        buttons: {
            layout: 'horizontal',
            counter: true
        },
        effects: {
            flip: true
        },
        
        triggers: {
            overlayRender: function(options, networkName, buttonName, isTouch){
                var overlay = isTouch ? $("<a></a>") : $("<div></div>");
                var title = options.title || $.onepress.sociallocker.lang[networkName + "_" + buttonName];
                
                overlay.addClass("onp-sociallocker-button-overlay") 
                      .append(
                       $("<div class='onp-sociallocker-overlay-front'></div>")
                            .append($("<div class='onp-sociallocker-overlay-icon'></div>"))
                            .append($("<div class='onp-sociallocker-overlay-text'>" + title + "</div>"))
                       )
                      .append($("<div class='onp-sociallocker-overlay-header'></div>"))
                      .append($("<div class='onp-sociallocker-overlay-back'></div>"));
                
                return overlay;
            }
        }
    };
    
    /* dandyish theme */
    
    $.onepress.sociallocker.presets['dandyish'] = {

        buttons: {
            layout: 'vertical',
            counter: true,
            unsupported: ['twitter-follow']
        },
        effects: {
            flip: false
        }
    };
    
    /* glass theme */
    
    $.onepress.sociallocker.presets['glass'] = {
        _iPhoneBug: false,
        
        buttons: {
            layout: 'horizontal',
            counter: true
        },
        effects: {
            flip: false
        }
    };

})(jQuery);;;

/**
* Helper Tools:
* - cookies getter/setter
* - md5 hasher
* - lightweight widget factory
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';

    if (!$.onepress) $.onepress = {};
    if (!$.onepress.tools) $.onepress.tools = {};

    /*
    * Cookie's function.
    * Allows to set or get cookie.
    *
    * Based on the plugin jQuery Cookie Plugin
    * https://github.com/carhartl/jquery-cookie
    *
    * Copyright 2011, Klaus Hartl
    * Dual licensed under the MIT or GPL Version 2 licenses.
    * http://www.opensource.org/licenses/mit-license.php
    * http://www.opensource.org/licenses/GPL-2.0
    */
    $.onepress.tools.cookie = $.onepress.tools.cookie || function (key, value, options) {

        // Sets cookie
        if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value === null || value === undefined)) {
            options = $.extend({}, options);

            if (value === null || value === undefined) {
                options.expires = -1;
            }

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = String(value);

            return (document.cookie = [
                encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '',
                options.path ? '; path=' + options.path : '',
                options.domain ? '; domain=' + options.domain : '',
                options.secure ? '; secure' : ''
            ].join(''));
        }

        // Gets cookie.
        options = value || {};
        var decode = options.raw ? function (s) { return s; } : decodeURIComponent;

        var pairs = document.cookie.split('; ');
        for (var i = 0, pair; pair = pairs[i] && pairs[i].split('='); i++) {
            if (decode(pair[0]) === key) return decode(pair[1] || '');
        }
        return null;
    };

    /*
    * jQuery MD5 Plugin 1.2.1
    * https://github.com/blueimp/jQuery-MD5
    *
    * Copyright 2010, Sebastian Tschan
    * https://blueimp.net
    *
    * Licensed under the MIT license:
    * http://creativecommons.org/licenses/MIT/
    * 
    * Based on
    * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
    * Digest Algorithm, as defined in RFC 1321.
    * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
    * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
    * Distributed under the BSD License
    * See http://pajhome.org.uk/crypt/md5 for more info.
    */
    $.onepress.tools.hash = $.onepress.tools.hash || function (str) {

        var hash = 0;
        if (str.length == 0) return hash;
        for (var i = 0; i < str.length; i++) {
            var charCode = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + charCode;
            hash = hash & hash;
        }
        hash = hash.toString(16);
        hash = hash.replace("-", "0");

        return hash;
    };

    /**
    * Checks does a browers support 3D transitions:
    * https://gist.github.com/3794226
    */
    $.onepress.tools.has3d = $.onepress.tools.has3d || function () {

        var el = document.createElement('p'),
            has3d,
            transforms = {
                'WebkitTransform': '-webkit-transform',
                'OTransform': '-o-transform',
                'MSTransform': '-ms-transform',
                'MozTransform': '-moz-transform',
                'Transform': 'transform'
            };

        // Add it to the body to get the computed style
        document.body.insertBefore(el, null);

        for (var t in transforms) {
            if (el.style[t] !== undefined) {
                el.style[t] = 'translate3d(1px,1px,1px)';
                has3d = window.getComputedStyle(el).getPropertyValue(transforms[t]);
            }
        }

        document.body.removeChild(el);
        return (has3d !== undefined && has3d.length > 0 && has3d !== "none");
    };

    /**
    * Returns true if a current user use a touch device
    * http://stackoverflow.com/questions/4817029/whats-the-best-way-to-detect-a-touch-screen-device-using-javascript
    */
    $.onepress.isTouch = $.onepress.isTouch || function () {

        return !!('ontouchstart' in window) // works on most browsers 
            || !!('onmsgesturechange' in window); // works on ie10
    };

    /**
    * OnePress Widget Factory.
    * Supports:
    * - creating a jquery widget via the standart jquery way
    * - call of public methods.
    */
    $.onepress.widget = function (pluginName, pluginObject) {

        var factory = {

            createWidget: function (element, options) {
                var widget = $.extend(true, {}, pluginObject);

                widget.element = $(element);
                widget.options = $.extend(true, widget.options, options);

                if (widget._init) widget._init();
                if (widget._create) widget._create();

                $.data(element, 'plugin_' + pluginName, widget);
            },

            callMethod: function (widget, methodName) {
                widget[methodName] && widget[methodName]();
            }
        };

        $.fn[pluginName] = function () {
            var args = arguments;
            var argsCount = arguments.length;

            this.each(function () {

                var widget = $.data(this, 'plugin_' + pluginName);

                // a widget is not created yet
                if (!widget && argsCount <= 1) {
                    factory.createWidget(this, argsCount ? args[0] : false);

                    // a widget is created, the public method with no args is being called
                } else if (argsCount == 1) {
                    factory.callMethod(widget, args[0]);
                }
            });
        };
    };

})(jQuery);;;

/**
* Part of jQuery Migrate plugin.
* It allows to add the support of the browser property into jquery 1.9.0+

* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

// Limit scope pollution from any deprecated API
(function() {
    if ( jQuery.browser ) return;
    
    var matched, browser;

// Use of jQuery.browser is frowned upon.
// More details: http://api.jquery.com/jQuery.browser
// jQuery.uaMatch maintained for back-compat
    jQuery.uaMatch = function( ua ) {
        ua = ua.toLowerCase();

        var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
            /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
            /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
            /(msie) ([\w.]+)/.exec( ua ) ||
            ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
            [];

        return {
            browser: match[ 1 ] || "",
            version: match[ 2 ] || "0"
        };
    };

    matched = jQuery.uaMatch( navigator.userAgent );
    browser = {};

    if ( matched.browser ) {
        browser[ matched.browser ] = true;
        browser.version = matched.version;
    }

// Chrome is Webkit, but Webkit is also Safari.
    if ( browser.chrome ) {
        browser.webkit = true;
    } else if ( browser.webkit ) {
        browser.safari = true;
    }

    jQuery.browser = browser;

    jQuery.sub = function() {
        function jQuerySub( selector, context ) {
            return new jQuerySub.fn.init( selector, context );
        }
        jQuery.extend( true, jQuerySub, this );
        jQuerySub.superclass = this;
        jQuerySub.fn = jQuerySub.prototype = this();
        jQuerySub.fn.constructor = jQuerySub;
        jQuerySub.sub = this.sub;
        jQuerySub.fn.init = function init( selector, context ) {
            if ( context && context instanceof jQuery && !(context instanceof jQuerySub) ) {
                context = jQuerySub( context );
            }

            return jQuery.fn.init.call( this, selector, context, rootjQuerySub );
        };
        jQuerySub.fn.init.prototype = jQuerySub.fn;
        var rootjQuerySub = jQuerySub(document);
        return jQuerySub;
    };

})();;;

/**
* Facebook Like Button widget for jQuery
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';
    if ($.fn.sociallocker_facebook_like) return;

    $.onepress.widget("sociallocker_facebook_like", {
        options: {},
        
        // is a user logged into Facebook?
        _loggedIntoFacebook: false,

        _defaults: {
            
            // URL to like/share
            url: null,
            
            // App Id used to get extended contol tools (optionly).
            // You can create your own app here: https://developers.facebook.com/apps	
            appId: 0,               
            // Language of the button labels. By default en_US.
            lang: 'en_US',
            // Button layout, available: standart, button_count, box_count. By default 'standart'.
            layout: 'standart',
            // Button container width in px, by default 450.
            width: 'auto',
            // The verb to display in the button. Only 'like' and 'recommend' are supported. By default 'like'.
            verbToDisplay: "like",
            // The color scheme of the plugin. By default 'light'.
            colorScheme: "light",
            // The font of the button. By default 'tahoma'.
            font: 'tahoma',
            // A label for tracking referrals.
            ref: null,
            // set to 'none' to hide the count box
            count: 'standart',
            
            // unlock event
            unlock: null
        },

        _create: function () {
            var self = this;

            this._prepareOptions();
            this._setupEvents();

            this.element.data('onepress-facebookButton', this);
            this._createButton();

            $.onepress.connector.connect("facebook", this.options, function (sdk) {
                sdk.render(self.element);
            });
        },

        _prepareOptions: function () {

            var values = $.extend({}, this._defaults);
            this.options = $.extend(values, this.options);
            this.url = (!this.options.url) ? window.location.href : this.options.url;
        },

        _setupEvents: function () {
            var self = this;

            $(document).bind('fb-like', function (e, url) {
                if (self.options.unlock && self.url == url) {
                    self.options.unlock(url, self);
                }
            });
        },

        /**
        * Generates an html code for the button using specified options.
        */
        _createButton: function () {
            var self = this;
            
            this.button = $("<div class='fake-fb-like'></div>");

            this.wrap = $("<div class='onp-social-button onp-facebook-button'></div>")
                        .appendTo(this.element)
                        .append(this.button);

            if (this.options.count == 'none') {
                this.wrap.addClass('onp-facebook-like-count-none');
                this.wrap.addClass('onp-facebook-like-' + this.options.lang);
            }

            this.button.data('facebook-widget', this);
            this.button.attr("data-show-faces", false);
            this.button.attr("data-send", false); 

            if (this.options.url) this.button.attr("data-href", this.options.url);
            if (this.options.font) this.button.attr("data-font", this.options.font);
            if (this.options.colorScheme) this.button.attr("data-colorscheme", this.options.colorScheme);
            if (this.options.ref) this.button.attr("data-ref", this.options.ref);
            if (this.options.width) this.button.attr("data-width", this.options.width);
            if (this.options.layout) this.button.attr("data-layout", this.options.layout);
            if (this.options.verbToDisplay) this.button.attr("data-action", this.options.verbToDisplay);  
        },
        
        getHtmlToRender: function () {
            return this.wrap;
        }
    });
    
})(jQuery);;;

/**
* Facebook Share Button widget for jQuery
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';
    if ($.fn.sociallocker_facebook_button) return;

    $.onepress.widget("sociallocker_facebook_share", {
        options: {},

        _defaults: {
            
            // URL to like/share
            url: null,
            counter: null,

            // App Id used to get extended contol tools (optionly).
            // You can create your own app here: https://developers.facebook.com/apps	
            appId: 0,               
            // standard or vertical
            layout: 'standard',
            // set to 'none' to hide the count box
            count: 'standart',
            
            // data to share
            name: null,
            caption: null,
            description: null,
            image: null,
            
            // unlock event
            unlock: null
        },

        _create: function () {
            var self = this;

            this._prepareOptions();

            this.element.data('onepress-facebookButton', this);
            this._createButton();

            $.onepress.connector.connect("facebook", this.options, function (sdk) {
                sdk.render(self.element);
            });
        },

        _prepareOptions: function () {

            var values = $.extend({}, this._defaults);
            this.options = $.extend(values, this.options);
            this.url = (!this.options.url) ? window.location.href : this.options.url;
        },

        /**
        * Generates an html code for the button using specified options.
        */
        _createButton: function () {
            var count = 0;
            var self = this;
            
            this.button = $("<div class='onp-facebook-share onp-facebook-layout-" + this.options.layout + "'></div>");
            this.button.append($("<a href='#' class='onp-facebook-share-icon'></a>"));
            this.button.append($("<div class='onp-facebook-share-count'>" + count + "</div>"));
            this.button.data('facebook-widget', this);
            
            this.wrap = $("<div class='onp-social-button onp-facebook-button'></div>")
                        .appendTo(this.element)
                        .append(this.button);
                        
            if (this.options.count == 'none') {
                this.button.addClass('onp-facebook-share-count-none');
            }
                        
            this.button.click(function(){
                FB.ui(
                {
                     method: 'feed',
                     name: self.options.name,
                     link: self.url,
                     picture: self.options.image,
                     caption: self.options.caption,
                     description: self.options.description
                },
                function(response) {
                    if (response && response.post_id) {
                       self.button.find('.onp-facebook-share-count').text(self.getCountLabel(count++));
                       self.options.unlock && self.options.unlock(self.url, self);
                    }
                }
            );
                
            return false;
           });
           
            $.onepress.connector.connect("facebook", this.options, function (sdk) {
                window.FB.api("/me?fields=id,name,first_name,last_name,email,location", { "id": self.options.counter ? self.options.counter : self.url }, function (response) {
                    if (response.error) return;
                    count = self.getCountLabel( response.shares || count );
                    self.button.find('.onp-facebook-share-count').text(count);
                });
            });
        },
        
        getCountLabel: function(count) {
            if ( count >= 1000000 ) return Math.floor( count / 1000000 ) + "m";
            if ( count >= 1000 ) return Math.floor( count / 1000 ) + "k";
            return count;
        },

        getHtmlToRender: function () {
            return this.wrap;
        }
    });
  
})(jQuery);;;

/**
* Google Plus One widget for jQuery
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';
    if ($.fn.sociallocker_google_button) return;
    
    $.onepress.widget("sociallocker_google_button", {
        options: {},

        _defaults: {
            
            // URL to plus one
            url: null,
            // plus or share
            type: null,
            
            // Language of the button labels. By default en-US.
            // https://developers.google.com/+/plugins/+1button/#available-languages
            lang: 'en-US',
            // small, medium, standard, tall (https://developers.google.com/+/plugins/+1button/#button-sizes)
            size: null,
            // Sets the annotation to display next to the button.
            annotation: null,
            // Button container width in px, by default 450.
            width: null,
            // Sets the horizontal alignment of the button assets within its frame.
            align: "left",
            // Sets the preferred positions to display hover and confirmation bubbles, which are relative to the button.
            // comma-separated list of top, right, bottom, left
            expandTo: "",
            // To disable showing recommendations within the +1 hover bubble, set recommendations to false.    
            recommendations: true,
            // Events
            unlock: null
        },

        _create: function () {
            var self = this;

            this._prepareOptions();
            this._setupEvents();

            this.element.data('onepress-googleButton', this);
            this._createButton();

            $.onepress.connector.connect("google", this.options, function (sdk) {
                sdk.render(self.element);
            });
        },

        _prepareOptions: function () {

            var values = $.extend({}, this._defaults);
            this.options = $.extend(values, this.options);
            this.url = (!this.options.url) ? window.location : this.options.url;
        },

        _setupEvents: function () {
            var self = this;

            if ( this.options.type == 'plus' ) {
                $(document).bind('gl-plus', function (e, url) {
                    $(".gc-bubbleDefault").hide();
                    if (self.options.unlock && (self.url == url || (self.url + '/') == url)) {
                        self.options.unlock(url, self);
                    }
                });
            }
            
            if ( this.options.type == 'share' ) {
                $(document).bind('gl-share', function (e, url) {
                    $(".gc-bubbleDefault").hide();
                    if (self.options.unlock && (self.url == url || (self.url + '/') == url)) {
                        self.options.unlock(url, self);
                    }
                });  
            }     
        },

        /**
        * Generates an html code for the button using specified options.
        */
        _createButton: function () {

            this.button = ( this.options.type == 'plus' )
                                ? $("<div class='fake-g-plusone'></div>")
                                : $("<div class='fake-g-share'></div>");
                                
            this.wrap = $("<div class='onp-social-button onp-google-button'></div>")
                        .appendTo(this.element)
                        .append(this.button);
            
            this.button.data('google-widget', this);

            if (this.options.url) this.button.attr("data-href", this.options.url);
            if (this.options.size) this.button.attr("data-size", this.options.size);
            if (this.options.annotation) this.button.attr("data-annotation", this.options.annotation);
            if (this.options.align) this.button.attr("data-align", this.options.align);
            if (this.options.expandTo) this.button.attr("data-expandTo", this.options.expandTo);
            if (this.options.recommendations) this.button.attr("data-recommendations", this.options.recommendations);
        },

        getHtmlToRender: function () {
            return this.wrap;
        }
    });
    
    $.fn.sociallocker_google_plus = function( options ){
        options.type = 'plus';
        return $(this).sociallocker_google_button(options);
    };
    
    $.fn.sociallocker_google_share = function( options ){
        options.type = 'share';
        return $(this).sociallocker_google_button(options);
    };
    
})(jQuery);;;

/**
* Twitter Button widget for jQuery
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';
    if ($.fn.sociallocker_twitter_button) return;
    
    $.onepress.widget("sociallocker_twitter_button", {

        options: {},

        _defaults: {

            // URL of the page to share.
            url: null,
            // tweet or follow button
            type: null,

            // Default Tweet text
            // [tweet]
            text: null,
            // Screen name of the user to attribute the Tweet to
            // [tweet]
            via: null,
            // The user's screen name shows up by default, but you can opt not to 
            // show the screen name in the button. 
            // [follow]
            showScreenName: false,
            // Related accounts
            // [tweet]
            related: null,
            // Count box position (none, horizontal, vertical)
            // [tweet]
            count: 'horizontal',
            // Followers count display
            // [follow]
            showCount: true,
            // The language for the Tweet Button
            // [tweet][follow]
            lang: 'en',
            // URL to which your shared URL resolves
            // [tweet]
            counturl: null,
            // The size of the rendered button (medium, large)
            size: 'large',
            
            // unlock event
            unlock: null
        },

        _create: function () {
            var self = this;

            this._prepareOptions();
            this._setupEvents();

            this.element.data('onepress-twitterButton', this);
            this._createButton();

            $.onepress.connector.connect("twitter", this.options, function (sdk) {
                sdk.render(self.element);
            });
        },

        _prepareOptions: function () {

            var values = $.extend({}, this._defaults);

            for (var prop in this._defaults) {
                if (this.element.data(prop) !== undefined) values[prop] = this.element.data(prop);
            }

            this.options = $.extend(values, this.options);

            if (!this.options.url && $("link[rel='canonical']").length > 0)
                this.options.url = $("link[rel='canonical']").attr('href');
            
            this.url = this.options.url || window.location.href;

        },

        _setupEvents: function () {
            var self = this;

            $(document).bind('tw-tweet', function (e, target, data) {
                if (self.options.type != 'tweet') return;
                var url = $(target).parent().attr('data-url-to-compare');
                if (self.url == url && self.options.unlock) self.options.unlock && self.options.unlock(url, self);
            });
            
            $(document).bind('tw-follow', function (e, target, data) {
                if (self.options.type != 'follow') return;
                var url = $(target).parent().attr('data-url-to-compare');
                if (self.url == url && self.options.unlock) self.options.unlock && self.options.unlock(url, self);
            });   
        },

        /**
        * Generates an html code for the button using specified options.
        */
        _createButton: function () {

            // What will title be used?
            var title;
            if (this.options.type == 'follow') {
                title = 'Follow Me';
                if (!this.options.url) title = "[Error] Setup an URL of your Twitter account.";
            } else {
                title = 'Tweet';
            }

            this.button = $("<a href='https://twitter.com/share'>" + title + "</a>");
            this.button.data('twitter-widget', this);
            
            this.wrap = $("<div class='onp-social-button onp-twitter-button'></div>")
                        .appendTo(this.element)
                        .append(this.button);
            
            if (this.options.type == 'tweet') {
                this.wrap.addClass('onp-twitter-tweet');
                this.button.addClass('twitter-share-button');
            }
            if (this.options.type == 'follow') {
                this.wrap.addClass('onp-twitter-follow');
                this.button.addClass('twitter-follow-button');
            }

            if (this.options.type == 'follow') this.button.attr('href', this.url);
            else this.button.attr("data-url", this.url);

            this.button.attr("data-show-count", this.options.showCount);
            if (this.options.via) this.button.attr("data-via", this.options.via);
            if (this.options.text) this.button.attr("data-text", this.options.text);
            if (this.options.related) this.button.attr("data-related", this.options.related);
            if (this.options.count) this.button.attr("data-count", this.options.count);
            if (this.options.showCount) this.button.attr("data-show-count", this.options.showCount); 
            if (this.options.lang) this.button.attr("data-lang", this.options.lang);
            if (this.options.counturl) this.button.attr("data-counturl", this.options.counturl);
            if (this.options.hashtags) this.button.attr("data-hashtags", this.options.hashtags);
            if (this.options.alignment) this.button.attr("data-alignment", this.options.alignment);
            if (this.options.size) this.button.attr("data-size", this.options.size);
            if (this.options.dnt) this.button.attr("data-dnt", this.options.dnt);
            if (this.options.showScreenName) this.button.attr("data-show-screen-name", this.options.showScreenName);
            
            this.wrap.attr('data-url-to-compare', this.url);
        },

        getHtmlToRender: function () {
            return this.button;
        }
    });
    
    $.fn.sociallocker_twitter_tweet = function( options ){
        options.type = 'tweet';
        return $(this).sociallocker_twitter_button(options);
    };
    
    $.fn.sociallocker_twitter_follow = function( options ){

        options.type = 'follow';
        return $(this).sociallocker_twitter_button(options);
    };

})(jQuery);;;

/**
* LinkedIn Share Button widget for jQuery
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';
    if ($.fn.sociallocker_linkedin_share) return;
    
    $.onepress.widget("sociallocker_linkedin_share", {

        options: {},

        _defaults: {

            // URL of the page to share.
            url: null,

            // Count box position (none, horizontal, vertical)
            // [tweet]
            counter: 'right',
            
            // unlock event
            unlock: null
        },

        _create: function () {
            var self = this;

            this._prepareOptions();
            this._setupEvents();
            this._createButton();
            this.element.data('linkedin-widget', this);
            
            $.onepress.connector.connect("linkedin", this.options, function (sdk) {
                sdk.render(self.element);
            });
        },

        _prepareOptions: function () {

            var values = $.extend({}, this._defaults);

            for (var prop in this._defaults) {
                if (this.element.data(prop) !== undefined) values[prop] = this.element.data(prop);
            }

            this.options = $.extend(values, this.options);
            this.url = this.options.url || window.location.href;

        },

        _setupEvents: function () {
            var self = this;

            $(document).bind('ln-share', function (e, url) {
                if (self.url == url && self.options.unlock) self.options.unlock && self.options.unlock(url, self);
            });
        },

        /**
        * Generates an html code for the button using specified options.
        */
        _createButton: function () {

            // What will title be used?
            
            this.button = $('<script type="IN/Share"></script>');
            this.wrap = $("<div class='onp-social-button onp-linkedin-button'></div>")
                        .appendTo(this.element)
                        .append(this.button);

            if (this.options.counter) this.button.attr("data-counter", this.options.counter);
            if (this.options.url) this.button.attr("data-url", this.url);
        },

        getHtmlToRender: function () {
            return this.button;
        }
    });

})(jQuery);;;

/**
* OnePress Local State Provider
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';

    if (!$.onepress) $.onepress = {};
    if (!$.onepress.providers) $.onepress.providers = {};

    /**
    * Returns a state provide for the Strict Mode.
    */
    $.onepress.providers.clientStoreStateProvider = function (networkName, buttonName, url, options) {

        this.networkName = networkName;
        this.buttonName = buttonName;
        this.name = networkName + "-" + buttonName;
        
        this.demo = options.demo;
        this.useCookies = options.locker.useCookies;
        this.cookiesLifetime = options.locker.cookiesLifetime;
        
        this.url = url;
        this.identity = "page_" + $.onepress.tools.hash(this.url) + "_hash_" + networkName + "-" + buttonName;

        /**
        * Does the provider contain an unlocked state?
        */
        this.isUnlocked = function () {
            if (this.demo) return false;
            return (this._getValue()) ? true : false;
        };

        /**
        * Does the provider contain a locked state?
        */
        this.isLocked = function () {
            return !this.isUnlocked();
        };

        /**
        * Gets a state and calls the callback with the one.
        */
        this.getState = function (callback) {
            if (this.demo) return callback(false);
            callback(this.isUnlocked());
        };

        /**
        * Sets state of a locker to provider.
        */
        this.setState = function (value) {
            if (this.demo) return true;
            return value == "unlocked" ? this._setValue() : this._removeValue();
        };

        this._setValue = function () {
            var self = this;

            return localStorage && !this.useCookies
                ? localStorage.setItem(this.identity, true)
                : $.onepress.tools.cookie(this.identity, true, { expires: self.cookiesLifetime, path: "/" });
        };

        this._getValue = function () {

            if (localStorage && !this.useCookies) {

                var value = localStorage.getItem(this.identity);
                if (value) return value;

                value = $.onepress.tools.cookie(this.identity);
                if (value) this._setValue();

                return value;
            }

            return $.onepress.tools.cookie(this.identity);

        };

        this._removeValue = function () {
            if (localStorage) localStorage.removeItem(this.identity);
            $.onepress.tools.cookie(this.identity, null);
        };
    };

})(jQuery);;;

/**
* SDK Connector for Social Networks:
* - Facebook
* - Twitter
* - Google
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';

    if (!$.onepress) $.onepress = {};

    $.onepress.connector = $.onepress.connector || {

        sdk: [

        // --
        // Facebook 
        // --
        {
            name: 'facebook',
            url: '//connect.facebook.net/{lang}/all.js',
            scriptId: 'facebook-jssdk',
            hasParams: true,
            isRender: true,

            isLoaded: function () {
                return (typeof (window.FB) === "object");
            },

            pre: function () {

                // root for facebook sdk
                $("#fb-root").length == 0 && $("<div id='fb-root'></div>").appendTo($("body"));

                // sets sdk language
                var lang = (this.options && this.options.lang) || "en_US";
                this.url = this.url.replace("{lang}", lang);
            },

            createEvents: function (isLoaded) {
                var self = this;

                var load = function () {

                    window.FB.init({
                        appId: (self.options && self.options.appId) || null,
                        status: true,
                        cookie: true,
                        xfbml: true
                    });

                    window.FB.Event.subscribe('edge.create', function (response) {
                        $(document).trigger('fb-like', [response]);
                    });

                    // The initialization is executed only one time.
                    // Any others attempts will call an empty function.
                    window.FB.init = function () { };
                    $(document).trigger(self.name + '-init');
                };

                if (isLoaded) { load(); return; }

                if (window.fbAsyncInit) var predefined = window.fbAsyncInit;
                window.fbAsyncInit = function () {
                    load(); predefined && predefined();
                    window.fbAsyncInit = function () { };
                };
            },

            render: function (widget) {

                var api = widget.data('onepress-facebookButton');
                if (!api) return;

                var $html = api.getHtmlToRender();
                $html.find('.fake-fb-like').addClass('fb-like');       
                window.FB.XFBML.parse($html[0]);
            }
        },

        // --
        // Twitter 
        // --
        {
        name: 'twitter',
        url: '//platform.twitter.com/widgets.js',
        scriptId: 'twitter-wjs',
        hasParams: false,
        isRender: true,

        pre: function () {

            var canonical = ($("link[rel='canonical']").length > 0)
				    ? $("link[rel='canonical']").attr('href')
				    : null;

            $(".twitter-share-button").each(function (index, item) {
                var $item = $(item);
                var $target = $(item).parent();

                if ($target.attr('data-url-to-compare')) return;

                var url = $item.attr("data-url");
                if (!url && canonical) url = canonical;
                url = (!url) ? window.location : url;

                $item.parent().attr('data-url-to-compare', url);
            });
        },

        isLoaded: function () {
            return (typeof (window.__twttrlr) !== "undefined");
        },

        createEvents: function (isLoaded) {
            var self = this;

            var load = function () {

                window.twttr.events.bind('tweet', function (event) {
                    $(document).trigger('tw-tweet', [event.target, event.data]);
                });

                window.twttr.events.bind('follow', function (event) {
                    $(document).trigger('tw-follow', [event.target, event.data]);
                });

                $(document).trigger(self.name + '-init');
            };

            if (isLoaded) { load(); return; }

            if (!window.twttr) window.twttr = {};
            if (!window.twttr.ready) window.twttr = $.extend(window.twttr, { _e: [], ready: function (f) { this._e.push(f); } });
            
            twttr.ready(function (twttr) { load(); });
        },

        /**
        * A twitter buttons works by other way.
        * When the script loaded 
        */
        render: function (widget) {

            var api = widget.data('onepress-twitterButton');
            if (!api) return;

            var $html = api.getHtmlToRender().parent();
            var attemptCounter = 5;

            // Chrome fix
            // If there is SDK script on the same page that is loading now when a tweet button will not appear.
            // Setup special timeout function what will check 5 times when we can render the twitter button.
            var timoutFunction = function () {
                if ($html.find('iframe').length > 0) return;

                if (window.twttr.widgets && window.twttr.widgets.load) {
                    window.twttr.widgets.load($html[0]);
                    widget.trigger('tw-render');
                } else {
                    if (attemptCounter <= 0) return;
                    attemptCounter--;

                    setTimeout(function () {
                        timoutFunction();
                    }, 1000);
                }
            };

            timoutFunction();
        }
    },

    // --
    // Google 
    // --
    {
        name: 'google',
        url: '//apis.google.com/js/plusone.js',
        scriptId: 'google-jssdk',
        hasParams: true,
        isRender: true,

        pre: function () {

            // sets sdk language
            var lang = (this.options && this.options.lang) || "en";
            window.___gcfg = window.___gcfg || { lang: lang };

            window.onepressPlusOneCallback = function (data) {
                if (data.state == "on") $(document).trigger('gl-plus', [data.href]);
            };
            
            window.onepressGoogleShareCallback = function (data) {
                $(document).trigger('gl-share', [data.id]);
            };    
        },

        isLoaded: function () {
            return (typeof (window.gapi) === "object");
        },

        render: function (widget) {

            var api = widget.data('onepress-googleButton');
            if (!api) return;

            var self = this;

            setTimeout(function () {
                var $html = api.getHtmlToRender();
                
                var plusone = $html.find('.fake-g-plusone');
                if ( plusone.length > 0 ) {
                    self._addCallbackToControl($html);
                    plusone.addClass('g-plusone');
                    window.gapi.plusone.go($html[0]);
                    return;
                }
                
                var share = $html.find('.fake-g-share');
                if ( share.length > 0 ) {
                    share.attr("data-onendinteraction", "onepressGoogleShareCallback");
                    share.addClass('g-plus').attr('data-action', 'share');

                    window.gapi.plus.go($html[0]);
                    return;
                }
 
            }, 100);
        },

        _addCallbackToControl: function ($control) {

            var $elm = (!$control.is(".g-plusone")) ? $control.find(".fake-g-plusone") : $control;

            var callback = $elm.attr("data-callback");
            if (callback && callback != "onepressPlusOneCallback") {
                var newCallback = "__plusone_" + callback;
                window[newCallback] = function (data) {
                    window[callback](data);
                    window.onepressPlusOneCallback(data);
                };
                $elm.attr("data-callback", newCallback);
            } else {
                $elm.attr("data-callback", "onepressPlusOneCallback");
            }
        }
    },
    
    // --
    // Linked In 
    // --
    {
        name: 'linkedin',
        url: '//platform.linkedin.com/in.js',
        scriptId: 'linkedin-jssdk',
        hasParams: false,
        isRender: true,

        pre: function () {

            window.onepressLinkedInShareCallback = function (data) {
                $(document).trigger('ln-share', [data]);
            };
        },

        isLoaded: function () {
            return (typeof (window.IN) === "object");
        },

        render: function (widget) {

            var api = widget.data('linkedin-widget');
            if (!api) return;

            setTimeout(function () {
                var $html = api.getHtmlToRender();
                $html.attr("data-onsuccess", "onepressLinkedInShareCallback");
                IN.init();
            }, 100);
        }
    }
    ],

    // contains dictionary sdk_name => is_sdk_ready (bool)
    _ready: {},

    // contains dictionaty sdk_name => is_sdk_connected (bool)
    _connected: {},

    /**
    * Get SDK object by its name.
    */
    getSDK: function (name) {

        for (var index in this.sdk) if (this.sdk[index].name == name) return this.sdk[index];
        return null;
    },

    /**
    * Checks whether a specified SDK is connected (sdk script is included into a page).
    */
    isConnected: function (sdk) {
        if ( $("#" + sdk.scriptId).length > 0 ) return true;

        var found = false;
        $("script").each(function(){
            var src = $(this).attr('src');
            if ( !src ) return true;
            
            found = src.indexOf(sdk.url) !== -1;
            if ( found ) { 
                $(this).attr('id', sdk.scriptId);
                return false;
            }
        });
        return found;
    },

    /**
    * Gets loading SDK script on a page.
    */
    getLoadingScript: function (sdk) {
        var byId = $("#" + sdk.scriptId);
        var byScr = $("script[src='*" + sdk.url + "']");
        return (byId.length > 0) ? byId : byScr;
    },

    /**
    * Checks whether a specified SQK is loaded and ready to use.
    */
    isLoaded: function (sdk) {
        return this.isConnected(sdk) && sdk.isLoaded && sdk.isLoaded();
    },

    /**
    * Connects SKD if it's needed then calls callback.
    */
    connect: function (name, options, callback) {
        var self = this, sdk = this.getSDK(name);

        if (!sdk) {
            console && console.log('Invalide SDK name: ' + name);
            return;
        }

        sdk.options = options;

        // fire or bind callback
        if (callback) this._ready[name]
                ? callback(sdk)
                : $(document).bind(name + "-init", function () { callback(sdk); });

        if (this._connected[name]) return;

        // sets the default method if it's not specified
        if (!sdk.createEvents) {
            sdk.createEvents = function (isLoaded) {
                var selfSDK = this;

                var load = function () {
                    $(document).trigger(selfSDK.name + '-init');
                };

                if (isLoaded) { load(); return; }

                $(document).bind(selfSDK.name + "-script-loaded", function () {
                    load();
                });
            };
        }

        if (sdk.pre) sdk.pre();

        var loaded = this.isLoaded(sdk);
        var connected = this.isConnected(sdk);

        $(document).bind(name + "-init", function () { self._ready[name] = true; });

        // subscribes to events
        sdk.createEvents(loaded);

        // conencts sdk
        if (!connected) {

            var scriptConnection = function () {

                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.id = sdk.scriptId;
                script.src = sdk.url;

                var bodyElement = document.getElementsByTagName('body')[0];
                bodyElement.appendChild(script);
            };

            sdk.isRender
                ? scriptConnection()
                : $(function () { $(function () { scriptConnection(); }); });
        }

        // subsribes to onload event
        if (!loaded) {

            var loadingScript = this.getLoadingScript(sdk)[0];

            if (loadingScript) {
                loadingScript.onreadystatechange = loadingScript.onload = function () {
                    var state = loadingScript.readyState;
                    if ((!state || /loaded|complete/.test(state))) $(document).trigger(sdk.name + '-script-loaded');
                };
            }
        }

        this._connected[name] = true;
    }
};

})(jQuery);;;

/**
* Social Locker
* for jQuery: http://onepress-media.com/plugin/sociallocker-for-jquery/get
* for Wordpress: http://onepress-media.com/plugin/sociallocker-for-wordpress/get
*
* Copyright 2013, OnePress, http://onepress-media.com/portfolio
* Help Desk: http://support.onepress-media.com/
*/

(function ($) {
    'use strict';
    if ($.fn.sociallocker) return;

    $.onepress.widget("sociallocker", {

        options: {},

        // The variable stores a current locker state.
        _isLocked: false,

        // Defauls option's values.
        _defaults: {
            _iPhoneBug: false,
            
            // Url that used to like/tweet/plusone.
            // It's obligatory to check whether a user likes a page.
            url: null,

            // Text above the locker buttons.
            text: {
                header: $.onepress.sociallocker.lang.defaultHeader,
                message: $.onepress.sociallocker.lang.defaultMessage
            },

            // Theme applied to the locker
            theme: "starter",

            // Sets whether the locker keep the state of always appears
            demo: false,

            // Social buttons to use
            buttons: {

                // horizontal or vertical
                layout: 'horizontal',

                // an order of the buttons, available buttons:
                // -
                // twitter: twitter-tweet, twitter-follow
                // facebook: facebook-like, facebook-share
                // google: google-plus, google-share
                // -
                order: ["twitter-tweet", "facebook-like", "google-plus"],

                // fixed or auto
                size: "auto",

                // hide or show counters for the buttons
                counter: true
            },

            // --
            // Locker functionality.
            locker: {

                // Sets whether a user may remove the locker by a cross placed at the top-right corner.
                close: false,
                // Sets a timer interval to unlock content when the zero is reached.
                // If the value is 0, the timer will not be created. 
                timer: 0,
                // Sets whether the locker appears for mobiles devides.
                mobile: true,

                // force to use cookies instead of a local storage
                useCookies: false,
                // the number of days for cookies lifetime
                cookiesLifetime: 3560
            },

            // -
            // Content that will be showen after unlocking.
            // -
            content: null,

            // --
            // Events set
            events: {

                lock: null,
                unlock: null,
                ready: null,

                unlockByCross: null,
                unlockByTimer: null
            },

            // --
            // Locker effects
            effects: {

                // Turns on the Flip effect.
                flip: false,

                // Turns on the Highlight effect.
                highlight: true
            },

            // --
            // Facebook Options
            facebook: {
                url: null,

                // App Id used to get extended contol tools (optionly).
                // You can create your own app here: https://developers.facebook.com/apps
                appId: null,
                // Language of the button labels. By default en_US.
                lang: 'en_US',
                // The color scheme of the plugin. By default 'light'.
                colorScheme: "light",
                // The font of the button. By default 'tahoma'.
                font: 'tahoma',
                // A label for tracking referrals.
                ref: null,
                
                // - Separeted options for each buttons.
     
                like: {
                    title: $.onepress.sociallocker.lang.facebook_like
                },
                share: {
                    title: $.onepress.sociallocker.lang.facebook_share
                }
            },

            twitter: {
                url: null,

                // Screen name of the user to attribute the Tweet to
                via: null,
                // Default Tweet text
                text: null,
                // Related accounts
                related: null,
                // The language for the Tweet Button
                lang: 'en',
                // URL to which your shared URL resolves
                counturl: null,
                
                // - Separeted options for each buttons.
     
                tweet: {
                    title: $.onepress.sociallocker.lang.twitter_tweet
                },
                follow: {
                    title: $.onepress.sociallocker.lang.twitter_follow
                }
            },

            google: {
                url: null,

                // Language of the button labels. By default en-US.
                // https://developers.google.com/+/plugins/+1button/#available-languages
                lang: 'en-US',
                // Sets the annotation to display next to the button.
                annotation: null,
                // To disable showing recommendations within the +1 hover bubble, set recommendations to false.    
                recommendations: true,
                
                // - Separeted options for each buttons.
     
                plus: {
                    title: $.onepress.sociallocker.lang.google_plus
                },
                share: {
                    title: $.onepress.sociallocker.lang.google_share
                }
            },
            
            linkedin: {
                url: null,
                counter: "right",
                
                // - Separeted options for each buttons.
                
                share: {
                    title: $.onepress.sociallocker.lang.linkedin_share 
                }
            }
        },

        /**
        * Enter point to start creating the locker. 
        */
        _create: function () {
            var self = this;

            // parse options
            this._processOptions();

            // don't show a locker in ie7
            if ($.browser.msie && parseInt($.browser.version, 10) === 7) {
                this._unlock("ie7"); return;
            }

            // check mobile devices
            if (!this.options.locker.mobile && this._isMobile()) {
                this._unlock("mobile"); return;
            }
            
            // remove buttons that are not supported by iPhone
            if ((/iPhone/i).test(navigator.userAgent) && this.options._iPhoneBug ) {
                var twitterIndex = $.inArray("twitter-tweet", this.options.buttons.order);
                if (twitterIndex >= 0) this.options.buttons.order.splice(twitterIndex, 1);
            }
            
            // remove a google share button for Opera, IE8 and mobile devices
            if ( $.browser.opera || $.browser.msie || this._isTabletOrMobile() ) {
                var googleIndex = $.inArray("google-share", this.options.buttons.order);   
                if (googleIndex >= 0) this.options.buttons.order.splice(googleIndex, 1);
            }
            
            // unlock the locker if no buttons are defined
            if (this.options.buttons.order.length == 0) {
                this._unlock("nobuttons"); return;
            }

            // creates provider
            this._controller = this._createProviderController();

            // get state to decide what our next step is
            this._controller.getState(function (state) {
                state ? self._unlock("provider") : self._lock();
                self.options.events.ready && self.options.events.ready(state);
            });
        },

        /**
        * Creates and returns a controler of providers by using the options.
        */
        _createProviderController: function () {
            var self = this;
            this._providers = {};

            var totalCount = 0;

            for (var providerIndex in this.options.buttons.order) {
                var sourceName = this.options.buttons.order[providerIndex];
                if (typeof (sourceName) != 'string') continue;
                
                if ( !this._isValidButton(sourceName) ) {
                    this._setError("The button '" + sourceName + "' not found.");
                    return;
                }
                
                // button separator
                if ( sourceName == '#' ) continue;

                var parts = sourceName.split('-');
                var networkName = parts[0];
                var buttonName = parts[1];

                var buttonOptions = $.extend({}, this.options[networkName]);
                if ( this.options[networkName][buttonName] ) buttonOptions = $.extend(buttonOptions, this.options[networkName][buttonName] );
                var url = buttonOptions.url || this.options.url || window.location.href;
                
                if ( !this._providers[sourceName] ) {
                    this._providers[sourceName] = 
                        new $.onepress.providers.clientStoreStateProvider( networkName, buttonName, url, self.options );
                    totalCount++;
                }
            }

            // controller of providers
            return {

                /**
                * Gets result state for all defined providers.
                */
                getState: function (callback) {
                    
                    var counter = totalCount;
                    var resultState = false;

                    for (var name in self._providers) {
                        var provider = self._providers[name];

                        provider.getState(function (state) {
                            counter--; resultState = resultState || state;
                            if (counter == 0) callback(resultState, provider);
                        });
                    }
                }
            };
        },

        /**
        * Processes the locker options.
        */
        _processOptions: function () {
            var theme = this.options.theme || this._defaults.theme;
            var options = $.extend(true, {}, this._defaults);

            // uses preset options
            if ($.onepress.sociallocker.presets[theme]) {
                options = $.extend(true, {}, options, $.onepress.sociallocker.presets[theme]);

                if ( 
                    $.onepress.sociallocker.presets[theme].buttons && 
                    $.onepress.sociallocker.presets[theme].buttons.order) {
                    
                    options.buttons.order = $.onepress.sociallocker.presets[theme].buttons.order;
                }
            }

            // users user defined options
            options = $.extend(true, options, this.options);

            if (this.options.buttons && this.options.buttons.order) {
                options.buttons.order = this.options.buttons.order;
            }

            options.effects.flip = options.effects.flip || (options.style == 'onp-sociallocker-secrets');

            if (options.buttons.layout == "vertical") {
                options.facebook.like.layout = "box_count";
                options.facebook.share.layout = "vertical";       
                options.twitter.count = "vertical";
                options.twitter.size = "medium";
                options.google.plus.size = "tall";
                options.google.share.annotation = "vertical-bubble";   
                options.linkedin.share.counter = "top";                   
                options.buttons.counter = true;
            }

            if (options.buttons.layout == "horizontal") {
                options.facebook.layout = "button_count";
                options.twitter.count = "horizontal";
                options.twitter.size = "medium";
                options.google.size = "medium";
                options.google.annotation = 'bubble';
                options.linkedin.share.counter = "right";   
                
                if (!options.buttons.counter) {
                    options.twitter.count = 'none';
                    options.twitter.showCount = false;        
                    options.google.annotation = 'none';
                    options.facebook.count = 'none';
                    options.linkedin.share.counter = "none";   
                }
            }
            
            if (typeof options.text != "object" || (!options.text.header && !options.text.message)) {
                options.text = { message: options.text };
            }

            if (options.text.header) {
                options.text.header = (typeof options.text.header === "function" && options.text.header(this)) ||
                                      (typeof options.text.header === "string" && $("<div>" + options.text.header + "</div>")) ||
                                      (typeof options.text.header === "object" && options.text.header.clone());
            }

            if (options.text.message) {
                options.text.message = (typeof options.text.message === "function" && options.text.message(this)) ||
                                       (typeof options.text.message === "string" && $("<div>" + options.text.message + "</div>")) ||
                                       (typeof options.text.message === "object" && options.text.message.clone());
            }

            options.locker.timer = parseInt(options.locker.timer);
            if (options.locker.timer == 0) options.locker.timer = null;

            this.options = options;
            
            // builds the css class name based on the theme name
            this.style = "onp-sociallocker-" + theme;    
        },

        /**
        * Returns true if a current user use a mobile device, else false.
        */
        _isMobile: function () {
            
            if ((/webOS|iPhone|iPod|BlackBerry/i).test(navigator.userAgent)) return true;
            if ((/Android/i).test(navigator.userAgent) && (/Mobile/i).test(navigator.userAgent)) return true;
            return false;
        },
        
        /**
        * Returns true if a current user use a mobile device or tablet device, else false.
        */
        _isTabletOrMobile: function () {
            
            if ((/webOS|iPhone|iPad|Android|iPod|BlackBerry/i).test(navigator.userAgent)) return true;
            return false;
        },
        
        /**
         * Checks whether a button is valide for the locker.
         */
        _isValidButton: function( sourceName ) {
            for ( var index in $.onepress.sociallocker.buttons ) {
                if ( $.onepress.sociallocker.buttons[index] == sourceName ) return true;;
            }
            return false;
        },

        /**
        * Sets an error state.
        */
        _setError: function (text) {
            this._error = true;
            this._errorText = text;

            this.locker && this.locker.hide();

            this.element.html("<strong>[Error]: " + text + "</strong>");
            this.element.show().addClass("onp-sociallocker-error");
        },

        // --------------------------------------------------------------------------------------
        // Markups and others.
        // --------------------------------------------------------------------------------------

        /**
        * Creates plugin markup.
        */
        _createMarkup: function () {
            var self = this;

            this.element.addClass("onp-sociallocker-content");

            var browser = (jQuery.browser.mozilla && 'mozilla') ||
                          (jQuery.browser.opera && 'opera') ||
                          (jQuery.browser.webkit && 'webkit') || 'msie';

            this.locker = $("<div class='onp-sociallocker onp-sociallocker-" + browser + "' style='display: none;'></div>");
            this.outerWrap = $("<div class='onp-sociallocker-outer-wrap'></div>").appendTo(this.locker);
            this.innerWrap = $("<div class='onp-sociallocker-inner-wrap'></div>").appendTo(this.outerWrap);
            if ( this.options.buttons.size == "fixed") this.locker.addClass("onp-sociallocker-buttons-fixed");
            this.locker.addClass(this.style);

            if (!this.options.buttons.counter) this.locker.addClass('onp-sociallocker-no-counters');
            $.onepress.isTouch()
                ? this.locker.addClass('onp-sociallocker-touch')
                : this.locker.addClass('onp-sociallocker-no-touch');

            var resultText = $("<div class='onp-sociallocker-text'></div>");
            if (this.options.text.header) resultText.append(this.options.text.header.addClass('onp-sociallocker-strong').clone());
            if (this.options.text.message) resultText.append(this.options.text.message.clone());

            // main locker message
            this.innerWrap.append(resultText.addClass());
            resultText.prepend(($("<div class='onp-sociallocker-before-text'></div>")));
            resultText.append(($("<div class='onp-sociallocker-after-text'></div>")));

            // creates markup for buttons
            this._createButtonMarkup();

            // bottom locker message
            this.options.bottomText && this.innerWrap.append(this.options.bottomText.addClass('onp-sociallocker-bottom-text'));

            // close button and timer if needed
            this.options.locker.close && this._createClosingCross();
            this.options.locker.timer && this._createTimer();

            var after = (this.element.parent().is('a')) ? this.element.parent() : this.element;
            this.locker.insertAfter(after);

            this._markupIsCreated = true;
        },

        /**
        * Creates markup for every social button.
        */
        _createButtonMarkup: function () {
            var self = this;
            this.buttonsWrap = $("<div class='onp-sociallocker-buttons'></div>").appendTo(this.innerWrap);
            var zIndex = 50;
            
            for (var index in this.options.buttons.order) {
                var sourceName = this.options.buttons.order[index];
                if (typeof (sourceName) != 'string') continue;

                // button separator
                if ( sourceName == '#' ) {
                    this.buttonsWrap.append("<div class='onp-button-separator'></div>");
                    continue;
                }
                
                // is button supported?

                if ( this.options.buttons.unsupported && jQuery.inArray(sourceName, this.options.buttons.unsupported ) >= 0 ) {
                    var title = 'The button "' + sourceName + '" is not supported by this theme.';
                    var button = $("<div class='onp-sociallocker-button onp-sociallocker-button-unsupported'></div>");
                    var innerWrap = $("<div class='onp-sociallocker-button-inner-wrap'>" + title + "</div>").appendTo(button);
                    this.buttonsWrap.append(button);
                    continue;
                }

                var parts = sourceName.split('-');
                var networkName = parts[0];
                var buttonName = parts[1];
                var buttonFunctionName = "sociallocker_" + networkName + "_" + buttonName;

                // setup options
                var buttonOptions = $.extend({}, this.options[networkName]);
                if ( this.options[networkName][buttonName] ) buttonOptions = $.extend(buttonOptions, this.options[networkName][buttonName] );
                buttonOptions.url = buttonOptions.url || this.options.url;
       
                buttonOptions._provider = this._providers[sourceName];
                buttonOptions.unlock = function () { self._unlock("button", this._provider); };

                // creates button
                var button = $("<div class='onp-sociallocker-button onp-sociallocker-button-" + sourceName + "'></div>");
                button.addClass('onp-sociallocker-button-' + networkName);
                
                button.data('name', sourceName);
                this.buttonsWrap.append(button);

                var innerWrap = $("<div class='onp-sociallocker-button-inner-wrap'></div>").appendTo(button);
                innerWrap[buttonFunctionName](buttonOptions);

                var flipEffect = this.options.effects.flip;
                var flipSupport = $.onepress.tools.has3d();

                // addes the flip effect
                (flipEffect && flipSupport && button.addClass("onp-sociallocker-flip")) || button.addClass("onp-sociallocker-no-flip");
                if (!flipEffect) continue;
                
                // if it's a touch device
                if ($.onepress.isTouch()) {
                    
                    var overlay = ( this.options.triggers && this.options.triggers.overlayRender )
                        ? this.options.triggers.overlayRender(buttonOptions, networkName, buttonName, true)
                        : $("<a class='onp-sociallocker-button-overlay' href='#'></a>");

                    overlay.prependTo(innerWrap);
                    
                    // if it's a touch device and flip effect enabled.
                    if (flipSupport) {

                        overlay.click(function () {
                            var btn = $(this).parents('.onp-sociallocker-button');

                            if (btn.hasClass('onp-sociallocker-flip-hover')) {
                                btn.removeClass('onp-sociallocker-flip-hover');
                            } else {
                                $('.onp-sociallocker-flip-hover').removeClass('onp-sociallocker-flip-hover');
                                btn.addClass('onp-sociallocker-flip-hover');
                            }

                            return false;
                        });

                    // if it's a touch device and flip effect is not enabled.
                    } else {

                        overlay.click(function () {
                            var overlay = $(this);
                            overlay.stop().animate({ opacity: 0 }, 200, function () {
                                overlay.hide();
                            });
                            
                            return false;
                        });

                    }

                // if it's not a touch device
                } else {
                    
                    var overlay = ( this.options.triggers && this.options.triggers.overlayRender )
                        ? this.options.triggers.overlayRender(buttonOptions, networkName, buttonName, false)
                        : $("<div class='onp-sociallocker-button-overlay' href='#'></div>");

                    overlay.prependTo(innerWrap);
                    
                    if (!flipSupport) {
                        button.hover(
                            function () {
                                var overlay = $(this).find(".onp-sociallocker-button-overlay");
                                overlay.stop().animate({ opacity: 0 }, 200, function () {
                                    overlay.hide();
                                });
                            },
                            function () {
                                var overlay = $(this).find(".onp-sociallocker-button-overlay").show();
                                overlay.stop().animate({ opacity: 1 }, 200);
                            }
                        );
                    }
                }
                
                if ( overlay ) {
                    overlay.css('z-index', zIndex);
                    overlay.find('.onp-sociallocker-overlay-front').css('z-index', zIndex);
                    overlay.find('.onp-sociallocker-overlay-back').css('z-index', zIndex - 1);  
                    overlay.find('.onp-sociallocker-overlay-header').css('z-index', zIndex - 1);                  
                }
                zIndex = zIndex - 5;
            }
        },

        _makeSimilar: function (overlay, source, dontSubscrtibe) {
            var self = this;

            overlay.css({
                "width": source.outerWidth(false),
                "height": source.outerHeight(false)
            });

            if (!dontSubscrtibe) $(window).resize(function () {
                self._makeSimilar(overlay, source, true);
            });
        },

        _createClosingCross: function () {
            var self = this;

            $("<div class='onp-sociallocker-cross' title='" + $.onepress.sociallocker.lang.close + "' />")
                .prependTo(this.locker)
                .click(function () {
                    if (!self.close || !self.close(self)) self._unlock("cross", true);
                });
        },

        _createTimer: function () {

            this.timer = $("<span class='onp-sociallocker-timer'></span>");
            var timerLabelText = $.onepress.sociallocker.lang.orWait;
            var secondLabel = $.onepress.sociallocker.lang.seconds;

            this.timerLabel = $("<span class='onp-sociallocker-timer-label'>" + timerLabelText + " </span>").appendTo(this.timer);
            this.timerCounter = $("<span class='onp-sociallocker-timer-counter'>" + this.options.locker.timer + secondLabel + "</span>").appendTo(this.timer);

            this.timer.appendTo(this.locker);

            this.counter = this.options.locker.timer;
            this._kickTimer();
        },

        _kickTimer: function () {
            var self = this;

            setTimeout(function () {

                if (!self._isLocked) return;

                self.counter--;
                if (self.counter <= 0) {
                    self._unlock("timer");
                } else {
                    self.timerCounter.text(self.counter + $.onepress.sociallocker.lang.seconds);

                    // Opera fix.
                    if ($.browser.opera) {
                        var box = self.timerCounter.clone();
                        box.insertAfter(self.timerCounter);
                        self.timerCounter.remove();
                        self.timerCounter = box;
                    }

                    self._kickTimer();
                }
            }, 1000);
        },

        // --------------------------------------------------------------------------------------
        // Lock/Unlock content.
        // --------------------------------------------------------------------------------------

        _lock: function (typeSender, sender) {

            if (this._isLocked || this._stoppedByWatchdog) return;
            if (!this._markupIsCreated) this._createMarkup();

            if (typeSender == "button") sender.setState("locked");

            this.element.hide();
            this.isInline ? this.locker.css("display", "inline-block") : this.locker.fadeIn(1000);

            this._isLocked = true;
            if (this.options.events.lock) this.options.events.lock(typeSender, sender && sender.name);
        },

        _unlock: function (typeSender, sender) {
            var self = this;

            if (!this._isLocked) { this._showContent(true); return false; }
            if (typeSender == "button") sender.setState("unlocked");

            this._showContent(true);

            this._isLocked = false;
            if (typeSender == "timer" && this.options.events.unlockByTimer) return this.options.events.unlockByTimer();
            if (typeSender == "close" && this.options.events.unlockByClose) return this.options.events.unlockByClose();
            if (this.options.events.unlock) this.options.events.unlock(typeSender, sender && sender.name);
        },

        lock: function () {
            this._lock("user");
        },

        unlock: function () {
            this._unlock("user");
        },

        _showContent: function (useEffects) {
            var self = this;

            var effectFunction = function () {
                if (self.locker) self.locker.hide();
                if (!useEffects) { self.element.show(); return; }

                self.element.fadeIn(1000, function () {
                    self.options.effects.highlight && self.element.effect && self.element.effect('highlight', { color: '#fffbcc' }, 800);
                });
            };

            if (!this.options.content) {
                effectFunction();

            } else if (typeof this.options.content === "string") {
                this.element.html(this.options.content);
                effectFunction();

            } else if (typeof this.options.content === "object" && !this.options.content.url) {
                this.element.append(this.options.content.clone().show());
                effectFunction();

            } else if (typeof this.options.content === "object" && this.options.content.url) {

                var ajaxOptions = $.extend(true, {}, this.options.content);

                var customSuccess = ajaxOptions.success;
                var customComplete = ajaxOptions.complete;
                var customError = ajaxOptions.error;

                ajaxOptions.success = function (data, textStatus, jqXHR) {

                    !customSuccess ? self.element.html(data) : customSuccess(self, data, textStatus, jqXHR);
                    effectFunction();
                };

                ajaxOptions.error = function (jqXHR, textStatus, errorThrown) {

                    self._setError("An error is triggered during the ajax request! Text: " + textStatus + " " + errorThrown);
                    customError && customError(jqXHR, textStatus, errorThrown);
                };

                ajaxOptions.complete = function (jqXHR, textStatus) {

                    customComplete && customComplete(jqXHR, textStatus);
                };

                $.ajax(ajaxOptions);

            } else {
                effectFunction();
            }
        }
    });

    /**
    * [obsolete]
    */
    $.fn.socialLock = function (opts) {

        opts = $.extend({}, opts);
        $(this).sociallocker(opts);
    };

})(jQuery);;;

