jQuery(document).ready(function($) {
    $('.map-container').each(function(){
        var dataObj = $(this).data();
        $(this).css({
            'width':dataObj['width'],
            'height':dataObj['height']
        });
        initializeMap(this, dataObj);
    })
});

function initializeMap(mapElement, dataObj) {
  var Latlng = new google.maps.LatLng(dataObj['latitude'],dataObj['longitude']);
  var mapOptions = {
    zoom: dataObj['zoom'],
    center: Latlng
  };

  var map = new google.maps.Map(mapElement, mapOptions);

  var marker = new google.maps.Marker({
      position: Latlng,
      map: map,
      title: dataObj['title']
  });
  google.maps.event.addListener(marker, 'click', function() {
    infowindow.open(map,marker);
  });
}