<?php
/*
NOTES:

Purposed Query Structure:
qstring:type(0|1), data(query|csv|file), width, height, title, leg_loc, theme, xtext, ts_interval

To Use:
\[NAMESPACE]\Graph::generateGraph($settings);

For CSV Text:
$settings = [
    'csv_data' => 1,
    'height' => 575,
    'width' => 1000,
    'title' => "Example Graph",
    'legend_location' => "left",
    'theme' => "0",
    'x_axis_text' => "Date",
    'ts_interval' => "1",
    'query_or_file' => <<<END2
Date,Scale 1.Plot 1,Scale 1.Plot 2,Scale 1.Plot 3,Scale 2.Plot 4,Scale 2.Plot 5,Scale 2.Plot 6,Scale 3.Plot 7,Scale 3.Plot 8,Scale 3.Plot 9
8/22/2012,1,117,18112,18,33,0,16,92,0
8/23/2012,1,117,17112,18,33,0,16,92,0
8/24/2012,1,108,18112,15,30,0,16,8,0
8/26/2012,1,40,18172,0,6,0,28,61,0
8/27/2012,1,105,18112,18,29,0,26,154,0
8/28/2012,1,111,18112,15,31,0,25,66,0
8/29/2012,2,100,18112,35,24,0,42,100,0
END2,
    ];


// For CSV File:
//$settings['query_or_file'] = "test_csv.csv";


// For Query:
$settings['query_or_file'] = "(SELECT unit.irig_time, soc.soc, soc.socv2, unit.DCIAnalog, unit.Ahr, unit.PowerCell_Total FROM soc, unit WHERE unit.irig_time=soc.irig_time AND unit.unit_id=11 AND soc.unit_id=11 AND unit.irig_time BETWEEN '2012-09-05 00:00:00' AND '2012-09-08 00:00:00')";
$settings['query_or_file'] .= " UNION ALL ";
$settings['query_or_file'] .= "(SELECT irig_time, 'soc', 'socv2','DCIAnalog', 'Ahr', 'PowerCell_Total' FROM unit WHERE MSG like '%Ahrs/Batt update%' AND irig_time BETWEEN '2012-09-05 00:00:00' AND '2012-09-08 00:00:00') ORDER BY irig_time";
$settings['scale_txt'] = array("SOC", "DCI", "Capacity", "PowerCell Total");
$settings['scale_cols'] = array("1,2", "3", "4", "5");
$settings['x_col'] = 0;
$settings['csv_data'] = 0;


Optional Settings:
$settings['deadband_width'] = 1;
$settings['deadband_min'] = 6500;
$settings['deadband_max'] = 11000;
$settings['deadband_color_str'] = "0,180,0";

$settings['static_scales'][0] = 0;
$settings['min_scale_value'][0] = 4;
$settings['max_scale_value'][0] = 7;

*/

namespace Methods;

use Formidable\Library\DataManipulator;

class Graph
{
    static $dbhost;
    static $dbuser;
    static $dbpass;
    static $dbts;
    static $image;
    static $font;
    static $colors;
    static $legendSize = [];
    static $legendGridSpacing = [];
    static $legendCoordinates = [
        'x' => 0,
        'y' => 0
    ];
    static $legendEntryBoxSize = [
        'height' => 10,
        'width' => 20
    ];
    static $legendEntries;
    static $settings;
    static $scaleNames;
    static $scaleCount;
    static $graphData;
    static $maxNumberOfPointsToPlot;

    // Layout variables
    static $graphStartY = 0;
    static $graphEndY = 0;
    static $graphStartX;
    static $graphEndX;
    static $renderSpaceX;
    static $renderSpaceY;
    static $xAxisTextSpacing = 6;
    static $ySpacingPixels;
    static $entryWidth;
    static $letterWidth = 8;
    static $letterHeight = 8;
    static $scalePaddingX = 50;
    static $testingMode = false;

    /**
     *              TODO: Tie to actual DB
     *
     * Sets DB creds for queries
     */
    static function loadDBCredentials() {
        self::$dbhost = "127.0.0.1";
        self::$dbuser = "DBUSER";
        self::$dbpass = "DBPASS";
        self::$dbts = "DB_NAME";
    }

    /**
     * Generates a graph from settings
     * @param $settings
     * @param false $plotOnly
     * @return bool
     */
    static function generateGraph($settings, $plotOnly = false) {
        //echo "Value: ", $val, "Minimum Value: ", $min_val, "Range: ", $range, "Pixel Range: ", $pixel_range, "Percent Y: ", $percentY, "<br /><br />";
        self::getColorsAndSetup($settings);
        self::prepareData();
        self::defineRenderVariables();
        self::renderLayout();
        self::renderTitleBar();
        self::setMaxPointsToPlot();
        self::colorGraphBackground();
        self::renderLegendLayout();
        self::plotChart();
        return self::saveImageResource();
    }

    /**
     * Calculates Y coordinate for a given scale and value, with range and pixel range provided
     * @param $val
     * @param $scale_i
     * @param $range
     * @return float
     */
    static function calculateY($val, $scale_i, $range) {
        $min_val = self::$settings['min_scale_value'][$scale_i];
//        echo "Value: ", $val, "Minimum Value: ", $min_val, "Range: ", $range, "Pixel Range: ", self::$renderSpaceY, "\n";
        if ($range != 0) {
            $percentY = ((($val - $min_val) / $range) * 100);
            $percent_spacing = self::$renderSpaceY / 100;
            $y_val = $percentY * $percent_spacing;
            return round(self::$graphEndY - $y_val, 2);

        } else {
            return round((self::$renderSpaceY - (self::$renderSpaceY / 2)) + self::$graphStartY, 2);
        }
    }

