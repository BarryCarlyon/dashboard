jQuery(document).ready(function() {
    jQuery('#controller ul').slideUp();
    jQuery('#controller').hover(function() {
        jQuery(this).find('ul').slideDown();
    }, function() {
        jQuery(this).find('ul').slideUp();
    });

    jQuery('.col_control ul').slideUp();

    jQuery('.create_column').click(function() {
        var width = prompt('Width?');
        if (width) {
            jQuery(this).append('<div class="loading">Loading</div>');
            jQuery.get('?do=addColumn&width=' + width, function(data) {
                jQuery('.loading').remove();
                jQuery(data).appendTo('body');
                jQuery('.col_control ul').slideUp();
                regen();
            });
        }
    });
    jQuery(document).on('click', '.change_col', function() {
        var name = jQuery(this).closest('.col').attr('id');
        var width = prompt('Width?');
        if (name && width) {
            jQuery(this).append('<div class="loading">Loading</div>');
            jQuery.getScript('?do=changeColumn&name=' + name + '&width=' + width, function() {
                jQuery('.loading').remove();
            });
        }
    });
    jQuery(document).on('click', '.delete_col', function() {
        var name = jQuery(this).closest('.col').attr('id');
        if (name) {
            jQuery(this).append('<div class="loading">Loading</div>');
            jQuery.getScript('?do=deleteColumn&name=' + name, function() {
                jQuery('.loading').remove();
            });
        }
    });

    jQuery('#widget_source .widgets').slideUp();
    jQuery('#widget_source .title').click(function() {
        if (jQuery(this).hasClass('isopen')) {
            jQuery('#widget_source .widgets').slideUp();
            jQuery(this).removeClass('isopen');
        } else {
            jQuery('#widget_source .widgets').slideDown();
            jQuery(this).addClass('isopen');
        }
    });

    jQuery('.module .ui-icon').click(function() {
        jQuery(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
        jQuery(this).parents('.module').find('.ui-widget-content').toggle();

        var name = jQuery(this).closest('.module').attr('id');
        var parent = jQuery(this).closest('.col').attr('id');

        jQuery.get('?do=toggleWidget&widget=' + name + '&parent=' + parent);

    });
    regen();
});

function regen() {
    jQuery('.col_control').on('mouseenter', function() {
        jQuery(this).find('ul').slideDown();
    });
    jQuery('.col_control').on('mouseleave', function() {
        jQuery(this).find('ul').slideUp();
    });

    jQuery('.col').sortable({
        connectWith:    '.col',
        items:          '.module',
        placeholder:    'ui-state-highlight',
        grid:           [25, 25],
        dropOnEmpty:    true,
        helper:         function() {
            return '<div class="ui-state-highlight">&nbsp;</div>'
        },
        stop:           function(event, ui) {
            var name = jQuery(ui.item).attr('id');
            var parent = jQuery(ui.item).parent('.col').attr('id');
            if (!jQuery('#widget_source').find(ui.item).length) {
                jQuery.get('?do=loadWidget&widget=' + name + '&parent=' + parent, function(data) {
                    jQuery('#'+name+' .module_content').html(data);
                });
            } else {
                jQuery('#'+name+' .widget-content').replaceWith('<div class="module_content"></div>');
                jQuery.get('?do=killWidget&widget=' + name, function(data) {});
            }
        }
    }).disableSelection();
}
