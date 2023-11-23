<?php

Class AppError {
    public static function reportError($message, $dumpObject = null) {
        $errorObject = [
            "error" => $message,
            "backtrace" => debug_backtrace(),
        ];
        // Adds dump ojbect if specified
        if(!is_null($dumpObject)) {
            $errorObject['dump'] = $dumpObject;
        }
        
        die(json_encode($errorObject));
    }
}