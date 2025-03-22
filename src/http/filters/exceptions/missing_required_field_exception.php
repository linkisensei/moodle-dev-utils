<?php namespace linkisensei\moodle_dev_utils\http\filters\exception;

class missing_required_field_exception extends condition_exception {
    public function __construct(string $message = "Missing required field", int $status = 400, ?\Throwable $previous = null){
        parent::__construct($message, $status, $previous);
    }
}