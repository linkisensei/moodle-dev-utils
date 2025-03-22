<?php

defined('MOODLE_INTERNAL') || die();

use \moodle_dev_utils\http\filters\lhs\lhs_filter;
use \moodle_dev_utils\http\filters\exceptions\forbidden_operator_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_choice_exception;
use \moodle_dev_utils\http\filters\exceptions\missing_required_field_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_operator_exception;
use \moodle_dev_utils\http\filters\lhs\conditions\eq_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\gt_sql_condition;
use \moodle_dev_utils\http\filters\lhs\conditions\sql_conditions_factory;
use \GuzzleHttp\Psr7\ServerRequest;

/**
 * Tests for lhs_filter.
 */
class lhs_filter_test extends advanced_testcase {

    /**
     * Custom filter for testing.
     */
    protected function get_test_filter(array $params = []): lhs_filter {
        return new class($params) extends lhs_filter {
            protected function define_fields(): array {
                return [
                    'age' => [
                        'type' => PARAM_INT,
                        'required' => true,
                        'default' => null,
                        'operators' => ['gt', 'eq'],
                    ],
                    'status' => [
                        'type' => PARAM_TEXT,
                        'choices' => ['draft', 'published'],
                        'operators' => ['eq'],
                    ]
                ];
            }
        };
    }

    public function test_accepts_valid_operator_and_generates_sql() {
        $filter = $this->get_test_filter(['age' => ['gt' => 30]]);
        $sql = $filter->get_conditions('u');
        $params = $filter->get_parameters();

        $this->assertStringContainsString('u.age > :age__gt', $sql);
        $this->assertArrayHasKey('age__gt', $params);
        $this->assertEquals(30, $params['age__gt']);
    }

    public function test_eq_operator_and_choices() {
        $filter = $this->get_test_filter(['status' => ['eq' => 'draft']]);

        $sql = $filter->get_conditions('t');
        $params = $filter->get_parameters();

        $this->assertStringContainsString('t.status = :status__eq', $sql);
        $this->assertEquals('draft', $params['status__eq']);
    }

    public function test_missing_required_field_throws_exception() {
        $this->expectException(missing_required_field_exception::class);
    
        $this->get_test_filter(['age' => null])->get_conditions();
    }

    public function test_invalid_choice_throws_exception() {
        $this->expectException(invalid_condition_choice_exception::class);

        $this->get_test_filter(['status' => ['eq' => 'archived']])->get_conditions();
    }

    public function test_invalid_operator_throws_exception() {
        $this->expectException(forbidden_operator_exception::class);

        $this->get_test_filter(['status' => ['gt' => 'draft']])->get_conditions();
    }

    public function test_unaccepted_operator_throws_invalid_operator_exception() {
    $this->expectException(invalid_operator_exception::class);

    $this->get_test_filter(['status' => ['invalid' => 'abc']])->get_conditions();
}


    public function test_from_request_parses_query() {
        $request = new ServerRequest('GET', '/?age[gt]=18');
        $parsed = [];
        parse_str($request->getUri()->getQuery(), $parsed);
        $request = $request->withQueryParams($parsed);

        $filter = $this->get_test_filter();
        $parsed = $filter::from_request($request);

        $this->assertInstanceOf(lhs_filter::class, $parsed);
        $this->assertTrue($parsed->has_conditions());
    }

    public function test_has_conditions_returns_false_if_empty() {
        $filter = $this->get_test_filter([]);
        $this->assertFalse($filter->has_conditions());
    }

    public function test_accepts_flat_query_param() {
        $filter = $this->get_test_filter(['age' => 99]);

        $sql = $filter->get_conditions();
        $params = $filter->get_parameters();

        $this->assertStringContainsString('age = :age__eq', $sql);
        $this->assertEquals(99, $params['age__eq']);
    }
}
