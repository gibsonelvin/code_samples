<?php
define('BASEPATH', realpath(__DIR__ . "/../"));

if(getenv('ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('ENVIRONMENT'));
} else {
    die("ENVIRONMENT not defined\n");
}

// Associate txt names with Numeric command
$commands = array(
    'sync' => 1,
    'reset' => 2,
    'rebuild' => 3,
    'rebase' => 4,
    'compress' => 5,
    'import' => 6
);

// Get command text names
$command_txt = array_keys($commands);

// Executes actions
if(isset($argv[1])) {
    handleArg($argv[1]);
} else {
    // ** CURRENTLY IT'S SET TO EXIT ON getCommand, as changed by a team member, but it used to present commands, and recursively call itself until a command was resolved ** //
    $command = getCommand();
    handleArg($command);
}

/**
 * @param $arg
 */
function handleArg($arg) {
    global $commands;

    // If numeric command passed, ensures it's a command, and sets it to the command
    if(is_numeric($arg) && in_array($arg, array_values($commands))) {
        $command = $arg;
    // If it isn't numeric, makes sure it's a real command, and sets it to the proper numeric command
    } elseif(!is_numeric($arg) && isset($commands[$arg])) {
        $command = $commands[$arg];
    // If the command isn't recognized, notifies the user, and prompts for new command
    } else {
        echo "\n\tUnrecognized command:\t'" . $arg . "'\n";
        $command = getCommand();
        handleArg($command);
    }

    // Executes command, if command is rebase, confirms first
    if($command==Build::COMMAND_REBASE) {
        confirmRebase();
    }
    new Build($command);
    exit;
}

/**
 * @return int|string
 */
function getCommand() {
    global $command_txt;

    // Prompts the user for an action
    echo "\nUsage:\tphp db/Build.php [command]\n"
        ."\tphp db/Build.php  import   [collection]\n"
        . "\n\t" . $command_txt[0] . "\t\tSynchronize alters."
        . "\n\t" . $command_txt[5] . "\t\tImport fixtures. Must define collection. Append ' -v' for Verbose output."
        . "\n"
        . "\n\t" . $command_txt[1] . "\t\tRecreate database and DON'T synchronize alters."
        . "\n\t" . $command_txt[2] . "\t\tRecreate database and synchronize alters."
        . "\n"
        . "\n\t" . $command_txt[3] . "\t\tRebase alter files, resetting the alter index."
        . "\n\t" . $command_txt[4] . "\tCompress DB base schema and alters > Schema.zip\n\n";

    exit;
}

/**
 * @return bool
 */
function confirmRebase() {
    echo <<<END

WARNING: Rebasing alter files will permanently delete all alter files.

Are you SURE you want to perform this action? (Y/N)

END;

    $confirm = stream_get_line(STDIN, null, PHP_EOL);
    if(strtolower($confirm)!="y") {
        exit;
    }
    return true;
}

/**
 * Class Build
 * Performs DB actions based on prompt
 */
class Build {

    // Defines Command Constants
    const COMMAND_SYNC = 1;
    const COMMAND_RESET = 2;
    const COMMAND_REBUILD = 3;
    const COMMAND_REBASE = 4;
    const COMMAND_COMPRESS = 5;
    const COMMAND_IMPORT = 6;

    // Db credentials [HOST]
    public $dbhost;

    // Db credentials [USER]
    public $dbuser;

    // Db credentials [PASS]
    public $dbpass;

    // Db credentials [DATABASE]
    public $database;

    // Db connection object
    public $conn;

    // Declares path to alters
    public $alters_path;

    // Base schema file
    public $base_schema = "000_BASE_SCHEMA.sql";

    // Consolidate Alters after sync
    public $consolidate_alters = false;

    // Array of file names to skip during sync
    public $skip_files = array(
        ".",
        "..",
        "readme.txt",
        ".DS_Store"
    );

    public function __construct($command = self::COMMAND_SYNC) {

        $this->alters_path = BASEPATH . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . "alters";

        // Includes db creds file, as well as determines proper credentials
        require_once(BASEPATH . "/application/config/database.php");

        // Sets DB Host, with possible ENV override
        $this->dbhost = isset($_ENV['FG_DB_HOST'])
            ? $_ENV['FG_DB_HOST']
            : $db['default']['hostname'];

        // Sets DB User, with possible ENV override
        $this->dbuser = isset($_ENV['FG_DB_USERNAME'])
            ? $_ENV['FG_DB_USERNAME']
            : $db['default']['username'];

        // Sets DB Pass, with possible ENV override
        $this->dbpass = isset($_ENV['FG_DB_PASSWORD'])
            ? $_ENV['FG_DB_PASSWORD']
            : $db['default']['password'];

        // Sets DB Name, with possible ENV override
        $this->database = isset($_ENV['FG_DB_DATABASE'])
            ? $_ENV['FG_DB_DATABASE']
            : $db['default']['database'];

        // Establishes DB connection
        $this->conn = new \Mysqli(
            $this->dbhost,
            $this->dbuser,
            $this->dbpass
        );

        // Handles Command
        if($command==self::COMMAND_SYNC) {

            $this->conn->select_db($this->database);
            echo "\nSynchronizing...\n\n";
            $this->synchronizeAlters();

        } elseif($command==self::COMMAND_RESET) {

            $this->recreateDB();
            echo "\nApplying base schema...";
            $this->applyBaseSchema();
            echo "\n\nDatabase reset to base schema!\n\n";

        } elseif($command==self::COMMAND_REBUILD) {

            echo "\nRebuilding Database....\n";
            $this->recreateDB();
            echo "\nApplying base schema...";
            $this->applyBaseSchema();
            echo "\nSynchronizing...\n\n";
            $this->synchronizeAlters();
            echo "\n\nDatabase has been rebuilt!\n\n";

        } elseif($command==self::COMMAND_REBASE) {

            echo "\nStarting alter schema rebase...\n\n";
            $this->consolidate_alters = true;
            echo "\nRebuilding Database....\n";
            $this->recreateDB();
            echo "\nApplying base schema...";
            $this->applyBaseSchema();
            echo "\nSynchronizing...\n\n";
            $this->synchronizeAlters();

        } elseif($command==self::COMMAND_COMPRESS) {

            // Notifies user of process start, plus builds file list
            echo "\nStarting Schema/Alter Compression...\n\n";
            $this->compressAlters();

        } elseif($command==self::COMMAND_IMPORT) {

            $this->importFixtures();

        } else {

            echo "\nFATAL ERROR: Unknown Command: " . $command . "\n\n";
            die();

        }
    }

    /**
     * Prompts for collection, then executes command to build the collection
     */
    public function importFixtures() {
        global $argv;

        // Sets Process parameters
        if(isset($argv[2])) {
            $collection = $argv[2];
        } else {
            $collection = $this->getCollection();
        }

        $handles = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('file', BASEPATH . '/db/sync.log', 'a')
        );

        // Executes insert command
        echo "\n\nImporting Fixtures\n";
        $process = proc_open('php index.php db import ' . $collection . ' && exit', $handles, $pipes, BASEPATH);

        // If command executed, show output, and on completion notify user
        if(is_resource($process)) {

            // Outputs loading indicator for command
            $i = 0;
            while($data = fgets($pipes[1])) {
                if(isset($argv[3]) && $argv[3]=="-v") {
                    echo $data;
                } else {
                    echo ($i%45==0 && $i>0 ? "\n": "") . ".";
                    $i++;
                }
            }
            echo "\n\n";

            // Closes resource handles as well as process execution
            fclose($pipes[0]);
            fclose($pipes[1]);
            $returnValue = proc_close($process);

            // If command successful, notifies user, and exits
            if($returnValue==0) {
                echo "Import complete!\n\n";
            } else {
                echo "\n\nERROR, See output above.";
            }
        } else {
            // If the command couldn't be executed, notifies user
            echo "\nCOMMAND EXECUTION ERROR! Could not build collection!\n\n";
        }
        exit;
    }

    /**
     * Prompts the user for the collection, and returns the proper name
     * @return string
     */
    public function getCollection() {
        // Gets array of collections & count
        $collections = $this->getCollectionList();
        $collection_cnt = count($collections);

        // Output user prompt for collections
        echo "\n\tError: Missing collection <";
        for($i=0;$i<$collection_cnt;$i++) {
            echo $collections[$i] . ",";
        }

        echo "...>\n\n";

        //show help then exit
        getCommand();
        exit;
    }

    /**
     * Returns the list of collections in the application/libraries/Fixtures/Collections Directory
     * @return array
     */
    public function getCollectionList() {
        $collections = array();
        foreach(glob(BASEPATH . "/application/libraries/Fixtures/Collections/*.php") as $collection) {
            preg_match("#/(?P<name>\w+)\.php#", $collection, $matches);
            $collections[] = $matches['name'];

        }
        return $collections;
    }

    /**
     * Compresses Alter files, and outputs /db/Schema.zip file
     */
    public function compressAlters() {
        list($files, ) = $this->buildFileList();
        // Adds base schema to file list
        array_unshift($files, $this->base_schema);
        // Creates new zip archive "Schema.zip"
        $zip = new ZipArchive();
        if(!$zip->open(BASEPATH . "/db/Schema.zip", ZipArchive::CREATE)) {
            echo "\n\nFatal Compression Error: .zip file cannot be created.\n\n";
            die();
        }
        // Foreach file, adds to archive
        foreach($files as $file) {

            echo "\nAdding '";
            if(strlen($file)>20) {
                echo substr($file, 0, 16) . "...";
            } else {
                echo $file;
            }
            echo "' to archive\t\t";

            if($zip->addFile($this->alters_path . DIRECTORY_SEPARATOR . $file, $file)) {
                echo "[OK]";
            } else {
                echo "[ERROR]\n\nFatal Compression Error: '" . $file . "' couldn't be added to archive.\n\n";
                die();
            }
        }
        echo "\n\nAll files added to archive!\n\n";
        // Closes Zip file, and notifies user of success
        $zip->close();
        echo "\nArchive saved as 'Schema.zip' with all alters including base!\n\n";
    }

    /**
     * Recreates DB
     * @return bool
     */
    public function recreateDB() {
        echo "\nDropping database...";
        $query = "DROP DATABASE IF EXISTS `" . $this->database . "`";
        if($this->conn->query($query)) {
            echo "\t\t[OK]";
            echo "\nRecreating database...";
            $query2 = "CREATE DATABASE `" . $this->database . "`";
            if($this->conn->query($query2)) {
                echo "\t\t[OK]";
                $query3 = "USE `" . $this->database . "`";
                if(!$this->conn->query($query3)) {
                    die("Couldn't access the recreated database: " . $this->conn->error);
                }
            } else {
                die("Couldn't create the database: " . $this->conn->error);
            }
        } else {
            die("Couldn't drop the database: " . $this->conn->error);
        }
        return true;
    }

    /**
     * Synchronizes alters
     */
    public function synchronizeAlters() {
        // Builds file list, and filters applied alters
        list($files, $file_ids) = $this->buildFileList();
        echo "Changes available:\n\t" . $this->formatNumbers($file_ids) . "\n";
        $this->filterAppliedIDs($file_ids, $files);

        // If all change scripts have been applied, exits
        if(empty($file_ids) && $this->consolidate_alters==false) {
            echo "\nNo changes to apply.\n";
            echo "\n\nSync Complete!\n\n";
            exit;
        } else {
            echo "\nChanges to apply:\n\t" . $this->formatNumbers($file_ids) . "\n";
            // Loops through each remaining file, and creates a dump for them:
            $file_cnt = count($file_ids);
            $file_i = 1;
            foreach($file_ids as $key => $file_id) {
                echo "\nApplying changes for file: " . $file_i . " of " . $file_cnt;
                $this->applySchema($file_id, $files[$key]);
                $file_i++;
            }
            if($this->consolidate_alters==true) {
                $this->consolidateAlters();
            }
            echo "\n\nSync Complete!\n\n";
        }
        exit;
    }

    /**
     * @return void
     */
    public function consolidateAlters() {
        $this->dumpSchema();
        $this->clearAlterList();
        $this->recreateDB();
        echo "\nApplying NEW Schema...";
        $this->applyBaseSchema();
        $this->consolidate_alters = false;
        $this->synchronizeAlters();
        // Previous line ends execution, no return value
    }

    /**
     * @return bool
     */
    public function dumpSchema() {
        echo "\n\nDumping updated schema to base schema...";
        $command = "mysqldump -u " . $this->dbuser . " -h " . $this->dbhost
            . " --password=" . $this->dbpass
            . " --no-data " . $this->database . " > " . $this->alters_path . "/" . $this->base_schema . " 2>" . BASEPATH . "/db/sync.log";
        system($command, $response);
        if($response==0) {

            // Removes definer attributes from Create statements
            $contents = preg_replace("#/\*!\d+\sDEFINER=`\w+`@`\w+`\*/#", "",
                file_get_contents($this->alters_path . "/" . $this->base_schema));

            // Saves schema dump w/o definer attributes
            $fh = fopen($this->alters_path . "/" . $this->base_schema, "w");
            fwrite($fh, $contents) or die("Fatal Build Error: Couldn't update base schema file!");
            fclose($fh);

            echo "\t[OK]\n";
        } else {
            die("\nFatal Build Error: Couldn't dump schema to base file.\n\n");
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clearAlterList() {
        echo "\n\nClearing Alter list...\n\n";

        // Gathers alter file list, then deletes files
        list($files, $file_ids) = $this->buildFileList();
        foreach($files as $key => $file) {
            if(!unlink($this->alters_path . "/" . $file)) {
                die("Fatal error: Couldn't Clear Alter List.");
            }
            echo "\nRemoving alter file #" . $file_ids[$key] . "\t\t[OK]";
        }

        // Removes entries from changelog table
        $query = "TRUNCATE `changelog`";
        if(!$this->conn->query($query)) {
            die("Fatal Build Error: Couldn't delete changelog entries.");
        }

        echo "\n\nAlter list successfully cleared!\n\n";
        return true;
    }

    /**
     * Returns array of fileIDs, and Files
     * @return array
     */
    public function buildFileList() {
        // Array for file names
        $files = array();

        // Array for file ids
        $file_ids = array();

        // Builds the two arrays above based of files, dying on duplicate IDs, & Alerting unrecognized files
        foreach(glob($this->alters_path . DIRECTORY_SEPARATOR . "*.sql") as $file) {
            // Cleans up file name, removing full path
            $file = substr($file, (strrpos($file, DIRECTORY_SEPARATOR)+1));
            // Skips skip files
            if(in_array($file, $this->skip_files)) {
                continue;
            }

            $file_id = $this->getNumericID($file);
            // Never includes base schema in file list
            if(intval($file_id)==0) {
                unset($file_id);
                continue;
            }

            // If the Id couldn't be determined, Notifies user it's being skipped
            if(is_null($file_id)) {
                echo "Invalid ID for alter file: '" . $file . "'\tSkipping...\n\n";
                continue;
            }

            // If the parsed ID is already gathered, throws fatal build error (Duplicate ID)
            if(in_array($file_id, $file_ids)) {
                die("Fatal Build Error: Duplicate change number for id: " . $file_id);
            }

            // Updates arrays files & file_ids
            $files[] = $file;
            $file_ids[] = $file_id;

            // Unsets the file_id
            unset($file_id);
        }
        return array($files, $file_ids);
    }

    /**
     * Removes file names and ids from arrays if applied
     * @param $file_ids
     * @param $files
     */
    public function filterAppliedIDs(&$file_ids, &$files) {
        /** @var $x_query \Mysqli_result */

        // Builds select query for changes applied
        $query = "SELECT change_number FROM changelog WHERE change_number IN ('" . implode("', '", $file_ids) . "')";

        // Executes the query, and returns error on failure
        $x_query= $this->conn->query($query)
            or die("Fatal Build Error, cannot check changelog status: " . $this->conn->error);

        // If Records returned remove all applied IDs from file_ids array
        if($x_query->num_rows>0) {
            while(list($id) = $x_query->fetch_row()) {

                // Looks for applied script ID in IDs from file names
                $idLoc = array_search($id, $file_ids);

                // If match found, removes the file entries
                if($idLoc!==FALSE) {
                    unset($file_ids[$idLoc], $files[$idLoc]);
                }
            }
        }
        return;
    }

    /**
     * Applies base schema file
     */
    public function applyBaseSchema() {
        $this->applySchema(0, $this->base_schema, false);
        echo "\n\n";
        return true;
    }

    /**
     * @param $file_id
     * @param $file
     * @param bool $log
     * @return bool
     */
    public function applySchema($file_id, $file, $log = true) {
        // Builds command to dump the alter file
        $command = "mysql -u " . $this->dbuser . " -h " . $this->dbhost
            . " --password=" . $this->dbpass
            . " " . $this->database . " < " . $this->alters_path . "/" . $file . " 2>" . BASEPATH . "/db/sync.log";

        // Executes command, and if error, dies and outputs details, otherwise prints OK
        system($command, $response);
        if($response==0) {
            if($log==true) {
                $this->logChange($file_id, $file);
            }
            echo "\t\t[OK]";
            return true;
        } else {
            echo "\t\t[ERROR]\n"
                . "\n\nERROR DETAILS:\n"
                . "\n***********************************\n\n"
                . file_get_contents(BASEPATH . "/db/sync.log")
                . "\nError details saved in \"" . BASEPATH . "/db/sync.log\"" . "\n\n"
                . "Please fix the SQL in \"" . $file . "\":\n\n"
                . file_get_contents($this->alters_path . "/" . $file)
                . "\n\n***********************************\n"
                . "\n\nSync Failed!\n\n";
            die();
        }
    }

    /**
     * @param $id
     * @param $desc
     * @return bool
     */
    public function logChange($id, $desc) {

        // Current date formatted for DB
        $date = date_create()->format("Y-m-d H:i:s");

        // Update later to be dynamic ??
        $user = "ci_phoenix";

        // Builds query
        $query = sprintf("INSERT INTO changelog VALUES('%d', '{$date}', '{$user}', '%s')", $id, $desc);

        // Throws fatal error if couldn't log query to changelog table
        if(!$this->conn->query($query)) {
            die("\n\nFatal build error, log query failed: " . $query
                . "\n\nCouldn't log change to db w/ error: " . $this->conn->error);
        }

        return true;
    }

    /**
     * @param array $numbers
     * @return string
     */
    public function formatNumbers($numbers = array()) {
        $i = 0;
        $lastRangeBreak = 0;
        $output = "";

        // Foreach number, checks if it's sequential to the last, if not, returns a range to output
        foreach($numbers as $number) {
            if(isset($lastNumber) && $lastNumber!=($number-1)) {
                $output .= ($output=="" ? "": ", ") . $this->returnRange(array_slice($numbers, $lastRangeBreak, ($i-$lastRangeBreak)));
                $lastRangeBreak = $i;
            }

            // If the last number in the range, calls return range again as the next in the sequence WOULD
            if($i == (count($numbers)-1)) {
                $output .= ($output=="" ? "": ", ") . $this->returnRange(array_slice($numbers, $lastRangeBreak, (($i+1)-$lastRangeBreak)));
            }

            // Sets last number for sequence checking
            $lastNumber = $number;
            $i++;
        }
        return $output;
    }

    /**
     * Returns "FROM# .. TO#" on a sequential array
     * @param $sequentialArray
     * @return string
     */
    public function returnRange($sequentialArray) {
        return (
            count($sequentialArray)==1
            ? $sequentialArray[0]
            : $sequentialArray[0] . ".." . $sequentialArray[count($sequentialArray)-1]
        );
    }

    /**
     * Matches digits at the beginning of the string, and returns null if not found
     * @param $str
     * @return int|null
     */
    public function getNumericID($str) {
        return (preg_match("@^[\d]+@", $str, $matches)===1 ? intval($matches[0]): null);
    }
}