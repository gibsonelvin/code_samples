<?php
define('BASEPATH', __DIR__ . "/../");

// Dies when no service specified
if(!isset($argv[1])) {
    die("\nNo service specified!\n\n\tUsage: createShim.php [Service]\n\n");
}

// Defines variables & function(s)
$service = $argv[1];
$servicePath = BASEPATH . "/application/libraries/Fg/Service/";
$spaces = "     ";
$regex = '@(?P<comment>/\*\*[^{]+\*/)[\n\r\t\s]+'
    . '(?P<declaration>(public|static|public\sstatic|static\spublic)\sfunction)\s'
    . '(?P<function_name>[A-z_]+)'
    . '(?P<args>\(.*\))\s*\{@Uxs';

// Function removes defaults from parameters
function formatParams($str) {

    // Removes type hint, dies on invalid param
    $varDef = strpos($str, "$");
    if($varDef===FALSE && strlen($str)>0) {
        die("Inappropriate paramater: " . $str);
    } else {
        $str = substr($str, $varDef);
    }

    // If the equal is found, returns parameter w/o default value
    $equalPos = strpos($str, "=");
    if($equalPos!==FALSE) {
        $str = trim(substr($str, 0, $equalPos-1));
    }
    return $str;
}

// Gets the file contents and sets it to $contents variable
$contents = file_get_contents($servicePath . $service . ".php");

// Matches all functions, and sets count
preg_match_all($regex, $contents, $matches);
$funcCnt = count($matches['comment']);

// Defines base class output
$output = "<?php\nnamespace Fg\\Service;\n\nclass " . $service . "Shim {\n" . $spaces;
for($i=0;$i<$funcCnt;$i++) {
    // Removes default values from params
    $params = implode(", ", array_map("formatParams", array_map("trim", explode(",", substr($matches['args'][$i], 1, -1)))));

    // Builds output for shim
    $output .= $matches['comment'][$i]
        . "\n" . $spaces . "public function " . $matches['function_name'][$i]
        . $matches['args'][$i] . " {\n" . $spaces . $spaces
        . "return " . $service . "::" . $matches['function_name'][$i] . "(" . $params . ");\n" . $spaces
        . "}\n\n" . ($i==($funcCnt-1) ? "": $spaces);
}

// Close class output
$output .= "}\n";

// Write the shim to file
$fh = fopen($servicePath . $service . "Shim.php", "w+");
$written = fwrite($fh, $output);
fclose($fh);

if($written) {
    echo "\n\nShim was successfully saved at '" . $service . "Shim.php'!\n\n";
} else {
    echo "Error saving shim! Please try again!";
}
