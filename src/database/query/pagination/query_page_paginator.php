<?php namespace linkisensei\moodle_dev_utils\database\query\pagination;

use \linkisensei\moodle_dev_utils\database\query\interfaces\page_paginator_interface;
use \linkisensei\moodle_dev_utils\database\query\moodle_query;

class query_page_paginator implements page_paginator_interface {
    protected moodle_query $query;
    protected int $page = 1;
    protected int $limit = 0;
    protected int $total = 0;

    public function __construct(moodle_query $query) {
        $this->query = $query;
    }

    public function set_limit(int $limit) : static {
        $this->query->limit($limit);
        return $this;
    }

    public function get_limit() : int {
        return $this->query->limit;
    }

    public function set_page(int $page): static {
        $this->page = $page;
        return $this;
    }

    public function get_page(): int {
        return $this->page;
    }

    public function get_total(): int {
        return $this->total;
    }

    public function get_total_pages(): int {
        if(!$this->get_limit()){
            return 0;
        }
        return ceil($this->total/$this->get_limit());
    }

    public function get_generator() : \Generator {
        $this->query->limit($this->get_limit(), ($this->page - 1) * $this->get_limit());

        $rs = $this->query->get_recordset();
        $has_records = $rs->valid();
        foreach ($rs as $record) {
            yield $record;
        }
        $rs->close();

        if($this->page == 1 && !$has_records){
            $this->total = 0;
        }else{
            $this->total = $this->query->count();
        }
    }
}
