<?php namespace moodle_dev_utils\http\filters\lhs\conditions;

class notnull_sql_condition extends abstract_sql_condition {

    public function __construct(string $field, mixed $value = null){
        $this->key = $field . '__' . static::get_alias();
        $this->value = null;
        $this->field = $field;
    }

    /**
     * Converts the condition into a sql expression
     *
     * @param string $table
     * @return string
     */
    public function to_sql(string $table = '') : string {
        if(!isset($this->sql)){
            $this->sql = $this->field . ' ' . $this->get_operator();
        }
        return empty($table) ? $this->sql : $table . "." . $this->sql;
    }
    
    /**
     * Returns the alias operator (to be used in query params)
     *
     * @return string
     */
    public static function get_alias() : string {
        return 'notnull';
    }

    /**
     * Returns the real sql operator
     *
     * @return string
     */
    public function get_operator() : string {
        return 'IS NOT NULL';
    }

}
