<?php namespace moodle_dev_utils\http\filters\lhs\conditions;

use \moodle_dev_utils\http\filters\exceptions\invalid_condition_value_exception;

class in_sql_condition extends abstract_sql_condition {

    protected array $params = [];

    public function __construct(string $field, mixed $value = null){
        $this->key = $field . '__' . static::get_alias();
        $this->field = $field;

        if (is_string($value)) {
            $this->value = $value;
            $this->params = array_map('trim', explode(',', $value));
        } elseif (is_array($value)){
            $this->value = implode(',', $value);
            $this->params = $value;
        } else {
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

        if (!isset($this->sql)) {
            [$insql, $inparams] = $DB->get_in_or_equal($this->params, SQL_PARAMS_NAMED, $this->key);
            $this->sql = $this->field . ' ' . $insql;
            $this->params = $inparams;
        }
        return empty($table) ? $this->sql : $table . "." . $this->sql;
    }

    /**
     * Appends its param into an assoc array
     *
     * @param array $params
     * @return void
     */
    public function append_param(array &$params){
        foreach ($this->params as $key => $value) {
            $params[$key] = $value;
        }
    }
    
    /**
     * Returns the alias operator (to be used in query params)
     *
     * @return string
     */
    public static function get_alias() : string {
        return 'in';
    }

    /**
     * Returns the real sql operator
     *
     * @return string
     */
    public function get_operator() : string {
        return 'IN';
    }

}
