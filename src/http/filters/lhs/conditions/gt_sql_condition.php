<?php namespace linkisensei\moodle_dev_utils\http\filters\lhs\conditions;

class gt_sql_condition extends abstract_sql_condition {
    
    /**
     * Returns the alias operator (to be used in query params)
     *
     * @return string
     */
    public static function get_alias() : string {
        return 'gt';
    }

    /**
     * Returns the real sql operator
     *
     * @return string
     */
    public function get_operator() : string {
        return '>';
    }

}
