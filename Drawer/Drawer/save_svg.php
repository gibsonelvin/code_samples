<?php
header("Content-Disposition: attachment; filename=image.svg");
echo urldecode($_GET['data']);