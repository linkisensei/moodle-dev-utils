<?php namespace moodle_dev_utils\http\filters\interfaces;

use \Psr\Http\Message\ServerRequestInterface;

interface filter_interface {
    public static function from_request(ServerRequestInterface $request) : static;

    public function get_conditions() : mixed;
    
    public function get_parameters() : array;
}
