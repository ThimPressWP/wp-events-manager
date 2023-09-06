// show map by iframe
// let iframeTextarea  = document.getElementById('_iframe');
// let mapContainer    = document.querySelector('.show_map_iframe');
// let errorMessage    = document.querySelector('.error_message');

// function updateMap() {
//     let iframeContent       = iframeTextarea.value.trim();
//     mapContainer.innerHTML  = iframeContent;

//     if (iframeContent.toLowerCase().indexOf('<iframe') === 0 || iframeContent === '') {
//         errorMessage.textContent = '';
//     } else {
//         mapContainer.innerHTML   = '';
//         errorMessage.textContent = 'Invalid or missing iframe.';
//     }
// }

// iframeTextarea.addEventListener('input', updateMap);

// document.addEventListener('DOMContentLoaded', updateMap);

document.addEventListener("DOMContentLoaded", function () {
    var locationInput = document.getElementById("_location"); // Lấy tham chiếu đến ô input

    locationInput.addEventListener("change", function () {
        var location = locationInput.value;
        get_location_map(location);
    });
});

function get_location_map(address) {
    if (!address || !wpems_get_option('google_map_api_key')) {
      return;
    }
  
    var geocoder = new google.maps.Geocoder();
  
    geocoder.geocode({ address: address }, function (results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        var location = results[0].geometry.location;
        var lat = location.lat(); // Lấy latitude
        var lng = location.lng(); // Lấy longitude
  
        // Bây giờ bạn có lat và lng, có thể sử dụng chúng để hiển thị bản đồ hoặc làm gì đó khác bạn cần.
        // Ví dụ: Hiển thị bản đồ bằng mã HTML
        var mapHtml = '<div id="map"></div>';
        document.getElementById("map-canvas").innerHTML = mapHtml;
  
        var map = new google.maps.Map(document.getElementById("map"), {
          center: location,
          zoom: 14
        });
  
        var marker = new google.maps.Marker({
          map: map,
          position: location
        });
      } else {
        // Xử lý trường hợp không tìm thấy địa chỉ
        console.log("Không thể tìm thấy địa chỉ.");
      }
    });
  }