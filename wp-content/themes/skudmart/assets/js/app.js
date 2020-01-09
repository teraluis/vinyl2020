(function($) {
    "use strict";
    // Initialize global variable

    var LaStudio = {
        global:     {},
        utils:      {},
        component:  {},
        core:       {}
    }

    window.LaStudio = LaStudio;

    $.exists = function($selector) {
        return ($selector.length > 0);
    };

    $.getCachedScript = function( url ) {
        var options = {
            dataType: "script",
            cache: true,
            url: url
        };
        return $.ajax( options );
    };

    LaStudio.utils.ajax_xhr = null; // helper for ajax

    LaStudio.utils.localCache = {
        /**
         * timeout for cache in millis
         * @type {number}
         */
        timeout: 600000, // 10 minutes
        /**
         * @type {{_: number, data: {}}}
         **/
        data: {},
        remove: function (url) {
            delete LaStudio.utils.localCache.data[url];
        },
        exist: function (url) {
            return !!LaStudio.utils.localCache.data[url] && ((new Date().getTime() - LaStudio.utils.localCache.data[url]._) < LaStudio.utils.localCache.timeout);
        },
        get: function (url) {
            console.log('Getting in cache for url ' + url);
            return LaStudio.utils.localCache.data[url].data;
        },
        set: function (url, cachedData, callback) {
            LaStudio.utils.localCache.remove(url);
            LaStudio.utils.localCache.data[url] = {
                _: new Date().getTime(),
                data: cachedData
            };
            if ($.isFunction(callback)) callback(cachedData);
        }
    };

    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        if (options.cache) {
            //Here is our identifier for the cache. Maybe have a better, safer ID (it depends on the object string representation here) ?
            // on $.ajax call we could also set an ID in originalOptions

            var id = originalOptions.url + ( "undefined" !== typeof originalOptions.ajax_request_id ? JSON.stringify(originalOptions.ajax_request_id) : JSON.stringify(originalOptions.data) );
            options.cache = false;
            options.beforeSend = function () {
                if (!LaStudio.utils.localCache.exist(id)) {
                    jqXHR.promise().done(function (data, textStatus) {
                        LaStudio.utils.localCache.set(id, data);
                    });
                }
                return true;
            };
        }
    });

    $.ajaxTransport("+*", function (options, originalOptions, jqXHR) {

        //same here, careful because options.url has already been through jQuery processing
        var id = originalOptions.url + ( "undefined" !== typeof originalOptions.ajax_request_id ? JSON.stringify(originalOptions.ajax_request_id) : JSON.stringify(originalOptions.data) );

        options.cache = false;

        if (LaStudio.utils.localCache.exist(id)) {
            return {
                send: function (headers, completeCallback) {
                    completeCallback(200, "OK", [LaStudio.utils.localCache.get(id)]);
                },
                abort: function () {
                    /* abort code, nothing needed here I guess... */
                }
            };
        }
    });

    $.fn.la_sticky = function (opts) {
        var doc_height, elm, enable_bottoming, inner_scrolling, manual_spacer, offset_top, outer_width, parent_selector, recalc_every, sticky_class, win_height, _fn, _i, _len, fake_parent, fake_parent_height;
        if (opts == null) {
            opts = {};
        }
        sticky_class = opts.sticky_class, inner_scrolling = opts.inner_scrolling, recalc_every = opts.recalc_every, parent_selector = opts.parent, offset_top = opts.offset_top, manual_spacer = opts.spacer, enable_bottoming = opts.bottoming, fake_parent = opts.fake_parent, fake_parent_height = opts.fake_parent_height;
        win_height = $(window).height();
        doc_height = $(document).height();
        if (offset_top == null) {
            offset_top = 0;
        }
        if (parent_selector == null) {
            parent_selector = void 0;
        }
        if (inner_scrolling == null) {
            inner_scrolling = true;
        }
        if (sticky_class == null) {
            sticky_class = "is_stuck";
        }
        if (enable_bottoming == null) {
            enable_bottoming = true;
        }

        outer_width = function(el) {
            var computed, w, _el;
            if (window.getComputedStyle) {
                _el = el[0];
                computed = window.getComputedStyle(el[0]);
                w = parseFloat(computed.getPropertyValue("width")) + parseFloat(computed.getPropertyValue("margin-left")) + parseFloat(computed.getPropertyValue("margin-right"));
                if (computed.getPropertyValue("box-sizing") !== "border-box") {
                    w += parseFloat(computed.getPropertyValue("border-left-width")) + parseFloat(computed.getPropertyValue("border-right-width")) + parseFloat(computed.getPropertyValue("padding-left")) + parseFloat(computed.getPropertyValue("padding-right"));
                }
                return w;
            } else {
                return el.outerWidth(true);
            }
        };
        _fn = function(elm, padding_bottom, parent_top, parent_height, top, height, el_float, detached) {
            var bottomed, detach, fixed, last_pos, last_scroll_height, offset, parent, recalc, recalc_and_tick, recalc_counter, spacer, tick;
            var _fake_parent;
            if (elm.data("la_sticky")) {
                return;
            }

            elm.data("la_sticky", true);

            last_scroll_height = doc_height;
            parent = elm.parent();
            if(fake_parent){
                _fake_parent = fake_parent;
            }
            if (parent_selector != null) {
                parent = parent.closest(parent_selector);
            }
            if (!parent.length) {
                throw "failed to find stick parent";
            }
            fixed = false;
            bottomed = false;
            spacer = manual_spacer != null ? manual_spacer && elm.closest(manual_spacer) : $("<div />");
            if (spacer) {
                spacer.css('position', elm.css('position'));
            }
            recalc = function() {
                var border_top, padding_top, restore;
                if (detached) {
                    return;
                }
                win_height = $(window).height();
                doc_height = $(document).height();
                last_scroll_height = doc_height;
                border_top = parseInt(parent.css("border-top-width"), 10);
                padding_top = parseInt(parent.css("padding-top"), 10);
                padding_bottom = parseInt(parent.css("padding-bottom"), 10);
                parent_top = parent.offset().top + border_top + padding_top;
                parent_height = fake_parent ? _fake_parent.height() : parent.height();
                if (fixed) {
                    fixed = false;
                    bottomed = false;
                    if (manual_spacer == null) {
                        elm.insertAfter(spacer);
                        spacer.detach();
                    }
                    elm.css({
                        position: "",
                        top: "",
                        width: "",
                        bottom: ""
                    }).removeClass(sticky_class);
                    restore = true;
                }
                top = elm.offset().top - (parseInt(elm.css("margin-top"), 10) || 0) - offset_top;
                height = elm.outerHeight(true);
                el_float = elm.css("float");
                if (spacer) {
                    spacer.css({
                        width: outer_width(elm),
                        height: height,
                        display: elm.css("display"),
                        "vertical-align": elm.css("vertical-align"),
                        "float": el_float
                    });
                }
                if (restore) {
                    return tick();
                }
            };
            recalc();
            if (height === parent_height) {
                return;
            }
            last_pos = void 0;
            offset = offset_top;
            recalc_counter = recalc_every;
            tick = function() {
                var css, delta, recalced, scroll, will_bottom;
                if (detached) {
                    return;
                }
                recalced = false;
                if (recalc_counter != null) {
                    recalc_counter -= 1;
                    if (recalc_counter <= 0) {
                        recalc_counter = recalc_every;
                        recalc();
                        recalced = true;
                    }
                }
                if (!recalced && doc_height !== last_scroll_height) {
                    recalc();
                    recalced = true;
                }
                scroll = $(window).scrollTop();
                if (last_pos != null) {
                    delta = scroll - last_pos;
                }
                last_pos = scroll;
                if (fixed) {
                    if (enable_bottoming) {
                        will_bottom = scroll + height + offset > parent_height + parent_top;
                        if (bottomed && !will_bottom) {
                            bottomed = false;
                            elm.css({
                                position: "fixed",
                                bottom: "",
                                top: offset
                            }).trigger("la_sticky:unbottom");
                        }
                    }
                    if (scroll <= top) {
                        fixed = false;
                        offset = offset_top;
                        if (manual_spacer == null) {
                            if (el_float === "left" || el_float === "right") {
                                elm.insertAfter(spacer);
                            }
                            spacer.detach();
                        }
                        css = {
                            position: "",
                            width: "",
                            top: ""
                        };
                        elm.css(css).removeClass(sticky_class).trigger("la_sticky:unstick");
                    }
                    if (inner_scrolling) {
                        if (height + offset_top > win_height) {
                            if (!bottomed) {
                                offset -= delta;
                                offset = Math.max(win_height - height, offset);
                                offset = Math.min(offset_top, offset);
                                if (fixed) {
                                    elm.css({
                                        top: offset + "px"
                                    });
                                }
                            }
                        }
                    }
                } else {
                    if (scroll > top) {
                        fixed = true;
                        css = {
                            position: "fixed",
                            top: offset
                        };
                        css.width = elm.css("box-sizing") === "border-box" ? elm.outerWidth() + "px" : elm.width() + "px";
                        elm.css(css).addClass(sticky_class);
                        if (manual_spacer == null) {
                            elm.after(spacer);
                            if (el_float === "left" || el_float === "right") {
                                spacer.append(elm);
                            }
                        }
                        elm.trigger("la_sticky:stick");
                    }
                }
                if (fixed && enable_bottoming) {
                    if (will_bottom == null) {
                        will_bottom = scroll + height + offset > parent_height + parent_top;
                    }
                    if (!bottomed && will_bottom) {
                        bottomed = true;
                        if (parent.css("position") === "static") {
                            parent.css({
                                position: "relative"
                            });
                        }
                        return elm.css({
                            position: "absolute",
                            bottom: padding_bottom,
                            top: "auto"
                        }).trigger("la_sticky:bottom");
                    }
                }
            };
            recalc_and_tick = function() {
                recalc();
                return tick();
            };
            detach = function() {
                detached = true;
                $(window).off("touchmove", tick);
                $(window).off("scroll", tick);
                $(window).off("resize", recalc_and_tick);
                $(document.body).off("la_sticky:recalc", recalc_and_tick);
                elm.off("la_sticky:detach", detach);
                elm.removeData("la_sticky");
                elm.css({
                    position: "",
                    bottom: "",
                    top: "",
                    width: ""
                });
                parent.position("position", "");
                if (fixed) {
                    if (manual_spacer == null) {
                        if (el_float === "left" || el_float === "right") {
                            elm.insertAfter(spacer);
                        }
                        spacer.remove();
                    }
                    return elm.removeClass(sticky_class);
                }
            };
            $(window).on("touchmove", tick);
            $(window).on("scroll", tick);
            $(window).on("resize", recalc_and_tick);
            $(document.body).on("la_sticky:recalc", recalc_and_tick);
            elm.on("la_sticky:detach", detach);
            return setTimeout(tick, 0);
        };
        for (_i = 0, _len = this.length; _i < _len; _i++) {
            elm = this[_i];
            _fn($(elm));
        }
        return this;
    }

})(jQuery);

// Initialize Helper
(function($) {
    'use strict';

    LaStudio.global.hasClass = function(elm, cls){
        return (' ' + elm.className + ' ').indexOf(' ' + cls + ' ') > -1;
    }

    LaStudio.global.isRTL = function(){
        return document.body.classList ? document.body.classList.contains('rtl') : /\brtl\b/g.test(document.body.className);
    }

    LaStudio.global.sanitizeSlug = function( text ){
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');
    }

    LaStudio.global.isCookieEnable = function(){
        if (navigator.cookieEnabled) return true;
        document.cookie = "cookietest=1";
        var ret = document.cookie.indexOf("cookietest=") != -1;
        document.cookie = "cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT";
        return ret;
    }

    LaStudio.global.getBrowseInformation = function () {
        var name,version,platform_name, _tmp;

        var ua = navigator.userAgent.toLowerCase(),
            platform = navigator.platform.toLowerCase(),
            UA = ua.match(/(opera|ie|firefox|chrome|version)[\s\/:]([\w\d\.]+)?.*?(safari|version[\s\/:]([\w\d\.]+)|$)/) || [null, 'unknown', '0'];


        function getInternetExplorerVersion() {
            var rv = -1, ua2, re2;
            if (navigator.appName == 'Microsoft Internet Explorer') {
                ua2 = navigator.userAgent;
                re2  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
                if (re2.exec(ua2) != null)
                    rv = parseFloat( RegExp.$1 );
            }
            else if (navigator.appName == 'Netscape') {
                ua2 = navigator.userAgent;
                re2  = new RegExp("Trident/.*rv:([0-9]{1,}[\.0-9]{0,})");
                if (re2.exec(ua2) != null)
                    rv = parseFloat( RegExp.$1 );
            }
            return rv;
        }

        _tmp = getInternetExplorerVersion();

        if(_tmp != -1){
            name = 'ie';
            version = _tmp;
        }
        else{
            name = (UA[1] == 'version') ? UA[3] : UA[1];
            version = UA[2].substring(0,2);
        }

        platform_name = ua.match(/ip(?:ad|od|hone)/) ? 'ios' : (ua.match(/(?:webos|android)/) || platform.match(/mac|win|linux/) || ['other'])[0];

        return {
            name : name,
            version : version,
            platform: platform_name
        };
    }

    LaStudio.global._setBrowserInformation = (function () {
        var information = LaStudio.global.getBrowseInformation();
        document.querySelector('html').className += information.name + ' ' + information.name + information.version + ' platform-' + information.platform;
    })

    LaStudio.global.isIELower16 = function(){
        var information = LaStudio.global.getBrowseInformation();
        return (information.name == 'ie' && parseInt(information.version) < 16)
    }
    
    LaStudio.global.getRandomID = function () {
        var text = "",
            char = "abcdefghijklmnopqrstuvwxyz",
            num = "0123456789",
            i;
        for( i = 0; i < 5; i++ ){
            text += char.charAt(Math.floor(Math.random() * char.length));
        }
        for( i = 0; i < 5; i++ ){
            text += num.charAt(Math.floor(Math.random() * num.length));
        }
        return text;
    }
    
    LaStudio.global.getAdminBarHeight = function () {
        return document.getElementById('wpadminbar') && window.innerWidth > 600 ? 32 : 0
    }

    LaStudio.global.addQueryArg = function ( url, key, value ) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = url.indexOf('?') !== -1 ? "&" : "?";
        if (url.match(re)){
            return url.replace(re, '$1' + key + "=" + value + '$2');
        }
        else{
            return url + separator + key + "=" + value;
        }
    }

    LaStudio.global.getUrlParameter = function (name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    LaStudio.global.removeURLParameter = function ( url, parameter ) {
        var urlparts= url.split('?');
        if (urlparts.length>=2) {
            var prefix= encodeURIComponent(parameter)+'=';
            var pars= urlparts[1].split(/[&;]/g);
            //reverse iteration as may be destructive
            for (var i= pars.length; i-- > 0;) {
                //idiom for string.startsWith
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }
            url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
            return url;
        }
        else {
            return url;
        }
    }

})(jQuery);

