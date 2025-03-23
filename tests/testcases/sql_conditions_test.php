<?php

defined('MOODLE_INTERNAL') || die();

use \moodle_dev_utils\http\filters\lhs\conditions\eq_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\gt_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\isnull_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\notnull_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\like_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\sql_conditions_factory;
use \moodle_dev_utils\http\filters\exceptions\missing_required_field_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_choice_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_value_exception;
use \moodle_dev_utils\http\exceptions\validation_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_operator_exception;
use \moodle_dev_utils\http\filters\exceptions\context\filter_context;
use \moodle_dev_utils\http\filters\lhs\conditions\in_sql_condition;

/**
 * Tests for SQL condition classes and factory.
 */
class sql_conditions_test extends advanced_testcase {

    /**
     * Generates the expected param placeholder.
     *
     * @param string $field
     * @param string $alias
     * @return string
     */
    protected function make_placeholder(string $field, string $alias): string {
        return "{$field}__{$alias}";
    }

    public function test_eq_condition_generates_correct_sql() {
        $condition = new eq_sql_condition('name', 'john');

        $placeholder = $this->make_placeholder('name', 'eq');
        $this->assertEquals("name = :{$placeholder}", $condition->to_sql());

        $params = [];
        $condition->append_param($params);

        $placeholder = $this->make_placeholder('name', 'eq');
        $this->assertEquals([$placeholder => 'john'], $params);

        $this->assertEquals('name', $condition->get_field());
        $this->assertEquals('eq', eq_sql_condition::get_alias());
        $this->assertEquals('=', $condition->get_operator());
    }

    public function test_condition_allows_custom_field() {
        $condition = new gt_sql_condition('created_at', 10);
        $condition->set_field('timestamp');

        $placeholder = $this->make_placeholder('created_at', 'gt');
        $this->assertEquals("timestamp > :{$placeholder}", $condition->to_sql());
    }

    public function test_validate_param_with_valid_int() {
        $condition = new gt_sql_condition('score', 50);
        $condition->validate_param(PARAM_INT);

        $this->assertEquals(50, $condition->get_value());
    }

    public function test_validate_param_sets_default_value() {
        $condition = new eq_sql_condition('active', null);
        $condition->validate_param(PARAM_INT, false, 1);

        $this->assertEquals(1, $condition->get_value());
    }

    public function test_validate_param_throws_required_exception() {
        $this->expectException(missing_required_field_exception::class);

        $condition = new eq_sql_condition('role', null);
        $condition->validate_param(PARAM_TEXT, true, null);
    }

    public function test_validate_param_with_enum_choices() {
        $this->expectException(invalid_condition_choice_exception::class);

        $condition = new eq_sql_condition('status', 'archived');
        $condition->validate_param(PARAM_TEXT, false, null, ['draft', 'published']);
    }

    public function test_isnull_condition_generates_expected_sql() {
        $condition = new isnull_sql_condition('deleted_at');
        $this->assertEquals('deleted_at IS NULL', $condition->to_sql());
        $this->assertEquals('IS NULL', $condition->get_operator());
    }

    public function test_notnull_condition_generates_expected_sql() {
        $condition = new notnull_sql_condition('updated_at');
        $this->assertEquals('updated_at IS NOT NULL', $condition->to_sql());
        $this->assertEquals('IS NOT NULL', $condition->get_operator());
    }

    public function test_like_condition_throws_without_percent() {
        $this->expectException(invalid_condition_value_exception::class);
        new like_sql_condition('name', 'doe');
    }

    public function test_sql_conditions_factory_invalid_operator_throws() {
        $this->expectException(invalid_operator_exception::class);

        sql_conditions_factory::make('invalid', 'field', 'value');
    }


    public function test_like_condition_accepts_percent_sign() {
        $field = 'name';
        $alias = 'like';
        $placeholder = $this->make_placeholder($field, $alias);
    
        $condition = new like_sql_condition($field, '%doe%');
    
        $this->assertEquals('LIKE', $condition->get_operator());
        $sql = $condition->to_sql();
        $this->assertStringContainsString(":$placeholder", $sql);
    }
    
    public function test_condition_to_sql_with_table_prefix() {
        $field = 'name';
        $alias = 'eq';
        $placeholder = $this->make_placeholder($field, $alias);
    
        $condition = new eq_sql_condition($field, 'john');
        $sql = $condition->to_sql('u');
    
        $this->assertEquals("u.$field = :$placeholder", $sql);
    }
    
    public function test_sql_conditions_factory_creates_correct_condition() {
        $field = 'field';
        $alias = 'eq';
        $value = 'value';
        $placeholder = $this->make_placeholder($field, $alias);
    
        $condition = sql_conditions_factory::make($alias, $field, $value);
        $this->assertInstanceOf(eq_sql_condition::class, $condition);
        $this->assertEquals("$field = :$placeholder", $condition->to_sql());
    }
    
    public function test_in_condition_generates_correct_sql() {
        $values = ['todo', 'open', 'inprogress'];
        $condition = new \moodle_dev_utils\http\filters\lhs\conditions\in_sql_condition('status', $values);
        $placeholder = $this->make_placeholder('status', 'in');
        

        $sql = $condition->to_sql();
        $this->assertStringStartsWith("status ", $sql);
        $this->assertStringContainsString("IN", $sql);

        $this->assertStringContainsString($placeholder, $sql);

        $this->assertEquals('todo,open,inprogress', $condition->get_value());
        
        $params = [];
        $condition->append_param($params);

        $this->assertCount(3, $params);
        $this->assertArrayHasKey('status__in1', $params);
        $this->assertArrayHasKey('status__in2', $params);
        $this->assertArrayHasKey('status__in3', $params);
        $this->assertEquals('todo', $params['status__in1']);
        $this->assertEquals('open', $params['status__in2']);
        $this->assertEquals('inprogress', $params['status__in3']);
    }

    public function test_in_condition_throws_exception_with_string_value() {
        $this->expectException(invalid_condition_value_exception::class);
        new in_sql_condition('status', null);
    }
}
