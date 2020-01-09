(function($) {
    'use strict';

    var $document = $(document),
        $menu = $('#menu-to-edit');

    $(function(){

        function update_menu_dependency( elem ){
            setTimeout(function(){
                if(elem.hasClass('menu-item-depth-1')){
                    $('.lasf-mm-menu-type select', $('#menu-item-' + $('.menu-item-data-parent-id', elem).val())).trigger('la_admin_menu_event:update_dependency');
                }
                if(elem.hasClass('menu-item-depth-0')){
                    $('.lasf-mm-menu-type select', elem).trigger('la_admin_menu_event:update_dependency');
                }
            },200);
        }

        $document
            .on('sortstart', '#menu-to-edit', function(e, ui){
                $('.menu-item-settings', ui.item).hide();
            })
            .on( 'sortstop','#menu-to-edit', function( event, ui ) {
                ui.item.removeClass('menu-item-edit-active').addClass('menu-item-edit-inactive');
                update_menu_dependency(ui.item);
            })
            .on( 'la_admin_menu_event:update_dependency', '.lasf-mm-menu-type select', function(e){
                var _select = $(this),
                    $li = _select.closest('li.menu-item'),
                    $sub = $('.menu-item-data-parent-id[value="'+$li.attr('id').replace('menu-item-','')+'"]').closest('.menu-item');
                if(_select.val() == 'wide'){
                    $('.lasf-mm-popup-column,.lasf-mm-popup-max-width,.lasf-mm-popup-background,.lasf-mm-force-full-width', $li).addClass('show');
                    $('.lasf-mm-item-column,.lasf-mm-block,.lasf-mm-block2', $sub).addClass('show');
                }
                else{
                    $('.lasf-mm-popup-column,.lasf-mm-popup-max-width,.lasf-mm-popup-background,.lasf-mm-force-full-width', $li).removeClass('show');
                    $('.lasf-mm-item-column,.lasf-mm-block,.lasf-mm-block2', $sub).removeClass('show');
                }
            });
        $menu
            .on( 'click', 'a.item-edit', function(){
                var $menu_item = $(this).closest('li.menu-item');

                $('.lastudio-megamenu-custom-fields', $menu_item).lasf_reload_script();
                setTimeout(function(){
                    update_menu_dependency($menu_item);
                }, 100);
            })
            .on( 'change', '.lasf-mm-menu-type select', function(e){
                console.log('hello');
                $(this).trigger('la_admin_menu_event:update_dependency');
            })
            .on( 'click', '.menus-move', function(){
                update_menu_dependency($(this).closest('li.menu-item'));
            })
    });

})(jQuery);

(function($) {
    'use strict';

    $(function () {

        if(typeof pagenow !== "undefined" && pagenow === "widgets"){
            $( '.widget-liquid-right' ).append( skudmart_sidebar_options.widget_info );

            var $create_box = $( '#la_pb_widget_area_create' ),
                $widget_name_input = $create_box.find( '#la_pb_new_widget_area_name' ),
                $la_pb_sidebars = $( 'div[id^=skudmart_widget_area_]' );

            $create_box.find( '.la_pb_create_widget_area' ).on('click', function( event ) {
                var $this_el = $(this);

                event.preventDefault();

                if ( $widget_name_input.val() === '' ) return;

                $.ajax( {
                    type: "POST",
                    url: skudmart_sidebar_options.ajaxurl,
                    data:
                        {
                            action : 'skudmart_core_action',
                            router : 'add_sidebar',
                            admin_load_nonce : skudmart_sidebar_options.admin_load_nonce,
                            widget_area_name : $widget_name_input.val()
                        },
                    success: function( data ){
                        if(data.success){
                            $this_el.closest( '#la_pb_widget_area_create' ).find( '.la_pb_widget_area_result' ).hide().html( data.data.message ).slideToggle();
                        }
                    }
                } );
            } );

            $la_pb_sidebars.each( function() {
                if ( $(this).is( '#la_pb_widget_area_create' ) || $(this).closest( '.inactive-sidebar' ).length ) return true;

                $(this).closest('.widgets-holder-wrap').find('.sidebar-name h2, .sidebar-name h3').before( '<a href="#" class="la_pb_widget_area_remove">' + skudmart_sidebar_options.delete_string + '</a>' );

                $( '.la_pb_widget_area_remove' ).on('click' ,function( event ) {
                    var $this_el = $(this);

                    event.preventDefault();

                    if(confirm(skudmart_sidebar_options.confirm_delete_string)){
                        $.ajax( {
                            type: "POST",
                            url: skudmart_sidebar_options.ajaxurl,
                            data:
                                {
                                    action : 'skudmart_core_action',
                                    router : 'remove_sidebar',
                                    admin_load_nonce : skudmart_sidebar_options.admin_load_nonce,
                                    widget_area_name : $this_el.closest( '.widgets-holder-wrap' ).find( 'div[id^=skudmart_widget_area_]' ).attr( 'id' )
                                },
                            success: function( data ){
                                if(data.success){
                                    $( '#' + data.data.sidebar_id ).closest( '.widgets-holder-wrap' ).remove();
                                }
                            }
                        } );
                    }

                    return false;
                } );
            } );
        }
    })

})(jQuery);