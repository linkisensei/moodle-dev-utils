<?php namespace moodle_dev_utils\http\filters\lhs\external;

use \core_external\external_single_structure;
use \core_external\external_value;
use \moodle_dev_utils\http\filters\lhs\conditions\in_sql_condition;

class lhs_filter_field_structure extends external_single_structure {

    /**
     * Constructor
     *
     * @param string[] $operators aliases for operators
     * @param string $desc
     */
    public function __construct(array $operators = [], $desc = '', $type = PARAM_RAW) {
        $keys = [];
        foreach ($operators as $operator) {
            switch ($operator) {
                case in_sql_condition::get_alias():
                    $keys[$operator] = new external_value(PARAM_RAW, '', VALUE_OPTIONAL);
                    break;
                
                default:
                    $keys[$operator] = new external_value($type, '', VALUE_OPTIONAL);
                    break;
            }
        }
        
        parent::__construct($keys, $desc, VALUE_OPTIONAL, null);
    }
}


