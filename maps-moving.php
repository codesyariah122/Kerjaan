<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map with Pin Animation</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            display: flex;
            height: 100vh;
        }
        .locations {
            width: 30%;
            padding: 20px;
            background-color: #f8f8f8;
        }
        .locations .location {
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            cursor: pointer;
        }
        #map {
            width: 70%;
            height: 100%;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="locations">
        <div class="location" data-lat="-6.181388711133112" data-lng="106.9747525767128">AION Harapan Indah</div>
        <div class="location" data-lat="-6.124121024526515" data-lng="106.7552426611303">AION Pantai Indah Kapuk</div>
        <div class="location" data-lat="-6.279948222095222" data-lng="106.82899945248322">AION Warung Buncit</div>
    </div>
    <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-plugins/layer/Marker.SlideTo.js"></script>
<script src="map-script.js"></script>


</body>
</html>
