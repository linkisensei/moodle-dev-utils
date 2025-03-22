<?php namespace linkisensei\moodle_dev_utils\http\filters\exceptions;

class invalid_operator_exception extends operator_exception {
    
    public function __construct(string $message = "Invalid operator", int $status = 400, ?\Throwable $previous = null){
        parent::__construct($message, $status, $previous);
    }
}