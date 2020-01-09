(function ($) {
    "use strict";

    var LaStudio = window.LaStudio || {};

    LaStudio.component = window.LaStudio.component || {};

    var $document = $(document),
        $window = $(window),
        $body = $('body');

    // Initialize global variable

    function clone_widget() {
        var $header_builder = $('#lastudio-header-builder');
        var $element_placeholders = $('.lahb-element--placeholder', $header_builder);
        var $element2_placeholders = $('.lahb-element--placeholder2', $header_builder);


        //Move Header Vertical element keep stylesheet;
        if($('.lahb-vcom', $header_builder).length){
            $('<div/>', {
                "class": 'lahb-screen-view lahb-desktop-view lahb-varea'
            }).appendTo($header_builder);
            $('.lahb-vcom', $header_builder).appendTo($('.lahb-varea'));
        }
        $('.lahb-varea .lahb-vertical .lahb-content-wrap').append('<div class="lahb-voverlay"></div>');

        $element_placeholders.each(function () {
            var _elmID = $(this).data('element-id');
            var $target_elm = $('.lahb-element[data-element-id="'+_elmID+'"]:not(.lahb-element--placeholder)', $header_builder).first();
            var $elm_clone = $target_elm.clone();
            $elm_clone.removeAttr('itemscope').removeAttr('itemtype');
            $elm_clone.find('.la-ajax-searchform').removeClass('has-init');
            $elm_clone.find('.lahb-element--dontcopy').remove();

            //do not copy primary menu on mobile panel
            if($(this).closest('.lahb-mobiles-view').length && $elm_clone.hasClass('lahb-nav-wrap') && $('.lahb-element.lahb-element--placeholder2[data-element2-id="'+_elmID+'"]', $header_builder).length){
                $(this).remove();
            }
            else{
                $(this).replaceWith($elm_clone);
            }
        });

        // Copy icon of primary for mobile panel
        $element2_placeholders.each(function () {
            var _elmID = $(this).data('element2-id');
            var $target_elm = $('.lahb-element[data-element2-id="'+_elmID+'"]:not(.lahb-element--placeholder2)', $header_builder).first();
            var $elm_clone = $target_elm.clone();
            $(this).replaceWith($elm_clone);
        });
        // Remove the target when the copy is complete
        //$('.lahb-desktop-view .lahb-element[data-element2-id]', $header_builder).remove();
    }

    $(document).on('LaStudio:Document:BeforeRunScript', function (e) {
        clone_widget();
    });

    var HeaderBuilder = {

        init: function(){

            var $header_builder = $('#lastudio-header-builder');

            // Navigation Current Menu
            $('.menu li.current-menu-item, .menu li.current_page_item, #side-nav li.current_page_item, .menu li.current-menu-ancestor, .menu li ul li ul li.current-menu-item , .hamburger-nav li.current-menu-item, .hamburger-nav li.current_page_item, .hamburger-nav li.current-menu-ancestor, .hamburger-nav li ul li ul li.current-menu-item, .full-menu li.current-menu-item, .full-menu li.current_page_item, .full-menu li.current-menu-ancestor, .full-menu li ul li ul li.current-menu-item ').addClass('current');
            $('.menu li ul li:has(ul)').addClass('submenu');


            // Social modal
            var header_social = $('.header-social-modal-wrap').html();
            $('.header-social-modal-wrap').remove();
            $('.main-slide-toggle').append(header_social);

            // Search modal Type 2
            var header_search_type2 = $('.header-search-modal-wrap').html();
            $('.header-search-modal-wrap').remove();
            $('.main-slide-toggle').append(header_search_type2);

            // Search Full
            var $header_search_typefull = $('.header-search-full-wrap').first();

            if($header_search_typefull.length){
                $('.searchform-fly > p').replaceWith($header_search_typefull.find('.searchform-fly-text'));
                $('.searchform-fly > .search-form').replaceWith($header_search_typefull.find('.search-form'));
                $('.header-search-full-wrap').remove();
                $('.searchform-fly-overlay').removeClass('has-init');
            }

            // Moving Hamburger
            $('.la-hamburger-wrap').each(function () {
                $(this).appendTo($body);
            });

            // Social dropdown
            $('.lahb-social .js-social_trigger_dropdown').on('click', function (e) {
                e.preventDefault();
                $(this).siblings('.header-social-dropdown-wrap').fadeToggle('fast');
            });
            $('.header-social-dropdown-wrap a').on('click', function (e) {
                $('.header-social-dropdown-wrap').css({
                    display: 'none'
                });
            });

            // Social Toggles
            $('.lahb-social .js-social_trigger_slide').on('click', function (e) {
                e.preventDefault();
                if( $header_builder.find('.la-header-social').hasClass('opened') ) {
                    $header_builder.find('.main-slide-toggle').slideUp('opened');
                    $header_builder.find('.la-header-social').removeClass('opened');
                }
                else{
                    $header_builder.find('.main-slide-toggle').slideDown(240);
                    $header_builder.find('#header-search-modal').slideUp(240);
                    $header_builder.find('#header-social-modal').slideDown(240);
                    $header_builder.find('.la-header-social').addClass('opened');
                    $header_builder.find('.la-header-search').removeClass('opened');
                }
            });

            $document.on('click', function (e) {
                if( $(e.target).hasClass('js-social_trigger_slide')){
                    return;
                }
                if ($header_builder.find('.la-header-social').hasClass('opened')) {
                    $header_builder.find('.main-slide-toggle').slideUp('opened');
                    $header_builder.find('.la-header-social').removeClass('opened');
                }
            });

            // Search full

            $('.lahb-cart > a').on('click', function (e) {
                if(!$(this).closest('.lahb-cart').hasClass('force-display-on-mobile')){
                    if($window.width() > 767){
                        e.preventDefault();
                        $('body').toggleClass('open-cart-aside');
                    }
                }
                else{
                    e.preventDefault();
                    $('body').toggleClass('open-cart-aside');
                }
            });

            $('.lahb-search.lahb-header-full > a').on('click', function (e) {
                e.preventDefault();
                $('body').addClass('open-search-form');
                setTimeout(function(){
                    $('.searchform-fly .search-field').focus();
                }, 600);
            });

            // Search Toggles
            $('.lahb-search .js-search_trigger_slide').on('click', function (e) {

                if ($header_builder.find('.la-header-search').hasClass('opened')) {
                    $header_builder.find('.main-slide-toggle').slideUp('opened');
                    $header_builder.find('.la-header-search').removeClass('opened');
                }
                else {
                    $header_builder.find('.main-slide-toggle').slideDown(240);
                    $header_builder.find('#header-social-modal').slideUp(240);
                    $header_builder.find('#header-search-modal').slideDown(240);
                    $header_builder.find('.la-header-search').addClass('opened');
                    $header_builder.find('.la-header-social').removeClass('opened');
                    $header_builder.find('#header-search-modal .search-field').focus();
                }
            });

            $document.on('click', function (e) {
                if( $(e.target).hasClass('js-search_trigger_slide') || $(e.target).closest('.js-search_trigger_slide').length ) {
                    return;
                }
                if($('.lahb-search .js-search_trigger_slide').length){
                    if ($header_builder.find('.la-header-search').hasClass('opened')) {
                        $header_builder.find('.main-slide-toggle').slideUp('opened');
                        $header_builder.find('.la-header-search').removeClass('opened');
                    }
                }
            });


            if ($.fn.niceSelect) {
                $('.la-polylang-switcher-dropdown select').niceSelect();
            }

            if ($.fn.superfish) {
                $('.lahb-area:not(.lahb-vertical) .lahb-nav-wrap:not(.has-megamenu) ul.menu').superfish({
                    delay: 300,
                    hoverClass: 'la-menu-hover',
                    animation: {
                        opacity: "show",
                        height: 'show'
                    },
                    animationOut: {
                        opacity: "hide",
                        height: 'hide'
                    },
                    easing: 'easeOutQuint',
                    speed: 100,
                    speedOut: 0,
                    pathLevels: 2
                });
            }

            $('.lahb-nav-wrap .menu li a').addClass('hcolorf');

            // Hamburger Menu
            var $hamurgerMenuWrapClone = $('.hamburger-type-toggle').find('.hamburger-menu-wrap');
            if ($hamurgerMenuWrapClone.length > 0) {
                $hamurgerMenuWrapClone.appendTo('body');
                $('.hamburger-type-toggle .la-hamuburger-bg').remove();
            }

            if ($('.hamburger-menu-wrap').hasClass('toggle-right')) {
                $('body').addClass('lahb-body lahmb-right');
            }
            else if ($('.hamburger-menu-wrap').hasClass('toggle-left')) {
                $('body').addClass('lahb-body lahmb-left');
            }

            if ($.fn.niceScroll) {
                //Hamburger Nicescroll
                $('.hamburger-menu-main').niceScroll({
                    scrollbarid: 'lahb-hamburger-scroll',
                    cursorwidth: "5px",
                    autohidemode: true
                });
            }

            $('.btn-close-hamburger-menu').on('click', function (e) {
                e.preventDefault();
                $body.removeClass('is-open');
                $('.lahb-hamburger-menu').removeClass('is-open');
                $('.hamburger-menu-wrap').removeClass('hm-open');
                if($.fn.getNiceScroll){
                    $('.hamburger-menu-main').getNiceScroll().resize();
                }
            });

            $('.hamburger-type-toggle a.lahb-icon-element').on('click', function (e) {
                e.preventDefault();
                var $that = $(this),
                    $_parent = $that.closest('.lahb-hamburger-menu'),
                    _cpt_id = $that.attr('data-id');

                if($_parent.hasClass('is-open')){
                    $('.btn-close-hamburger-menu').trigger('click');
                }
                else{
                    $_parent.addClass('is-open');
                    $body.addClass('is-open');
                    $body.find('.hamburger-menu-wrap.hamburger-menu-wrap-' + _cpt_id).addClass('hm-open');
                    if($.fn.getNiceScroll){
                        $('.hamburger-menu-main').getNiceScroll().resize();
                    }
                }

            });



            $('.hamburger-nav.toggle-menu').find('li').each(function () {
                var $list_item = $(this);

                if ($list_item.children('ul').length) {
                    $list_item.children('a').append('<i class="hamburger-nav-icon lastudioicon-down-arrow"></i>');
                }

                $('> a > .hamburger-nav-icon', $list_item).on('click', function (e) {
                    e.preventDefault();
                    var $that = $(this);
                    if( $that.hasClass('active') ){
                        $that.removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                        $('>ul', $list_item).stop().slideUp();
                    }
                    else{
                        $that.removeClass('lastudioicon-down-arrow').addClass('lastudioicon-up-arrow active');
                        $('>ul', $list_item).stop().slideDown(350, function () {
                            if($.fn.getNiceScroll){
                                $('.hamburger-menu-main').getNiceScroll().resize();
                            }
                        });
                    }
                })
            });

            //Full hamburger Menu
            $('.hamburger-type-full .js-hamburger_trigger').on('click', function (e) {
                e.preventDefault();
                var $that = $(this);
                if( $that.hasClass('open-button') ){
                    $('.la-hamburger-wrap-' + $that.data('id')).removeClass('open-menu');
                    $that.removeClass('open-button').addClass('close-button');
                    $('body').removeClass('opem-lahb-iconmenu');
                }
                else{
                    $('.la-hamburger-wrap-' + $that.data('id')).addClass('open-menu');
                    $that.removeClass('close-button').addClass('open-button');
                    $('body').addClass('opem-lahb-iconmenu');
                }
            });

            $('.btn-close-hamburger-menu-full').on('click', function (e) {
                e.preventDefault();
                $('.js-hamburger_trigger').removeClass('open-button').addClass('close-button');
                $('.la-hamburger-wrap').removeClass('open-menu');
                $('body').removeClass('opem-lahb-iconmenu');
            });

            $('.full-menu li > ul').each(function () {
                var $ul = $(this);
                $ul.prev('a').append('<i class="hamburger-nav-icon lastudioicon-down-arrow"></i>');
            });

            $('.full-menu').on('click', 'li .hamburger-nav-icon', function (e) {
                e.preventDefault();
                var $that = $(this),
                    $li_parent = $that.closest('li');

                if ($li_parent.hasClass('open')) {
                    $that.removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                    $li_parent.removeClass('open');
                    $li_parent.find('li').removeClass('open');
                    $li_parent.find('ul').stop().slideUp();
                    $li_parent.find('.hamburger-nav-icon').removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                }
                else {
                    $li_parent.addClass('open');
                    $that.removeClass('lastudioicon-down-arrow').addClass('active lastudioicon-up-arrow');
                    $li_parent.find('>ul').stop().slideDown();
                    $li_parent.siblings().removeClass('open').find('ul').stop().slideUp();
                    $li_parent.siblings().find('li').removeClass('open');
                    $li_parent.siblings().find('.hamburger-nav-icon').removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                }
            });

            $('.touchevents .full-menu').on('touchend', 'li .hamburger-nav-icon', function (e) {
                e.preventDefault();
                var $that = $(this),
                    $li_parent = $that.closest('li');

                if ($li_parent.hasClass('open')) {
                    $that.removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                    $li_parent.removeClass('open');
                    $li_parent.find('li').removeClass('open');
                    $li_parent.find('ul').stop().slideUp();
                    $li_parent.find('.hamburger-nav-icon').removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                }
                else {
                    $li_parent.addClass('open');
                    $that.removeClass('lastudioicon-down-arrow').addClass('active lastudioicon-up-arrow');
                    $li_parent.find('>ul').stop().slideDown();
                    $li_parent.siblings().removeClass('open').find('ul').stop().slideUp();
                    $li_parent.siblings().find('li').removeClass('open');
                    $li_parent.siblings().find('.hamburger-nav-icon').removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                }
            });

            // Toggle search form
            $('.lahb-search .js-search_trigger_toggle').on('click', function (e) {
                e.preventDefault();
                $(this).siblings('.lahb-search-form-box').toggleClass('show-sbox');
            });

            $document.on('click', function (e) {
                if( $(e.target).hasClass('js-search_trigger_toggle') || $(e.target).closest('.js-search_trigger_toggle').length){
                    return;
                }
                if( $('.lahb-search-form-box').length ) {
                    if( $(e.target).closest('.lahb-search-form-box').length == 0){
                        $('.lahb-search-form-box').removeClass('show-sbox');
                    }
                }
            });

            // Responsive Menu
            $('.lahb-responsive-menu-icon-wrap').on('click', function (e) {
                e.preventDefault();
                var $toggleMenuIcon = $(this),
                    uniqid = $toggleMenuIcon.data('uniqid'),
                    $responsiveMenu = $('.lahb-responsive-menu-wrap[data-uniqid="' + uniqid + '"]'),
                    $closeIcon = $responsiveMenu.find('.close-responsive-nav');

                if ($responsiveMenu.hasClass('open') === false) {
                    $toggleMenuIcon.addClass('open-icon-wrap').children().addClass('open');
                    $closeIcon.addClass('open-icon-wrap').children().addClass('open');

                    if(LaStudio.global.isRTL()){
                        $responsiveMenu.animate({'right': 0}, 350)
                    }
                    else{
                        $responsiveMenu.animate({'left': 0}, 350)
                    }
                    $responsiveMenu.addClass('open');
                }
                else {
                    $toggleMenuIcon.removeClass('open-icon-wrap').children().removeClass('open');
                    $closeIcon.removeClass('open-icon-wrap').children().removeClass('open');

                    if(LaStudio.global.isRTL()){
                        $responsiveMenu.animate({'right': -1 * $responsiveMenu.width()}, 350)
                    }
                    else{
                        $responsiveMenu.animate({'left': -1 * $responsiveMenu.width()}, 350)
                    }
                    $responsiveMenu.removeClass('open');
                }
            });

            $('.lahb-responsive-menu-wrap').each(function () {
                var $this = $(this),
                    uniqid = $this.data('uniqid'),
                    $responsiveMenu = $this.clone(),
                    $closeIcon = $responsiveMenu.find('.close-responsive-nav'),
                    $toggleMenuIcon = $('.lahb-responsive-menu-icon-wrap[data-uniqid="' + uniqid + '"]');

                // append responsive menu to lastudio header builder wrap
                $this.remove();
                $('#lastudio-header-builder').append($responsiveMenu);

                // add arrow down to parent menus
                $responsiveMenu.find('li').each(function () {
                    var $list_item = $(this);

                    if ($list_item.children('ul').length) {
                        $list_item.children('a').append('<i class="lastudioicon-down-arrow respo-nav-icon"></i>');
                    }

                    $('> a > .respo-nav-icon', $list_item).on('click', function (e) {
                        e.preventDefault();
                        var $that = $(this);
                        if( $that.hasClass('active') ){
                            $that.removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                            $('>ul', $list_item).stop().slideUp(350);
                        }
                        else{
                            $that.removeClass('lastudioicon-down-arrow').addClass('lastudioicon-up-arrow active');
                            $('>ul', $list_item).stop().slideDown(350);
                        }
                    });
                });

                // close responsive menu
                $closeIcon.on('click', function () {
                    if ($toggleMenuIcon.hasClass('open-icon-wrap')) {
                        $toggleMenuIcon.removeClass('open-icon-wrap').children().removeClass('open');
                        $closeIcon.removeClass('open-icon-wrap').children().removeClass('open');
                    }
                    else {
                        $toggleMenuIcon.addClass('open-icon-wrap').children().addClass('open');
                        $closeIcon.addClass('open-icon-wrap').children().addClass('open');
                    }

                    if ($responsiveMenu.hasClass('open') === true) {
                        if(LaStudio.global.isRTL()){
                            $responsiveMenu.animate({'right': -1 * $responsiveMenu.width() }, 350)
                        }
                        else{
                            $responsiveMenu.animate({'left': -1 * $responsiveMenu.width() }, 350)
                        }
                        $responsiveMenu.removeClass('open')
                    }
                });

                $responsiveMenu.on('click', 'li.menu-item:not(.menu-item-has-children) > a', function (e) {
                    $toggleMenuIcon.removeClass('open-icon-wrap').children().removeClass('open');
                    $closeIcon.removeClass('open-icon-wrap').children().removeClass('open');
                    if(LaStudio.global.isRTL()){
                        $responsiveMenu.animate({'right': -1 * $responsiveMenu.width() }, 350)
                    }
                    else{
                        $responsiveMenu.animate({'left': -1 * $responsiveMenu.width() }, 350)
                    }
                    $responsiveMenu.removeClass('open')
                });
            });

            // Login Dropdown

            $('.lahb-login .js-login_trigger_dropdown').each(function () {
                var $this = $(this);
                if($this.siblings('.lahb-modal-login').length == 0){
                    $('.lahb-modal-login.la-element-dropdown').first().clone().appendTo($this.parent());
                }
            });

            $('.lahb-login .js-login_trigger_dropdown').on('click', function (e) {
                e.preventDefault();
                $(this).siblings('.lahb-modal-login').fadeToggle('fast');
            });


            // Contact Dropdown
            $('.lahb-contact .js-contact_trigger_dropdown').on('click', function (e) {
                e.preventDefault();
                $(this).siblings('.la-contact-form').fadeToggle('fast');
            });
            $document.on('click', function (e) {
                if( $(e.target).hasClass('js-contact_trigger_dropdown')){
                    return;
                }
                if( $('.la-contact-form.la-element-dropdown').length ) {
                    if($(e.target).closest('.la-contact-form.la-element-dropdown').length == 0){
                        $('.la-contact-form.la-element-dropdown').css({
                            'display': 'none'
                        })
                    }
                }
            });


            // Icon Menu Dropdown

            $('.lahb-icon-menu .js-icon_menu_trigger').on('click', function (e) {
                e.preventDefault();
                $(this).siblings('.lahb-icon-menu-content').fadeToggle('fast');
            });

            $document.on('click', function (e) {
                if( $(e.target).hasClass('js-icon_menu_trigger')){
                    return;
                }
                if( $('.la-element-dropdown.lahb-icon-menu-content').length ) {
                    if($(e.target).closest('.la-element-dropdown.lahb-icon-menu-content').length == 0){
                        $('.la-element-dropdown.lahb-icon-menu-content').css({
                            'display': 'none'
                        })
                    }
                }
            });

            // Wishlist Dropdown
            $('.lahb-wishlist').each(function (index, el) {
                $(this).find('#la-wishlist-icon').on('click', function (event) {
                    $(this).siblings('.la-header-wishlist-wrap').fadeToggle('fast', function () {
                        if ($(".la-header-wishlist-wrap").is(":visible")) {
                            $(document).on('click', function (e) {
                                var target = $(e.target);
                                if (target.parents('.lahb-wishlist').length)
                                    return;
                                $(".la-header-wishlist-wrap").css({
                                    display: 'none'
                                });
                            });
                        }
                    });
                });
            });

            $('.la-header-wishlist-content-wrap').find('.la-wishlist-total').addClass('colorf');


            /* Profile Socials */

            $('.lahb-profile-socials-text')
                .on('mouseenter', function () {
                    $(this).closest('.lahb-profile-socials-wrap').find('.lahb-profile-socials-icons').removeClass('profile-socials-hide').addClass('profile-socials-show');
                })
                .on('mouseleave', function () {
                    $(this).closest('.lahb-profile-socials-wrap').find('.lahb-profile-socials-icons').removeClass('profile-socials-show').addClass('profile-socials-hide');
                });


            /* Vertical Header */

            var _sopt_offset = LaStudio.global.getAdminBarHeight();

            _sopt_offset += parseInt($('.header-type-vertical .lahb-row1-area').css('paddingTop'));

            var sopt = {
                to: 'top',
                offset: _sopt_offset,
                effectsOffset: 0,
                parent: '#main',
                classes: {
                    sticky: 'elementor-sticky',
                    stickyActive: 'elementor-sticky--active elementor-section--handles-inside',
                    stickyEffects: 'elementor-sticky--effects',
                    spacer: 'elementor-sticky__spacer'
                }
            };

            //$('.header-type-vertical--default .lahb-wrap .lahb-vertical .lahb-content-wrap').lasfsticky(sopt);

            // Toggle Vertical
            $('.lahb-vertical-toggle-wrap .vertical-toggle-icon').on('click', function (e) {
                e.preventDefault();
                if($body.hasClass('open-lahb-vertical')){
                    $body.removeClass('open-lahb-vertical');
                }
                else{
                    $body.addClass('open-lahb-vertical');
                }
            });

            // Vertical Menu
            $('.lahb-vertical .lahb-nav-wrap:not(.lahb-vertital-menu_nav)').removeClass('has-megamenu has-parent-arrow');
            $('.lahb-vertical .lahb-nav-wrap:not(.lahb-vertital-menu_nav) li.mega').removeClass('mega');
            $('.lahb-vertical .lahb-nav-wrap:not(.lahb-vertital-menu_nav) li.mm-popup-wide').removeClass('mm-popup-wide');
            $('.lahb-vertical .lahb-nav-wrap:not(.lahb-vertital-menu_nav) .menu li').each(function () {
                var $list_item = $(this);

                if ($list_item.children('ul').length) {
                    $list_item.children('a').removeClass('sf-with-ul').append('<i class="lastudioicon-down-arrow lahb-vertical-nav-icon"></i>');
                }

                $('> a > .lahb-vertical-nav-icon', $list_item).on('click', function (e) {
                    e.preventDefault();
                    var $that = $(this);
                    if( $that.hasClass('active') ){
                        $that.removeClass('active lastudioicon-up-arrow').addClass('lastudioicon-down-arrow');
                        $('>ul', $list_item).stop().slideUp();
                    }
                    else {
                        $that.removeClass('lastudioicon-down-arrow').addClass('lastudioicon-up-arrow active');
                        $('>ul', $list_item).stop().slideDown();
                    }
                });

            });

            $document.on('keyup', function (e) {
                if(e.keyCode == 27){
                    $body.removeClass('is-open open-search-form open-cart-aside open-lahb-vertical');
                    $('.hamburger-menu-wrap').removeClass('hm-open');
                    $('.lahb-hamburger-menu.hamburger-type-toggle').removeClass('is-open');
                    $('.lahb-hamburger-menu.hamburger-type-full .hamburger-op-icon').removeClass('open-button').addClass('close-button');
                    $('.la-hamburger-wrap').removeClass('open-menu');
                }
            });

            $('.lahb-mobiles-view .lahb-vertital-menu_nav').removeClass('has-parent-arrow');
            var $mb_vertial_menus = $('.lahb-mobiles-view .lahb-vertital-menu_nav > .menu');
            if($mb_vertial_menus.length){
                $mb_vertial_menus.each(function () {
                    var $_that = $(this).clone();
                    var $mb_v_parent = $(this).parent();
                    $(this).remove();
                    $_that.find('.mm-popup-wide').removeClass('mm-popup-wide mega');
                    $_that.find('.mm-popup-narrow').removeClass('mm-popup-narrow');
                    $_that.find('.mm-mega-ul').each(function () {
                        $(this).unwrap().unwrap().removeClass('mm-mega-ul').addClass('sub-menu');
                        $('>li', $(this)).removeAttr('style').removeClass('submenu');
                    });
                    $_that.find('> li > ul').each(function () {
                        $(this).before('<span class="narrow"><i></i></span>');
                    });
                    $_that.on('click', 'li > .narrow', function (e) {
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
                    $_that.appendTo($mb_v_parent);
                });
            }


            $( '.la-ajax-searchform' ).each(function () {
                LaStudio.core.InstanceSearch($(this));
            });

            $document.on('click', function (e) {
                if( $(e.target).closest('.la-ajax-searchform').length ) {
                    return;
                }
                $('.la-ajax-searchform .results-container').hide();
            })

        },

        reloadAllEvents: function () {
            clone_widget();
            $('body > .hamburger-menu-wrap').remove();
            LaStudio.component.HeaderBuilder.init();
            LaStudio.core.HeaderSticky();
            LaStudio.core.MegaMenu();
            $window.trigger('scroll');
            console.log('ok -- reloadAllEvents!');
        }
    }

    LaStudio.component.HeaderBuilder = HeaderBuilder;

    $(function () {
        LaStudio.component.HeaderBuilder.init();
    });

})(jQuery);