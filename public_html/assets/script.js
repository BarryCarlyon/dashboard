var gridster;
var refreshers_timer;

jQuery(document).ready(function() {
    loadingStart();
    jQuery('#widget_source').css({width: jQuery('#widget_source').width() + 'px'})
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
    jQuery('#widget_source').on('mouseleave', function() {
        if (jQuery('#widget_source .title').hasClass('isopen')) {
            jQuery('#widget_source .widgets').slideUp();
            jQuery('#widget_source .title').removeClass('isopen');
        }
    });

    refreshers_timer = setInterval('handleRefreshers()', 30000);

    gridster = jQuery('.gridster > ul').gridster({
        widget_margins: [5, 5],
        widget_base_dimensions: [140, 140],
        min_cols: 9,
        draggable: {
            stop: function() {
                saveState();
            }
        }
    }).data('gridster');

    jQuery('#widget_source .widgets').on('click', 'li', function() {
        loadingStart();
        gridster.add_widget(jQuery(this).clone(), parseInt(jQuery(this).attr('data-sizex-open')), parseInt(jQuery(this).attr('data-sizey-open')));
        jQuery('.gridster').find('.module_title').hide();
        var name = jQuery(this).attr('id');
        jQuery.get('?do=loadWidget&widget=' + name, function(data) {
            jQuery('#'+name+' .module_content').html(data);
            loadingComplete();
        });
        jQuery(this).remove();
        saveState();
    });

    jQuery('.module_control').slideUp();
    jQuery('.gridster').on('dblclick', 'li', function() {
        clearInterval(refreshers_timer);
        jQuery('#initilise').html('<p>PAUSED</p>').slideDown();
        jQuery(this).find('.module_content').slideUp();
        jQuery(this).find('.module_control').slideDown();

        jQuery(this).on('mouseleave', function() {
            jQuery(this).find('.module_content').slideDown();
            jQuery(this).find('.module_control').slideUp();
            refreshers_timer = setInterval('handleRefreshers()', 30000);
            jQuery('#initilise').html('<p>PENDING</p>');
            handleRefreshers();
        });
    });

    jQuery('body').on('click', '.removewidget', function(e) {
        e.preventDefault();
        jQuery(this).closest('.gs_w').clone().slideDown(function() {
            jQuery(this).removeClass('gs_w');
            jQuery(this).find('.module_content').html('');
            jQuery(this).find('.module_control').slideUp();
            jQuery(this).find('.module_title').show();
            jQuery(this).attr('data-sizex-open', jQuery(this).attr('data-sizex'));
            jQuery(this).attr('data-sizey-open', jQuery(this).attr('data-sizey'));
            jQuery(this).removeAttr('data-col').removeAttr('data-row').removeAttr('data-sizex').removeAttr('data-sizey');
        }).appendTo('#widget_source .widgets');
        gridster.remove_widget(jQuery(this).closest('.gs_w'), function() {
            saveState();
        });
    });

    jQuery('body').on('click', '.editwidget', function(e) {
        e.preventDefault();
        var id = jQuery(this).closest('.gs_w').attr('id');
        jQuery.ajax({
            url: '?do=loadoptions&widget=' + id,
            success: function(data) {
                jQuery('<div id="editdialog">' + data + '</div>').dialog({
                    modal: true,
                    width: 400,
                    height: 400,
                    title: 'Editing a Widget',
                    buttons: {
                        'Save Widget': function() {
                            var values = jQuery('#editdialog form').serialize();
                            jQuery('#editdialog').html('Updating');
                            jQuery.ajax({
                                url: '?do=updateoptions&widget=' + id,
                                data: values,
                                type: 'POST',
                                success: function(data) {
                                    jQuery('#editdialog').html(data);
                                    setTimeout("jQuery('#editdialog').dialog('close')", 5000);
                                }
                            })
                        },
                        Cancel: function() {
                            jQuery(this).dialog('close');
                        }
                    }
                });
            },
        });
    });

    loadingComplete();
});

var refreshers = new Array();
var refresher_targets = new Array();
function registerRefresh(widget_name, widget_target) {
    if (!widget_target) {
        widget_target = 'module_content';
    }
    refreshers.push(widget_name);
    refresher_targets.push(widget_target);
}

var rerender = function(data) {
    jQuery('#'+this.target+' .'+this.element).replaceWith(data);
    loadingComplete();
}

function handleRefreshers() {
    loadingStart();
    for (x=0;x<refreshers.length;x++) {
        if (jQuery('#'+refreshers[x]+'_content').length) {
            loadingStart();
            jQuery.ajax({
                url: '?do=loadWidget&widget=' + refreshers[x],
                target: refreshers[x],
                element: refresher_targets[x],
                success: rerender
            });
        }
    }
    loadingComplete();
}

var loadingtrack = 0;
function saveState() {
    loadingStart();
    // get widget dom order
    var items = new Array();
    jQuery('.gridster ul > li').each(function() {
        if (jQuery(this).attr('id')) {
            items.push(jQuery(this).attr('id'));
        }
    });
    console.log(JSON.stringify(gridster.serialize(), null, 2));
    console.log(JSON.stringify(items));
    jQuery.post('?do=saveState',
        {
            data: JSON.stringify(gridster.serialize(), null, 2),
            items: JSON.stringify(items)
        },
        function(data) {
            loadingComplete();
        }
    );
}

function loadingStart() {
    loadingtrack++;
    jQuery('#loading').show();
    jQuery('#loading').html(loadingtrack);
}
function loadingComplete() {
    loadingtrack--;
    jQuery('#loading').html(loadingtrack);
    if (loadingtrack <= 0) {
        loadingtrack = 0;
        jQuery('#loading').hide();
    }
    setTimeout("jQuery('#initilise').slideUp()", 2500);
}
