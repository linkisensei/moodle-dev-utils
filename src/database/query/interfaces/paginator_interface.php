<?php namespace linkisensei\moodle_dev_utils\database\query\interfaces;

interface paginator_interface {
    public function set_limit(int $limit) : static;
    
    public function get_limit() : int;

    public function get_generator() : \Generator;
}