(function($) {
    'use strict';

    function getHtmlScroll() {
        return {
            x: window.pageXOffset || document.documentElement.scrollLeft,
            y: window.pageYOffset || document.documentElement.scrollTop
        };
    }

    function isHtmlBodyTag(element) {
        return (/^(?:body|html)$/i).test(element.tagName);
    }

    function getElementScroll(elem) {
        var element = elem.parentNode,
            position = {x: 0, y: 0};
        while (element && !isHtmlBodyTag(element)) {
            position.x += element.scrollLeft;
            position.y += element.scrollTop;
            element = element.parentNode;
        }
        return position;
    }

    function getStyleToString(element, style){
        return $(element).css(style);
    }

    function getStyleToNumber(element, style) {
        return parseInt(getStyleToString(element, style)) || 0;
    }

    function getTopBorderOfElement(element) {
        return getStyleToNumber(element, 'border-top-width');
    }

    function getTopLeftOfElement(element) {
        return getStyleToNumber(element, 'border-left-width');
    }

    function elementHasBorderBox(element) {
        return getStyleToString(element, '-moz-box-sizing') == 'border-box';
    }

    function getOffset(elem){

        var browser_information = LaStudio.global.getBrowseInformation();

        if (elem.getBoundingClientRect && browser_information.platform != 'ios') {
            var bound = elem.getBoundingClientRect(),
                html = elem.ownerDocument.documentElement,
                htmlScroll = getHtmlScroll(),
                elemScrolls = getElementScroll(elem),
                isFixed = (getStyleToString(elem, 'position') == 'fixed');
            return {
                x: parseInt(bound.left) + elemScrolls.x + ((isFixed) ? 0 : htmlScroll.x) - html.clientLeft,
                y: parseInt(bound.top) + elemScrolls.y + ((isFixed) ? 0 : htmlScroll.y) - html.clientTop
            };
        }
        var element = elem,
            position = {
                x: 0,
                y: 0
            };

        if (isHtmlBodyTag(elem)) return position;

        while (element && !isHtmlBodyTag(element)) {
            position.x += element.offsetLeft;
            position.y += element.offsetTop;
            if (browser_information.name == 'firefox') {
                if (!elementHasBorderBox(element)) {
                    position.x += getTopLeftOfElement(element);
                    position.y += getTopBorderOfElement(element);
                }
                var parent = element.parentNode;
                if (parent && getStyleToString(parent, 'overflow') != 'visible') {
                    position.x += getTopLeftOfElement(parent);
                    position.y += getTopBorderOfElement(parent);
                }
            } else if (element != elem && browser_information.name == 'safari') {
                position.x += getTopLeftOfElement(element);
                position.y += getTopBorderOfElement(element);
            }
            element = element.offsetParent;
        }
        if (browser_information.name == 'firefox' && !elementHasBorderBox(elem)) {
            position.x -= getTopLeftOfElement(elem);
            position.y -= getTopBorderOfElement(elem);
        }
        return position;
    }

    LaStudio.global.getOffset = function ( $element ) {
        return $.exists($element) ? getOffset($element.get(0)) : {x:0, y:0};
    }

})(jQuery);

// Initialize loadDependencies
(function($) {
    var _loadedDependencies = [],
        _inQueue = {};


    $('body').on('lastudo-prepare-object-fit', function (e, $elm) {
        console.log('run fix object-fit');
        var objectFits = $('.figure__object_fit:not(.custom-object-fit) img', $elm);
        objectFits.each(function () {
            var $container = $(this).closest('.figure__object_fit'),
                imgUrl = $(this).prop('src');
            if (imgUrl) {
                $container.css('backgroundImage', 'url(' + imgUrl + ')').addClass('custom-object-fit');
            }
        })
    });

    if( LaStudio.global.isIELower16() ){
        $('body').on( 'lastudio-object-fit', function (e) {
            console.log('run fix object-fit');
            var objectFits = $('.figure__object_fit:not(.custom-object-fit) img');
            objectFits.each(function () {
                var $container = $(this).closest('.figure__object_fit'),
                    imgUrl = $(this).prop('src');
                if (imgUrl) {
                    $container.css('backgroundImage', 'url(' + imgUrl + ')').addClass('custom-object-fit');
                }
            })
        });
    }

    LaStudio.core.initAll = function( $scope ) {

        var $el = $scope.find( '.js-el' ),
            $components = $el.filter( '[data-la_component]' ),
            component = null;

        if($components.length <= 0 ){
            return;
        }

        // initialize  component
        var init_component = function (name, el) {
            var $el = $(el);

            if ( $el.data('init-' + name) ) return;

            if ( typeof LaStudio.component[ name ] !== 'function' ){
                console.log('[LaStudio Component ' + name + '] ---- init error')
            }
            else {
                component = new LaStudio.component[ name ]( el );
                component.init();
                $el.data('init-' + name, true);
                LaStudio.global.eventManager.publish('LaStudio:component_inited', [name, el]);
            }
        };

        $components.each( function() {
            var self = this,
                names =  $(this).data( 'la_component' );

            if( typeof names === 'string' ) {
                var _name = names ;
                init_component( _name , self);
            }
            else {
                names.forEach( function( name ) {
                    init_component(name, self);
                });
            }
        });

        $('body').trigger( 'lastudio-lazy-images-load' ).trigger( 'jetpack-lazy-images-load' ).trigger( 'lastudio-object-fit' );

    };

    LaStudio.global.loadDependencies = function( dependencies, callback ) {
        var _callback = callback || function() {};

        if( !dependencies ) {
            _callback();
            return;
        }

        var newDeps = dependencies.map( function( dep ) {
            if( _loadedDependencies.indexOf( dep ) === -1 ) {
                if( typeof _inQueue[ dep ] === 'undefined' ) {
                    return dep;
                }
                else {
                    _inQueue[ dep ].push( _callback );
                    return true;
                }
            }
            else {
                return false;
            }
        });

        if( newDeps[0] === true ) {
            return;
        }

        if( newDeps[0] === false ) {
            _callback();
            return;
        }

        var queue = newDeps.map( function( script ) {
            _inQueue[ script ] = [ _callback ];
            return $.getCachedScript( script );
        });

        // Callbacks invoking
        var onLoad = function onLoad() {
            var index = 0;
            newDeps.map( function( loaded ) {
                index++;
                _inQueue[ loaded ].forEach( function( callback ) {
                    if(index == newDeps.length){
                        console.log(loaded);
                        callback();
                    }
                });
                delete _inQueue[ loaded ];
                _loadedDependencies.push( loaded );
            });
        };

        // Run callbacks when promise is resolved
        $.when.apply( null, queue ).done( onLoad );
    };

    LaStudio.global.loadJsFile = function(name){
        return la_theme_config.js_path + name + (la_theme_config.js_min ? '.min.js' : '.js');
    };

    LaStudio.global.AnimateLoadElement = function( effect_name, $elements, callback ){
        var _callback = callback || function() {};
        var animation_timeout = 0;

        // hide all element that not yet loaded
        $elements.css({ 'opacity': 0 });

        if ( effect_name == 'fade') {
            $elements.each(function () {
                $(this).stop().animate({
                    'opacity': 1
                }, 1000 );
            });
            animation_timeout = 1000;
        }
        else if ( effect_name == 'sequencefade'){
            $elements.each(function (i) {
                var $elm = $(this);
                setTimeout(function () {
                    $elm.stop().animate({
                        'opacity': 1
                    }, 1000 );
                }, 100 + (i * 50) );
            });
            animation_timeout = 500 + ($elements.length * 50);
        }
        else if ( effect_name == 'upfade'){

            $elements.each(function(){
                var $elm = $(this),
                    t = parseInt($elm.css('top'), 10) + ( $elm.height() / 2);
                $elm.css({
                    top: t + 'px',
                    opacity: 0
                });
            });

            $elements.each(function () {
                var $el = $(this);
                $el.stop().animate({
                    top: parseInt($el.css('top'), 10) - ( $el.height() / 2),
                    opacity: 1
                }, 1500);
            });

            animation_timeout = 2000;
        }
        else if ( effect_name == 'sequenceupfade'){

            $elements.each(function(){
                var $elm = $(this),
                    t = parseInt($elm.css('top'), 10) + ( $elm.height() / 2);
                $elm.css({
                    top: t + 'px',
                    opacity: 0
                });
            });

            $elements.each(function (i) {
                var $elm = $(this);
                setTimeout(function () {
                    $elm.stop().animate({
                        top: parseInt($elm.css('top'), 10) - ( $elm.height() / 2),
                        opacity: 1
                    }, 1000);
                }, 100 + i * 50);
            });

            animation_timeout = 500 + ($elements.length * 50);
        }
        else{
            $elements.css({ 'opacity': 1 });
            animation_timeout = 1000;
        }

        /* run callback */
        setTimeout(function(){
            _callback.call();
        }, animation_timeout );
    };

    LaStudio.global.InsightInitLazyEffects = function( selector, $container, load_immediately ){
        function _init_effect(){
            var _effect_name = false === !!$container.attr('data-la-effect') ? 'sequenceupfade' : $container.attr('data-la-effect');
            $container.addClass('InsightInitLazyEffects-inited');
            LaStudio.global.AnimateLoadElement(_effect_name, $(selector, $container), function(){
                $(selector, $container).addClass('showmenow');
            });
        }

        if($container.hasClass('InsightInitLazyEffects-inited')){
            return;
        }

        if(load_immediately){
            _init_effect();
        }
        else{
            LaStudio.global.LazyLoad($container, {
                load : function () {
                    _init_effect();
                }
            }).observe();
        }

    };

    LaStudio.global.ShowMessageBox = function( html, ex_class ) {

        if(typeof LaStudio.utils.timeOutMessageBox === "undefined" ) {
            LaStudio.utils.timeOutMessageBox = null;
        }

        var show_popup = function(){
            lightcase.start({
                href: '#',
                showSequenceInfo: false,
                maxWidth:600,
                maxHeight: 500,
                onInit: {
                    clearTimeout: function(){
                        clearTimeout(LaStudio.utils.timeOutMessageBox);
                    }
                },
                onFinish: {
                    insertContent: function () {
                        if(ex_class) {
                            $('body').addClass(ex_class);
                        }
                        lightcase.get('content').append('<div class="custom-lightcase-overlay"></div>');
                        lightcase.get('contentInner').children().html('<div class="la-global-message">' + html + '</div>');
                        lightcase.get('contentInner').append('<a class="custom-lighcase-btn-close" href="#"><i class="lastudioicon-e-remove"></i></a>');
                        lightcase.resize();
                        LaStudio.utils.timeOutMessageBox = setTimeout(function(){
                            lightcase.close();
                        }, 20 * 1000);
                    }
                },
                onClose : {
                    qux: function() {
                        if(ex_class){
                            $('body').removeClass(ex_class);
                        }
                        $('.custom-lightcase-overlay').remove();
                        $('.custom-lighcase-btn-close').remove();
                        clearTimeout(LaStudio.utils.timeOutMessageBox);
                    }
                }
            });
        }

        if($.fn.lightcase){
            show_popup();
        }
        else{
            LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('jquery.lightcase')], show_popup );
        }
    };

})(jQuery);

// Initialize Event Manager
(function($) {
    'use strict';

    LaStudio.global.eventManager = {};

    LaStudio.global.eventManager.subscribe = function(evt, func) {
        $(this).on(evt, func);
    };

    LaStudio.global.eventManager.unsubscribe = function(evt, func) {
        $(this).off(evt, func);
    };
    LaStudio.global.eventManager.publish = function(evt, params) {
        $(this).trigger(evt, params);
    };

}(jQuery));

// Initialize Lazyload
(function($) {
    "use strict";

    var defaultConfig = {
        rootMargin: '100px',
        threshold: 0,
        load: function load(element) {
            var base_src = element.getAttribute('data-src') || element.getAttribute('data-lazy') || element.getAttribute('data-lazy-src') || element.getAttribute('data-lazy-original'),
                base_srcset = element.getAttribute('data-src') || element.getAttribute('data-lazy-srcset'),
                base_sizes = element.getAttribute('data-sizes') || element.getAttribute('data-lazy-sizes');

            if (base_src) {
                element.src = base_src;
            }
            if (base_srcset) {
                element.srcset = base_srcset;
            }
            if (base_sizes) {
                element.sizes = base_sizes;
            }
            if (element.getAttribute('data-background-image')) {
                element.style.backgroundImage = 'url("' + element.getAttribute('data-background-image') + '")';
            }
            element.setAttribute('data-element-loaded', true);
            if ($(element).hasClass('jetpack-lazy-image')) {
                $(element).addClass('jetpack-lazy-image--handled');
            }
        },
        complete: function( $elm ){
            // this function will be activated when element has been loaded
        }
    };

    function markAsLoaded(element) {
        element.setAttribute('data-element-loaded', true);
    }

    var isLoaded = function isLoaded(element) {
        return element.getAttribute('data-element-loaded') === 'true';
    };

    var onIntersection = function onIntersection(load) {
        return function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.intersectionRatio > 0) {
                    observer.unobserve(entry.target);

                    if (!isLoaded(entry.target)) {
                        load(entry.target);
                        markAsLoaded(entry.target);
                    }
                }
            });
        };
    };

    LaStudio.global.LazyLoad = function () {
        var selector = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
        var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

        var _defaultConfig$option = $.extend({}, defaultConfig, options),
            rootMargin = _defaultConfig$option.rootMargin,
            threshold = _defaultConfig$option.threshold,
            load = _defaultConfig$option.load,
            complete = _defaultConfig$option.complete;

        // // If initialized, then disconnect the observer
        var observer = void 0;

        if ( observer ) {
            observer.disconnect();
        }

        if (window.IntersectionObserver) {
            observer = new IntersectionObserver(onIntersection(load), {
                rootMargin: rootMargin,
                threshold: threshold
            });
        }

        return {
            observe: function observe() {
                if ( !$.exists(selector) ) {
                    return;
                }
                for (var i = 0; i < selector.length; i++) {
                    if (isLoaded(selector[i])) {
                        continue;
                    }
                    if (observer) {
                        observer.observe(selector[i]);
                        continue;
                    }
                    load(selector[i]);
                    markAsLoaded(selector[i]);
                }
                complete(selector);
            }
        };
    };

    LaStudio.global.makeImageAsLoaded = function( elm ){
        if(!isLoaded(elm)){
            defaultConfig.load(elm);
            markAsLoaded(elm);
            $(elm).removeClass('lazyload');
        }
    }

    $('body').on('lastudio-lazy-images-load', function () {
        var $elm = $('.la-lazyload-image:not([data-element-loaded="true"])');
        LaStudio.global.LazyLoad($elm, {rootMargin: '200px'}).observe();
    });

}(jQuery));

