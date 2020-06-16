<?php

require 'class.api.php';

header('Content-Type: application/json; charset=utf-8');

$api = new Api(($_POST['token'] ?? false));

echo $api->getGeoObjects(($_POST['search'] ?? false));
