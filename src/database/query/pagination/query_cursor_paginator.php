<?php namespace linkisensei\moodle_dev_utils\database\query\pagination;

use \linkisensei\moodle_dev_utils\database\query\moodle_query;
use \linkisensei\moodle_dev_utils\database\query\interfaces\cursor_paginator_interface;

/**
 * A forward cursor paginator for moodle_query
 */
class query_cursor_paginator implements cursor_paginator_interface {
    protected moodle_query $query;
    protected mixed $cursor = null;
    protected string $cursor_field;
    protected mixed $next_cursor = null;

    /**
     * @param moodle_query $query
     * @param string $cursor_field The column that will be used to order the query
     */
    public function __construct(moodle_query $query, string $cursor_field) {
        $this->query = $query;
        $this->cursor_field = $cursor_field;
    }

    public function set_limit(int $limit) : static {
        $this->query->limit($limit);
        return $this;
    }

    public function get_limit() : int {
        return $this->query->limit;
    }
    
    public function set_cursor_field(string $field) : static {
        $this->cursor_field = $field;
        return $this;
    }

    public function set_cursor(mixed $cursor) : static {
        $this->cursor = $cursor;
        return $this;
    }

    /**
     * @return mixed Returns null if last page
     */
    public function get_next_cursor() : mixed {
        return $this->next_cursor;
    }

    protected function paginate_query() : moodle_query {
        $query = clone $this->query;
        $query->reset_order_by();
        $query->order_by($this->cursor_field, 'ASC');

        if($this->cursor !== null){
            $query->where("$this->cursor_field > :pagination_cursor", ['pagination_cursor' => $this->cursor]);
        }
    
        return $query;
    }

    public function get_generator() : \Generator {
        $query = $this->paginate_query();

        $this->next_cursor = null;

        $counter = 0;
        $cursor_property = explode('.', $this->cursor_field); // removing alias.
        $cursor_property = end($cursor_property); // removing alias.

        $query->limit($this->get_limit() + 1, 0);
        $recordset = $query->get_recordset();
        $last_cursor = null;
        foreach ($recordset as $record){
            if($counter < $this->query->limit){
                yield $record;
            }

            $last_cursor = $record?->$cursor_property;
            $counter++;
        }
        $recordset->close();

        // Adding next page cursor
        if($counter > $this->query->limit){
            $this->next_cursor = $last_cursor;
        }
    }
}
