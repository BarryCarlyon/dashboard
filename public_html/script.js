jQuery(document).ready(function() {
    jQuery('#controller ul').slideUp();
    jQuery('#controller').hover(function() {
        jQuery(this).find('ul').slideDown();
    }, function() {
        jQuery(this).find('ul').slideUp();
    });

    jQuery('.col_control ul').slideUp();
    jQuery('.col_control').on('mouseenter', function() {
        jQuery(this).find('ul').slideDown();
    });
    jQuery('.col_control').on('mouseleave', function() {
        jQuery(this).find('ul').slideUp();
    });

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

    jQuery('.module .ui-widget-header').prepend( "<span class='ui-icon ui-icon-minusthick'></span>")
    jQuery('.module .ui-icon').click(function() {
        jQuery(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
        jQuery(this).parents('.module').find('.ui-widget-content').toggle();
    });
    regen();
});

function regen() {
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
                jQuery('#'+name+' .module_content').html('');
                jQuery.get('?do=killWidget&widget=' + name, function(data) {});
            }
        }
    }).disableSelection();
}
