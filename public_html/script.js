jQuery(document).ready(function() {
    jQuery('.create_column').click(function() {
        var width = prompt('Width?');
        if (width) {
            jQuery(this).append('<div class="loading">Loading</div>');
            jQuery.get('?do=addColumn&width=' + width, function(data) {
                jQuery('.loading').remove();
                jQuery(data).appendTo('body');
            });
        }
    });
    jQuery('.change_col').live('click', function() {
        var name = jQuery(this).closest('.col').attr('id');
        var width = prompt('Width?');
        if (name && width) {
            jQuery(this).append('<div class="loading">Loading</div>');
            jQuery.getScript('?do=changeColumn&name=' + name + '&width=' + width, function() {
                jQuery('.loading').remove();
            });
        }
    });
    jQuery('.delete_col').live('click', function() {
        var name = jQuery(this).closest('.col').attr('id');
        if (name) {
            jQuery(this).append('<div class="loading">Loading</div>');
            jQuery.getScript('?do=deleteColumn&name=' + name, function() {
                jQuery('.loading').remove();
            });
        }
    });


    jQuery('.module .ui-widget-header').prepend( "<span class='ui-icon ui-icon-minusthick'></span>")
    jQuery('.col').sortable({
        connectWith:    '.col',
        items:          '.module',
        placeholder:    'ui-state-highlight',
        containment:    '#content',
        grid:           [25, 25]
    });
    jQuery('.module .ui-icon').click(function() {
        jQuery(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
        jQuery(this).parents('.module').find('.ui-widget-content').toggle();
    });
});
