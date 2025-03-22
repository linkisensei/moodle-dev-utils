<?php namespace moodle_dev_utils\database\query\interfaces;

interface page_paginator_interface extends paginator_interface {

    public function set_page(int $page): static;

    public function get_page(): int;

    public function get_total(): int;

    public function get_total_pages(): int;

}
