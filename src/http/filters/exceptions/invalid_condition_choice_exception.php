<?php namespace linkisensei\moodle_dev_utils\http\filters\exception;

class invalid_condition_choice_exception extends condition_exception {
    public function __construct(string $message = "Invalid condition value", int $status = 400, ?\Throwable $previous = null){
        parent::__construct($message, $status, $previous);
    }
}