    /**
     * Parses CSV text or CSV file into array
     * @param $data
     * @param $isFile
     * @return array
     */
    static function parseCSV($data, $isFile) {
        ini_set('memory_limit', '512M');

        self::$scaleNames = [];
        $scales = [];

        if($isFile==1) {
            $file_to_graph = file_get_contents($data);
        } else {
            $file_to_graph = $data;
        }

        $lines = explode("\n", trim($file_to_graph));
        $line_cnt = count($lines);
        $columnInfo = [];

        for($i=0;$i<$line_cnt;$i++) {
            $fields = explode(",", $lines[$i]);
            $field_cnt = count($fields);

            // Evaluates first line of CSV, collects titles and scale names/plot names
            if($i==0) {
                for($e=1;$e<$field_cnt;$e++) {
                    $dot_loc = strpos($fields[$e], ".");
                    if($dot_loc !== -1) {
                        $scale_name = substr($fields[$e], 0, $dot_loc);
                        $columnInfo[$e] = [];

                        if(!in_array($scale_name, self::$scaleNames)) {
                            self::$scaleNames[] = $scale_name;
                            $scales[$scale_name] = [];
                        }

                        $col_name = substr($fields[$e], $dot_loc+1);
                        $columnInfo[$e] = [
                            'scale_name' => $scale_name,
                            'col_name' => $col_name
                        ];

                    } else {
                        $columnInfo[$e] = [
                            'scale_name' => "Default",
                            'col_name' => $fields[$e]
                        ];
                    }
                }
            } else {
                for($e=1;$e<$field_cnt;$e++) {
                    if($fields[$e]=="") $fields[$e] = 0;

                    if($i==($line_cnt-1) && $e==1) {
                        $time[] = $fields[0];

                        // Adds count, and timestamps to 'value_count' key
                        $scales["value_data"]['count'] = $line_cnt-1;
                        $scales["value_data"]['data'] = $time;
                    }

                    $scales[$columnInfo[$e]['scale_name']]["min"] = (
                        isset($scales[$columnInfo[$e]['scale_name']]["min"])
                        && $scales[$columnInfo[$e]['scale_name']]["min"]<floatval($fields[$e])
                            ? $scales[$columnInfo[$e]['scale_name']]["min"]
                            : floatval($fields[$e])
                    );

                    $scales[$columnInfo[$e]['scale_name']]["max"] = (
                        isset($scales[$columnInfo[$e]['scale_name']]["max"])
                        && $scales[$columnInfo[$e]['scale_name']]["max"]>floatval($fields[$e])
                            ? $scales[$columnInfo[$e]['scale_name']]["max"]
                            : floatval($fields[$e])
                    );

                    $scales[$columnInfo[$e]['scale_name']][$columnInfo[$e]['col_name']][] = floatval($fields[$e]);
                }
                $time[] = $fields[0];
            }
        }
        return $scales;
    }

    /**
     * Runs a query, and formats results for graph, based on scale name, and column grouping, as well as defining the x-column
     * @param $data
     * @param $col_grouping
     * @param $x
     * @return array
     */
    static function runQuery($data, $col_grouping, $x) {
        self::loadDBCredentials();
        ini_set('memory_limit', '512M');

        $conn = mysqli_connect(self::$dbhost, self::$dbuser, self::$dbpass, self::$dbts);
        $x_query = $conn->query($data) or die("Unable to execute query: " . $conn->error);
        $line_cnt = $x_query->num_rows;

        if($line_cnt==0) die("No results for query!");

        $scale_keys = [];
        $time = [];
        $scale_cnt = count($col_grouping);
        $scale_name_cnt = count(self::$scaleNames);
        if($scale_cnt!=$scale_name_cnt) die("Unequal scale names, and associated columns.");

        for($i=0;$i<$scale_cnt;$i++)
        {
            $raw_scale_keys = explode(",", $col_grouping[$i]);
            if(in_array($x, $raw_scale_keys)) die("X-axis column can't be graphed.");
            foreach($raw_scale_keys as $rs_keys)
            {
                $scale_keys[$rs_keys] = self::$scaleNames[$i];
            }
        }
        $i = 0;
        $db_cols = [];

        while($chunks = ($i==0 ? $x_query->fetch_assoc(): $x_query->fetch_row()))
        {
            $chunk_cnt = count($chunks);
            if($i==0)
            {
                $db_cols = array_keys($chunks);
                for($e=0;$e<$chunk_cnt;$e++)
                {
                    if($e!=$x)
                    {
                        $scales[$scale_keys[$e]]["max"] = NULL;
                        $scales[$scale_keys[$e]]["min"] = NULL;
                        $scales[$scale_keys[$e]][$db_cols[$e]] = array();
                    }
                }
            }
            for($e=0;$e<$chunk_cnt;$e++)
            {
                $val = ($i==0 ? $chunks[$db_cols[$e]]: $chunks[$e]);
                if($e!=$x)
                {
                    $val = floatval($val);
                    $scales[$scale_keys[$e]]["max"] = (isset($scales[$scale_keys[$e]]["max"]) && $scales[$scale_keys[$e]]["max"]>$val ? $scales[$scale_keys[$e]]["max"]: $val);
                    $scales[$scale_keys[$e]]["min"] = (isset($scales[$scale_keys[$e]]["min"]) && $scales[$scale_keys[$e]]["min"]<$val ? $scales[$scale_keys[$e]]["min"]: $val);
                    $scales[$scale_keys[$e]][$db_cols[$e]][$i] = $val;
                }
                if($e==$x) $time[] = $val;
            }
            $i++;
        }
        $scales["value_data"]['count'] = $line_cnt;
        $scales["value_data"]['data'] = $time;
        return $scales;
    }

