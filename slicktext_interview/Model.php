<?php

Class Model {

    private $db;

    // Methods prefexed with (get|set) that are to be ignored by "magic" __call method
    private $setPrefixedMethods = [];
    private $getPrefixedMethods = ['getDB'];

    public function __construct() {
    }

    /**
     * Magic method for handling calls go getters and setters of properties
     */
    public function __call($method, $params) {
        if(
            preg_match("@^get(?P<camel_name>\w+)$@", $method, $matches)
            && !in_array($method, $this->getPrefixedMethods)
         ) {

            $propName = $this->getSnakeCaseName($matches['camel_name']);
            if(property_exists($this, $propName)) {
                if(!empty($params)) {
                    [$forDB] = $params;
                    // Returns prepped for database to avoid SQL injection, if specified with getter
                    return ( $forDB
                        ? addslashes(htmlentities($this->{$propName}))
                        : $this->{$propName}
                    );
                } else {
                    return $this->{$propName};
                }
            } else {
                AppError::reportError("Invalid property getter called: $method -- See backtrace attached to request.", $this);
            }

        } else if(
            preg_match("@^set(?P<camel_name>\w+)$@", $method, $matches)
            && !in_array($method, $this->setPrefixedMethods)
         ) {

            $propName = $this->getSnakeCaseName($matches['camel_name']);
            if(!empty($params)) {
                [$value] = $params;

                // Currently all fields are required --- A value can be added to the properties matrix to turn on and off required fields if needed
                if(!empty($value) && $this->validateValueAndProperty($propName, $value)) {
                    $this->{$propName} = $value;
                    return $this;
                } else {
                    AppError::reportError("Invalid value \"$value\" for " . $this->translateShortName($propName, true) . ".", ['field' => $propName]);
                }
            } else {
                AppError::reportError("Setter called with no value, see attached backtrace.");
            }
        }
    }
    
    public static function outputHeaderRow($data) {
        echo "<tr>";
        $column_i = 0;
        foreach($data as $field => $dataBit) {
            $field = join(" ", array_map("ucfirst", explode("_", $field)));
            echo "<th class='column_head column" . $column_i . "'>" . $field . "</th>";
            $column_i++;
        }
    
        echo "<th>&nbsp;</th><th>&nbsp;</th></tr>";
    }

    protected function getGetterName($property) {
        $formattedName = $this->translateShortName($property);
        return "get" . $formattedName;
    }

    protected function getSetterName($property) {
        $formattedName = $this->translateShortName($property);
        return "set" . $formattedName;
    }

    public function dumpProperties() {
        $returnArray = [];
        foreach($this->properties as $property => $type) {
            $getter = $this->getGetterName($property);
            $returnArray[$property] = $this->{$getter}();
        }
        return $returnArray;
    }

    public function validateValueAndProperty($property, $value) {
        if(isset($this->properties[$property])) {
            switch($this->properties[$property]) {
                case 'int':
                    return (is_numeric($value));
                    break;
                case 'string':
                    return (is_string($value));
                    break;
                case 'date':
                    return (preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $value));
                    break;
                case 'email':
                    return (preg_match("/^[A-Za-z0-9\.]+@[A-Za-z0-9\.]+\.[A-Za-z0-9\.]{2,3}$/", $value));
                    break;
                case 'phone':
                    return ($value > 2000000000 && $value < 9999999999);
                    break;
                default:
                    AppError::reportError("FATAL ERROR: Unhandled data type for property $property, and value: $value!");
            }
        } else {
            AppError::reportError("Invalid property set, see attached backtrace.", ['property' => $property, 'properties' => $this->properties]);
        }
    }

    public function load($data) {
        foreach($data as $key => $value) {
            $setter = $this->getSetterName($key);
            // Placed in variable if needed for debugging
            $evaluationLine = "\$this->" . $setter . "(\$value);";
            eval($evaluationLine);
        }
        return $this;
    }

    public function loadById($id) {
        $this->getDB();
        $query = "SELECT * FROM " . $this->table_name . " WHERE id=" . $id;
        $x_query = $this->db->query($query) or AppError::reportError($this->db->error);
        return $this->load($x_query->fetch_assoc());
    }

    public function getDB() {
        $this->db = $this->db
            ?? new mysqli(
                    DB::$dbhost,
                    DB::$dbuser,
                    DB::$dbpass,
                    DB::$dbname
                )
                or AppError::reportError("Fatal error, unable to connect to DB");
    }

    public function translateShortName($shortName, $spaces = false) {
        return join(($spaces ? " ": ""), array_map("ucfirst", explode("_", $shortName)));
    }

    public function getSnakeCaseName($name) {
        return join("_", array_map("strtolower", preg_split("@(?<=[[:lower:]]{1})(?=[[:upper:]]{1})@", $name)));
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name;
        $this->getDB();
        $x_query = $this->db->query($query);
        if(!$x_query) {
            AppError::reportError("FATAL ERROR: Query misconfiguration: " . $this->db->error);
        }
        return $x_query;
    }

    public function save() {
        $this->getDB();
        // If saving a record which exists, updates, otherwise inserts
        if(property_exists($this, 'id') && !is_null($this->id)) {
            $query = "UPDATE " . $this->table_name . " set";
            $property_i = 0;
            foreach($this->properties as $property => $type) {
                
                // Excludes non-form properties
                if(!in_array($property, ['id', 'created', 'last_updated'])) {
                    $getter = $this->getGetterName($property);
                    $query .= ($property_i === 0 ? ' ': ', ') . $property . '="' . $this->{$getter}(true) . '"';
                    $property_i++;
                }
            }
            $query .= ", last_updated='" . date("Y-m-d H:i:s") . "' WHERE id=" . $this->getId();

            $this->db->query($query) or AppError::reportError($this->db->error);
            $this->loadById($this->id);

        } else {
            $query = "INSERT INTO " . $this->table_name . " VALUES(null";
            foreach($this->properties as $property => $type) {

                // Excludes non-form properties
                if(!in_array($property, ['id', 'created', 'last_updated'])) {
                    $getter = $this->getGetterName($property);
                    $query .= ", \"" . $this->{$getter}(true) . "\"";
                }
            }
            // Appends null values for created and last updated (updated automatically by db)
            $query .= ", '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "')";

            $this->db->query($query) or AppError::reportError($this->db->error);
            $this->loadById($this->db->insert_id);
        }
    }

    public function delete() {
        if(property_exists($this, 'id') && !is_null($this->id)) {
            $this->getDB();
            $query = "DELETE FROM " . $this->table_name . " WHERE id=" . $this->id;
            $this->db->query($query) or AppError::reportError($this->db->error);
        } else {
            AppError::reportError("Unable to delete record if object doesn't have an ID (been saved).");
        }
    }
}