var gridster;

jQuery(document).ready(function() {
    gridster = jQuery('.gridster > ul').gridster({
        widget_margins: [5, 5],
        widget_base_dimensions: [250, 250],
        min_cols: 5,
        draggable: {
            stop: function() {
                saveState();
            }
        }
    }).data('gridster');
});

function saveState() {

}
