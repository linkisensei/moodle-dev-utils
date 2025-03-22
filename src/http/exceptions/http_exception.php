<?php namespace linkisensei\moodle_dev_utils\http\exceptions;

use \Exception;

class http_exception extends Exception {
    protected int $status = 500;
    protected array $debug = [];
    protected object $context;

    public function __construct(string $message, int $status = 500, ?\Throwable $previous = null){
        parent::__construct($message, $status, $previous);
        $this->set_status_code($status);
    }

    public function set_status_code(int $status) : static {
        $this->status = $status;
        return $this;
    }

    public function get_status_code(): int {
        return $this->status;
    }

    public static function from_exception(Exception $ex) : static {
        return new static($ex->getMessage(), 500, $ex->getPrevious());
    }

    public function set_context(object|array $context) : static {
        $this->context = (object) $context;
        return $this;
    }

    public function get_context() : object {
        return $this->context;
    }

    public static function new(...$args) : static {
        return new static(...$args);
    }
}