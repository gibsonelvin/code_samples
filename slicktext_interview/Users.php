<?php

Class Users extends Model {

    public $properties;

    public function __construct() {
        $this->table_name = 'users';
        $this->properties = [
            'id' => 'int',
            'first_name' => 'string',
            'last_name' => 'string',
            'email' => 'email',
            'mobile_number' => 'phone',
            'address' => 'string',
            'city' => 'string',
            'state' => 'string',
            'zip' => 'int',
            'country' => 'string',
            'timezone' => 'string',
            'created' => 'date',
            'last_updated' => 'date'
        ];

        parent::__construct();
    }
}