<?php namespace moodle_dev_utils\http\filters\interfaces;

interface sql_filter_interface extends filter_interface {
    public function get_conditions() : string;
}
