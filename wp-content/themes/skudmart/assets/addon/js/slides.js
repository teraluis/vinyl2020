(function ($) {

    "use strict";

    $(window).on('elementor/frontend/init', function () {

        var SlidesHandler = elementorModules.frontend.handlers.Base.extend({
            getDefaultSettings: function getDefaultSettings() {
                return {
                    selectors: {
                        slider: '.elementor-slides',
                        slideContent: '.elementor-slide-content'
                    },
                    classes: {
                        animated: 'animated'
                    },
                    attributes: {
                        dataSliderOptions: 'slider_options',
                        dataAnimation: 'animation'
                    }
                };
            },

            getDefaultElements: function getDefaultElements() {
                var selectors = this.getSettings('selectors');

                return {
                    $slider: this.$element.find(selectors.slider)
                };
            },

            initSlider: function initSlider() {
                var $slider = this.elements.$slider;

                if (!$slider.length) {
                    return;
                }

                $slider.slick($slider.data(this.getSettings('attributes.dataSliderOptions')));
            },

            goToActiveSlide: function goToActiveSlide() {
                this.elements.$slider.slick('slickGoTo', this.getEditSettings('activeItemIndex') - 1);
            },

            onPanelShow: function onPanelShow() {
                var $slider = this.elements.$slider;

                $slider.slick('slickPause');

                // On switch between slides while editing. stop again.
                $slider.on('afterChange', function () {
                    $slider.slick('slickPause');
                });
            },

            bindEvents: function bindEvents() {
                var $slider = this.elements.$slider,
                    settings = this.getSettings(),
                    animation = $slider.data(settings.attributes.dataAnimation);

                if (!animation) {
                    return;
                }

                if (elementorFrontend.isEditMode()) {
                    elementor.hooks.addAction('panel/open_editor/widget/slides', this.onPanelShow);
                }

                $slider.on({
                    beforeChange: function beforeChange() {
                        var $sliderContent = $slider.find(settings.selectors.slideContent);

                        $sliderContent.removeClass(settings.classes.animated + ' ' + animation).hide();
                    },
                    afterChange: function afterChange(event, slick, currentSlide) {
                        var $currentSlide = jQuery(slick.$slides.get(currentSlide)).find(settings.selectors.slideContent);

                        $currentSlide.show().addClass(settings.classes.animated + ' ' + animation);
                    }
                });
            },

            onInit: function onInit() {
                elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);

                this.initSlider();

                if (this.isEdit) {
                    this.goToActiveSlide();
                }
            },

            onEditSettingsChange: function onEditSettingsChange(propertyName) {
                if ('activeItemIndex' === propertyName) {
                    this.goToActiveSlide();
                }
            }
        });

        window.elementorFrontend.hooks.addAction('frontend/element_ready/lastudio-slides.default', function ($scope) {
            new SlidesHandler({ $element: $scope });
        });
    });

}(jQuery));