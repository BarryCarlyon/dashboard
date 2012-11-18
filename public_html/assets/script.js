var gridster;

jQuery(document).ready(function() {
    loadingStart();
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

    setInterval('handleRefreshers()', 30000);

    gridster = jQuery('.gridster > ul').gridster({
        widget_margins: [5, 5],
        widget_base_dimensions: [140, 140],
        min_cols: 6,
        draggable: {
            stop: function() {
                saveState();
            }
        }
    }).data('gridster');

    jQuery('#widget_source .widgets > li').click(function() {
        loadingStart();
        gridster.add_widget(jQuery(this).clone(), parseInt(jQuery(this).attr('data-sizex-open')), parseInt(jQuery(this).attr('data-sizey-open')));
        var name = jQuery(this).attr('id');
        jQuery.get('?do=loadWidget&widget=' + name, function(data) {
            jQuery('#'+name+' .module_content').html(data);
            loadingComplete();
        });
        jQuery(this).remove();
        saveState();
    });
    loadingComplete();
});

var refreshers = new Array();
function registerRefresh(widget_name) {
    refreshers.push(widget_name);
}
function handleRefreshers() {
    loadingStart();
    for (x=0;x<refreshers.length;x++) {
        if (jQuery('#'+refreshers[x]+'_content').length) {
            loadingStart();
            jQuery.get('?do=loadWidget&widget=' + refreshers[x], function(data) {
                jQuery('#'+refreshers[x]+'_content').replaceWith(data);
                loadingComplete();
            });
        }
    }
    loadingComplete();
}

var loadingtrack = 0;
function saveState() {
    loadingStart();
    jQuery.post('?do=saveState',
        {
            data: JSON.stringify(gridster.serialize(), null, 2)
        },
        function(data) {
            loadingComplete();
        }
    );
}

function loadingStart() {
    loadingtrack++;
    jQuery('#loading').show();
}
function loadingComplete() {
    loadingtrack--;
    if (loadingtrack <= 0) {
        loadingtrack = 0;
        jQuery('#loading').hide();
    }
}
