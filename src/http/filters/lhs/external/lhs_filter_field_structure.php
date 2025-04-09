<?php namespace moodle_dev_utils\http\filters\lhs\external;

use \core_external\external_single_structure;
use \core_external\external_value;

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
            $keys[$operator] = new external_value($type, '', VALUE_OPTIONAL);
        }
        
        parent::__construct($keys, $desc, VALUE_OPTIONAL, null);
    }
}


