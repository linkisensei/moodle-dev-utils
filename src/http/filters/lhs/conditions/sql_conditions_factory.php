<?php namespace linkisensei\moodle_dev_utils\http\filters\lhs\conditions;

use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\sql_condition_interface;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\eq_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\neq_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\gt_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\gte_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\lt_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\lte_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\isnull_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\notnull_sql_condition;
use \linkisensei\moodle_dev_utils\http\filters\lhs\conditions\like_sql_condition;

use linkisensei\moodle_dev_utils\http\filters\exception\invalid_operator_exception;
use \linkisensei\moodle_dev_utils\http\filters\exceptions\context\filter_context;

final class sql_conditions_factory {

    /**
     * @throws linkisensei\moodle_dev_utils\http\exceptions\validation_exception
     * @param string $operator_alias
     * @param string $field
     * @param mixed $value
     * @return sql_condition_interface
     */
    public static function make(string $operator_alias, string $field, mixed $value) : sql_condition_interface {
        $class =  match ($operator_alias) {
            gt_sql_condition::get_alias()    => gt_sql_condition::class,
            gte_sql_condition::get_alias()   => gte_sql_condition::class,
            lt_sql_condition::get_alias()    => lt_sql_condition::class,
            lte_sql_condition::get_alias()   => lte_sql_condition::class,
            eq_sql_condition::get_alias()    => eq_sql_condition::class,
            neq_sql_condition::get_alias()   => neq_sql_condition::class,
            isnull_sql_condition::get_alias()  => isnull_sql_condition::class,
            notnull_sql_condition::get_alias() => notnull_sql_condition::class,
            like_sql_condition::get_alias()  => like_sql_condition::class,
            default                    => null,
        };

        if($class === null){
            $ctx = new filter_context($field, $operator_alias);
            throw invalid_operator_exception::new()->set_context($ctx);
        }

        return new $class($field, $value);
    }
}
