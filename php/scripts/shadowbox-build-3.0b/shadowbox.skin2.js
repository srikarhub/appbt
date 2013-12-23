Shadowbox.skin = function(){

    var S = Shadowbox,
        U = S.util,

//Wizzud...
		_closeTop = {hide:0, show:0},

    /**
     * Keeps track of whether or not the overlay is activated.
     *
     * @var     Boolean
     * @private
     */
    overlay_on = false,

    /**
     * Id's of elements that need transparent PNG support in IE6.
     *
     * @var     Array
     * @private
     */
    png = [
        'sb-nav-close',
        'sb-nav-next',
        'sb-nav-play',
        'sb-nav-pause',
        'sb-nav-previous'
    ];

    /**
     * Sets the top of the container element. This is only necessary in IE6
     * where the container uses absolute positioning instead of fixed.
     *
     * @return  void
     * @private
     */
    function fixTop(){
        U.get('sb-container').style.top = document.documentElement.scrollTop + 'px';
    }

    /**
     * Toggles the visibility of #sb-container and sets its size (if on
     * IE6). Also toggles the visibility of elements (<select>, <object>, and
     * <embed>) that are troublesome for semi-transparent modal overlays. IE has
     * problems with <select> elements, while Firefox has trouble with
     * <object>s.
     *
     * @param   Function    cb      A callback to call after toggling on, absent
     *                              when toggling off
     * @return  void
     * @private
     */
    function toggleVisible(cb){
        var so = U.get('sb-overlay'),
            sc = U.get('sb-container'),
            sb = U.get('sb-wrapper');

        if(cb){
            if(S.client.isIE6){
                // fix container top before showing
                fixTop();
                S.lib.addEvent(window, 'scroll', fixTop);
            }
            if(S.options.showOverlay){
                overlay_on = true;

                // set overlay color/opacity
                so.style.backgroundColor = S.options.overlayColor;
                U.setOpacity(so, 0);
                if(!S.options.modal) S.lib.addEvent(so, 'click', S.close);

                sb.style.display = 'none'; // cleared in onLoad
            }
            sc.style.visibility = 'visible';
            if(overlay_on){
                // fade in effect
                var op = parseFloat(S.options.overlayOpacity);
                U.animate(so, 'opacity', op, S.options.fadeDuration, cb);
            }else
                cb();
        }else{
            if(S.client.isIE6)
                S.lib.removeEvent(window, 'scroll', fixTop);
            S.lib.removeEvent(so, 'click', S.close);
            if(overlay_on){
                // fade out effect
                sb.style.display = 'none';
                U.animate(so, 'opacity', 0, S.options.fadeDuration, function(){
                    // the following is commented because it causes the overlay to
                    // be hidden on consecutive activations in IE8, even though we
                    // set the visibility to "visible" when we reactivate
                    //sc.style.visibility = 'hidden';
                    sc.style.display = '';
                    sb.style.display = '';
                    U.clearOpacity(so);
                });
            }else
                sc.style.visibility = 'hidden';
        }
    }

    /**
     * Toggles the display of the nav control with the given id on and off.
     *
     * @param   String      id      The id of the navigation control
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @return  void
     * @private
     */
    function toggleNav(id, on){
        var el = U.get('sb-nav-' + id);
        if(el) el.style.display = on ? '' : 'none';
    }

    /**
     * Toggles the visibility of the "loading" layer.
     *
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @param   Function    cb      The callback function to call when toggling
     *                              completes
     * @return  void
     * @private
     */
    function toggleLoading(on, cb){
        var ld = U.get('sb-loading'),
            p = S.getCurrent().player,
            anim = (p == 'img' || p == 'html'); // fade on images & html

        if(on){
            function fn(){
                U.clearOpacity(ld);
                if(cb) cb();
            }

            U.setOpacity(ld, 0);
            ld.style.display = '';

            if(anim)
                U.animate(ld, 'opacity', 1, S.options.fadeDuration, fn);
            else
                fn();
        }else{
            function fn(){
                ld.style.display = 'none';
                U.clearOpacity(ld);
                if(cb) cb();
            }

            if(anim)
                U.animate(ld, 'opacity', 0, S.options.fadeDuration, fn);
            else
                fn();
        }
    }

    /**
     * Builds the content for the title and information bars.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              bars are built
     * @return  void
     * @private
     */
    function buildBars(cb){
        var obj = S.getCurrent();

        // build the title, if present
        U.get('sb-title-inner').innerHTML = obj.title || '';

        // build the nav
        var c, n, pl, pa, p;
        if(S.options.displayNav){
            c = true;
            // next & previous links
            var len = S.gallery.length;
            if(len > 1){
                if(S.options.continuous)
                    n = p = true; // show both
                else{
                    n = (len - 1) > S.current; // not last in gallery, show next
                    p = S.current > 0; // not first in gallery, show previous
                }
            }
            // in a slideshow?
            if(S.options.slideshowDelay > 0 && S.hasNext()){
                pa = !S.isPaused();
                pl = !pa;
            }
        }else{
            c = n = pl = pa = p = false;
        }
        toggleNav('close', c);
        toggleNav('next', n);
        toggleNav('play', pl);
        toggleNav('pause', pa);
        toggleNav('previous', p);

        // build the counter
        var c = '';
        if(S.options.displayCounter && S.gallery.length > 1){
            var count = S.getCounter();
            if(typeof count == 'string') // default
                c = count;
            else{
                U.each(count, function(i){
                    c += '<a onclick="Shadowbox.change(' + i + ');"'
                    if(i == S.current) c += ' class="sb-counter-current"';
                    c += '>' + (i + 1) + '</a>';
                });
            }
        }
        U.get('sb-counter').innerHTML = c;

        cb();
    }

    /**
     * Hides the title and info bars.
     *
     * @param   Boolean     anim    True to animate the transition
     * @param   Function    cb      A callback function to execute after the
     *                              animation completes
     * @return  void
     * @private
     */
    function hideBars(anim, cb){
        var sw = U.get('sb-wrapper'),
            st = U.get('sb-title'),
            si = U.get('sb-info'),
            ti = U.get('sb-title-inner'),
            ii = U.get('sb-info-inner'),
            t = parseInt(S.lib.getStyle(ti, 'height')) || 0,
            b = parseInt(S.lib.getStyle(ii, 'height')) || 0;

        function fn(){
            // hide bars here in case of overflow, build after hidden
            ti.style.visibility = ii.style.visibility = 'hidden';
            buildBars(cb);
        }

//Wizzud...
				if(!_closeTop.hide){
					_closeTop.hide = t;
					_closeTop.show = t - parseInt(S.lib.getStyle(U.get('sb-nav-close'), 'height'), 10);
				}

        if(anim){
//Wizzud...
						U.animate(U.get('sb-nav-close'), 'top', _closeTop.hide, 0.35);
            U.animate(st, 'height', 0, 0.35);
            U.animate(si, 'height', 0, 0.35);
            U.animate(sw, 'paddingTop', t, 0.35);
            U.animate(sw, 'paddingBottom', b, 0.35, fn);
        }else{
//Wizzud...
						U.get('sb-nav-close').style.top = _closeTop.hide+'px';
            st.style.height = si.style.height = '0px';
            sw.style.paddingTop = t + 'px';
            sw.style.paddingBottom = b + 'px';
            fn();
        }
    }

    /**
     * Shows the title and info bars.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              animation completes
     * @return  void
     * @private
     */
    function showBars(cb){
        var sw = U.get('sb-wrapper'),
            st = U.get('sb-title'),
            si = U.get('sb-info'),
            ti = U.get('sb-title-inner'),
            ii = U.get('sb-info-inner'),
            t = parseInt(S.lib.getStyle(ti, 'height')) || 0,
            b = parseInt(S.lib.getStyle(ii, 'height')) || 0;

        // clear visibility before animating into view
        ti.style.visibility = ii.style.visibility = '';

//Wizzud...
				U.animate(U.get('sb-nav-close'), 'top', _closeTop.show, 0.35);

        // show title?
        if(ti.innerHTML != ''){
            U.animate(st, 'height', t, 0.35);
            U.animate(sw, 'paddingTop', 0, 0.35);
        }
        U.animate(si, 'height', b, 0.35);
        U.animate(sw, 'paddingBottom', 0, 0.35, cb);
    }

    /**
     * Adjusts the height of #sb-body and centers #sb-wrapper vertically
     * in the viewport.
     *
     * @param   Number      height      The height to use for #sb-body
     * @param   Number      top         The top to use for #sb-wrapper
     * @param   Boolean     anim        True to animate the transition
     * @param   Function    cb          A callback to use when the animation
     *                                  completes
     * @return  void
     * @private
     */
    function adjustHeight(height, top, anim, cb){
        var sb = U.get('sb-body'),
            s = U.get('sb-wrapper'),
            h = parseInt(height),
            t = parseInt(top);

        if(anim){
            U.animate(sb, 'height', h, S.options.resizeDuration);
            U.animate(s, 'top', t, S.options.resizeDuration, cb);
        }else{
            sb.style.height = h + 'px';
            s.style.top = t + 'px';
            if(cb) cb();
        }
    }

    /**
     * Adjusts the width and left of #sb-wrapper.
     *
     * @param   Number      width       The width to use for #sb-wrapper
     * @param   Number      left        The left to use for #sb-wrapper
     * @param   Boolean     anim        True to animate the transition
     * @param   Function    cb          A callback to use when the animation
     *                                  completes
     * @return  void
     * @private
     */
    function adjustWidth(width, left, anim, cb){
        var s = U.get('sb-wrapper'),
            w = parseInt(width),
            l = parseInt(left);

        if(anim){
            U.animate(s, 'width', w, S.options.resizeDuration);
            U.animate(s, 'left', l, S.options.resizeDuration, cb);
        }else{
            s.style.width = w + 'px';
            s.style.left = l + 'px';
            if(cb) cb();
        }
    }

    /**
     * Resizes Shadowbox to the appropriate height and width for the current
     * content.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              resize completes
     * @return  void
     * @private
     */
    function resizeContent(cb){
        var c = S.content;
        if(!c) return;

        // set new dimensions
        var d = setDimensions(c.height, c.width, c.resizable);

        switch(S.options.animSequence){
            case 'hw':
                adjustHeight(d.inner_h, d.top, true, function(){
                    adjustWidth(d.width, d.left, true, cb);
                });
            break;
            case 'wh':
                adjustWidth(d.width, d.left, true, function(){
                    adjustHeight(d.inner_h, d.top, true, cb);
                });
            break;
            default: // sync
                adjustWidth(d.width, d.left, true);
                adjustHeight(d.inner_h, d.top, true, cb);
        }
    }

    /**
     * Calculates the dimensions for Shadowbox, taking into account the borders
     * and surrounding elements of #sb-body.
     *
     * @param   Number      height      The content height
     * @param   Number      width       The content width
     * @param   Boolean     resizable   True if the content is able to be
     *                                  resized. Defaults to false
     * @return  Object                  The new dimensions object
     * @private
     */
    function setDimensions(height, width, resizable){
        var sbi = U.get('sb-body-inner')
            sw = U.get('sb-wrapper'),
            so = U.get('sb-overlay'),
            tb = sw.offsetHeight - sbi.offsetHeight,
            lr = sw.offsetWidth - sbi.offsetWidth,
            max_h = so.offsetHeight, // measure overlay to get viewport size for IE6
            max_w = so.offsetWidth;

        return S.setDimensions(height, width, max_h, max_w, tb, lr, resizable);
    }

    return {

        /**
         * The HTML markup to use.
         *
         * @var     String
         * @public
         */
        markup: '<div id="sb-container">' +
                    '<div id="sb-overlay"></div>' +
                    '<div id="sb-wrapper">' +
                        '<div id="sb-title">' +
                            '<div id="sb-title-inner"></div>' +
                        '</div>' +
                        '<a id="sb-nav-close" title="{close}" onclick="Shadowbox.close()"></a>' +
                        '<div id="sb-body">' +
                            '<div id="sb-body-inner"></div>' +
                            '<div id="sb-loading">' +
                                '<a onclick="Shadowbox.close()">{cancel}</a>' +
                            '</div>' +
                        '</div>' +
                        '<div id="sb-info">' +
                            '<div id="sb-info-inner">' +
                                '<div id="sb-counter"></div>' +
                                '<div id="sb-nav">' +
                                    '<a id="sb-nav-next" title="{next}" onclick="Shadowbox.next()"></a>' +
                                    '<a id="sb-nav-play" title="{play}" onclick="Shadowbox.play()"></a>' +
                                    '<a id="sb-nav-pause" title="{pause}" onclick="Shadowbox.pause()"></a>' +
                                    '<a id="sb-nav-previous" title="{previous}" onclick="Shadowbox.previous()"></a>' +
                                '</div>' +
                                '<div style="clear:both"></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>',

        options: {

            /**
             * Determines the sequence of the resizing animations. A value of
             * "hw" will resize first the height, then the width. Likewise, "wh"
             * will resize the width first, then the height. The default is
             * "sync" and will resize both simultaneously.
             *
             * @var     String
             * @public
             */
            animSequence: 'sync'

        },

        /**
         * Initialization function. Called immediately after this skin's markup
         * has been appended to the document with all of the necessary language
         * replacements done.
         *
         * @return  void
         * @public
         */
        init: function(){
            // several fixes for IE6
            if(S.client.isIE6){
                // trigger hasLayout on sb-body
                U.get('sb-body').style.zoom = 1;

                // support transparent PNG's via AlphaImageLoader
                var el, m, re = /url\("(.*\.png)"\)/;
                U.each(png, function(id){
                    el = U.get(id);
                    if(el){
                        m = S.lib.getStyle(el, 'backgroundImage').match(re);
                        if(m){
                            el.style.backgroundImage = 'none';
                            el.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,src=' +
                                m[1] + ',sizingMethod=scale);';
                        }
                    }
                });
            }
        },

        /**
         * Gets the element that content should be appended to.
         *
         * @return  HTMLElement     The body element
         * @public
         */
        bodyEl: function(){
            return U.get('sb-body-inner');
        },

        /**
         * Called when Shadowbox opens from an inactive state.
         *
         * @param   Number      h       The initial height to use
         * @param   Number      w       The initial width to use
         * @param   Function    cb      The function to call when finished
         * @return  void
         * @public
         */
        onOpen: function(h, w, cb){
            U.get('sb-container').style.display = 'block';

            var d = setDimensions(h, w);
            adjustHeight(d.inner_h, d.top, false);
            adjustWidth(d.width, d.left, false);
            toggleVisible(cb);
        },

        /**
         * Called when a new piece of content is being loaded.
         *
         * @param   mixed       content     The content object
         * @param   Boolean     change      True if the content is changing
         *                                  from some previous content
         * @param   Function    cb          A callback that should be fired when
         *                                  this function is finished
         * @return  void
         * @public
         */
        onLoad: function(content, change, cb){
            toggleLoading(true);

            hideBars(change, function(){ // if changing, animate the bars transition
                if(!content) return;

                // if opening, clear #sb-wrapper display
                if(!change) U.get('sb-wrapper').style.display = '';

                cb();
            });
        },

        /**
         * Called when the content is ready to be loaded (e.g. when the image
         * has finished loading). Should resize the content box and make any
         * other necessary adjustments.
         *
         * @param   Function    cb          A callback that should be fired when
         *                                  this function is finished
         * @return  void
         * @public
         */
        onReady: function(cb){
            resizeContent(function(){
                showBars(cb);
            });
        },

        /**
         * Called when the content is loaded into the box and is ready to be
         * displayed.
         *
         * @param   Function    cb          A callback that should be fired when
         *                                  this function is finished
         * @return  void
         * @public
         */
        onFinish: function(cb){
            toggleLoading(false, cb);
        },

        /**
         * Called when Shadowbox is closed.
         *
         * @return  void
         * @public
         */
        onClose: function(){
            toggleVisible(false);
        },

        /**
         * Called in Shadowbox.play().
         *
         * @return  void
         * @public
         */
        onPlay: function(){
            toggleNav('play', false);
            toggleNav('pause', true);
        },

        /**
         * Called in Shadowbox.pause().
         *
         * @return  void
         * @public
         */
        onPause: function(){
            toggleNav('pause', false);
            toggleNav('play', true);
        },

        /**
         * Called when the window is resized.
         *
         * @return  void
         * @public
         */
        onWindowResize: function(){
            var c = S.content;
            if(!c) return;

            // set new dimensions
            var d = setDimensions(c.height, c.width, c.resizable);

            adjustWidth(d.width, d.left, false);
            adjustHeight(d.inner_h, d.top, false);

            var el = U.get(S.contentId());
            if(el){
                // resize resizable content when in resize mode
                if(c.resizable && S.options.handleOversize == 'resize'){
                    el.height = d.resize_h;
                    el.width = d.resize_w;
                }
            }
        }

    };

}();