<?php
/**
 * Created by PhpStorm.
 * User: egibson
 * Date: 3/12/14
 * Time: 1:17 PM
 */

define('BASEPATH', __DIR__ . "/../");

if(!isset($argv[1])) {
    die("\nPlease specify a service to convert.\n\n
        Usage: convertService.php Service [service|view|controller] [? Parent Controller]\n\n");
}

new Convert($argv[1]);


class Convert {

    /**
     * Holds the regular expression for parsing static function calls
     * @var string $regex
     */
    protected  $regex;

    /**
     * Holds the converting service
     * @var string $service
     */
    protected $service;

    /**
     * Holds services to not inject
     * @var array $doNotInject
     */
    protected $doNotInject;

    /**
     * Holds all the use declarations [class] => Ref_Name
     * @var $uses array
     */
    protected $uses;

    /**
     * Hold regex for finding class declarations
     * @var $classDeclarationRegex string
     */
    protected $classDeclarationRegex;

    /**
     * Holds service methods/namespaces to inject
     * @var array $injectables
     */
    protected $injectables;

    /**
     * @param $service
     */
    public function __construct($service) {
        // Holds the service being called
        $this->service = $service;

        // List of services to not inject
        $this->doNotInject = array("parent", "base", "self", "container", strtolower($this->service));

        // List of services/namespaces to inject
        $this->injectables = array("fg\\service", "doctrine", "codeigniter");

        // Regex for finding functions
        $this->regex = "/(?P<service>[\\A-z_]+?)"
            . "(?P<separator>[:]{1}[:]{1})"
            . "(?P<call>[\\\$A-z_0-9]+\(.*\))/";

        // Sets Regex for finding class declarations
        $this->classDeclarationRegex = "@(class\s[^{]+\{)@";

        // Sets service variables as well as gets file contents
        $servicePath = $this->getServicePath();
        $serviceFileName = $servicePath . $service . ".php";
        $contents = $this->getServiceFile($serviceFileName);

        // Gets Use declarations
        $this->updateUses($contents);

        // Calls worker function
        $this->normalizeClass($contents, $serviceFileName);
    }

    /**
     * @param $contents
     * @param $filename
     */
    public function normalizeClass($contents, $filename) {
        $functionNames = array();
        preg_match_all($this->regex, $contents, $matches);
        $services = array_unique($matches['service']);

        $injectionBlock = "";
        $injectedServices = array();
        foreach($services as $serviceCall) {
            if(!$this->isService($serviceCall)) {
                continue;
            }

            $formattedName = $this->formatName($serviceCall);
            $lcName = strtolower($formattedName);
            if(!in_array($lcName, $this->doNotInject)) {
                $injectionBlock .= $this->buildInjectionBlock($serviceCall, $formattedName);
                $functionNames[$serviceCall] = "\$_" . lcfirst($formattedName) . "Service";
                $injectedServices[] = $serviceCall;
            } elseif($lcName=="doctrine") {
                $injectionBlock .= $this->buildInjectionBlock("\\Doctrine\\ORM\\EntityManager", "em", false);
                $functionNames[$serviceCall] = "\$_em";
            } elseif($lcName=="codeigniter") {
                $injectionBlock .= $this->buildInjectionBlock("\\CI_Controller", "ci", false);
                $functionNames[$serviceCall] = "\$_ci";
            }
        }

        // Matches each service public function call, and replaces it with it's injected variable
        $newFile = preg_replace_callback($this->regex, function($temps) use($functionNames) {
            // Variable Assignments
            list(, $service, , $call) = $temps;

            // Skips if not a service call
            if(!$this->isService($service)) {
                return $temps[0];
            }

            // Formats name
            $formattedName = $this->formatName($service);
            $call = substr($call, 0, 1)=="$" ? substr($call, 1): $call;

            // Returns new call
            $lcName = strtolower($formattedName);
            if(!in_array($lcName, $this->doNotInject)) {
                return "\$this->" . substr($functionNames[$service], 1) . "->" . $call;
            } elseif($lcName=="self" || $lcName==strtolower($this->service)) {
                return "\$this->" . $call;
            } elseif($lcName=="doctrine") {
                return "\$this->_em";
            } elseif($lcName=="codeigniter") {
                return "\$this->_ci";
            } else {
                return $temps[0];
            }
        }, $contents);

        $newFile = $this->insertInjectionBlock($injectionBlock, $newFile);
        $newFile = $this->convertStaticFunctionsToDynamic($newFile);

        // If the service was properly injected, notifies user it should also be converted
        if($this->saveService($filename, $newFile)) {
            echo "The following services were injected, ensure they're dynamic classes:\n"
                . implode("\n", $injectedServices)
                . "\n";
        }
    }

    public function isService($className) {
        $baseClass = $this->getBaseClass($className);
        if($baseClass) {
            // If base class isn't a true base class i.e. Used namespace
            if(!empty($this->uses) && in_array($baseClass, $this->uses)) {
                // $key[0] = true base class
                $key = array_keys($this->uses, $baseClass);
                return (in_array(strtolower($key[0]), $this->injectables) ? true: false);
            } else {
                return (in_array(strtolower($baseClass), $this->injectables) ? true: false);
            }
        } else {
            return false;
        }
    }

    /**
     * @param $contents
     */
    public function updateUses($contents) {
        // Grabs the pre-class definition code
        $fileParts = preg_split($this->classDeclarationRegex, $contents);
        $lines = explode("\n", $fileParts[0]);

        // Gets each line pre-class definition
        foreach($lines as $line) {
            if(substr($line, 0, 3)=="use") {
                // Gets all Use calls into an array
                $declarations = substr($line, 4);
                $uses = explode(", ", $declarations);

                // Removes ";" from the last use declaration
                foreach($uses as $use) {
                    if(substr($use, -1)==";") {
                        // cleans name
                        $cleanName = substr($use, 0, -1);

                        // Sets up uses array
                        $this->uses[$cleanName] = $this->formatName($cleanName);
                        continue;
                    }
                    // Sets up uses array
                    $this->uses[$use] = $this->formatName($use);
                }
            }
        }
    }

    /**
     * @param $className
     * @return bool|string
     */
    public function getBaseClass($className) {
        $classes = explode("\\", $className);
        // Sets base to the base class of the call
        if(isset($classes[0]) && $classes[0]!="") {
            $base = $classes[0];
            $baseOffset = 0;
        } elseif(isset($classes[1])) {
            $base = $classes[1];
            $baseOffset = 1;
        } else {
            $base = false;
        }

        // If the base is Fg, sets base to Fg\X
        if($base=="Fg") {
            $base .= (isset($classes[$baseOffset+1])
                ? "\\" . $classes[$baseOffset+1]
                : "");
        }
        return $base;
    }

    /**
     * @param $name
     * @return string
     */
    public function formatName($name) {
        // Formats name for variable assignment
        $formattedName = strrchr($name, "\\");
        if($formattedName) {
            $formattedName = substr($formattedName, 1);
        } else {
            $formattedName = $name;
        }
        return $formattedName;
    }

    /**
     * @param $injectedService
     * @param $varName
     * @param bool $service
     * @return string
     */
    public function buildInjectionBlock($injectedService, $varName, $service = true) {
        $slashRemoved = false;
    
        // Builds injection block string
        if(substr($injectedService, 0, 1)=="\\") {
            $injectedService = substr($injectedService, 1);
            $slashRemoved = true;
        }
    
        return "\t/**\n\t* @var " . ($slashRemoved==true ? "\\": "") . $injectedService . "\n\t* @Inject(\"" . $injectedService . "\")"
        . "\n\t*/\n\tprotected \$_" . lcfirst($varName) . ($service ? "Service": "") . ";\n\n";
    }

    /**
     * @return string
     */
    public function getServicePath() {
        $applicationPath = BASEPATH . "application" . DIRECTORY_SEPARATOR;

        // Sets file path based on command line argument
        if(isset($argv[2])) {
            switch($argv[2]) {
                case 'controller':
                    $servicePath = "controllers" . DIRECTORY_SEPARATOR . (isset($argv[3]) ? $argv[3] . DIRECTORY_SEPARATOR: "");
                    break;
                case 'view':
                    $servicePath = implode(DIRECTORY_SEPARATOR, array("libraries", "Fg", "Views", "Application", ""));
                    break;
                default:
                    $servicePath = implode(DIRECTORY_SEPARATOR, array("libraries", "Fg", "Service", ""));
                    break;
            }
        } else {
            $servicePath = implode(DIRECTORY_SEPARATOR, array("libraries", "Fg", "Service", ""));
        }
        return $applicationPath . $servicePath;
    }

    /**
     * @param $dir
     * @return array
     */
    public function getAllFiles($dir) {
        $filePaths = array();
        $dh = opendir($dir);
        while($file = readdir($dh)) {
            if($file=="." || $file=="..") continue;
            $filePaths[] = $dir . $file;
        }
        return $filePaths;
    }

    /**
     * @param $fileName
     * @return string
     */
    public function getServiceFile($fileName) {
        if(file_exists($fileName)) {
            return file_get_contents($fileName);
        } else {
            die("File does not exist: " . $fileName);
        }
    }

    /**
     * @param $file
     * @param $contents
     * @return int
     */
    public function saveService($file, $contents) {
        // Saved converted file
        $fh = fopen($file, "w+");
        $write = fwrite($fh, $contents);
        fclose($fh);
        return $write;
    }

    /**
     * @param $block
     * @param $contents
     * @return mixed
     */
    public function insertInjectionBlock($block, $contents) {
        return preg_replace(
            $this->classDeclarationRegex,
            "$0" . ($block=="" ? "": "\n" . $block),
            $contents);
    }

    /**
     * @param $contents
     * @return mixed
     */
    public function convertStaticFunctionsToDynamic($contents) {
        // Changes public function declarations from static to public
        return preg_replace(
            "@(public|static|public\sstatic|static\spublic)\sfunction@",
            "public function",
            $contents);
    }
}
