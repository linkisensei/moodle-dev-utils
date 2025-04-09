<?php namespace moodle_dev_utils\http\filters\lhs\external;

use \core_external\external_single_structure;

class lhs_filter_structure extends external_single_structure {

    /**
     * Constructor
     *
     * @param string[] $fields
     * @param string $desc
     */
    public function __construct(array $fields = [], ?string $desc = null) {
        $desc = $desc ?? 'LHS filters, like <field>[<operator>]=<value>';
        parent::__construct($fields, $desc, VALUE_DEFAULT, []);
    }

    public function add_filter(string $key, string $type, array $operators = []) : static {
        $desc = $this->make_filter_description($key, $type, $operators);
        $this->keys[$key] = new lhs_filter_field_structure($operators, $desc, $type);
        return $this;
    }

    /**
     * You can override this method to implement custom descriptions
     * 
     * @param string $key
     * @param string $type
     * @param array $operators
     * @return string
     */
    public function make_filter_description(string $key, string $type, array $operators = []) : string {
        return "Supported operators: " . implode(',', $operators);
    }
}


