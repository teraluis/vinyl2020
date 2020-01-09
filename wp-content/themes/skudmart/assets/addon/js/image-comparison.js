(function ($) {

    "use strict";

    function initCarousel($target, options) {

        var laptopSlides, tabletPortraitSlides, tabletSlides, mobileSlides, mobilePortraitSlides, defaultOptions,
            slickOptions;

        laptopSlides = parseInt(options.slidesToShow.laptop) || 1;
        tabletSlides = parseInt(options.slidesToShow.tablet) || laptopSlides;
        tabletPortraitSlides = parseInt(options.slidesToShow.tabletportrait) || tabletSlides;
        mobileSlides = parseInt(options.slidesToShow.mobile) || tabletPortraitSlides;
        mobilePortraitSlides = parseInt(options.slidesToShow.mobileportrait) || mobileSlides;

        options.slidesToShow = parseInt(options.slidesToShow.desktop) || 1;

        defaultOptions = {
            customPaging: function (slider, i) {
                return $('<span />').text(i + 1);
            },
            dotsClass: 'lastudio-slick-dots',
            responsive: [
                {
                    breakpoint: 1600,
                    settings: {
                        slidesToShow: laptopSlides,
                        slidesToScroll: laptopSlides
                    }
                },
                {
                    breakpoint: 1025,
                    settings: {
                        slidesToShow: tabletSlides,
                        slidesToScroll: tabletSlides
                    }
                },
                {
                    breakpoint: 800,
                    settings: {
                        slidesToShow: tabletPortraitSlides,
                        slidesToScroll: tabletPortraitSlides
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: mobileSlides,
                        slidesToScroll: mobileSlides
                    }
                },
                {
                    breakpoint: 577,
                    settings: {
                        slidesToShow: mobilePortraitSlides,
                        slidesToScroll: mobilePortraitSlides
                    }
                }
            ]
        };

        slickOptions = $.extend({}, defaultOptions, options);

        var _autoPlay = slickOptions.autoplay || false;

        $target.slick(slickOptions);

        if ($(window).width() > 1200) {

            if ($target.closest('.slick-allow-scroll').length > 0) {
                $target.on('wheel', (function (e) {
                    e.preventDefault();
                    if (e.originalEvent.deltaY < 0) {
                        $(this).slick('slickNext');
                    } else {
                        $(this).slick('slickPrev');
                    }
                }));

            }
        }

        if ($target.closest('.lastudio-portfolio').length == 0) {
            _autoPlay = false;
        }

        if (_autoPlay) {
            var $bar = $('<div class="slick-controls-auto"><a class="slick-control-start" href="#"><i class="fa fa-play" aria-hidden="true"></i></a><a class="slick-control-stop active" href="#"><i class="fa fa-pause" aria-hidden="true"></i></a></div>');
            $bar.appendTo($target);
            $target
                .on('click', '.slick-control-start', function (e) {
                    e.preventDefault();
                    $(this).removeClass('active').siblings('a').addClass('active');
                    $target.slick('slickPlay');
                })
                .on('click', '.slick-control-stop', function (e) {
                    e.preventDefault();
                    $(this).removeClass('active').siblings('a').addClass('active');
                    $target.slick('slickPause');
                })
        }

        $(window).on('load', function () {
            setTimeout(function () {
                $('.la-lazyload-image', $target).each(function () {
                    LaStudioElementTools.makeImageAsLoaded(this);
                });
            }, 500)
        })

    }

    $(window).on('elementor/frontend/init', function () {

        window.elementorFrontend.hooks.addAction('frontend/element_ready/lastudio-image-comparison.default', function ($scope) {

            var $target = $scope.find('.lastudio-image-comparison__instance'),
                instance = null,
                imageComparisonItems = $('.lastudio-image-comparison__container', $target),
                settings = $target.data('settings'),
                elementId = $scope.data('id');

            if (!$target.length) {
                return;
            }

            window.juxtapose.scanPage('.lastudio-juxtapose');

            settings.draggable = false;
            settings.infinite = false;
            //settings.adaptiveHeight = true;
            initCarousel($target, settings);

        });
    });

}(jQuery));