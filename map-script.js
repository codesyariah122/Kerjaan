/**
 * @author PujiErmanto<pujiermanto@gmail.com>
 * @param {*} marker 
 * @param {*} toLatLng 
 * @param {*} duration 
 */

function animateMarker(marker, toLatLng, duration) {
    var start = null;
    var fromLatLng = marker.getLatLng();

    function animate(timestamp) {
        if (!start) start = timestamp;
        var progress = timestamp - start;
        var progressRatio = Math.min(progress / duration, 1);

        var currentLat = fromLatLng.lat + (toLatLng.lat - fromLatLng.lat) * progressRatio;
        var currentLng = fromLatLng.lng + (toLatLng.lng - fromLatLng.lng) * progressRatio;

        marker.setLatLng([currentLat, currentLng]);

        if (progress < duration) {
            requestAnimationFrame(animate);
        } else {
            marker.setLatLng(toLatLng);
        }
    }

    requestAnimationFrame(animate);
}

var map = L.map('map').setView([-6.181388711133112, 106.9747525767128], 10); 

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
}).addTo(map);

var carIcon = L.icon({
    iconUrl: 'https://aionindonesia.com/wp-content/webp-express/webp-images/doc-root/wp-content/uploads/2024/07/HT-Color-Maroon-Velvet-1.png.webp', // Ganti dengan URL icon mobil
    iconSize: [100, 60], 
    iconAnchor: [19, 38], 
    popupAnchor: [0, -38]
});

var marker = L.marker([-6.181388711133112, 106.9747525767128], {icon: carIcon, draggable: false}, {draggable: false}).addTo(map);

document.querySelectorAll('.location').forEach(function(el) {
    el.addEventListener('click', function() {
        var lat = parseFloat(this.getAttribute('data-lat'));
        var lng = parseFloat(this.getAttribute('data-lng'));
        var newLatLng = new L.LatLng(lat, lng);
        
        animateMarker(marker, newLatLng, 1000);
        map.panTo(newLatLng);
    });
});
