var gridster;

jQuery(document).ready(function() {
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

    regen();

    setInterval('handleRefreshers()', 30000);

    gridster = jQuery('.gridster > ul').gridster({
        widget_margins: [10, 10],
        widget_base_dimensions: [140, 140],
        min_cols: 6
    }).data('gridster');

    jQuery('#widget_source .widgets > li').click(function() {
        gridster.add_widget(jQuery(this).clone(), parseInt(jQuery(this).attr('data-sizex-open')), parseInt(jQuery(this).attr('data-sizey-open')));
        var name = jQuery(this).attr('id');
        jQuery.get('?do=loadWidget&widget=' + name, function(data) {
            jQuery('#'+name+' .module_content').html(data);
        });
        jQuery(this).remove();
        // regenerate the toggles
        regen();
    });
});

function regen() {
    jQuery('.gridster .toggle').show();
    jQuery('.toggle').on('click', function() {
        jQuery(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
        jQuery(this).parents('.module').find('.ui-widget-content').toggle();

        jQuery.get('?do=toggleWidget&widget=' + jQuery(this).closest('.module').attr('id'));
    });
}

var refreshers = new Array();
function registerRefresh(widget_name) {
    refreshers.push(widget_name);
}
function handleRefreshers() {
    for (x=0;x<refreshers.length;x++) {
        if (jQuery('#'+refreshers[x]+'_content').length) {
            jQuery.get('?do=loadWidget&widget=' + refreshers[x], function(data) {
                jQuery('#'+refreshers[x]+'_content').replaceWith(data);
            });
        }
    }
}
