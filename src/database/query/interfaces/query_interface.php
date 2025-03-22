<?php namespace moodle_dev_utils\database\query\interfaces;

interface query_interface {
    
    /**
     * Execute the query and return results.
     *
     * @return mixed Query result
     */
    public function execute(): mixed;
}
