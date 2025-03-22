<?php namespace linkisensei\moodle_dev_utils\http\filters\lhs\conditions;

interface sql_condition_interface {

    /**
     * Constructor
     *
     * @param string $field
     * @param mixed $value
     */
    public function __construct(string $field, mixed $value = null);

    /**
     * Converts the condition into a sql expression
     *
     * @param string $table
     * @return string
     */
    public function to_sql(string $table = '') : string;

    /**
     * Returns the field name
     *
     * @return string
     */
    public function get_field() : string;

    /**
     * Overrides the field name
     *
     * @return string
     */
    public function set_field(string $field) : static;

    /**
     * Returns the param value
     *
     * @return string
     */
    public function get_value() : mixed;

    /**
     * Returns the param key
     *
     * @return string
     */
    public function get_key() : string;

    /**
     * Appends its param into an assoc array
     *
     * @param array $params
     * @return void
     */
    public function append_param(array &$params);

    /**
     * Returns the alias operator (to be used in query params)
     *
     * @return string
     */
    public static function get_alias() : string;

    /**
     * Returns the real sql operator
     *
     * @return string
     */
    public function get_operator() : string;

    /**
     * Validates the parameter value and updates its with
     * a cleaned version
     *
     * @throws validation_exception
     * @param string $type like PARAM_RAW, PARAM_INT etc
     * @param bool $allow_nulls
     * @param mixed $default
     * @param array|null $choices Like ENUM
     * @return void
     */
    public function validate_param(string $type, bool $allow_null = NULL_NOT_ALLOWED, mixed $default = null, ?array $choices = []);
}
