jQuery(document).ready(function($) {

    var editMap = document.getElementById('scribble-edit');
    if (null !== editMap) {
        var mapId = editMap.value
        var sm = new ScribbleMap("ScribbleMap");
        sm.map.load({"id": mapId}, function(data) {
        }, function(errorEvent) {
        }, false);
    } else {
        var sm = new scribblemaps.ScribbleMap('ScribbleMap');
    }
    sm.map.addListener(scribblemaps.MapEvent.MAP_SAVED, function(event) {
        var mapData = sm.map.getWorkingInfo();
        var ajaxData = {
            action: 'save_map',
            id: mapData['id'],
            title: mapData['title'],
            description: mapData['description'],
            listType: mapData['listType']
        };
        jQuery.ajax({
            type: "POST",
            url: ajax_object.ajax_url,
            data: ajaxData,
            success: function(data) {
                //   alert('Got this from the server: ' + data);
            }
        });
    });
});