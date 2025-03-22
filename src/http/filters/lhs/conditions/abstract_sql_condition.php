<?php namespace linkisensei\moodle_dev_utils\http\filters\lhs\conditions;

use \invalid_parameter_exception;
use linkisensei\moodle_dev_utils\http\exceptions\validation_exception;
use linkisensei\moodle_dev_utils\http\filters\exception\invalid_condition_choice_exception;
use \linkisensei\moodle_dev_utils\http\filters\exception\missing_required_field_exception;

abstract class abstract_sql_condition implements sql_condition_interface {

    protected $value = null;
    protected string $key;
    protected string $field;
    protected string $sql;

    public function __construct(string $field, mixed $value = null){
        $this->key = $field . '__' . static::get_alias();
        $this->value = $value;
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
            $this->sql = $this->field . ' ' . $this->get_operator() . ' :' . $this->key;
        }
        return empty($table) ? $this->sql : $table . "." . $this->sql;
    }

    /**
     * Returns the param value
     *
     * @return string
     */
    public function get_value() : mixed {
        return $this->value;
    }

    /**
     * Returns the param key
     *
     * @return string
     */
    public function get_key() : string {
        return $this->key;
    }

    /**
     * Overrides the field name
     *
     * @return string
     */
    public function set_field(string $field) : static {
        $this->field = $field;
        return $this;
    }

    /**
     * Returns the field name
     *
     * @return string
     */
    public function get_field() : string {
        return $this->field;
    }

    /**
     * Appends its param into an assoc array
     *
     * @param array $params
     * @return void
     */
    public function append_param(array &$params){
        $params[$this->get_key()] = $this->get_value();
    }

    /**
     * Returns the alias operator (to be used in query params)
     *
     * @return string
     */
    abstract public static function get_alias() : string;

    /**
     * Returns the real sql operator
     *
     * @return string
     */
    abstract public function get_operator() : string;

    /**
     * Validates the parameter value and updates its with
     * a cleaned version
     *
     * @throws linkisensei\moodle_dev_utils\http\exceptions\validation_exception
     * @throws linkisensei\moodle_dev_utils\http\filters\exception\invalid_condition_choice_exception
     * @throws linkisensei\moodle_dev_utils\http\filters\exception\missing_required_field_exception
     * @param string $type like PARAM_RAW, PARAM_INT etc
     * @param bool $required
     * @param mixed $default
     * @param array|null $choices Like ENUM
     * @return void
     */
    public function validate_param(string $type, bool $required = false, mixed $default = null, ?array $choices = []){
        try {
            $value = $this->value;

            if($value === null && $required && $default === null){
                throw missing_required_field_exception::new()->set_context(['field' => $this->field]);
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
                $ctx = [
                    'field' => $this->field,
                    'choices' => implode(', ', $choices),
                ];
                throw invalid_condition_choice_exception::new()->set_context($ctx);
            }
    
            $this->value = validate_param($value, $type, !$required);

        } catch (invalid_parameter_exception $ex) {
            throw new validation_exception($ex->getMessage());
        }
    }
}
