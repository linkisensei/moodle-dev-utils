<?php namespace linkisensei\moodle_dev_utils\http\filters\exception;

class forbidden_operator_exception extends operator_exception {
    
    public function __construct(string $message = "Operator not accepted by field", int $status = 400, ?\Throwable $previous = null){
        parent::__construct($message, $status, $previous);
    }
}