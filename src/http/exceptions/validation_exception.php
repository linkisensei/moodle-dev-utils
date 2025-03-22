<?php namespace moodle_dev_utils\http\exceptions;

class validation_exception extends http_exception {
    public function __construct(string $message, int $status = 422, ?\Throwable $previous = null){
        parent::__construct($message, $status, $previous);
    }
}