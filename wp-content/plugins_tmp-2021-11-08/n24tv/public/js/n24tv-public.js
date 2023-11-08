/**
 * hoverIntent plugin
 */
/*!
 * hoverIntent v1.8.1 // 2014.08.11 // jQuery v1.9.1+
 * http://briancherne.github.io/jquery-hoverIntent/
 *
 * You may use hoverIntent under the terms of the MIT license. Basically that
 * means you are free to use hoverIntent as long as this header is left intact.
 * Copyright 2007, 2014 Brian Cherne
 */

/* hoverIntent is similar to jQuery's built-in "hover" method except that
 * instead of firing the handlerIn function immediately, hoverIntent checks
 * to see if the user's mouse has slowed down (beneath the sensitivity
 * threshold) before firing the event. The handlerOut function is only
 * called after a matching handlerIn.
 *
 * // basic usage ... just like .hover()
 * .hoverIntent( handlerIn, handlerOut )
 * .hoverIntent( handlerInOut )
 *
 * // basic usage ... with event delegation!
 * .hoverIntent( handlerIn, handlerOut, selector )
 * .hoverIntent( handlerInOut, selector )
 *
 * // using a basic configuration object
 * .hoverIntent( config )
 *
 * @param  handlerIn   function OR configuration object
 * @param  handlerOut  function OR selector for delegation OR undefined
 * @param  selector    selector OR undefined
 * @author Brian Cherne <brian(at)cherne(dot)net>
 */

;(function(factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else if (jQuery && !jQuery.fn.hoverIntent) {
        factory(jQuery);
    }
})(function($) {
    'use strict';

    // default configuration values
    var _cfg = {
        interval: 100,
        sensitivity: 6,
        timeout: 0
    };

    // counter used to generate an ID for each instance
    var INSTANCE_COUNT = 0;

    // current X and Y position of mouse, updated during mousemove tracking (shared across instances)
    var cX, cY;

    // saves the current pointer position coordinates based on the given mousemove event
    var track = function(ev) {
        cX = ev.pageX;
        cY = ev.pageY;
    };

    // compares current and previous mouse positions
    var compare = function(ev,$el,s,cfg) {
        // compare mouse positions to see if pointer has slowed enough to trigger `over` function
        if ( Math.sqrt( (s.pX-cX)*(s.pX-cX) + (s.pY-cY)*(s.pY-cY) ) < cfg.sensitivity ) {
            $el.off(s.event,track);
            delete s.timeoutId;
            // set hoverIntent state as active for this element (permits `out` handler to trigger)
            s.isActive = true;
            // overwrite old mouseenter event coordinates with most recent pointer position
            ev.pageX = cX; ev.pageY = cY;
            // clear coordinate data from state object
            delete s.pX; delete s.pY;
            return cfg.over.apply($el[0],[ev]);
        } else {
            // set previous coordinates for next comparison
            s.pX = cX; s.pY = cY;
            // use self-calling timeout, guarantees intervals are spaced out properly (avoids JavaScript timer bugs)
            s.timeoutId = setTimeout( function(){compare(ev, $el, s, cfg);} , cfg.interval );
        }
    };

    // triggers given `out` function at configured `timeout` after a mouseleave and clears state
    var delay = function(ev,$el,s,out) {
        delete $el.data('hoverIntent')[s.id];
        return out.apply($el[0],[ev]);
    };

    $.fn.hoverIntent = function(handlerIn,handlerOut,selector) {
        // instance ID, used as a key to store and retrieve state information on an element
        var instanceId = INSTANCE_COUNT++;

        // extend the default configuration and parse parameters
        var cfg = $.extend({}, _cfg);
        if ( $.isPlainObject(handlerIn) ) {
            cfg = $.extend(cfg, handlerIn);
            if ( !$.isFunction(cfg.out) ) {
                cfg.out = cfg.over;
            }
        } else if ( $.isFunction(handlerOut) ) {
            cfg = $.extend(cfg, { over: handlerIn, out: handlerOut, selector: selector } );
        } else {
            cfg = $.extend(cfg, { over: handlerIn, out: handlerIn, selector: handlerOut } );
        }

        // A private function for handling mouse 'hovering'
        var handleHover = function(e) {
            // cloned event to pass to handlers (copy required for event object to be passed in IE)
            var ev = $.extend({},e);

            // the current target of the mouse event, wrapped in a jQuery object
            var $el = $(this);

            // read hoverIntent data from element (or initialize if not present)
            var hoverIntentData = $el.data('hoverIntent');
            if (!hoverIntentData) { $el.data('hoverIntent', (hoverIntentData = {})); }

            // read per-instance state from element (or initialize if not present)
            var state = hoverIntentData[instanceId];
            if (!state) { hoverIntentData[instanceId] = state = { id: instanceId }; }

            // state properties:
            // id = instance ID, used to clean up data
            // timeoutId = timeout ID, reused for tracking mouse position and delaying "out" handler
            // isActive = plugin state, true after `over` is called just until `out` is called
            // pX, pY = previously-measured pointer coordinates, updated at each polling interval
            // event = string representing the namespaced event used for mouse tracking

            // clear any existing timeout
            if (state.timeoutId) { state.timeoutId = clearTimeout(state.timeoutId); }

            // namespaced event used to register and unregister mousemove tracking
            var mousemove = state.event = 'mousemove.hoverIntent.hoverIntent'+instanceId;

            // handle the event, based on its type
            if (e.type === 'mouseenter') {
                // do nothing if already active
                if (state.isActive) { return; }
                // set "previous" X and Y position based on initial entry point
                state.pX = ev.pageX; state.pY = ev.pageY;
                // update "current" X and Y position based on mousemove
                $el.off(mousemove,track).on(mousemove,track);
                // start polling interval (self-calling timeout) to compare mouse coordinates over time
                state.timeoutId = setTimeout( function(){compare(ev,$el,state,cfg);} , cfg.interval );
            } else { // "mouseleave"+
                // do nothing if not already active
                if (!state.isActive) { return; }
                // unbind expensive mousemove event
                $el.off(mousemove,track);
                // if hoverIntent state is true, then call the mouseOut function after the specified delay
                state.timeoutId = setTimeout( function(){delay(ev,$el,state,cfg.out);} , cfg.timeout );
            }
        };

        // listen for mouseenter and mouseleave
        return this.on({'mouseenter.hoverIntent':handleHover,'mouseleave.hoverIntent':handleHover}, cfg.selector);
    };
});

