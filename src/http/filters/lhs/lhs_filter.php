<?php namespace moodle_dev_utils\http\filters\lhs;

use \Psr\Http\Message\ServerRequestInterface;

use \moodle_dev_utils\http\filters\interfaces\sql_filter_interface;
use \moodle_dev_utils\http\filters\lhs\conditions\eq_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\sql_conditions_factory;
use \moodle_dev_utils\http\filters\exceptions\forbidden_operator_exception;
use \moodle_dev_utils\http\filters\exceptions\context\filter_context;

/**
 * Parses LHS Bracket filters and transforms them into
 * sql conditions.
 */
class lhs_filter implements sql_filter_interface {
    protected array $conditions = [];
    protected static array $definition_cache = [];

    /**
     * Defines the accepted fields
     * 
     * Example:
     * 
     * [
     *      'fieldname' => [
     *          'type' => PARAM_RAW,
     *          'required' => false, (default)
     *          'default' => null, (default)
     *          'choices' => [], (default)
     *          'operators' => [
     *              eq_sql_condition::get_alias(), (default),
     *          ],
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    protected function define_fields() : array {
        return [];
    }


    /**
     * Returns the fields definition.
     * This is cached in memory and all defaults are set
     *
     * @return array
     */
    protected function get_fields_definition() : array {
        $subclass = static::class;
        if(!isset(self::$definition_cache[$subclass])){
            self::$definition_cache[$subclass] = [];

            foreach ($this->define_fields() as $field => $definition) {
                $definition['required'] = $definition['required'] ?? false;
                $definition['default'] = $definition['default'] ?? null;
                $definition['choices'] = $definition['choices'] ?? [];

                if(empty($definition['operators'])){
                    $definition['operators'] = [static::define_default_operator()];
                }

                self::$definition_cache[$subclass][$field] = $definition;
            }
        }

        return self::$definition_cache[$subclass];
    }

    protected static function define_default_operator(){
        return eq_sql_condition::get_alias();
    }

    /**
     * Determines if the field is accepted
     *
     * @param string $field
     * @return boolean
     */
    protected function accepts_field(string $field) : bool {
        $definitions = $this->get_fields_definition();
        if(empty($definitions)){
            return true; // No definition, accepts all fields
        }

        return isset($definitions[$field]);
    }

    public function set_condition(string $operator_alias, string $field, mixed $value = null) : static {
        // Validating if field is accepted
        if(!$this->accepts_field($field)){
            return $this;
        }

        // Instantiating condition
        $condition = sql_conditions_factory::make($operator_alias, $field, $value);

        // Validating condition param
        $definitions = $this->get_fields_definition();
        if(!empty($definitions) && isset($definitions[$field])){
            if(!in_array($operator_alias, $definitions[$field]['operators'])){
                $ctx = new filter_context($field, $operator_alias, $definitions[$field]['operators']);
                throw forbidden_operator_exception::new()->set_context($ctx);
            }

            $type = $definitions[$field]['type'];
            $required = $definitions[$field]['required'] ?? false;
            $default = $definitions[$field]['default'] ?? null;
            $choices = $definitions[$field]['choices'] ?? [];
            $condition->validate_param($type, $required, $default, $choices);
        }

        // Setting condition
        $this->conditions[$condition->get_key()] = $condition;
        return $this;
    }

    /**
     * Override this method to change the parameters before
     * the filters initialization and validation.
     *
     * @param array $query_params (by reference)
     * @return void
     */
    protected function before_validation(array &$query_params){}

    public function __construct(array $query_params = []){
        $default_operator = static::define_default_operator();

        $this->before_validation($query_params);

        foreach ($query_params as $field => $values) {
            if(!is_array($values)){
                $this->set_condition($default_operator, $field, $values);
                continue;
            }
            
            foreach ($values as $operator => $value) {
                $this->set_condition($operator, $field, $value);
            }
        }
    }

    public static function from_request(ServerRequestInterface $request) : static {
        return new static($request->getQueryParams());
    }

    public function get_conditions(string $table = '') : string {
        $conditions = array_map(function($condition) use ($table){
            return $condition->to_sql($table);
        }, $this->conditions);

        return implode(' AND ', $conditions);
    }
    
    public function get_parameters() : array {
        $params = [];
        foreach ($this->conditions as $condition) {
            $condition->append_param($params);
        }
        return $params;
    }

    public function has_conditions() : bool {
        return !empty($this->conditions);
    }
}
