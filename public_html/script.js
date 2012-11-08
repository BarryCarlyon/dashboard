jQuery(document).ready(function() {
    jQuery('.create_column').click(function() {
        var name = prompt('Name?');
        var width = prompt('Width?');
        jQuery(this).append('<div class="loading">Loading</div>');
        jQuery.get('?do=addColumn&name=' + name + '&width=' + width, function(data) {
            jQuery('.loading').remove();
            jQuery(data).appendTo('body');
        });
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