/**
 * End hoverIntent 
 */
(function( $ ) {
    'use strict';

    /**
     * Helpers
     */
    function get_cookie(name){
        var re = new RegExp(name + "=([^;]+)");
        var value = re.exec(document.cookie);
        return (value != null) ? unescape(value[1]) : null;
    }

    /**
     * Check if user was notified about cookie policy
     */
    function did_view_cookie_policy(){
        return (get_cookie('viewed_cookie_policy') == 'yes');
    }


    /**
     * UI functions
     */
    // setup masonry
    function masonry(){
        var $grid = $('.masonry-grid').masonry({
          itemSelector: '.masonry-grid-item',
          columnWidth: '.masonry-grid-sizer',
          percentPosition: true,
          gutter: 0,
          isInitLayout: false
        });

        $grid.masonry( 'on', 'layoutComplete', function() {
            $('#n24tv-masonry').css('visibility', 'visible');
        });

        $grid.masonry();

    }

    function navbar_submenu_setup(submenu){

        var categories = submenu.find('a.list-group-item');

        if (categories.length > 0){
            var first = categories.first();
            var posts_id;
            var active_posts;
            first.addClass('active');

            posts_id = 'n24tv-submenu-posts-' + first.data('id');
            active_posts = submenu.find('.' + posts_id);
            active_posts.fadeIn(200);

            submenu.find('a.list-group-item').hoverIntent(
                {
                    sensitivity: 3, 
                    interval: 100,
                    timeout: 0,
                    over: function(){
                        var category = $(this);
                        categories.removeClass('active');
                        category.addClass('active');
                        active_posts.css('display', 'none');
                        //submenu.find('.n24tv-submenu-posts').fadeOut();
                        posts_id = 'n24tv-submenu-posts-' + category.data('id');
                        active_posts = submenu.find('.' + posts_id);
                        active_posts.fadeIn(300);
                    },
                    out: function(){

                    }
                }
            );
        }
        else {
            submenu.find('.n24tv-submenu-posts').fadeIn(200);
        }
    }

    function navbar(){
        var lis = $('#n24tv-navbar li.menu-item-object-category');

        lis.hoverIntent({
            sensitivity: 3,     // number = sensitivity threshold (must be 1 or higher)
            interval: 200,      // number = milliseconds for onMouseOver polling interval
            timeout: 100,       // number = milliseconds delay before onMouseOut
            over: function(){
                var width = (window.innerWidth || document.body.clientWidth);
                if (width < 768) return; // don't do it on XS displays
                var children = $(this).children('ul.n24tv-submenu');
                if (children.length == 0){
                    var mId = 'n24tv_submenu_' + $(this).children('a').text().toLowerCase().replace(/[^0-9a-z]+/g, '_');
                    var mEl = $('#' + mId);
                    if (mEl.length > 0){
                        var submenu = mEl;
                        //navbar_submenu_setup(mEl);
                        $(this).append(mEl);
                        submenu.fadeIn(200, function(){
                            navbar_submenu_setup(mEl);
                        });
                    }
                }
                else {
                    var submenu = $(this).children('ul.n24tv-submenu');
                    submenu.fadeIn(200);
                }
            },
            out: function(){
                $(this).children('ul.n24tv-submenu').fadeOut(200);
            }
        }
        );

    
        navbar_search();
    }

    function navbar_search(){
        var li = $('#n24tv-navbar li:last-child');

        li.hoverIntent({
            sensitivity: 3, 
            interval: 100,
            timeout: 100,
            over: function(){
                var children = $(this).children('ul.n24tv-submenu');
                if (children.length == 0){
                    var mId = 'n24tv_submenu_search';
                    var mEl = $('#' + mId);
                    if (mEl.length > 0){
                        console.log('form: ', mEl.children('form'));
                        mEl.find('form').submit(function(){
                            if ($(this).find('#searchInput').val().trim().length == 0){
                                event.preventDefault();
                            }
                        });
                        $(this).append(mEl);
                    }
                }
                var children = $(this).children('ul.n24tv-submenu');
                children.find('#searchInput').focus(); 
                children.fadeIn(200);
            },
            out: function(){
                $(this).children('ul.n24tv-submenu').fadeOut(200);
            }
        });
    }

    // post page view 
    function post_page_view(){
        // check if plugin is active and AJAX is defined
        if (n24tv_ajax && n24tv_ajax.url && n24tv_ajax.nonce){
            if (n24tv_ajax.id > 0){
                /**
                 * Send of the n24tv_page_view action
                 */
                $.post(
                    n24tv_ajax.url, 
                    { 
                        action: 'n24tv_post_view',
                        id: n24tv_ajax.id,
                        _ajax_nonce: n24tv_ajax.nonce
                    },
                    function(data){
                        var response = $.parseJSON(data);
                        if (response.nonce){
                            n24tv_ajax.none = response.nonce;
                        }
                    }
                );
            }
        }
    }

    // setup sidebar affix
    function sidebar_affix(){
        if ($('#n24tv-content').outerHeight(true) > $('#n24tv-sidebar').outerHeight(true)){
            $('#n24tv-sidebar-affix').affix({
                offset: {
                    top: function(){
                        var isAd = $('#n24tv-sidebar .n24tv-ad').first().outerHeight(true);
                        /* isAd height - n24tv-sidebar-item padding */
                        return (this.top = $('#n24tv-main').position().top + isAd - 40)
                    },
                    bottom: function(){
                        return (this.bottom = $('#n24tv-footer').outerHeight(true))
                    }
                }
            });
        }
    }

    function cookie_policy_button(){
        var el = $('#cookie_action_close_header');
        if (el.length > 0){
            el.on('click', function(){
                location.reload();
            });
        }
    }

    $(function(){
        
        // attach to cookie policy accept button
        cookie_policy_button();

        // load masonry
        masonry();

        navbar();

        // finally, register page view
        post_page_view();

        // what to run after all the images were loaded
        $(window).load(function(){  
            sidebar_affix();
        });
    })

})( jQuery );
