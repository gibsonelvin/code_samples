<?php
require('./autoload.php');
Users::outputHeaderRow(json_decode($_GET['data'], true));