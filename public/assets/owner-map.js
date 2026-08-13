/**
 * Owner dashboard delivery map.
 *
 * Reads its data from the <script type="application/json" id="owner-map-data">
 * block the dashboard template renders, so no order data is ever interpolated
 * into executable markup.
 */
(function () {
  var dataNode = document.getElementById('owner-map-data');
  var mapNode = document.getElementById('owner-map');

  if (!dataNode || !mapNode || typeof L === 'undefined') {
    return;
  }

  var data;
  try {
    data = JSON.parse(dataNode.textContent);
  } catch (err) {
    return;
  }

  var store = data.store || { lat: -1.2654, lng: 116.8312 };
  var points = data.points || [];
  var colors = data.colors || {};

  var map = L.map(mapNode, { scrollWheelZoom: false }).setView([store.lat, store.lng], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  var bounds = [[store.lat, store.lng]];

  L.marker([store.lat, store.lng])
    .addTo(map)
    .bindPopup('<strong>Hanaka Cake</strong><br>Lokasi toko');

  points.forEach(function (point) {
    var color = colors[point.status] || '#c8683d';

    L.circleMarker([point.lat, point.lng], {
      radius: 7,
      color: '#ffffff',
      weight: 2,
      fillColor: color,
      fillOpacity: 0.9,
    })
      .addTo(map)
      .bindPopup(
        '<strong>' + point.orderNumber + '</strong><br>' +
          'Status: ' + point.status + '<br>' +
          'Total: Rp ' + Number(point.totalPrice).toLocaleString('id-ID')
      );

    bounds.push([point.lat, point.lng]);
  });

  if (bounds.length > 1) {
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
  }
})();
