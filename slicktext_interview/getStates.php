<?php
$c = $_GET['country'];
$localeData = json_decode(file_get_contents("localeData.json"));
echo json_encode($localeData->countries->{$c});
