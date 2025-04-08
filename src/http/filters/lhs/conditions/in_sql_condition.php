<?php namespace moodle_dev_utils\http\filters\lhs\conditions;

use \moodle_dev_utils\http\filters\exceptions\context\filter_context;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_choice_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_value_exception;
use \moodle_dev_utils\http\filters\exceptions\missing_required_field_exception;
use \invalid_parameter_exception;
use \moodle_dev_utils\http\exceptions\validation_exception;

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


     /**
     * Validates the parameter value and updates its with
     * a cleaned version
     *
     * @throws moodle_dev_utils\http\exceptions\validation_exception
     * @throws moodle_dev_utils\http\filters\exceptions\invalid_condition_choice_exception
     * @throws moodle_dev_utils\http\filters\exceptions\missing_required_field_exception
     * @param string $type like PARAM_RAW, PARAM_INT etc
     * @param bool $required
     * @param mixed $default
     * @param array|null $choices Like ENUM
     * @return void
     */
    public function validate_param(string $type, bool $required = false, mixed $default = null, ?array $choices = []){
        foreach ($this->params as &$value) {
            try {   
                if($value === null && $required && $default === null){
                    $ctx = $this->get_context();
                    throw missing_required_field_exception::new()->set_context($ctx);
                }
        
                if($value === null && $required && $default !== null){
                    $value = $default;
                }
        
                if($type === PARAM_BOOL && $value === false){
                    $value = 0; // Validate_param() does not like false with PARAM_BOOL
                }
        
                if($type === PARAM_CLEANHTML){
                    $value = clean_param($value, PARAM_CLEANHTML);
                }
        
                if (!empty($choices) && !in_array($value, $choices)) {
                    $ctx = new filter_context($this->field, $this->get_alias(), '', $choices);
                    throw invalid_condition_choice_exception::new()->set_context($ctx);
                }
        
                $value = validate_param($value, $type, !$required) ?? $default;
    
            } catch (invalid_parameter_exception $ex) {
                throw new validation_exception($ex->getMessage());
            }

            $this->value = implode(',', $this->params);
        }
    }
}