// Initialize Component
(function($) {
    'use strict';

    var $window = $(window),
        $document = $(document),
        $htmlbody = $('html,body'),
        $body = $('body'),
        $masthead = $('#lastudio-header-builder');

    LaStudio.component.SVGAnimation = function(el){
        var $this = $(el),
            _settings = $this.data(),
            _type     = _settings.type ? _settings.type : 'delayed',
            _duration = _settings.duration ? _settings.duration : 100,
            _options  = {
                type: _type,
                duration: _duration
            },
            $svg = $this.find('svg');

        var setup_vivus = function(){
            var _vivus = new Vivus( $svg[0], _options );
            if ( _settings.hover ){

                if(_settings.hoveron){
                    $(_settings.hoveron)
                        .on('mouseenter', function(){
                            _vivus.stop()
                                .reset()
                                .play( 2 );
                        })
                        .on('mouseleave', function(){
                            _vivus.finish();
                        })
                }
                else{
                    $this
                        .on('mouseenter', function(){
                            _vivus.stop()
                                .reset()
                                .play( 2 );
                        })
                        .on('mouseleave', function(){
                            _vivus.finish();
                        })
                }
            }
        };

        return {
            init : function () {
                if(typeof Vivus === 'undefined'){
                    LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('vivus')], setup_vivus );
                }
                else{
                    setup_vivus();
                }
            }
        }
    }

    LaStudio.component.MasonryFilter = function(el){
        var $this = $(el),
            options = ($this.data('isotope_option') || {}),
            $isotope = $($this.data('isotope_container'));


        var setup_filter = function(){
            $('.isotope__filter-item', $this).on('click', function (e) {
                e.preventDefault();
                var selector = $(this).attr('data-filter');
                $this.find('.active').removeClass('active');

                if (selector != '*')
                    selector = '.' + selector;
                if ($isotope){
                    $isotope.attr('lafilter', selector);
                    $isotope.isotope(
                        $.extend(options,{
                            filter: selector
                        })
                    );
                }
                $(this).addClass('active');
            })
        };

        return {
            init : function(){
                if($.isFunction( $.fn.isotope )) {
                    setup_filter();
                }
                else{
                    LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('isotope.pkgd')], setup_filter );
                }
            }
        }
    }

    LaStudio.component.DefaultMasonry = function( el ){
        var $isotope_container = $(el),
            item_selector   = $isotope_container.data('item_selector'),
            configs         = ( $isotope_container.data('config_isotope') || {} );

        configs = $.extend({
            percentPosition: true,
            itemSelector : item_selector
        },configs);

        var setup_masonry = function(){

            $isotope_container.isotope(configs);

            LaStudio.global.LazyLoad($isotope_container.parent(), {
                rootMargin: '100px',
                load: function(){
                    LaStudio.global.eventManager.publish('LaStudio:Component:LazyLoadImage', [ $isotope_container ]);
                    $('.la-isotope-loading', $isotope_container).hide();
                    $isotope_container.addClass('loaded');
                    LaStudio.global.InsightInitLazyEffects(item_selector, $isotope_container, false);
                }
            }).observe();

            try{
                Waypoint.refreshAll();
            }
            catch (e) { }
        };

        return {
            init : function(){

                setTimeout(function () {
                    $('.la-isotope-loading', $isotope_container).show();
                }, 50);

                if($.isFunction( $.fn.isotope )){
                    setTimeout(function () {
                        setup_masonry();
                    }, 100)
                }
                else{
                    LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('isotope.pkgd')], setup_masonry );
                }
            }
        }
    }

    LaStudio.component.AdvancedMasonry = function( el ){
        var $isotope_container = $(el),
            item_selector   = $isotope_container.data('item_selector'),
            configs         = ( $isotope_container.data('config_isotope') || {} );

        configs = $.extend({
            percentPosition: true,
            itemSelector : item_selector,
            masonry : {
                gutter: 0
            }
        },configs);

        var get_isotope_column_number = function (w_w, item_w) {
            return Math.round(w_w / item_w);
        };

        LaStudio.global.eventManager.subscribe('LaStudio:AdvancedMasonry:calculatorItemWidth', function( e, $isotope_container, need_relayout ){
            if($isotope_container.hasClass('grid-items')){
                return;
            }
            var ww = $window.width(),
                _base_w = $isotope_container.data('item-width'),
                _base_h = $isotope_container.data('item-height'),
                _container_width_base = ( false !== !!$isotope_container.data('container-width') ? $isotope_container.data('container-width') : $isotope_container.width()),
                _container_width = $isotope_container.width();

            var item_per_page = get_isotope_column_number(_container_width_base, _base_w);

            if( ww > 1300){

                var __maxItem = $isotope_container.parent().attr('class').match(/masonry-max-item-per-row-(\d+)/);
                var __minItem = $isotope_container.parent().attr('class').match(/masonry-min-item-per-row-(\d+)/);

                if(__maxItem && __maxItem[1] && item_per_page > parseInt(__maxItem[1])){
                    item_per_page = parseInt(__maxItem[1]);
                }
                if(__minItem && __minItem[1] && item_per_page < parseInt(__minItem[1])){
                    item_per_page = parseInt(__minItem[1]);
                }
            }

            if( ww < 1024){
                item_per_page = $isotope_container.data('md-col');
                $isotope_container.removeClass('cover-img-bg');
            }
            else{
                $isotope_container.addClass('cover-img-bg');
            }
            if( ww < 800){
                item_per_page = $isotope_container.data('sm-col');
            }
            if( ww < 576){
                item_per_page = $isotope_container.data('xs-col');
            }
            if( ww < 480){
                item_per_page = $isotope_container.data('mb-col');
            }
            var itemwidth = Math.floor(_container_width / item_per_page),
                selector = $isotope_container.data('item_selector'),
                margin = parseInt($isotope_container.data('item_margin') || 0),
                dimension = parseFloat( _base_w / _base_h );


            $( selector, $isotope_container ).each(function (idx) {

                var thiswidth = parseFloat( $(this).data('width') || 1 ),
                    thisheight = parseFloat( $(this).data('height') || 1),
                    _css = {};

                if (isNaN(thiswidth)) thiswidth = 1;
                if (isNaN(thisheight)) thisheight = 1;

                if( ww < 1024){
                    thiswidth = thisheight = 1;
                }

                _css.width = Math.floor((itemwidth * thiswidth) - (margin / 2));
                _css.height = Math.floor((itemwidth / dimension) * thisheight);

                if( ww < 1024){
                    _css.height = 'auto';
                }

                $(this).css(_css);

            });
            if(need_relayout) {
                if($isotope_container.data('isotope')){
                    $isotope_container.isotope('layout');
                }
            }
        });

        var setup_masonry = function(){

            if(!$isotope_container.hasClass('masonry__column-type-default')){
                configs.masonry.columnWidth = 1;
            }

            $isotope_container.isotope(configs);

            if(!$isotope_container.hasClass('showposts-loop') && !$isotope_container.hasClass('loaded')){
                $isotope_container.on('layoutComplete', function(e){
                    LaStudio.global.InsightInitLazyEffects(item_selector, $isotope_container, true);
                });
            }

            LaStudio.global.LazyLoad($isotope_container.parent(), {
                rootMargin: '100px',
                load: function(){
                    LaStudio.global.eventManager.publish('LaStudio:Component:LazyLoadImage', [ $isotope_container ]);
                    $('.la-isotope-loading', $isotope_container).hide();
                    $isotope_container.addClass('loaded');
                    LaStudio.global.InsightInitLazyEffects(item_selector, $isotope_container, false);
                }
            }).observe();

            $window.on('resize', function(e) {
                LaStudio.global.eventManager.publish('LaStudio:AdvancedMasonry:calculatorItemWidth', [$isotope_container, true]);
            });

            try{
                Waypoint.refreshAll();
            }catch (e) {

            }
        };

        return {
            init : function(){
                $('.la-isotope-loading', $isotope_container).show();
                setTimeout(function () {
                    LaStudio.global.eventManager.publish('LaStudio:AdvancedMasonry:calculatorItemWidth', [$isotope_container, false]);
                }, 50);
                if($.isFunction( $.fn.isotope )){
                    setTimeout(function () {
                        setup_masonry();
                    }, 100);
                }
                else{
                    LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('isotope.pkgd')], setup_masonry );
                }
            }
        }
    }

    LaStudio.component.AutoCarousel = function(el){

        var $slider = $(el),
            options =  $slider.data('slider_config') || {};

        var setup_slick = function(){

            var laptopSlides, tabletPortraitSlides, tabletSlides, mobileSlides, mobilePortraitSlides, defaultOptions, slickOptions, slidesToShow;
            slidesToShow = parseInt(options.slidesToShow.desktop) || 1;
            laptopSlides = parseInt(options.slidesToShow.laptop) || slidesToShow;
            tabletSlides = parseInt(options.slidesToShow.tablet) || laptopSlides;
            tabletPortraitSlides = parseInt(options.slidesToShow.tabletportrait) || tabletSlides;
            mobileSlides = parseInt(options.slidesToShow.mobile) || tabletPortraitSlides;
            mobilePortraitSlides = parseInt(options.slidesToShow.mobileportrait) || mobileSlides;

            options.slidesToShow = slidesToShow;

            var rows = 1;

            if( typeof options.extras !== "undefined" && typeof options.extras.rows !== "undefined" ) {
                rows = parseInt(options.extras.rows);
            }

            if(rows < 1 || isNaN(rows)){
                rows = 1;
            }

            var res_s1 = {
                    slidesToShow: laptopSlides,
                    slidesToScroll: laptopSlides,
                },
                res_s2 = {
                    slidesToShow: tabletSlides,
                    slidesToScroll: tabletSlides
                },
                res_s3 = {
                    slidesToShow: tabletPortraitSlides,
                    slidesToScroll: tabletPortraitSlides
                },
                res_s4 = {
                    slidesToShow: mobileSlides,
                    slidesToScroll: mobileSlides
                },
                res_s5 = {
                    slidesToShow: mobilePortraitSlides,
                    slidesToScroll: mobilePortraitSlides
                };

            if(rows > 1){
                res_s1.rows = rows;
                res_s2.rows = rows;
                res_s3.rows = 1;
                res_s4.rows = 1;
                res_s5.rows = 1;
            }

            defaultOptions = {
                customPaging: function(slider, i) {
                    return $( '<span />' ).text( i + 1 );
                },
                dotsClass: 'lastudio-slick-dots',
                responsive: [
                    {
                        breakpoint: 1600,
                        settings: res_s1
                    },
                    {
                        breakpoint: 1300,
                        settings: res_s2
                    },
                    {
                        breakpoint: 800,
                        settings: res_s3
                    },
                    {
                        breakpoint: 768,
                        settings: res_s4
                    },
                    {
                        breakpoint: 576,
                        settings: res_s5
                    }
                ]
            };

            if(rows > 1){
                defaultOptions.rows = rows;
            }

            slickOptions = $.extend( {}, defaultOptions, options );

            $slider.on('init', function(e, slick){
                if(slick.slideCount <= slick.options.slidesToShow){
                    slick.$slider.addClass('hidden-dots');
                }
                else{
                    slick.$slider.removeClass('hidden-dots');
                }

                if(slick.options.centerMode){
                    slick.$slider.addClass('la-slick-centerMode');
                }
            });

            $slider.slick( slickOptions );

            $('<div class="slick-controls-auto"><a class="slick-control-start" href="#"><i class="fa fa-play" aria-hidden="true"></i></a><a class="slick-control-stop active" href="#"><i class="fa fa-pause" aria-hidden="true"></i></a></div>').insertAfter($slider);

            $slider
                .on('click', '.slick-control-start', function (e) {
                    e.preventDefault();
                    $(this).removeClass('active').siblings('a').addClass('active');
                    $slider.slick('slickPlay');
                })
                .on('click', '.slick-control-stop', function (e) {
                    e.preventDefault();
                    $(this).removeClass('active').siblings('a').addClass('active');
                    $slider.slick('slickPause');
                });

            LaStudio.global.LazyLoad($slider.parent(), {
                rootMargin: '100px',
                load: function(){
                    LaStudio.global.eventManager.publish('LaStudio:Component:LazyLoadImage', [ $slider ]);
                },
            }).observe();

        };

        return {
            init : function () {
                if($.isFunction( $.fn.slick )){
                    setup_slick();
                }
                else{
                    LaStudio.core.loadDependencies([ LaStudio.global.loadJsFile('jquery.slick') ], setup_slick );
                }
            }
        }
    }

    LaStudio.component.CountDownTimer = function(el){
        var $scope = $(el);

        var timeInterval,
            $coutdown = $scope.find( '[data-due-date]' ),
            endTime = new Date( $coutdown.data( 'due-date' ) * 1000 ),
            elements = {
                days: $coutdown.find( '[data-value="days"]' ),
                hours: $coutdown.find( '[data-value="hours"]' ),
                minutes: $coutdown.find( '[data-value="minutes"]' ),
                seconds: $coutdown.find( '[data-value="seconds"]' )
            };

        LaStudio.component.CountDownTimer.updateClock = function() {

            var timeRemaining = LaStudio.component.CountDownTimer.getTimeRemaining( endTime );

            $.each( timeRemaining.parts, function( timePart ) {

                var $element = elements[ timePart ];

                if ( $element.length ) {
                    $element.html( this );
                }

            } );

            if ( timeRemaining.total <= 0 ) {
                clearInterval( timeInterval );
            }
        };

        LaStudio.component.CountDownTimer.initClock = function() {
            LaStudio.component.CountDownTimer.updateClock();
            timeInterval = setInterval( LaStudio.component.CountDownTimer.updateClock, 1000 );
        };

        LaStudio.component.CountDownTimer.splitNum = function( num ) {

            var num   = num.toString(),
                arr   = [],
                reult = '';

            if ( 1 === num.length ) {
                num = 0 + num;
            }

            arr = num.match(/\d{1}/g);

            $.each( arr, function( index, val ) {
                reult += '<span class="lastudio-countdown-timer__digit">' + val + '</span>';
            });

            return reult;
        };

        LaStudio.component.CountDownTimer.getTimeRemaining = function( endTime ) {

            var timeRemaining = endTime - new Date(),
                seconds = Math.floor( ( timeRemaining / 1000 ) % 60 ),
                minutes = Math.floor( ( timeRemaining / 1000 / 60 ) % 60 ),
                hours = Math.floor( ( timeRemaining / ( 1000 * 60 * 60 ) ) % 24 ),
                days = Math.floor( timeRemaining / ( 1000 * 60 * 60 * 24 ) );

            if ( days < 0 || hours < 0 || minutes < 0 ) {
                seconds = minutes = hours = days = 0;
            }

            return {
                total: timeRemaining,
                parts: {
                    days: LaStudio.component.CountDownTimer.splitNum( days ),
                    hours: LaStudio.component.CountDownTimer.splitNum( hours ),
                    minutes: LaStudio.component.CountDownTimer.splitNum( minutes ),
                    seconds: LaStudio.component.CountDownTimer.splitNum( seconds )
                }
            };
        };

        LaStudio.component.CountDownTimer.initClock();

        return {
            init : function(){
                LaStudio.component.CountDownTimer.initClock();
            }
        }
    }

    LaStudio.component.InfiniteScroll = function(el){
        var $pagination = $($(el).data('pagination'));
        return {
            init : function () {
                LaStudio.core.InfiniteScroll($pagination);
            }
        }
    }

    LaStudio.core.InfiniteScroll = function( $pagination ){
        LaStudio.global.LazyLoad( $pagination, {
            rootMargin: '40px',
            threshold: 0.1,
            load : function () {
                $('.pagination_ajax_loadmore a', $pagination).trigger('click');
            }
        }).observe();
    }

    LaStudio.core.HeaderSticky = function () {
        var $header_builder = $masthead;

        var scroll_direction = 'none',
            last_scroll = $window.scrollTop();

        $window.on('scroll', function(){
            var currY = $window.scrollTop();
            scroll_direction = (currY > last_scroll) ? 'down' : ((currY === last_scroll) ? 'none' : 'up');
            last_scroll = currY;
        });

        var prepareHeightForHeader = function (){
            var _current_height = 0;
            if( $.exists($header_builder) ){
                _current_height = $('.lahbhinner').outerHeight();
                $('.lahb-wrap-sticky-height').height( _current_height );
                document.documentElement.style.setProperty('--header-height', _current_height + 'px');
            }
        };
        prepareHeightForHeader();
        $window.on('resize', prepareHeightForHeader);

        function init_mobile_bar_sticky(){

            if(!$.exists($('.footer-handheld-footer-bar'))){
                return;
            }

            var $_mobile_bar = $('.footer-handheld-footer-bar');

            $window.on('scroll', function(e){

                var mb_height = LaStudio.global.getAdminBarHeight() + $('.lahbhinner', $header_builder).outerHeight();

                if(mb_height < 20){
                    mb_height = 100;
                }

                if($window.scrollTop() > mb_height){
                    if(la_theme_config.mobile_bar == 'down'){
                        if(scroll_direction == 'down'){
                            $_mobile_bar.removeClass('sticky--unpinned').addClass('sticky--pinned');
                        }
                        else{
                            $_mobile_bar.removeClass('sticky--pinned').addClass('sticky--unpinned');
                        }
                    }
                    else if(la_theme_config.mobile_bar == 'up'){
                        if(scroll_direction == 'up'){
                            $_mobile_bar.removeClass('sticky--unpinned').addClass('sticky--pinned');
                        }
                        else{
                            $_mobile_bar.removeClass('sticky--pinned').addClass('sticky--unpinned');
                        }
                    }
                }
                else{
                    $_mobile_bar.removeClass('sticky--pinned sticky--unpinned');
                }
            })
        }
        init_mobile_bar_sticky();

        var sticky_auto_hide = !!$body.hasClass('header-sticky-type-auto');
        function init_builder_sticky() {
            if( ! $.exists($header_builder) ) {
                return;
            }

            var $_header = $header_builder,
                $_header_outer = $('.lahbhouter', $header_builder),
                $_header_inner = $('.lahbhinner', $header_builder);

            var lastY = 0,
                offsetY = LaStudio.global.getOffset($_header_outer).y;

            $window
                .on('resize', function(e){
                    offsetY = LaStudio.global.getOffset($_header_outer).y;
                })
                .on('scroll', function(e){

                    var currentScrollY = $window.scrollTop();

                    var _breakpoint = offsetY - LaStudio.global.getAdminBarHeight();

                    if(sticky_auto_hide){
                        _breakpoint = offsetY - LaStudio.global.getAdminBarHeight() + $_header_inner.outerHeight();
                    }

                    if( currentScrollY > _breakpoint ) {
                        $_header_inner.css('top', LaStudio.global.getAdminBarHeight());

                        if( !$_header.hasClass('is-sticky') ) {
                            $_header.addClass('is-sticky');
                        }

                        if(sticky_auto_hide){
                            if(currentScrollY < $body.height() && lastY > currentScrollY){
                                if($_header_inner.hasClass('sticky--unpinned')){
                                    $_header_inner.removeClass('sticky--unpinned');
                                }
                                if(!$_header_inner.hasClass('sticky--pinned')){
                                    $_header_inner.addClass('sticky--pinned');
                                }
                            }
                            else{
                                if($_header_inner.hasClass('sticky--pinned')){
                                    $_header_inner.removeClass('sticky--pinned');
                                }
                                if(!$_header_inner.hasClass('sticky--unpinned')){
                                    $_header_inner.addClass('sticky--unpinned');
                                }
                            }
                        }
                        else{
                            $_header_inner.addClass('sticky--pinned');
                        }
                    }
                    else{
                        if(sticky_auto_hide){
                            if($_header.hasClass('is-sticky')){
                                if(_breakpoint - currentScrollY < $_header_inner.outerHeight()){
                                }
                                else{
                                    /** remove stuck **/
                                    $_header.removeClass('is-sticky');
                                    $_header_inner.css('top','0').removeClass('sticky--pinned sticky--unpinned');
                                }
                            }
                        }
                        else{
                            if($_header.hasClass('is-sticky')){
                                $_header.removeClass('is-sticky');
                                $_header_inner.css('top','0').removeClass('sticky--pinned sticky--unpinned');
                            }
                        }
                    }

                    lastY = currentScrollY;
                })
        }

        if(!$body.hasClass('enable-header-sticky')) return;

        init_builder_sticky();
    }

    LaStudio.core.InstanceSearch = function ($modal) {

        if($modal.hasClass('has-init')){
            return;
        }
        $modal.addClass('has-init');

        var xhr = null,
            term = '',
            searchCache = {},
            $form = $modal.find( 'form.search-form' ),
            $search = $form.find( 'input.search-field' ),
            $results = $modal.find( '.search-results' ),
            $button = $results.find( '.search-results-button' ),
            post_type = $modal.find( 'input[name=post_type]' ).val();


        var delaySearch = (function(){
            var timer = 0;
            return function(callback, ms){
                clearTimeout (timer);
                timer = setTimeout(callback, ms);
            };
        })();

        $modal
            .on( 'keyup', '.search-field', function ( e ) {

                var valid = false;

                if ( typeof e.which === 'undefined' ) {
                    valid = true;
                }
                else if ( typeof e.which === 'number' && e.which > 0 ) {
                    valid = !e.ctrlKey && !e.metaKey && !e.altKey;
                }
                if ( !valid ) {
                    return;
                }
                if ( xhr ) {
                    xhr.abort();
                }
                delaySearch(function(){
                    search( true );
                }, 400 );

            })
            .on( 'change', '.product-cats input', function () {
                if ( xhr ) {
                    xhr.abort();
                }
                search( false );
            })
            .on( 'change', 'select', function () {
                if ( xhr ) {
                    xhr.abort();
                }
                search( false );
            })
            .on( 'click', '.search-reset', function () {
                if ( xhr ) {
                    xhr.abort();
                }
                $modal.addClass( 'reset' );
                $results.find( '.results-container, .view-more-results' ).slideUp( function () {
                    $modal.removeClass( 'searching searched found-products found-no-product invalid-length reset' );
                });
            } )
            .on( 'focusout', '.search-field', function () {
                if ( $(this).val().length < 2 ) {
                    $results.find( '.results-container, .view-more-results' ).slideUp( function () {
                        $modal.removeClass( 'searching searched found-products found-no-product invalid-length' );
                    });
                }
            })
            .on('focus', '.search-field', function () {
                if($modal.hasClass('found-products')){
                    $results.find( '.results-container' ).slideDown(200);
                }
            })

        /**
         * Private function for searching products
         */
        function search( typing ) {

            var keyword = $search.val(),
                $category = $form.find( '.product-cats input:checked' ),
                category = $category.length ? $category.val() : ( $form.find('select').length ? $form.find('select').val() : '' ),
                key = keyword + '[' + category + ']';

            if ( term === keyword && typing ) {
                return;
            }

            term = keyword;

            if ( keyword.length < 2 ) {
                $modal.removeClass( 'searching found-products found-no-product' ).addClass( 'invalid-length' );
                return;
            }

            var url = $form.attr( 'action' ) + '?' + $form.serialize();

            $button.removeClass( 'fadeInUp' );
            $( '.view-more-results', $results ).slideUp( 10 );
            $modal.removeClass( 'found-products found-no-product' ).addClass( 'searching' );

            if ( key in searchCache ) {
                showResult( searchCache[key] );
            }
            else {
                xhr = $.get( url, function ( response ) {

                    var $content = $( '#content.site-content', response );

                    if ( 'product' === post_type ) {
                        var $products = $( '#la_shop_products .row ul.products', $content );

                        if ( $products.length ) {
                            $products.children( 'li:eq(20)' ).nextAll().remove();
                            // Cache
                            searchCache[key] = {
                                found: true,
                                items: $products,
                                url  : url
                            };
                        }
                        else {
                            // Cache
                            searchCache[key] = {
                                found: false,
                                text : $( '.woocommerce-info', $content ).text()
                            };
                        }
                    }
                    else {

                        var $posts = $( '#blog-entries .lastudio-posts__item', $content );

                        if ( $posts.length ) {
                            $posts.addClass( 'col-md-4' );

                            searchCache[key] = {
                                found: true,
                                items: $( '<div class="posts" />' ).append( $posts ),
                                url  : url
                            };
                        }
                        else {
                            searchCache[key] = {
                                found: false,
                                text : $( '#blog-entries article .entry', $content ).text()
                            };
                        }
                    }

                    showResult( searchCache[key] );

                    $modal.addClass( 'searched' );

                    xhr = null;

                }, 'html' );
            }
        }

        /**
         * Private function for showing the search result
         *
         * @param result
         */
        function showResult( result ) {

            var extraClass = 'product' === post_type ? 'woocommerce' : 'la-post-grid';

            $modal.removeClass( 'searching' );

            if ( result.found ) {
                var grid = result.items.clone(),
                    items = grid.children();

                $modal.addClass( 'found-products' );

                $results.find( '.results-container' ).addClass( extraClass ).html( grid );

                LaStudio.core.initAll($results);

                // Add animation class
                for ( var index = 0; index < items.length; index++ ) {
                    $( items[index] ).css( 'animation-delay', index * 100 + 'ms' );
                }

                items.addClass( 'fadeInUp animated' );

                $button.attr( 'href', result.url ).css( 'animation-delay', index * 100 + 'ms' ).addClass( 'fadeInUp animated' );

                $results.find( '.results-container, .view-more-results' ).slideDown( 300, function () {
                    $modal.removeClass( 'invalid-length' );
                } );
            }
            else {
                $modal.addClass( 'found-no-product' );

                $results.find( '.results-container' ).removeClass( extraClass ).html( $( '<div class="not-found text-center" />' ).text( result.text ) );
                $button.attr( 'href', '#' );

                $results.find( '.view-more-results' ).slideUp( 300 );
                $results.find( '.results-container' ).slideDown( 300, function () {
                    $modal.removeClass( 'invalid-length' );
                });
            }

            $modal.addClass( 'searched' );
        }
    }

    LaStudio.core.MegaMenu = function () {

        function fix_megamenu_position( $elem, $container, container_width, isVerticalMenu) {

            if($('.megamenu-inited', $elem).length){
                return false;
            }
            var $popup = $('> .sub-menu', $elem);

            if ($popup.length == 0) return;
            var megamenu_width = $popup.outerWidth();

            if (megamenu_width > container_width) {
                megamenu_width = container_width;
            }
            if (!isVerticalMenu) {

                var container_padding_left = parseInt($container.css('padding-left')),
                    container_padding_right = parseInt($container.css('padding-right')),
                    parent_width = $popup.parent().outerWidth(),
                    left = 0,
                    container_offset = LaStudio.global.getOffset($container),
                    megamenu_offset = LaStudio.global.getOffset($popup);

                var megamenu_offset_x = megamenu_offset.x,
                    container_offset_x = container_offset.x;

                if (megamenu_width > parent_width) {
                    left = -(megamenu_width - parent_width) / 2;
                }
                else{
                    left = 0
                }
                if(LaStudio.global.isRTL()){

                    var megamenu_offset_x_swap = $window.width() - ( megamenu_width + megamenu_offset_x ),
                        container_offset_x_swap = $window.width() - ( $container.outerWidth() + container_offset_x );

                    if ((megamenu_offset_x_swap - container_offset_x_swap - container_padding_right + left) < 0) {
                        left = -(megamenu_offset_x_swap - container_offset_x_swap - container_padding_right);
                    }
                    if ((megamenu_offset_x_swap + megamenu_width + left) > (container_offset_x + $container.outerWidth() - container_padding_left)) {
                        left -= (megamenu_offset_x_swap + megamenu_width + left) - (container_offset_x + $container.outerWidth() - container_padding_left);
                    }
                    $popup.css('right', left).css('right');
                }
                else{
                    if ((megamenu_offset_x - container_offset_x - container_padding_left + left) < 0) {
                        left = -(megamenu_offset_x - container_offset_x - container_padding_left);
                    }
                    if ((megamenu_offset_x + megamenu_width + left) > (container_offset_x + $container.outerWidth() - container_padding_right)) {
                        left -= (megamenu_offset_x + megamenu_width + left) - (container_offset_x + $container.outerWidth() - container_padding_right);
                    }
                    $popup.css('left', left).css('left');
                }
            }

            if (isVerticalMenu) {
                var clientHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight,
                    itemOffset = $popup.offset(),
                    itemHeight = $popup.outerHeight(),
                    scrollTop = $window.scrollTop();

                if (itemOffset.top - scrollTop + itemHeight > clientHeight) {
                    $popup.css({top: clientHeight - itemOffset.top + scrollTop - itemHeight - 20});
                }
            }

            $popup.addClass('megamenu-inited');
        }

        LaStudio.global.eventManager.subscribe('LaStudio:MegaMenuBuilder:MenuPosition', function(e, $megamenu){
            if($.exists($megamenu)){

                $megamenu.closest('.lahb-content-wrap').addClass('position-relative');

                $megamenu.each(function(){
                    var _that = $(this),
                        container_width = 0,
                        $container = _that.closest('.lahb-content-wrap'),
                        isVerticalMenu = false;

                    container_width = $container.width();

                    if( _that.closest('.lahb-vertital-menu_nav').length ) {
                        isVerticalMenu = true;
                    }

                    if($body.hasClass('header-type-vertical')){
                        container_width = 1170;
                        if( $window.width() < 1500 ) {
                            container_width = $window.width() -  $masthead.outerWidth();
                        }
                    }
                    else{
                        if( _that.hasClass('lahb-vertital-menu_nav')){
                            container_width = container_width - _that.outerWidth();
                        }
                    }

                    $('li.mega .megamenu-inited', _that).removeClass('megamenu-inited');

                    $('li.mega > .sub-menu', _that).removeAttr('style');

                    $('li.mega', _that).each(function(){
                        var $menu_item = $(this),
                            $popup = $('> .sub-menu', $menu_item),
                            $inner_popup = $('> .sub-menu > .mm-mega-li', $menu_item),
                            item_max_width = parseInt(!!$inner_popup.data('maxWidth') ? $inner_popup.data('maxWidth') : $inner_popup.css('maxWidth') ),
                            $_container = $container;

                        var default_width = 1170;

                        if(container_width < default_width){
                            default_width = container_width;
                        }

                        if(isNaN(item_max_width)){
                            item_max_width = default_width;
                        }

                        if(default_width > item_max_width){
                            default_width = parseInt(item_max_width);
                        }

                        if( $menu_item.hasClass('mm-popup-force-fullwidth')){
                            $inner_popup.data('maxWidth', item_max_width).css('maxWidth', 'none');
                            $('> ul', $inner_popup).css('width', item_max_width);
                            if(!isVerticalMenu){
                                default_width = $window.width();
                                $_container = $body;
                            }
                            else{
                                default_width = $('#outer-wrap > #wrap').width();
                            }
                        }
                        $popup.width(default_width);
                        fix_megamenu_position( $menu_item, $_container, container_width, isVerticalMenu);
                    });
                })
            }
        });

        LaStudio.global.eventManager.publish('LaStudio:MegaMenuBuilder:MenuPosition', [ $('body .lahb-nav-wrap.has-megamenu') ]);

        $window.on('resize', function(){
            LaStudio.global.eventManager.publish('LaStudio:MegaMenuBuilder:MenuPosition', [ $('body .lahb-nav-wrap.has-megamenu') ]);
        });

        $('.lahb-vertital-menu_nav .lahb-vertital-menu_button button').on('click', function (e) {
            e.preventDefault();
            var $parent = $(this).closest('.lahb-vertital-menu_nav');
            $parent.hasClass('open') ? $parent.removeClass('open') : $parent.addClass('open');
        });
    }

    LaStudio.core.ElementClickEvent = function(){
        $document
            .on('touchend click', '.la-overlay-global', function (e) {
                e.preventDefault();
                $body.removeClass('open-lahb-vertical open-cart-aside');
            })
            .on('click', '.footer-handheld-footer-bar .la_com_action--dropdownmenu .component-target', function (e) {
                e.preventDefault();
                var $_parent = $(this).parent();
                $body.removeClass('open-mobile-menu open-search-form');
                if($_parent.hasClass('active')){
                    $_parent.removeClass('active');
                    $body.removeClass('open-overlay');
                }else{
                    $_parent.addClass('active');
                    $_parent.siblings().removeClass('active');
                    $body.addClass('open-overlay');
                }
            })
            .on('click', '.footer-handheld-footer-bar .la_com_action--searchbox', function (e) {
                e.preventDefault();
                var $this = $(this);
                if($(this).hasClass('active')){
                    $body.removeClass('open-search-form ');
                    $(this).removeClass('active');
                }
                else{
                    $body.addClass('open-search-form');
                    $(this).addClass('active');
                    $(this).siblings().removeClass('active');
                    $body.removeClass('open-overlay')
                }
            })
            .on('click', '.la-popup:not(.elementor-widget), .la-popup.elementor-widget a', function (e) {
                e.preventDefault();
                var $that = $(this);
                var init_auto_popup = function(){

                    $that.lightcase('start', {
                        showTitle: false,
                        showCaption: false,
                        maxWidth: $window.width(),
                        maxHeight: $window.height(),
                        showSequenceInfo: false,
                        slideshow: true,
                        iframe:{
                            width:1280,
                            height:720
                        },
                        onFinish: {
                            createOverlay: function () {
                                lightcase.get('content').append('<div class="custom-lightcase-overlay"></div>');
                                lightcase.get('contentInner').append('<a class="custom-lighcase-btn-close" href="#"><i class="lastudioicon-e-remove"></i></a>');
                            }
                        },
                        onClose : {
                            qux : function(){
                                $('.custom-lightcase-overlay').remove();
                                $('.custom-lighcase-btn-close').remove();
                            }
                        }
                    });

                }
                if($.fn.lightcase){
                    init_auto_popup();
                }
                else{
                    LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('jquery.lightcase')], init_auto_popup );
                }
            })
            .on('click', '.la-inline-popup', function (e) {
                e.preventDefault();
                var _this = $(this);
                var $popup = $(_this.attr('href'));
                var extra_class = _this.data('component_name');

                lightcase.start({
                    href: '#',
                    maxWidth: parseInt(la_theme_config.popup.max_width),
                    maxHeight: parseInt(la_theme_config.popup.max_height),
                    onInit : {
                        foo: function() {
                            $body.addClass(extra_class);
                        }
                    },
                    onFinish: {
                        injectContent: function () {
                            lightcase.get('content').append('<div class="custom-lightcase-overlay"></div>');
                            lightcase.get('contentInner').children().append($popup);
                            lightcase.get('contentInner').append('<a class="custom-lighcase-btn-close" href="#"><i class="lastudioicon-e-remove"></i></a>');
                            $('.lightcase-icon-close').hide();
                            lightcase.resize();
                        }
                    },
                    onClose : {
                        qux: function() {
                            $('.custom-lightcase-overlay').remove();
                            $('.custom-lighcase-btn-close').remove();
                            $popup.appendTo(_this.parent());
                            $body.removeClass(extra_class);
                        }
                    }
                });
            })
            .on('click', '.custom-lighcase-btn-close, .popup-button-continue, .custom-lightcase-overlay', function(e){
                e.preventDefault();
                try{
                    lightcase.close();
                }catch (ex){}
            })
            .on('click', '.btn-close-search', function (e) {
                e.preventDefault();
                $body.removeClass('open-search-form')
            })
            .on('click', '.btn-close-cart', function (e) {
                e.preventDefault();
                $body.removeClass('open-cart-aside')
            })
            .on('click', '.la-ajax-pagination .pagination_ajax_loadmore a', function (e) {
                e.preventDefault();

                if($('body').hasClass('elementor-editor-active')){
                    return false;
                }

                var $pagination, url_request, _parent_container, _container, _item_selector;
                $pagination = $(this).closest('.la-ajax-pagination');
                _parent_container =  $pagination.data('parent-container');
                _container =  $pagination.data('container');
                _item_selector =  $pagination.data('item-selector');

                var _infinite_flag = false;

                if( $pagination.data('infinite-flag') ) {
                    _infinite_flag = $pagination.data('infinite-flag');
                }

                if( $('a.next', $pagination).length ) {

                    if($pagination.hasClass('doing-ajax')){
                        return false;
                    }

                    $pagination.addClass('doing-ajax');
                    $(_parent_container).addClass('doing-ajax');

                    url_request = $('a.next', $pagination).attr('href');

                    var ajaxOpts = {
                        url: url_request,
                        type: "get",
                        dataType: 'html',
                        success: function (response) {
                            var $data = $(response).find(_container + ' ' + _item_selector);

                            if($(_container).hasClass('la-slick-slider')) {
                                $(_container).slick('slickAdd', $data);
                                $(_container).slick('setPosition');
                            }
                            else if( $(_container).data('isotope') ){
                                $(_container).isotope('insert', $data.addClass('showmenow') );
                                if( $(_container).data('la_component') == 'AdvancedMasonry' ) {
                                    LaStudio.global.eventManager.publish('LaStudio:AdvancedMasonry:calculatorItemWidth', [$(_container), false]);
                                    $(_container).isotope('layout');
                                }
                                else{
                                    setTimeout(function(){
                                        $(_container).isotope('layout');
                                    }, 500);
                                }
                                $(_container).trigger('LaStudio:Masonry:ajax_loadmore', [$(_container)]);
                            }
                            else{
                                $data.addClass('fadeIn animated').appendTo($(_container));
                            }

                            $('body').trigger('lastudio-fix-ios-limit-image-resource').trigger( 'lastudio-lazy-images-load' ).trigger( 'jetpack-lazy-images-load' ).trigger( 'lastudio-object-fit' );

                            LaStudio.core.initAll($(_parent_container));

                            $(_parent_container).removeClass('doing-ajax');
                            $pagination.removeClass('doing-ajax la-ajax-load-first');

                            if($(response).find(_parent_container + ' .la-ajax-pagination').length){
                                var $new_pagination = $(response).find(_parent_container + ' .la-ajax-pagination');
                                $pagination.replaceWith($new_pagination);
                                $pagination = $new_pagination;
                            }
                            else{
                                $pagination.addClass('nothingtoshow');
                            }

                            if(_infinite_flag !== false){
                                setTimeout(function () {
                                    LaStudio.core.InfiniteScroll($pagination);
                                }, 2000);
                            }
                        }
                    };

                    $.ajax(ajaxOpts);
                }
            })
            .on('click', '.la-ajax-pagination .page-numbers a', function (e) {
                e.preventDefault();

                if($('body').hasClass('elementor-editor-active')){
                    return false;
                }

                var $pagination, url_request, _parent_container, _container, _item_selector;
                $pagination = $(this).closest('.la-ajax-pagination');
                _parent_container =  $pagination.data('parent-container');
                _container =  $pagination.data('container');
                _item_selector =  $pagination.data('item-selector');

                if($(_parent_container).hasClass('doing-ajax')){
                    return;
                }

                $(_parent_container).addClass('doing-ajax');
                $pagination.addClass('doing-ajax');

                url_request = LaStudio.global.removeURLParameter($(this).attr('href'), '_');

                $.ajax({
                    url: url_request,
                    type: "get",
                    dataType: 'html',
                    cache: true,
                    ajax_request_id: LaStudio.global.getUrlParameter($pagination.data('ajax_request_id'), url_request),
                    success: function (response) {
                        var $data = $(response).find(_container + ' ' + _item_selector);

                        if($(_container).hasClass('la-slick-slider')) {
                            $(_container).slick('unslick').removeData('initAutoCarousel');
                            $data.appendTo($(_container).empty());
                        }
                        else if( $(_container).data('isotope') ){

                            $(_container).isotope('remove', $(_container).isotope('getItemElements'));
                            $(_container).isotope('insert', $data.addClass('showmenow'));
                            if( $(_container).data('la_component') == 'AdvancedMasonry' ) {
                                LaStudio.global.eventManager.publish('LaStudio:AdvancedMasonry:calculatorItemWidth', [$(_container), false]);
                                $(_container).isotope('layout');
                            }
                            else{
                                setTimeout(function(){
                                    $(_container).isotope('layout');
                                }, 500);
                            }
                            $(_container).trigger('LaStudio:Masonry:ajax_pagination', [$(_container)]);

                        }
                        else{
                            $data.addClass('fadeIn animated');
                            $data.appendTo($(_container).empty());
                        }

                        $('body').trigger('lastudio-fix-ios-limit-image-resource').trigger( 'lastudio-lazy-images-load' ).trigger( 'jetpack-lazy-images-load' ).trigger( 'lastudio-object-fit' );

                        LaStudio.core.initAll($(_parent_container));

                        $(_parent_container).removeClass('doing-ajax');
                        $pagination.removeClass('doing-ajax');

                        if($(response).find(_parent_container + ' .la-ajax-pagination').length){
                            $pagination.replaceWith($(response).find(_parent_container + ' .la-ajax-pagination'));
                        }
                    }

                });

            });
    }

    LaStudio.core.Blog = function( $sidebar_inner ){
        $sidebar_inner = $sidebar_inner || $('.sidebar-inner');

        $('.menu li a:empty', $sidebar_inner).each(function () {
            $(this).closest('li').remove();
        })

        $('.widget_pages > ul, .widget_archive > ul, .widget_categories > ul, .widget_product_categories > ul, .widget_meta > ul', $sidebar_inner).addClass('menu').closest('.widget').addClass('accordion-menu');
        $('.widget_nav_menu', $sidebar_inner).closest('.widget').addClass('accordion-menu');
        $('.widget_categories > ul li.cat-parent,.widget_product_categories li.cat-parent', $sidebar_inner).addClass('mm-item-has-sub');

        $('.menu li > ul', $sidebar_inner).each(function(){
            var $ul = $(this);
            $ul.before('<span class="narrow"><i></i></span>');
        });

        $sidebar_inner.on('click','.accordion-menu li.menu-item-has-children > a,.menu li.mm-item-has-sub > a,.menu li > .narrow',function(e){
            e.preventDefault();
            var $parent = $(this).parent();
            if ($parent.hasClass('open')) {
                $parent.removeClass('open');
                $parent.find('>ul').stop().slideUp();
            }
            else {
                $parent.addClass('open');
                $parent.find('>ul').stop().slideDown();
                $parent.siblings().removeClass('open').find('>ul').stop().slideUp();
            }
        });
    }

    LaStudio.core.SitePreload = function () {

        var pbar = document.getElementById('wpadminbar');
        if(pbar){
            pbar.classList.add('wpbar');
        }

        /** Back To Top **/
        $window.on('load scroll', function(){
            if($window.scrollTop() > $window.height() + 100){
                $('.backtotop-container').addClass('show');
            }
            else{
                $('.backtotop-container').removeClass('show');
            }
        })
        $document.on('click', '.btn-backtotop', function(e){
            e.preventDefault();
            $htmlbody.animate({
                scrollTop: 0
            }, 800)
        })

        $body.on('lastudio-fix-ios-limit-image-resource', function () {
            if ( ! ( 'matchMedia' in window ) ) { return; }
            if( window.matchMedia("(max-width: 1024px)").matches ) {
                $('li.product_item.thumb-has-effect').each(function () {
                    $(this).removeClass('thumb-has-effect');
                    $(this).find('.p_img-second').remove();
                })
            }

        }).trigger('lastudio-fix-ios-limit-image-resource');

        $body.removeClass('site-loading');

        $window.on('beforeunload', function(e){
            var browser_information = LaStudio.global.getBrowseInformation();
            if(browser_information.name != 'safari' && window.self === window.top){
                if( typeof window['hack_beforeunload_time'] === "undefined" || ( typeof window['hack_beforeunload_time'] !== "undefined" && e.timeStamp - window['hack_beforeunload_time'] > 1000 ) ) {
                    $('#outer-wrap.site').css('opacity', '0');
                    $body.addClass('site-loading');
                }
            }
        });

        $document.on('click', 'a[href^="tel:"], a[href^="mailto:"], a[href^="callto"], a[href^="skype"], a[href^="whatsapp"], a.mail-link', function(e){
            window['hack_beforeunload_time'] = parseInt(e.timeStamp);
        });
        $window.on('pageshow', function(e){
            if (e.originalEvent.persisted) {
                $body.removeClass('site-loading');
            }
        });

        LaStudio.global.eventManager.subscribe('LaStudio:Component:LazyLoadImage', function(e, $container){
            $container.find('.la-lazyload-image:not([data-element-loaded="true"]), img[data-lazy-src]:not([data-element-loaded="true"]), img[data-lazy-original]:not([data-element-loaded="true"])').each(function(idx, element){
                LaStudio.global.makeImageAsLoaded(element);
            })
        });

        $('body').trigger( 'lastudio-lazy-images-load' ).trigger( 'jetpack-lazy-images-load' ).trigger( 'lastudio-object-fit' );
    }

    LaStudio.core.WooCommerceQuickView = function(){
        $document.on('click','.la-quickview-button',function(e){
            var _qv_mw, _qv_mh;
            if( $window.width() > 1500){
                _qv_mw = 1440;
                _qv_mh = 900;
            }
            else if( $window.width() > 1200 ) {
                _qv_mw = 1000;
                _qv_mh = 650;
            }
            else{
                _qv_mw = 900;
                _qv_mh = 600;
            }

            if(_qv_mh > $window.height()){
                _qv_mh = $window.height() * 0.8;
            }

            if($window.width() > 900){
                e.preventDefault();
                var $this = $(this);
                var show_popup = function(){
                    lightcase.start({
                        href: $this.data('href'),
                        showSequenceInfo: false,
                        type: 'ajax',
                        maxWidth: _qv_mw,
                        maxHeight: _qv_mh,
                        speedIn: 150,
                        speedOut: 100,
                        ajax: {
                            width: _qv_mw,
                            height: _qv_mh,
                            cache: true,
                            ajax_request_id: LaStudio.global.getUrlParameter('product_quickview', $this.data('href'))
                        },

                        onClose : {
                            qux : function(){
                                $body.removeClass('open-quickview-product lightcase--completed lightcase--pending');
                                $('.custom-lightcase-overlay').remove();
                                $('.custom-lighcase-btn-close').remove();
                            }
                        },
                        onWait: function( obj, $obj, data){
                            $body.addClass('open-quickview-product');
                            obj.objects.content.append('<div class="custom-lightcase-overlay"></div>');
                            obj.objects.contentInner.append('<a class="custom-lighcase-btn-close" href="#"><i class="lastudioicon-e-remove"></i></a>');
                            $obj.html(data);
                            var $woo_gallery = $('.la-woo-product-gallery', $obj);
                            if($woo_gallery.length && $.fn.lastudio_product_gallery){
                                $body.addClass('lightcase--pending');
                                $woo_gallery.lastudio_product_gallery();
                                obj._showContent($obj);
                            }
                            else{
                                obj._showContent($obj);
                            }
                        }
                    })
                }
                if($.fn.lightcase){
                    show_popup();
                }
                else{
                    LaStudio.global.loadDependencies([ LaStudio.global.loadJsFile('jquery.lightcase')], show_popup );
                }
            }
        });
    }

    LaStudio.core.WooCommerce = function (){
        /*
         * Initialize all galleries on page.
         */
        $( '.la-woo-product-gallery' ).each( function() {
            $( this ).lastudio_product_gallery();
        } );

        $('.variations_form').trigger('wc_variation_form');

        $document.on('click','.product_item .la-swatch-control .swatch-wrapper', function(e){
            e.preventDefault();
            var $swatch_control = $(this),
                $image = $swatch_control.closest('.product_item').find('.p_img-first img').first(),
                $btn_cart = $swatch_control.closest('.product_item').find('.la-addcart');

            if($swatch_control.hasClass('selected')) return;
            $swatch_control.addClass('selected').siblings().removeClass('selected');
            if(!$image.hasClass('_has_changed')){
                $image.attr('data-o-src', $image.attr('src')).attr('data-o-sizes', $image.attr('sizes')).attr('data-o-srcset', $image.attr('srcset'));
            }
            $image.attr('src', ($swatch_control.attr('data-thumb') ? $swatch_control.attr('data-thumb') : $image.attr('data-o-src'))).removeAttr('sizes srcset');
            if($btn_cart.length > 0){
                var _href = $btn_cart.attr('href');
                _href = LaStudio.global.addQueryArg(_href, 'attribute_' + $swatch_control.attr('data-attribute'), $swatch_control.attr('data-value'));
                $btn_cart.attr('href', _href);
            }
        })

        /**
         * Lazyload image for cart widget
         */
        var cart_widget_timeout = null;
        $(document.body).on('wc_fragments_refreshed updated_wc_div wc_fragments_loaded', function(e){
            clearTimeout( cart_widget_timeout );
            cart_widget_timeout = setTimeout( function(){
                LaStudio.global.eventManager.publish('LaStudio:Component:LazyLoadImage', [$('.widget_shopping_cart_content')]);
            }, 100);
        });
        $document.on('click', '.la_com_action--cart', function(e){
            if(!$(this).hasClass('force-display-on-mobile')){
                if($window.width() > 767){
                    e.preventDefault();
                    $body.toggleClass('open-cart-aside');
                }
            }
            else{
                e.preventDefault();
                $body.toggleClass('open-cart-aside');
            }
        });

        /**
         * Cart Plus & Minus action
         */
        $document.on('click', '.quantity .qty-minus', function(e){
            e.preventDefault();
            var $qty = $(this).next('.qty'),
                val = parseInt($qty.val());
            $qty.val( val > 1 ? val-1 : 1).trigger('change');
        })
        $document.on('click', '.quantity .qty-plus', function(e){
            e.preventDefault();
            var $qty = $(this).prev('.qty'),
                val = parseInt($qty.val());
            $qty.val( val > 0 ? val+1 : 1 ).trigger('change');
        })

        /**
         * View mode toggle
         */
        $document
            .on('click','.wc-view-toggle button',function(){
                var _this = $(this),
                    _mode = _this.data('view_mode');
                if(!_this.hasClass('active')){
                    $('.wc-view-toggle button').removeClass('active');
                    _this.addClass('active');

                    var $ul_products = $('.single-page-article').find('ul.products[data-grid_layout]'),
                        _old_grid = $ul_products.attr('data-grid_layout');
                    if(_mode == 'grid'){
                        $ul_products.removeClass('products-list').addClass('products-grid').addClass(_old_grid);
                    }
                    else {
                        $ul_products.removeClass('products-grid').addClass('products-list').removeClass(_old_grid);
                    }
                    Cookies.set('skudmart_wc_catalog_view_mode', _mode, { expires: 2 });
                }
            })
            .on('mouseover', '.lasf-custom-dropdown', function (e) {
                $(this).addClass('is-hover');
            })
            .on('mouseleave', '.lasf-custom-dropdown', function (e) {
                $(this).removeClass('is-hover');
            })
        /**
         * Ajax add-to-cart
         */
        $document.on('adding_to_cart', function (e, $button, data) {
            if( $button && $button.closest('.la_wishlist_table').length ) {
                data.la_remove_from_wishlist_after_add_to_cart = data.product_id;
            }
            $body.removeClass('open-search-form').addClass('open-cart-aside');
            $('.cart-flyout').addClass('cart-flyout--loading');
            $('i.cart-i_icon').each(function () {
                var _old_icon = $(this).data('icon');
                $(this).removeClass(_old_icon).addClass('fa fa-spinner fa-spin');
            });
        });
        $document.on('added_to_cart', function( e, fragments, cart_hash, $button ){
            $('.cart-flyout').removeClass('cart-flyout--loading');
            $('i.cart-i_icon').each(function () {
                $(this).removeClass('fa fa-spinner fa-spin').addClass($(this).data('icon'));
            })
        } );

        /**
         * Ajax add-to-cart - Single Page
         */
        if( la_theme_config.single_ajax_add_cart ) {
            $document.on('submit', '.la-p-single-wrap:not(.product-type-external) .entry-summary form.cart', function(e){
                e.preventDefault();
                $document.trigger('adding_to_cart');

                var form = $(this),
                    product_url = form.attr('action') || window.location.href,
                    action_url = LaStudio.global.addQueryArg(product_url, 'product_quickview', '1');

                $.post(action_url, form.serialize() + '&_wp_http_referer=' + product_url, function (result) {
                    // Show message
                    if($(result).eq(0).hasClass('woocommerce-message') || $(result).eq(0).hasClass('woocommerce-error')){
                        $('.woocommerce-message, .woocommerce-error').remove();
                        $('.la-p-single-wrap.type-product').eq(0).before($(result).eq(0));
                    }
                    try{
                        lightcase.close();
                    }catch (ex){}
                    // update fragments
                    $.ajax({
                        url: woocommerce_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ),
                        type: 'POST',
                        success: function( data ) {
                            if ( data && data.fragments ) {
                                $.each( data.fragments, function( key, value ) {
                                    $( key ).replaceWith( value );
                                });
                                $( document.body ).trigger( 'wc_fragments_refreshed' );
                                $('.cart-flyout').removeClass('cart-flyout--loading');
                                $('i.cart-i_icon').each(function () {
                                    $(this).removeClass('fa fa-spinner fa-spin').addClass($(this).data('icon'));
                                })
                            }
                        }
                    });
                });
            });
        }

        if($('.la-p-single-wrap .s_product_content_top > .product--summary .product-nextprev').length){
            $('.la-p-single-wrap .s_product_content_top > .product--summary .product-nextprev').clone().prependTo($('.la-p-single-wrap .s_product_content_top >.product-main-image'));
        }

        /**
         * Sticky panel for product layout 03
         */
        $('.la-p-single-wrap.la-p-single-3 .la-custom-pright').la_sticky({
            parent: $('.la-single-product-page'),
            offset_top: ($masthead.length ? parseInt($masthead.height()) + 30 : 30)
        });
        $('.la-p-single-wrap.la-p-single-3 .woocommerce-product-gallery__actions, .la-p-single-wrap.la-p-single-4 .woocommerce-product-gallery__actions').la_sticky({
            parent: $('.la-woo-product-gallery'),
            offset_top: $window.height() - 150,
        });

        /**
         * My Account toggle
         */

        $('#customer_login .u-column1.col-1').addClass('active');

        $document.on('click', '#customer_login h2', function (e) {
            e.preventDefault();
            $(this).parent().toggleClass('active').siblings('div').removeClass('active');
        });

        /**
         * WooCommerce Tabs
         */
        $('.woocommerce-tabs .wc-tab-title a').on('click', function(e){
            e.preventDefault();
            var $this = $(this),
                $wrap = $this.closest('.woocommerce-tabs'),
                $wc_tabs = $wrap.find('.wc-tabs'),
                $panel = $this.closest('.wc-tab');

            $wc_tabs.find('a[href="'+ $this.attr('href') +'"]').parent().toggleClass('active').siblings().removeClass('active');
            $panel.toggleClass('active').siblings().removeClass('active');
        });
        $('.woocommerce-Tabs-panel--description').addClass('active');

        /**
         * Cart Pages
         */

        function move_calc_form_and_coupon_form(){
            $('.lasf-extra-cart--calc .lasf-extra-cart-box').empty();
            $('.lasf-extra-cart--coupon .lasf-extra-cart-box').empty();
            $('.cart-collaterals .lasf-extra-cart').removeClass('active');
            if($('.cart-collaterals .cart_totals .woocommerce-shipping-calculator').length){
                $('.cart-collaterals .cart_totals .woocommerce-shipping-calculator').appendTo($('.lasf-extra-cart--calc .lasf-extra-cart-box'));
                $('.lasf-extra-cart--calc').addClass('active');
            }
            if($('.woocommerce-cart .woocommerce td.actions .coupon').length){

                var $coupon_clone = $('.woocommerce-cart .woocommerce td.actions .coupon').clone();
                $coupon_clone.find('label').attr('for', 'coupon_code_ref');
                $coupon_clone.find('.input-text').attr('name', 'coupon_code_ref').attr('id', 'coupon_code_ref');
                $coupon_clone.find('.button').attr('name', 'apply_coupon_ref');
                $coupon_clone.appendTo($('.lasf-extra-cart--coupon .lasf-extra-cart-box'));
                $('.lasf-extra-cart--coupon').addClass('active');

                $('.button[name="apply_coupon_ref"]').on('click', function (e) {
                    e.preventDefault();
                    $('.woocommerce-cart-form__contents input#coupon_code').val( $('#coupon_code_ref').val() );
                    $('.woocommerce-cart-form__contents .coupon .button').trigger('click');
                });
            }
        }


    }

    LaStudio.core.WooCommerceWishlist = function(){
        /**
         * Support YITH Wishlist
         */
        function set_attribute_for_wl_table(){
            var $table = $('table.wishlist_table');
            $table.addClass('shop_table_responsive');
            $table.find('thead th').each(function(){
                var _th = $(this),
                    _text = _th.text().trim();
                if(_text != ""){
                    $('td.' + _th.attr('class'), $table).attr('data-title', _text);
                }
            });
        }
        set_attribute_for_wl_table();
        $body.on('removed_from_wishlist', function(e){
            set_attribute_for_wl_table();
        });
        $document.on('added_to_cart', function(e, fragments, cart_hash, $button){
            setTimeout(set_attribute_for_wl_table, 800);
        });
        $document.on('click','.product a.add_wishlist.la-yith-wishlist',function(e){
            if(!$(this).hasClass('added')) {
                e.preventDefault();
                var $button     = $(this),
                    product_id = $button.data( 'product_id' ),
                    $product_image = $button.closest('.product').find('.product_item--thumbnail img:eq(0)'),
                    product_name = 'Product',
                    data = {
                        add_to_wishlist: product_id,
                        product_type: $button.data( 'product-type' ),
                        action: yith_wcwl_l10n.actions.add_to_wishlist_action
                    };
                if (!!$button.data('product_title')) {
                    product_name = $button.data('product_title');
                }
                if($button.closest('.product--summary').length){
                    $product_image = $button.closest('.product').find('.woocommerce-product-gallery__image img:eq(0)');
                }
                try {
                    if (yith_wcwl_l10n.multi_wishlist && yith_wcwl_l10n.is_user_logged_in) {
                        var wishlist_popup_container = $button.parents('.yith-wcwl-popup-footer').prev('.yith-wcwl-popup-content'),
                            wishlist_popup_select = wishlist_popup_container.find('.wishlist-select'),
                            wishlist_popup_name = wishlist_popup_container.find('.wishlist-name'),
                            wishlist_popup_visibility = wishlist_popup_container.find('.wishlist-visibility');

                        data.wishlist_id = wishlist_popup_select.val();
                        data.wishlist_name = wishlist_popup_name.val();
                        data.wishlist_visibility = wishlist_popup_visibility.val();
                    }

                    if (!LaStudio.global.isCookieEnable()) {
                        alert(yith_wcwl_l10n.labels.cookie_disabled);
                        return;
                    }

                    $.ajax({
                        type: 'POST',
                        url: yith_wcwl_l10n.ajax_url,
                        data: data,
                        dataType: 'json',
                        beforeSend: function () {
                            $button.addClass('loading');
                        },
                        complete: function () {
                            $button.removeClass('loading').addClass('added');
                        },
                        success: function (response) {
                            var msg = $('#yith-wcwl-popup-message'),
                                response_result = response.result,
                                response_message = response.message;

                            if (yith_wcwl_l10n.multi_wishlist && yith_wcwl_l10n.is_user_logged_in) {
                                var wishlist_select = $('select.wishlist-select');
                                if (typeof $.prettyPhoto !== 'undefined') {
                                    $.prettyPhoto.close();
                                }
                                wishlist_select.each(function (index) {
                                    var t = $(this),
                                        wishlist_options = t.find('option');
                                    wishlist_options = wishlist_options.slice(1, wishlist_options.length - 1);
                                    wishlist_options.remove();

                                    if (typeof response.user_wishlists !== 'undefined') {
                                        var i = 0;
                                        for (i in response.user_wishlists) {
                                            if (response.user_wishlists[i].is_default != "1") {
                                                $('<option>')
                                                    .val(response.user_wishlists[i].ID)
                                                    .html(response.user_wishlists[i].wishlist_name)
                                                    .insertBefore(t.find('option:last-child'))
                                            }
                                        }
                                    }
                                });

                            }
                            var html = '<div class="popup-added-msg">';
                            if (response_result == 'true') {
                                if ($product_image.length){
                                    html += $('<div>').append($product_image.clone()).html();
                                }
                                html += '<div class="popup-message"><strong class="text-color-heading">'+ product_name +' </strong>' + la_theme_config.i18n.wishlist.success + '</div>';
                            }else {
                                html += '<div class="popup-message">' + response_message + '</div>';
                            }
                            html += '<a class="button view-popup-wishlish" rel="nofollow" href="' + response.wishlist_url.replace('/view', '') + '">' + la_theme_config.i18n.wishlist.view + '</a>';
                            html += '<a class="button popup-button-continue" rel="nofollow" href="#">' + la_theme_config.i18n.global.continue_shopping + '</a>';
                            html += '</div>';

                            LaStudio.global.ShowMessageBox(html, 'open-wishlist-msg');

                            $button.attr('href',response.wishlist_url);
                            $('.add_wishlist[data-product_id="' + $button.data('product_id') + '"]').addClass('added');
                            $body.trigger('added_to_wishlist');
                        }
                    });
                } catch (ex) {
                    console.log(ex);
                }
            }
        });


        /**
         * Support TI Wishlist
         */
        $document.on('click','.product a.add_wishlist.la-ti-wishlist',function(e){
            e.preventDefault();
            var $ti_action;
            if($(this).closest('.entry-summary').length){
                $ti_action = $(this).closest('.entry-summary').find('form.cart .tinvwl_add_to_wishlist_button');
            }
            else{
                $ti_action = $(this).closest('.product').find('.tinvwl_add_to_wishlist_button');
            }
            $ti_action.trigger('click');
        })

        /**
         * Core Wishlist
         */
        $document
            .on('click','.product a.add_wishlist.la-core-wishlist',function(e){
                if(!$(this).hasClass('added')) {
                    e.preventDefault();
                    var $button     = $(this),
                        product_id = $button.data( 'product_id' ),
                        $product_image = $button.closest('.product').find('.product_item--thumbnail img:eq(0)'),
                        product_name = 'Product',
                        data = {
                            action: 'la_helpers_wishlist',
                            security: la_theme_config.security.wishlist_nonce,
                            post_id: product_id,
                            type: 'add'
                        };
                    if (!!$button.data('product_title')) {
                        product_name = $button.data('product_title');
                    }
                    if($button.closest('.product--summary').length){
                        $product_image = $button.closest('.product').find('.woocommerce-product-gallery__image img:eq(0)');
                    }

                    $.ajax({
                        type: 'POST',
                        url: la_theme_config.ajax_url,
                        data: data,
                        dataType: 'json',
                        beforeSend: function () {
                            $button.addClass('loading');
                        },
                        complete: function () {
                            $button.removeClass('loading').addClass('added');
                        },
                        success: function (response) {
                            var html = '<div class="popup-added-msg">';

                            if (response.success) {
                                if ($product_image.length){
                                    html += $('<div>').append($product_image.clone()).html();
                                }
                                html += '<div class="popup-message"><strong class="text-color-heading">'+ product_name +' </strong>' + la_theme_config.i18n.wishlist.success + '</div>';
                            }
                            else {
                                html += '<div class="popup-message">' + response.data.message + '</div>';
                            }
                            html += '<a class="button view-popup-wishlish" rel="nofollow" href="'+response.data.wishlist_url+'">' + la_theme_config.i18n.wishlist.view + '</a>';
                            html += '<a class="button popup-button-continue" rel="nofollow" href="#">' + la_theme_config.i18n.global.continue_shopping + '</a>';
                            html += '</div>';

                            LaStudio.global.ShowMessageBox(html, 'open-wishlist-msg');
                            $('.la-wishlist-count').html(response.data.count);

                            $('.add_wishlist[data-product_id="' + $button.data('product_id') + '"]').addClass('added').attr('href', response.data.wishlist_url);
                        }
                    });

                }
            })
            .on('click', '.la_wishlist_table a.la_remove_from_wishlist', function(e){
                e.preventDefault();
                var $table = $('#la_wishlist_table_wrapper');
                if( typeof $.fn.block != 'undefined' ) {
                    $table.block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                }
                $table.load( e.target.href + ' #la_wishlist_table_wrapper2', function(){
                    if( typeof $.fn.unblock != 'undefined' ) {
                        $table.stop(true).css('opacity', '1').unblock();
                    }
                } );
            });

        $document
            .on('adding_to_cart', function( e, $button, data ){
                if( $button && $button.closest('.la_wishlist_table').length ) {
                    data.la_remove_from_wishlist_after_add_to_cart = data.product_id;
                }
            })
            .on('added_to_cart', function( e, fragments, cart_hash, $button ){
                if($button && $button.closest('.la_wishlist_table').length ) {
                    var $table = $('#la_wishlist_table_wrapper');
                    $button.closest('tr').remove();
                    $table.load( window.location.href + ' #la_wishlist_table_wrapper2')
                }
            })
    }

    LaStudio.core.WooCommerceCompare = function(){
        /**
         * Support YITH Compare
         */
        $document
            .on('click', 'table.compare-list .remove a', function(e){
                e.preventDefault();
                $('.add_compare[data-product_id="' + $(this).data('product_id') + '"]', window.parent.document).removeClass('added');
            })
            .on('click','.la_com_action--compare', function(e){
                if(typeof yith_woocompare !== "undefined"){
                    e.preventDefault();
                    try{
                        lightcase.close();
                    }catch (ex){}
                    $body.trigger('yith_woocompare_open_popup', { response: LaStudio.global.addQueryArg( LaStudio.global.addQueryArg('', 'action', yith_woocompare.actionview) , 'iframe', 'true') });
                }
            })
            .on('click', '.product a.add_compare:not(.la-core-compare)', function(e){
                e.preventDefault();

                if($(this).hasClass('added')){
                    $body.trigger('yith_woocompare_open_popup', { response: LaStudio.global.addQueryArg( LaStudio.global.addQueryArg('', 'action', yith_woocompare.actionview) , 'iframe', 'true') });
                    return;
                }

                var $button     = $(this),
                    widget_list = $('.yith-woocompare-widget ul.products-list'),
                    $product_image = $button.closest('.product').find('.product_item--thumbnail img:eq(0)'),
                    data        = {
                        action: yith_woocompare.actionadd,
                        id: $button.data('product_id'),
                        context: 'frontend'
                    },
                    product_name = 'Product';
                if(!!$button.data('product_title')){
                    product_name = $button.data('product_title');
                }

                if($button.closest('.product--summary').length){
                    $product_image = $button.closest('.product').find('.woocommerce-product-gallery__image img:eq(0)');
                }

                $.ajax({
                    type: 'post',
                    url: yith_woocompare.ajaxurl.toString().replace( '%%endpoint%%', yith_woocompare.actionadd ),
                    data: data,
                    dataType: 'json',
                    beforeSend: function(){
                        $button.addClass('loading');
                    },
                    complete: function(){
                        $button.removeClass('loading').addClass('added');
                    },
                    success: function(response){
                        if($.isFunction($.fn.block) ) {
                            widget_list.unblock()
                        }
                        var html = '<div class="popup-added-msg">';
                        if ($product_image.length){
                            html += $('<div>').append($product_image.clone()).html();
                        }
                        html += '<div class="popup-message"><strong class="text-color-heading">'+ product_name +' </strong>' + la_theme_config.i18n.compare.success + '</div>';
                        html += '<a class="button la_com_action--compare" rel="nofollow" href="'+response.table_url+'">'+la_theme_config.i18n.compare.view+'</a>';
                        html += '<a class="button popup-button-continue" href="#" rel="nofollow">'+ la_theme_config.i18n.global.continue_shopping + '</a>';
                        html += '</div>';

                        LaStudio.global.ShowMessageBox(html, 'open-compare-msg');

                        $('.add_compare[data-product_id="' + $button.data('product_id') + '"]').addClass('added');

                        widget_list.unblock().html( response.widget_table );
                    }
                });
            });

        /**
         * Core Compare
         */
        $document.on('LaStudio.WooCommerceCompare.FixHeight', '.la-compare-table-items' ,function (e) {
             $('th', $(this)).each(function (idx) {
                 $('.la-compare-table-heading th').eq(idx).css( 'height', $(this).outerHeight() );
             })
        });

        $('.la-compare-table-items').trigger('LaStudio.WooCommerceCompare.FixHeight');

        $document
            .on('click', '.product a.add_compare.la-core-compare', function(e){
                if(!$(this).hasClass('added')) {
                    e.preventDefault();
                    var $button     = $(this),
                        product_id = $button.data( 'product_id' ),
                        $product_image = $button.closest('.product').find('.product_item--thumbnail img:eq(0)'),
                        product_name = 'Product',
                        data = {
                            action: 'la_helpers_compare',
                            security: la_theme_config.security.compare_nonce,
                            post_id: product_id,
                            type: 'add'
                        };
                    if (!!$button.data('product_title')) {
                        product_name = $button.data('product_title');
                    }
                    if($button.closest('.product--summary').length){
                        $product_image = $button.closest('.product').find('.woocommerce-product-gallery__image img:eq(0)');
                    }

                    $.ajax({
                        type: 'POST',
                        url: la_theme_config.ajax_url,
                        data: data,
                        dataType: 'json',
                        beforeSend: function () {
                            $button.addClass('loading');
                        },
                        complete: function () {
                            $button.removeClass('loading').addClass('added');
                        },
                        success: function (response) {
                            var html = '<div class="popup-added-msg">';

                            if (response.success) {
                                if ($product_image.length){
                                    html += $('<div>').append($product_image.clone()).html();
                                }
                                html += '<div class="popup-message"><strong class="text-color-heading">'+ product_name +' </strong>' + la_theme_config.i18n.compare.success + '</div>';
                            }
                            else {
                                html += '<div class="popup-message">' + response.data.message + '</div>';
                            }
                            html += '<a class="button view-popup-compare" rel="nofollow" href="'+response.data.compare_url+'">' + la_theme_config.i18n.compare.view + '</a>';
                            html += '<a class="button popup-button-continue" rel="nofollow" href="#">' + la_theme_config.i18n.global.continue_shopping + '</a>';
                            html += '</div>';

                            LaStudio.global.ShowMessageBox(html, 'open-compare-msg');
                            $('.la-compare-count').html(response.data.count);

                            $('.add_compare[data-product_id="' + $button.data('product_id') + '"]').addClass('added').attr('href', response.data.compare_url);
                        }
                    });

                }
            })
            .on('click', '.la_remove_from_compare', function(e){
                e.preventDefault();
                var $table = $('#la_compare_table_wrapper');
                if( typeof $.fn.block != 'undefined' ) {
                    $table.block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                }

                console.log( e.target.href);

                $table.load( e.target.href + ' #la_compare_table_wrapper2', function(){
                    if( typeof $.fn.unblock != 'undefined' ) {
                        $table.stop(true).css('opacity', '1').unblock();
                        setTimeout(function () {
                            $('.la-compare-table-items').trigger('LaStudio.WooCommerceCompare.FixHeight');
                        }, 300);
                    }
                } );
            });

    }

    LaStudio.core.AjaxShopFilter = function () {

        if( $('#la_shop_products').length == 0 || $('#la_shop_products.deactive-filters').length){
            return;
        }

        $('li.current-cat, li.current-cat-parent', $('#sidebar_primary')).each(function(){
            $(this).addClass('open');
            $('>ul', $(this)).css('display','block');
        });

        function clone_view_count() {
            return;
            var $vcount = $('.wc-toolbar-top .wc-view-count');
            if($vcount.length){
                $('#la_shop_products .woocommerce-pagination').addClass('wc-toolbar').append($vcount.clone());
            }
        }

        clone_view_count();

        function init_price_filter() {
            if ( typeof woocommerce_price_slider_params === 'undefined' ) {
                return false;
            }

            $( 'input#min_price, input#max_price' ).hide();
            $( '.price_slider, .price_label' ).show();

            var min_price = $( '.price_slider_amount #min_price' ).data( 'min' ),
                max_price = $( '.price_slider_amount #max_price' ).data( 'max' ),
                current_min_price = $( '.price_slider_amount #min_price' ).val(),
                current_max_price = $( '.price_slider_amount #max_price' ).val();

            $( '.price_slider:not(.ui-slider)' ).slider({
                range: true,
                animate: true,
                min: min_price,
                max: max_price,
                values: [ current_min_price, current_max_price ],
                create: function() {

                    $( '.price_slider_amount #min_price' ).val( current_min_price );
                    $( '.price_slider_amount #max_price' ).val( current_max_price );

                    $( document.body ).trigger( 'price_slider_create', [ current_min_price, current_max_price ] );
                },
                slide: function( event, ui ) {

                    $( 'input#min_price' ).val( ui.values[0] );
                    $( 'input#max_price' ).val( ui.values[1] );

                    $( document.body ).trigger( 'price_slider_slide', [ ui.values[0], ui.values[1] ] );
                },
                change: function( event, ui ) {

                    $( document.body ).trigger( 'price_slider_change', [ ui.values[0], ui.values[1] ] );
                }
            });
        }

        var elm_to_replace = [
            '#la_shop_products',
            '.wc-toolbar-top',
            '.la-advanced-product-filters .sidebar-inner',
            '.wc_page_description'
        ];

        var target_to_init = '#la_shop_products .la-pagination:not(.la-ajax-pagination) a, .la-advanced-product-filters-result a',
            target_to_init2 = '.woo-widget-filter a, .wc-ordering a, .wc-view-count a, .woocommerce.product-sort-by a, .woocommerce.la-price-filter-list a, .woocommerce.widget_layered_nav a, .woocommerce.widget_product_tag_cloud li a, .woocommerce.widget_product_categories a',
            target_to_init3 = '.woocommerce.widget_product_tag_cloud:not(.la_product_tag_cloud) a';

        LaStudio.global.eventManager.subscribe('LaStudio:AjaxShopFilter', function(e, url, element){

            if( $('.wc-toolbar-container').length > 0) {
                var position = $('.wc-toolbar-container').offset().top - 200;
                $htmlbody.stop().animate({
                    scrollTop: position
                }, 800 );
            }

            if ('?' == url.slice(-1)) {
                url = url.slice(0, -1);
            }
            url = url.replace(/%2C/g, ',');

            url = LaStudio.global.removeURLParameter(url,'la_doing_ajax');

            try{
                history.pushState(null, null, url);
            }catch (ex) {

            }

            LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter:before_send', [url, element]);

            if (LaStudio.utils.ajax_xhr) {
                LaStudio.utils.ajax_xhr.abort();
            }

            url = LaStudio.global.addQueryArg(url, 'la_doing_ajax', 'true');

            LaStudio.utils.ajax_xhr = $.get(url, function ( response ) {

                for ( var i = 0; i < elm_to_replace.length; i++){
                    if( $(elm_to_replace[i]).length ){
                        if( elm_to_replace[i] == '.la-advanced-product-filters .sidebar-inner'){
                            if( $(response).find(elm_to_replace[i]).length ){
                                $(elm_to_replace[i]).replaceWith( $(response).find(elm_to_replace[i]) );
                                LaStudio.core.Blog($(elm_to_replace[i]));
                                $('li.current-cat, li.current-cat-parent', $(elm_to_replace[i])).each(function(){
                                    $(this).addClass('open');
                                    $('>ul', $(this)).css('display','block');
                                });
                            }
                        }
                        else{
                            $(elm_to_replace[i]).replaceWith( $(response).find(elm_to_replace[i]) );
                        }
                    }
                }

                if( $('#sidebar_primary').length && $(response).find('#sidebar_primary').length ) {
                    $('#sidebar_primary').replaceWith($(response).find('#sidebar_primary'));
                    LaStudio.core.Blog($('#sidebar_primary'));
                    $('li.current-cat, li.current-cat-parent', $('#sidebar_primary')).each(function(){
                        $(this).addClass('open');
                        $('>ul', $(this)).css('display','block');
                    });
                }

                if( $('#section_page_header').length && $(response).find('#section_page_header').length ) {
                    $('#section_page_header').replaceWith($(response).find('#section_page_header'));
                }

                try {
                    var _view_mode = Cookies.get('skudmart_wc_catalog_view_mode');
                    $('.wc-toolbar .wc-view-toggle button[data-view_mode="'+_view_mode+'"]').trigger('click');

                }catch (e) {

                }

                $('body').trigger('lastudio-fix-ios-limit-image-resource');

                $('.la-ajax-shop-loading').removeClass('loading');

                LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter:success', [response, url, element]);

            }, 'html');
        });
        LaStudio.global.eventManager.subscribe('LaStudio:AjaxShopFilter:success', function(e, response, url, element){
            if( $('.widget.woocommerce.widget_price_filter').length ) {
                init_price_filter();
            }
            if($body.hasClass('open-advanced-shop-filter')){
                $body.removeClass('open-advanced-shop-filter');
                $('.la-advanced-product-filters').stop().slideUp('fast');
            }
            clone_view_count();
            $('body').trigger('lastudio-fix-ios-limit-image-resource').trigger( 'lastudio-lazy-images-load' ).trigger( 'jetpack-lazy-images-load' ).trigger( 'lastudio-object-fit' );
            LaStudio.core.initAll($document);
        });

        $document
            .on('click', '.btn-advanced-shop-filter', function (e) {
                e.preventDefault();
                $body.toggleClass('open-advanced-shop-filter');
                $('.la-advanced-product-filters').stop().animate({
                    height: 'toggle'
                });
            })
            .on('click', '.la-advanced-product-filters .close-advanced-product-filters', function(e){
                e.preventDefault();
                $('.btn-advanced-shop-filter').trigger('click');
            })
            .on('click', target_to_init, function(e){
                e.preventDefault();
                $('.la-ajax-shop-loading').addClass('loading');
                LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter', [$(this).attr('href'), $(this)]);
            })
            .on('click', target_to_init2, function(e){
                e.preventDefault();
                $('.la-ajax-shop-loading').addClass('loading');
                if($(this).closest('.widget_layered_nav').length){
                    $(this).parent().addClass('active');
                }
                else{
                    $(this).parent().addClass('active').siblings().removeClass('active');
                }

                $('.lasf-custom-dropdown').removeClass('is-hover');

                var _url = $(this).attr('href'),
                    _preset_from_w = LaStudio.global.getUrlParameter('la_preset'),
                    _preset_from_e = LaStudio.global.getUrlParameter('la_preset', _url);

                if(!_preset_from_e && _preset_from_w){
                    _url = LaStudio.global.addQueryArg(_url, 'la_preset', _preset_from_w);
                }

                LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter', [_url, $(this)]);
            })

            .on('click', target_to_init3, function(e){
                e.preventDefault();
                $('.la-ajax-shop-loading').addClass('loading');
                $(this).addClass('active').siblings().removeClass('active');
                var _url = $(this).attr('href'),
                    _preset_from_w = LaStudio.global.getUrlParameter('la_preset'),
                    _preset_from_e = LaStudio.global.getUrlParameter('la_preset', _url);

                if(!_preset_from_e && _preset_from_w){
                    _url = LaStudio.global.addQueryArg(_url, 'la_preset', _preset_from_w);
                }
                LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter', [_url, $(this)]);
            })
            .on('click', '.woocommerce.widget_layered_nav_filters a', function(e){
                e.preventDefault();
                $('.la-ajax-shop-loading').addClass('loading');
                LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter', [$(this).attr('href'), $(this)]);
            })
            .on('submit', '.widget_price_filter form', function(e){
                e.preventDefault();
                var $form = $(this),
                    url = $form.attr('action') + '?' + $form.serialize();
                $('.la-ajax-shop-loading').addClass('loading');
                LaStudio.global.eventManager.publish('LaStudio:AjaxShopFilter', [url, $form]);
            })
    }

    LaStudio.core.OnLoadEvent = function(){

        $body.removeClass('site-loading').addClass('body-loaded');

        LaStudio.core.HeaderSticky();

        $('.slick-slider').on('beforeChange afterChange', function( event, slick, currentSlide, nextSlide ){
            LaStudio.global.eventManager.publish('LaStudio:Component:LazyLoadImage', [ $(this) ]);
        });

        $('.force-active-object-fit').each(function () {
            $body.trigger('lastudo-prepare-object-fit', [$(this)]);
        });
    }

    LaStudio.core.OpenNewsletterPopup = function( $popup, callback ){
        lightcase.start({
            href: '#',
            maxWidth: parseInt(la_theme_config.popup.max_width),
            maxHeight: parseInt(la_theme_config.popup.max_height),
            inline: {
                width : parseInt(la_theme_config.popup.max_width),
                height : parseInt(la_theme_config.popup.max_height)
            },
            onInit : {
                foo: function() {
                    $('body.lastudio-skudmart').addClass('open-newsletter-popup');
                }
            },
            onClose : {
                qux: function() {
                    if(typeof callback === 'function'){
                        callback();
                    }
                    $('body.lastudio-skudmart').removeClass('open-newsletter-popup');
                    $('.custom-lightcase-overlay').remove();
                    $('.custom-lighcase-btn-close').remove();
                }
            },
            onFinish: {
                injectContent: function () {
                    lightcase.get('content').append('<div class="custom-lightcase-overlay"></div>');
                    lightcase.get('contentInner').children().append($popup);
                    lightcase.get('contentInner').append('<a class="custom-lighcase-btn-close" href="#"><i class="lastudioicon-e-remove"></i></a>');
                    $('.lightcase-icon-close').hide();
                    lightcase.resize();
                }
            }
        });
    }

    LaStudio.component.NewsletterPopup = function(el){
        var $popup = $(el),
            disable_on_mobile = parseInt($popup.attr('data-show-mobile') || 0),
            p_delay = parseInt($popup.attr('data-delay') || 2000),
            backtime = parseInt($popup.attr('data-back-time') || 1),
            waitfortrigger = parseInt($popup.attr('data-waitfortrigger') || 0);

        $(document).on('click', '.btn-close-newsletter-popup', function(e){
            e.preventDefault();
            lightcase.close();
        });

        if(waitfortrigger == 1){
            $(document).on('click touchend', '.elm-trigger-open-newsletter', function(e){
                e.preventDefault();
                LaStudio.core.OpenNewsletterPopup($popup);
            })
        }

        return {
            init : function(){
                if(waitfortrigger != 1){
                    if($(window).width() < 767){
                        if(disable_on_mobile){
                            return;
                        }
                    }
                    try{
                        if(Cookies.get('skudmart_dont_display_popup') == 'yes'){
                            return;
                        }
                    }catch (ex){ console.log(ex); }

                    $(window).load(function(){
                        setTimeout(function(){
                            LaStudio.core.OpenNewsletterPopup($popup, function(){
                                if($('.cbo-dont-show-popup', $popup).length && $('.cbo-dont-show-popup', $popup).is(':checked')){
                                    try {
                                        Cookies.set('skudmart_dont_display_popup', 'yes', { expires: backtime, path: '/' });
                                    } catch (ex){}
                                }
                            })
                        }, p_delay)
                    })
                }
            }
        }

    };

})(jQuery);

// Kickoff all event
(function($) {
    'use strict';

    $(function(){

        $(document).trigger('LaStudio:Document:BeforeRunScript');

        LaStudio.core.SitePreload();
        LaStudio.core.MegaMenu();

        $( '.la-ajax-searchform' ).each(function () {
            LaStudio.core.InstanceSearch($(this));
        });

        LaStudio.core.initAll($(document));

        LaStudio.core.ElementClickEvent();

        LaStudio.core.Blog();

        /**
         * WooCommerce
         */
        LaStudio.core.WooCommerce();
        LaStudio.core.WooCommerceQuickView();
        LaStudio.core.WooCommerceWishlist();
        LaStudio.core.WooCommerceCompare();
        LaStudio.core.AjaxShopFilter();

        $(window).on('load', LaStudio.core.OnLoadEvent);

    });

})(jQuery);