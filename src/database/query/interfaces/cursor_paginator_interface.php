<?php namespace moodle_dev_utils\database\query\interfaces;

interface cursor_paginator_interface extends paginator_interface {

    public function set_cursor(mixed $cursor): static;

    public function get_next_cursor() : mixed;

    public function set_cursor_field(string $field) : static;

}
