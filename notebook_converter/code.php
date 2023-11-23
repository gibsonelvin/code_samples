<?php

$file = $argv[1];
if(!file_exists($file)) {
	die("Invalid file specified");
}

[$fileName, $extension] = explode(".", $file);

if($extension !== "ipynb") {
	die("Please specify a Jupyter Notebook file type: '.ipynb'");
}

$newFileName = $fileName . ".py";

$codeLines = [];
$content = json_decode(file_get_contents($file));

foreach($content->cells as $dataCell) {
	if($dataCell->cell_type=="code") {
		$codeLines = array_merge($codeLines, $dataCell->source);
	}
}

$codeLines = array_filter($codeLines, function($e) {
	if(!preg_match("/^\s+$/", trim($e))) {
		return $e;
	} else {
		return "...";
	}
	}
);

$fh = fopen($newFileName, "w");
fwrite($fh, join("", $codeLines));
fclose($fh);