    /**
     * Generates and returns colors for the theme, and plots
     * @param $theme
     * @return array
     */
    static function getColors($theme) {
        $colors = [];

        //Generate Plot Colors
        for($e=0;$e<5;$e++) {
            $incrementer = 0;
            for($i=25;$i>1;$i--)
            {
                $pt = [];
                $pt[0] = (in_array($e, [2, 4]) ? ($i*10): 10);
                $pt[1] = (in_array($e, [1, 3, 4]) ? ($i*10): 10);
                $pt[2] = (in_array($e, [0, 3]) ? ($i*10): 10);
                $colors['plots'][$e][$incrementer] = imagecolorallocate(self::$image, $pt[0], $pt[1], $pt[2]);
                $incrementer++;
            }
        }

        $colors['light'] = imagecolorallocate(self::$image, 150, 150, 150);
        $colors['dark'] = imagecolorallocate(self::$image, 200, 200, 200);
        $colors['legend_title_text'] = imagecolorallocate(self::$image, 255, 255, 255);

        if($theme==1 || $theme=="purple") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 230, 210, 255);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 250, 245, 255);
            $colors['primary'] = imagecolorallocate(self::$image, 205, 185, 230);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 75, 30, 100);
            $colors['title_text'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['title_bg'] = imagecolorallocate(self::$image, 100, 40, 150);
        } else if($theme==2 || $theme=="blue") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 210, 210, 255);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 245, 245, 255);
            $colors['primary'] = imagecolorallocate(self::$image, 185, 185, 230);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 30, 30, 100);
            $colors['title_text'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['title_bg'] = imagecolorallocate(self::$image, 40, 40, 150);
        } else if($theme==3 || $theme=="green") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 210, 255, 210);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 245, 255, 245);
            $colors['primary'] = imagecolorallocate(self::$image, 185, 230, 185);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 30, 100, 30);
            $colors['title_text'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['title_bg'] = imagecolorallocate(self::$image, 40, 150, 40);
        } else if($theme==4 || $theme=="red") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 255, 210, 210);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 255, 245, 245);
            $colors['primary'] = imagecolorallocate(self::$image, 230, 185, 185);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 100, 30, 30);
            $colors['title_text'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['title_bg'] = imagecolorallocate(self::$image, 150, 40, 40);
        } else if($theme==5 || $theme=="gray" || $theme=="white") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 50, 50, 50);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['primary'] = imagecolorallocate(self::$image, 220, 220, 220);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 150, 150, 150);
            $colors['title_text'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['title_bg'] = imagecolorallocate(self::$image, 130, 130, 130);
        } else if($theme==6 || $theme=="tech" || $theme=="homebrew") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 0, 230, 0);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 210, 255, 210);
            $colors['primary'] = imagecolorallocate(self::$image, 0, 230, 0);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 0, 255, 255);
            $colors['title_text'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['title_bg'] = imagecolorallocate(self::$image, 0, 230, 0);
        } else if($theme==7 || $theme=="black" || $theme=="smoke") {
            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 150, 150, 150);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 220, 255, 220 );
            $colors['primary'] = imagecolorallocate(self::$image, 150, 150, 150);

            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['title_text'] = imagecolorallocate(self::$image, 150, 150, 150);
            $colors['title_bg'] = imagecolorallocate(self::$image, 50, 50, 50);
        } else {

            $colors['outlineAndFont'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['backgroundColor'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['graph_bg'] = imagecolorallocate(self::$image, 245, 245, 245);
            $colors['primary'] = imagecolorallocate(self::$image, 180, 180, 180);
            $colors['legend_title_bg'] = imagecolorallocate(self::$image, 0, 0, 0);
            $colors['title_text'] = imagecolorallocate(self::$image, 255, 255, 255);
            $colors['title_bg'] = imagecolorallocate(self::$image, 70, 70, 70);
        }
        
        return $colors;
    }

    /**
     * Overrides default plot colors with colors provided to settings
     * @param $plotColors
     * @return array
     */
    static function overrideColors($plotColors) {
        $tmpColors = [];
        foreach($plotColors as $scale => $colors) {
            foreach($colors as $color) {
                $tmpColors[$scale][] = imagecolorallocate(self::$image, $color[0], $color[1], $color[2]);
            }
        }
        return $tmpColors;
    }

    /**
     * Renders main layout
     */
    static function renderLayout() {
        if (self::$settings['legend_location'] == "top") {
            $x_1 = 0;
            $x_2 = self::$settings['width'];
            $y_1 = self::$legendSize['height'];
            $y_2 = self::$settings['height'];
        } else if (self::$settings['legend_location'] == "bottom") {
            $x_1 = 0;
            $x_2 = self::$settings['width'];
            $y_1 = 0;
            $y_2 = self::$settings['height'] - self::$legendSize['height'];
        } else if (self::$settings['legend_location'] == "left") {
            $x_1 = self::$legendSize['width'];
            $x_2 = self::$settings['width'];
            $y_1 = 0;
            $y_2 = self::$settings['height'];
        } else if (self::$settings['legend_location'] == "right") {
            $x_1 = 0;
            $x_2 = self::$settings['width'] - self::$legendSize['width'];
            $y_1 = 0;
            $y_2 = self::$settings['height'];
        } else {
            $x_1 = 0;
            $x_2 = self::$settings['width'];
            $y_1 = 0;
            $y_2 = self::$settings['height'];
        }

        imagefilledrectangle(self::$image, $x_1, $y_1, $x_2, $y_2, self::$colors['outlineAndFont']);
        imagefilledrectangle(self::$image, $x_1 + 1, $y_1 + 1, $x_2 - 2, $y_2 - 2, self::$colors['backgroundColor']);
        if (self::$settings['x_axis_text'] != "off") {
            imagettftext(self::$image, 12, 0, ((self::$renderSpaceX / 2) + self::$graphStartX), $y_2 - 30, self::$colors['outlineAndFont'], self::$font, "(" . self::$settings['x_axis_text'] . ")");
        }
    }

    /**
     * Sets image resource, font, and colors
     * @param $settings
     */
    static function getColorsAndSetup($settings) {
        $im = imagecreatetruecolor($settings['width'], $settings['height']);
        self::$image = $im;
        self::$font = Main::$basePath . DIRECTORY_SEPARATOR . "cool.ttf";
        self::$legendSize = [
            'width' => 0,
            'height' => 0,
            'columns' => 0,
            'rows' => 0
        ];

        // Limits size
        $settings['width'] = ($settings['width'] > 1200 ? 1200 : $settings['width']);
        $settings['height'] = ($settings['height'] > 800 ? 800 : $settings['height']);

        self::$settings = $settings;
        $colors = self::getColors($settings['theme']);

        // Overides default colors if specified, structure: 'plotColors' => [ [ [0, 0, 0], [0, 0, 255] ], [ [0, 255, 0] ] ]
        if(isset($settings['plotColors'])) {
            $colors['plots'] = self::overrideColors($settings['plotColors']);
        }
        self::$colors = $colors;
    }

    /**
     * Gets longest name and longest plot value count for a plot
     * @return int|mixed
     */
    static function getLongestNameAndSetLongestPlot() {
        $longest_n = 0;

        for ($i = 0; $i < self::$scaleCount; $i++) {
            self::$maxNumberOfPointsToPlot = (isset(self::$maxNumberOfPointsToPlot) && self::$maxNumberOfPointsToPlot > (count(self::$graphData['data'][self::$scaleNames[$i]]) - 2) ? self::$maxNumberOfPointsToPlot : count(self::$graphData['data'][self::$scaleNames[$i]]) - 2);
            $keys_for_scale = array_keys(self::$graphData['data'][self::$scaleNames[$i]]);
            $plot_names = array_splice($keys_for_scale, 2);
            $pn_cnt = count($plot_names);
            for ($e = 0; $e < $pn_cnt; $e++) {
                $longest_n = ($longest_n > strlen($plot_names[$e]) ? $longest_n : strlen($plot_names[$e]));
            }
        }
        
        return $longest_n;
    }

    /**
     * Dynamically prepares data for graphing based on data type
     */
    static function prepareData() {

        // -------GETS-AND-PREPARES-DATA-FROM-SOURCE----------
        /*
            Returned Data Structure:
            self::$graphData['data'][scale name][plot name] (i.e. serial number = key)
            self::$graphData['data'][scale name][plot name][i] (values for plot)
            self::$graphData['data'][scale name][min] (min value for scale)
            self::$graphData['data'][scale name][max] (max value for scale)
            self::$graphData['data'][value_data][0] (number of points on x axis)
            self::$graphData['data'][value_data][1] (timestamps/x-axis iteration text)
        */

        if(self::$settings['csv_data'] == 1) {
            $the_data = self::parseCSV(self::$settings['query_or_file'], is_file(self::$settings['query_or_file']));
        } else {
            $the_data = self::runQuery(self::$settings['query_or_file'], self::$settings['scale_cols'], self::$settings['x_col']);
        }

        self::$scaleNames = array_keys($the_data);
        self::$scaleCount = count($the_data);
        self::$graphData = [
            'data' => $the_data,
            'count' => $the_data["value_data"]['count']
        ];
    }

    /**
     * Defines render variables from settings
     */
    static function defineRenderVariables()
    {

        $longestName = self::getLongestNameAndSetLongestPlot();
        self::$entryWidth = ($longestName * self::$letterWidth) + 5 + (self::$legendEntryBoxSize['width']);
        self::$legendEntries = (self::$maxNumberOfPointsToPlot * self::$scaleCount);

        self::$legendGridSpacing = [
            'x' => 30,
            'y' => self::$entryWidth
        ];

        self::$settings['scale_cols'] = ceil(self::$legendSize['columns'] / self::$scaleCount);

        self::$graphStartX = 75;
        self::$graphEndX = self::$settings['width'] - ((self::$scaleCount - 1) * self::$scalePaddingX);

        self::$graphStartY = 20;
        self::$graphEndY = self::$settings['height'] - (self::$settings['x_axis_text'] == "off" ? 20 : 200);

        $legend_entry_height = self::$legendEntryBoxSize['height'] + self::$legendGridSpacing['y'];
        $legend_entry_width = (($longestName * self::$letterWidth) + 5 + (self::$legendEntryBoxSize['width']));

        self::$renderSpaceX = self::$graphEndX - self::$graphStartX - self::$letterHeight;
        self::$graphStartX += self::$letterHeight;
        self::$renderSpaceY = self::$graphEndY - self::$graphStartY;

        if (in_array(self::$settings['legend_location'], array("top", "bottom"))) {

            $columnCalculation = ceil($legend_entry_height / (self::$settings['height'] - self::$renderSpaceY));
            self::$legendSize = [
                'width' => self::$settings['width'],
                'height' => self::$legendGridSpacing['x'] * $columnCalculation + 50,
                'columns' => $columnCalculation,
                'rows' => ceil($legend_entry_width / self::$settings['width'])
            ];


            if (self::$settings['legend_location'] == "bottom") {
                self::$graphEndY -= self::$legendSize['height'];

            } else if (self::$settings['legend_location'] == "top") {
                self::$graphStartY += self::$legendSize['height'];
            }

            self::$renderSpaceY -= self::$legendSize['height'];

        } else if (in_array(self::$settings['legend_location'], array("left", "right"))) {

            $columnCalculation = ceil($legend_entry_width / (self::$settings['width'] - self::$renderSpaceX));
            self::$legendSize = [
                'width' => self::$entryWidth * $columnCalculation + 5,
                'height' => self::$settings['height'],
                'columns' => $columnCalculation,
                'rows' => null
            ];

            if (self::$settings['legend_location'] == "right") {
                self::$graphEndX -= self::$legendSize['width'];

            } else if (self::$settings['legend_location'] == "left") {
                self::$graphStartX += self::$legendSize['width'];

            }

            self::$renderSpaceX -= self::$legendSize['width'];

        } else {
            // IF NO LEGEND:
            self::$settings['scale_cols'] = 0;

        }
    }

    /**
     * Renders Legend, and sets misc variables needed for rendering
     */
    static function renderLegendLayout() {
        if (self::$settings['legend_location'] == "bottom") {
            imagefilledrectangle(self::$image, 0, (self::$settings['height'] - self::$legendSize['height']), self::$settings['width'], self::$settings['height'], self::$colors['outlineAndFont']);
            imagefilledrectangle(self::$image, 2, (self::$settings['height'] - self::$legendSize['height']) + 1, self::$settings['width'] - 3, self::$settings['height'] - 3, self::$colors['primary']);
            imagefilledrectangle(self::$image, 2, (self::$settings['height'] - self::$legendSize['height']) + 1, self::$settings['width'] - 3, (self::$settings['height'] - self::$legendSize['height']) + 30, self::$colors['legend_title_bg']);
            imagettftext(self::$image, 14, 0, ((self::$settings['width'] / 2) - 40), (self::$settings['height'] - self::$legendSize['height']) + 25, self::$colors['legend_title_text'], self::$font, "Legend:");


        } else if (self::$settings['legend_location'] == "top") {
            imagefilledrectangle(self::$image, 0, 0, self::$settings['width'], self::$legendSize['height'], self::$colors['outlineAndFont']);
            imagefilledrectangle(self::$image, 2, 2, self::$settings['width'] - 3, self::$legendSize['height'] - 2, self::$colors['primary']);
            imagefilledrectangle(self::$image, 2, 2, self::$settings['width'] - 3, 30, self::$colors['legend_title_bg']);
            imagettftext(self::$image, 14, 0, ((self::$settings['width'] / 2) - 40), 25, self::$colors['legend_title_text'], self::$font, "Legend:");


        } else if (self::$settings['legend_location'] == "right") {
            imagefilledrectangle(self::$image, (self::$settings['width'] - self::$legendSize['width']), 0, self::$settings['width'], self::$settings['height'], self::$colors['outlineAndFont']);
            imagefilledrectangle(self::$image, (self::$settings['width'] - self::$legendSize['width']) + 2, 2, self::$settings['width'] - 3, self::$settings['height'] - 3, self::$colors['primary']);
            imagefilledrectangle(self::$image, (self::$settings['width'] - self::$legendSize['width']) + 2, 2, self::$settings['width'] - 3, 30, self::$colors['legend_title_bg']);
            imagettftext(self::$image, 14, 0, (self::$settings['width'] - (self::$legendSize['width'] / 2) - 30), 25, self::$colors['legend_title_text'], self::$font, "Legend:");


        } else if (self::$settings['legend_location'] == "left") {
//            die(var_dump(self::$legendSize));
            imagefilledrectangle(self::$image, 0, 0, self::$legendSize['width'], self::$legendSize['height'], self::$colors['dark']);
            imagefilledrectangle(self::$image, 2, 2, self::$legendSize['width'] - 2, self::$settings['height'] - 3, self::$colors['primary']);
            imagefilledrectangle(self::$image, 2, 2, self::$legendSize['width'] - 2, 30, self::$colors['legend_title_bg']);
            imagettftext(self::$image, 14, 0, ((self::$legendSize['width'] / 2) - 30), 25, self::$colors['legend_title_text'], self::$font, "Legend:");
        }
    }

    /**
     * Fills graph background, and outputs scale name
     */
    static function colorGraphBackground() {
        self::$graphStartY += 40;
        self::$renderSpaceY -= 40;
        $temp_y = (self::$renderSpaceY / 2) + self::$graphStartY + (strlen(self::$scaleNames[0]) * (self::$letterWidth / 2));
        imagefilledrectangle(self::$image, self::$graphStartX, self::$graphStartY, self::$graphEndX, self::$graphEndY, self::$colors['graph_bg']);

        // Outputs first scale name
        imagettftext(self::$image, 12, 90, self::$graphStartX - 75 + self::$letterHeight, $temp_y, self::$colors['outlineAndFont'], self::$font, self::$scaleNames[0]);
    }

    /**
     * Calculates and stores max # of points to plot
     */
    static function setMaxPointsToPlot() {
        $max_points = floor(self::$renderSpaceX / (self::$letterHeight + self::$xAxisTextSpacing));
        self::$maxNumberOfPointsToPlot = ($max_points < self::$graphData['count'] ? $max_points : self::$graphData['count']);
    }

    /**
     * Renders deadbands if set to do so
     * @param $scaleIteration
     * @param $range
     */
    static function renderDeadbands($scaleIteration, $range) {
        if (isset(self::$settings['deadband_min']) && self::$settings['deadband_min'] != "") {
            $y_coord1 = Graph::calculateY(self::$settings['deadband_max'], self::$settings['min_scale_value'][$scaleIteration], $range);
            $y_coord2 = Graph::calculateY(self::$settings['deadband_min'], self::$settings['min_scale_value'][$scaleIteration], $range);
            $db_color = explode(",", self::$settings['deadband_color_str']);
            $d_color = imagecolorallocate(self::$image, $db_color[0], $db_color[1], $db_color[2]);
            imagesetthickness(self::$image, self::$settings['deadband_width']);
            imageline(self::$image, self::$graphStartX, $y_coord1, self::$graphEndX, $y_coord1, $d_color);
            imageline(self::$image, self::$graphStartX, $y_coord2, self::$graphEndX, $y_coord2, $d_color);
            imagesetthickness(self::$image, 1);
        }
    }

    /**
     * Draws title bar (rectangle, with text over it)
     */
    static function renderTitleBar() {
        imagefilledrectangle(self::$image, (self::$graphStartX - 74 - self::$letterHeight), self::$graphStartY - 20, self::$graphEndX + self::$scalePaddingX - 2, self::$graphStartY + 10, self::$colors['title_bg']);
        imagettftext(self::$image, 14, 0, self::$graphStartX, self::$graphStartY + 5, self::$colors['title_text'], self::$font, self::$settings['title']);
//        }
    }

    /**
     * Draws Y axis line
     * @param $xDashCoordinate
     * @param int $thickness
     */
    static function drawYaxis($xDashCoordinate, $thickness = 3) {
        imagesetthickness(self::$image, $thickness);
        imageline(self::$image, $xDashCoordinate + 4, self::$graphStartY, $xDashCoordinate + 4, self::$graphEndY, self::$colors['outlineAndFont']);
        imagesetthickness(self::$image, 2);
    }

    /**
     * Draws X axis line
     */
    static function drawXaxis() {
        imagesetthickness(self::$image, 3);
        imageline(self::$image, self::$graphStartX, self::$graphEndY, self::$graphEndX, self::$graphEndY, self::$colors['outlineAndFont']);
        imagesetthickness(self::$image, 1);
    }

    /**
     *                      TODO: Clean up!!!
     *
     * Creates a legend entry for a plot
     * @param $scale_i
     * @param $plot_i
     * @param $plotName
     */
    static function createLegendEntry($scale_i, $plot_i, $plotName) {

        //-------------------DETERMINES LEGEND COORDINATES AND WRITES PLOT INFO TO LEGEND----------------------

        if (!in_array($plotName, array("min", "max")) && self::$settings['legend_location'] != "none") {
            if (self::$settings['legend_location'] == "left" || self::$settings['legend_location'] == "right") {
                if ($plot_i == 2) {
                    self::$legendCoordinates['x'] = 0;
                    self::$legendCoordinates['y'] = $scale_i * ceil(self::$maxNumberOfPointsToPlot / self::$legendSize['columns']) + $scale_i + 1;
                } else if (($plot_i - 2) % self::$legendSize['columns'] == 0) {
                    self::$legendCoordinates['x'] = 0;
                    self::$legendCoordinates['y'] = self::$legendCoordinates['y'] + 1;
                } else {
                    self::$legendCoordinates['x'] = self::$legendCoordinates['x'] + 1;
                }

                $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']);
                if (self::$settings['legend_location'] == "left") {
                    $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']);
                } else {
                    $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + (self::$settings['width'] - self::$legendSize['width']);
                }

            } else {

                if ($plot_i == 2) {
                    self::$legendCoordinates['x'] = $scale_i * self::$settings['scale_cols'];
                    self::$legendCoordinates['y'] = 1;
                } else if (($plot_i - 2) % self::$settings['scale_cols'] == 0) {
                    self::$legendCoordinates['x'] = $scale_i * self::$settings['scale_cols'];
                    self::$legendCoordinates['y'] += 1;
                } else {
                    self::$legendCoordinates['x'] = self::$legendCoordinates['x'] + 1;
                }

                if (self::$settings['legend_location'] == "top") {
                    $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + ($scale_i * 20);
                    $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']);
                } else {
                    $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + ($scale_i * 20);
                    $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + (self::$settings['height'] - self::$legendSize['height']);
                }
            }

            // Why the offset?
            $legend_x_coor += 5;
            $legend_y_coor += 30;

            imagefilledrectangle(self::$image, $legend_x_coor, $legend_y_coor, $legend_x_coor + self::$legendEntryBoxSize['width'], $legend_y_coor + self::$legendEntryBoxSize['height'], self::$colors['plots'][$scale_i][$plot_i]);
            imagettftext(self::$image, 9, 0, $legend_x_coor + self::$legendEntryBoxSize['width'] + 5, $legend_y_coor + 10, self::$colors['outlineAndFont'], self::$font, $plotName);

        } else if (self::$settings['legend_location'] != "none") {
        
            //----------- Write Scale Names & Separators ------------------

            if ($plot_i == 0) {
                if (self::$settings['legend_location'] == "left" || self::$settings['legend_location'] == "right") {

                    //-------------------Writes Scale Name--------------------

                    self::$legendCoordinates['x'] = 0;
                    self::$legendCoordinates['y'] = $scale_i * ceil(self::$maxNumberOfPointsToPlot / self::$legendSize['columns']) + $scale_i;
                    if (self::$settings['legend_location'] == "left") {
                        $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']);
                        $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + 30;
                    } else {
                        $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + (self::$settings['width'] - self::$legendSize['width']);
                        $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + 30;
                    }
                    imagettftext(self::$image, 10, 0, $legend_x_coor + 5, $legend_y_coor + 15, self::$colors['outlineAndFont'], self::$font, self::$scaleNames[$scale_i]);

                    //-------------------Draws Separator--------------------
                    if (self::$scaleCount - 1 > 1) {
                        self::$legendCoordinates['x'] = 0;
                        self::$legendCoordinates['y'] = ($scale_i + 1) * ceil(self::$maxNumberOfPointsToPlot / self::$legendSize['columns']) + $scale_i + 1;
                        if (self::$settings['legend_location'] == "left") {
                            $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']);
                            $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + 30;
                            imageline(self::$image, $legend_x_coor, $legend_y_coor, self::$legendSize['width'], $legend_y_coor, self::$colors['outlineAndFont']);
                        } else {
                            $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + (self::$settings['width'] - self::$legendSize['width']);
                            $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + 30;
                            imageline(self::$image, $legend_x_coor, $legend_y_coor, self::$settings['width'], $legend_y_coor, self::$colors['outlineAndFont']);
                        }
                    }
                } else {

                    //-------------------Writes Scale Name--------------------

                    self::$legendCoordinates['x'] = $scale_i * self::$settings['scale_cols'];
                    self::$legendCoordinates['y'] = 0;
                    if (self::$settings['legend_location'] == "top") {
                        $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + ($scale_i * 20);
                        $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + 30;
                    } else {
                        $legend_x_coor = (self::$legendCoordinates['x'] * self::$legendGridSpacing['x']) + ($scale_i * 20);
                        $legend_y_coor = (self::$legendCoordinates['y'] * self::$legendGridSpacing['y']) + (self::$settings['height'] - self::$legendSize['height']) + 30;
                    }
                    imagettftext(self::$image, 12, 0, $legend_x_coor + 5, $legend_y_coor + 17, self::$colors['outlineAndFont'], self::$font, self::$scaleNames[$scale_i]);

                    if (self::$scaleCount - 1 > 1) {
                        self::drawLegendSeparator($scale_i);
                    }
                }
            }
        }
    }

    /**
     * Draws a legend separator for the given scale
     * @param $scale_i
     */
    static function drawLegendSeparator($scale_i) {
        $x = ($scale_i + 1) * self::$settings['scale_cols'];
        $y = 0;

        if (self::$settings['legend_location'] == "top") {
            $legend_x_coor = ($x * self::$legendGridSpacing['x']) + ($scale_i * 20);
            $legend_y_coor = ($y * self::$legendGridSpacing['y']) + 30;
            imageline(self::$image, $legend_x_coor, $legend_y_coor, $legend_x_coor, self::$legendSize['height'], self::$colors['outlineAndFont']);
        } else if (self::$settings['legend_location'] == "bottom") {
            $legend_x_coor = ($x * self::$legendGridSpacing['x']) + ($scale_i * 20);
            $legend_y_coor = ($y * self::$legendGridSpacing['y']) + (self::$settings['height'] - self::$legendSize['height']) + 30;
            imageline(self::$image, $legend_x_coor, $legend_y_coor, $legend_x_coor, self::$settings['height'], self::$colors['outlineAndFont']);
        }
        
    }

    /**
     * Plots the chart
     */
    static function plotChart() {
        $point_iteration = self::$graphData['count'] / (self::$maxNumberOfPointsToPlot);
        $grab_interval = self::$graphData['count'] / $point_iteration;
        $xaxis_spacing = self::$renderSpaceX / ($grab_interval - (self::$maxNumberOfPointsToPlot == self::$graphData['count'] ? 1 : 0));

        // Plots each scale (Multi-scale supported)
        for ($scaleIteration = 0; $scaleIteration < self::$scaleCount - 1; $scaleIteration++) {
            $scaleData = self::$graphData['data'][self::$scaleNames[$scaleIteration]];
            $plotNames = array_keys($scaleData);
            if (!isset(self::$settings['static_scales'][$scaleIteration]) || self::$settings['static_scales'][$scaleIteration] != 1) {
                self::$settings['min_scale_value'][$scaleIteration] = self::$graphData['data'][self::$scaleNames[$scaleIteration]]["min"];
                self::$settings['max_scale_value'][$scaleIteration] = self::$graphData['data'][self::$scaleNames[$scaleIteration]]["max"];
            }
            $range = self::$settings['max_scale_value'][$scaleIteration] - self::$settings['min_scale_value'][$scaleIteration];

            self::buildYAxis($scaleIteration, $range);
            self::plotScale($scaleData, $plotNames, $scaleIteration, $xaxis_spacing, $range);
        }
        self::drawXaxis();
    }

    /**
     * Draws/ticks/labels the y-axis
     * @param $scaleIteration
     * @param $range
     * @return float|int|mixed
     */
    static function buildYAxis($scaleIteration, $range) {

        // Assigns defaults just in case
        $longestString = 0;
        $prev_x = 0;

        self::$ySpacingPixels = (self::$graphEndY - self::$graphStartY) / 6;

        // Outputs ticks to both axis if first scaleIteration
        for ($xTickIteration = 0; $xTickIteration < 7; $xTickIteration++) {
            [$y_coor, $dashXcoordinate, $textXcoordinate, $longestString] = self::labelYaxis($scaleIteration, $xTickIteration, $prev_x, $range, $longestString);
            self::tickYaxis($scaleIteration, $xTickIteration, $y_coor);
        }

        $lastLongestString = $longestString;
        $prev_x = $textXcoordinate + ($lastLongestString * self::$letterWidth) + 10 + self::$letterHeight;

        self::drawYaxis($dashXcoordinate);
        return $prev_x;
    }

    /**
     * Draws Y-axis lines across graph
     * @param $scaleIteration
     * @param $xTickIteration
     * @param $yCoordinate
     */
    static function tickYaxis($scaleIteration, $xTickIteration, $yCoordinate) {
        if ($scaleIteration == 0) {

            if ($xTickIteration == 6) {
                $diff_btw_points = 0;
            } else {

                // Outputs main lines, and adjusts thickness
                imagesetthickness(self::$image, 3);
                imageline(self::$image, self::$graphStartX, $yCoordinate, self::$graphEndX, $yCoordinate, self::$colors['light']);
                imagesetthickness(self::$image, 1);
                $diff_btw_points = (self::$ySpacingPixels * $xTickIteration) - (self::$ySpacingPixels * ($xTickIteration + 1));
            }

            // Outputs little lines between main lines
            $yAxisDistanceFactor = $diff_btw_points / 5;
            for ($yAxisSubTickIteration = 1; $yAxisSubTickIteration < 5; $yAxisSubTickIteration++) {
                imageline(self::$image, self::$graphStartX, $yCoordinate - ($yAxisDistanceFactor * $yAxisSubTickIteration), self::$graphEndX, $yCoordinate - ($yAxisDistanceFactor * $yAxisSubTickIteration), self::$colors['dark']);
            }
        }
    }

    /**
     * Outputs Y labels and plot name (on last plot for scale) on y axis
     * @param $scaleIteration
     * @param $xTickIteration
     * @param $previousX
     * @param $range
     * @param $longestString
     * @return array
     */
    static function labelYaxis($scaleIteration, $xTickIteration, $previousX, $range, $longestString) {
        $yspacing = ($range / 6);

        $yCoordinate = (self::$ySpacingPixels * $xTickIteration) + self::$graphStartY;
        $yTickLabel = round(($yspacing * (6 - $xTickIteration)) + self::$settings['min_scale_value'][$scaleIteration], 2);
        $yTickLabel = DataManipulator::simplifyValue($yTickLabel);
        $longestString = (isset($longestString) && $longestString > strlen($yTickLabel) ? $longestString : strlen($yTickLabel));

        if ($scaleIteration == 0) {
            $textXcoordinate = self::$graphStartX - 6 - (self::$letterWidth * strlen($yTickLabel));
            $dashXcoordinate = self::$graphStartX - 4;
        } else {
            if ($scaleIteration == 1) {
                $textXcoordinate = self::$graphEndX + 6;
                $dashXcoordinate = self::$graphEndX - 4;
            } else {
                $textXcoordinate = $previousX + 6;
                $dashXcoordinate = $previousX - 4;
            }

            // On last tick, outputs y-axis scale name
            if ($xTickIteration == 6) {
                $scaleNameXCoordinate = $textXcoordinate + ($longestString * self::$letterWidth);
                $tickY = (self::$renderSpaceY / 2) + self::$graphStartY - (strlen(self::$scaleNames[$scaleIteration]) * (self::$letterWidth / 2));
                imagettftext(self::$image, 12, 270, $scaleNameXCoordinate, $tickY, self::$colors['outlineAndFont'], self::$font, self::$scaleNames[$scaleIteration]);
            }
        }

        // outputs Y tick value
        imagettftext(self::$image, 12, 0, $textXcoordinate, $yCoordinate + (self::$letterHeight / 2), self::$colors['outlineAndFont'], self::$font, $yTickLabel);
        return [$yCoordinate, $dashXcoordinate, $textXcoordinate, $longestString];
    }

    /**
     * Plots each scale, and the given plots for that scale
     * @param $scaleData
     * @param $plotNames
     * @param $scaleIteration
     * @param $xAxisSpacing
     * @param $range
     */
    static function plotScale($scaleData, $plotNames, $scaleIteration, $xAxisSpacing, $range) {
        $timeModulus = 0;
        $plotCnt = count($scaleData);
        $xAxisTickSpacing = self::$renderSpaceX / (self::$graphData['count'] - 1);

        $plotIteration = 0;
        foreach($plotNames as $plotName) {
            if(in_array($plotName, ['min', 'max'])) continue;

            $plotPointCount = count($scaleData[$plotName]);
            for ($pointIteration = 0; $pointIteration < $plotPointCount; $pointIteration++) {

                if ($scaleIteration == 0 && $pointIteration == 0) {
                    self::renderDeadbands($scaleIteration, $range);
                }

                if ($scaleIteration == 0 && $pointIteration == 2) {
                    if (self::$maxNumberOfPointsToPlot > 1 && self::$settings['x_axis_text'] != "off") {
                        [$timeModulus, $longest_x_text] = self::labelXaxis($xAxisSpacing, $pointIteration, $timeModulus, ($longest_x_text ?? null));
                    }
                }

                // Calculates coordinates and draws line
                $x_coor = ($xAxisTickSpacing * $pointIteration) + self::$graphStartX;
                $y_coor = Graph::calculateY($scaleData[$plotName][$pointIteration], $scaleIteration, $range);

                // If there's a data point after the current, draws line to it
                if (isset($scaleData[$plotName][$pointIteration + 1])) {
                    $x_coor2 = ($xAxisTickSpacing * ($pointIteration + 1)) + self::$graphStartX;
                    $y_coor2 = Graph::calculateY($scaleData[$plotName][$pointIteration + 1], $scaleIteration, $range);
                    imageline(self::$image, $x_coor, $y_coor, $x_coor2, $y_coor2, self::$colors['plots'][$scaleIteration][$plotIteration]);
                }
//                    echo "COORDINATES:: x1: $x_coor, x2: $x_coor2, y1: $y_coor, y2: $y_coor2\n";

                self::createLegendEntry($scaleIteration, $pointIteration, $plotNames[$plotIteration]);
            }
            $plotIteration++;
        }

    }

    /**
     * Labels X axis as needed, returns the modulus used for ticks (how often they were labeled), and the longest label
     * @param $xSpacing
     * @param $iteration
     * @param $timeModulus
     * @param $longest_x_text
     * @return array
     */
    static function labelXaxis($xSpacing, $iteration, $timeModulus, $longest_x_text) {
        $x_tick_text = self::$graphData['data']["value_data"]["data"][$iteration];
        $x_coor = self::$graphStartX + (self::$letterHeight / 2) + 1 + ($xSpacing);
        $y_coor = self::$graphEndY + (strlen($x_tick_text) * self::$letterWidth);
        $longest_x_text = (isset($longest_x_text) && $longest_x_text > strlen($x_tick_text) ? $longest_x_text : strlen($x_tick_text));
        if ($timeModulus % self::$settings['ts_interval'] == 0) {
            imagettftext(self::$image, 11, 90, $x_coor, $y_coor, self::$colors['outlineAndFont'], self::$font, $x_tick_text);
        }
        //---------------------DRAWS X AXIS TICK MARKS--------------------------------------
        imageline(self::$image, $x_coor - (self::$letterHeight / 2) - 1, self::$graphEndY - 4, $x_coor - (self::$letterHeight / 2) - 1, self::$graphEndY + 4, self::$colors['outlineAndFont']);
        $timeModulus++;

        return [$timeModulus, $longest_x_text];
    }

    /**
     * Generates filename and saves
     * @return bool
     */
    static function saveImageResource() {
        $microTime = str_replace(".", "", microtime(true));
        $ipNum = floatval(str_replace([".", ":"], "", $_SERVER['REMOTE_ADDR']));
        $randomness = rand(10000, 1000000);
        $randomString = $ipNum . $randomness . $microTime;
        $file_name = 'generated_images' . DIRECTORY_SEPARATOR . str_replace("=", "", base64_encode($randomString)) . ".png";

        if(self::$testingMode) {
            header("Content-type: image/png");
            imagepng(self::$image);
        } else {
            $created = imagepng(self::$image, Main::$basePath . DIRECTORY_SEPARATOR . $file_name);
            return($created ? $file_name: false);
        }
    }
}
