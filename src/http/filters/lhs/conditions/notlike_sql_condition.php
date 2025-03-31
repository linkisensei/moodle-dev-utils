<?php namespace moodle_dev_utils\http\filters\lhs\conditions;

use \moodle_dev_utils\http\filters\exceptions\invalid_condition_value_exception;
use \moodle_dev_utils\http\filters\lhs\conditions\traits\wildcard_trait;

class notlike_sql_condition extends abstract_sql_condition {

    use wildcard_trait;

    public function __construct(string $field, mixed $value = null){
        $value = $this->normalize_wildcards($value);

        $this->key = $field . '__' . static::get_alias();
        $this->value = $value;
        $this->field = $field;

        if(!str_contains($value, '%')){
            throw invalid_condition_value_exception::new()->set_context($this->get_context());
        }
    }

    /**
     * Converts the condition into a sql expression
     *
     * @param string $table
     * @return string
     */
    public function to_sql(string $table = '') : string {
        global $DB;

        if(!isset($this->sql)){
            $this->sql = $DB->sql_like($this->field, ":$this->key", false, false, true);
        }
        return empty($table) ? $this->sql : $table . "." . $this->sql;
    }
    
    /**
     * Returns the alias operator (to be used in query params)
     *
     * @return string
     */
    public static function get_alias() : string {
        return 'notlike';
    }

    /**
     * Returns the real sql operator
     *
     * @return string
     */
    public function get_operator() : string {
        return 'NOT LIKE';
    }

}
