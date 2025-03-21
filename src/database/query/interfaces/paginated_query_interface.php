<?php namespace linkisensei\moodle_dev_utils\database\query\interfaces;

interface paginated_query_interface {

    /**
     * Get paginator instance.
     *
     * @return linkisensei\moodle_dev_utils\database\query\interfaces\paginator_interface
     */
    public function get_paginator(): paginator_interface;
}
