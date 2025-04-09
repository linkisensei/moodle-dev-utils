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

use \moodle_dev_utils\http\filters\lhs\external\lhs_filter_structure;
use \moodle_dev_utils\http\filters\lhs\external\lhs_filter_field_structure;

/**
 * Tests for lhs_filter.
 */
class lhs_filter_test extends advanced_testcase {

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

        $placeholder = $this->make_placeholder('age', 'gt');
        $this->assertStringContainsString("u.age > :{$placeholder}", $sql);
        $this->assertArrayHasKey($placeholder, $params);
        $this->assertEquals(30, $params[$placeholder]);
    }

    public function test_eq_operator_and_choices() {
        $filter = $this->get_test_filter(['status' => ['eq' => 'draft']]);

        $sql = $filter->get_conditions('t');
        $params = $filter->get_parameters();

        $placeholder = $this->make_placeholder('status', 'eq');
        $this->assertStringContainsString("t.status = :{$placeholder}", $sql);
        $this->assertEquals('draft', $params[$placeholder]);
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

        $placeholder = $this->make_placeholder('age', 'eq');
        $this->assertStringContainsString("age = :{$placeholder}", $sql);
        $this->assertEquals(99, $params[$placeholder]);
    }

    public function test_multiple_fields_generate_combined_sql_and_params() {
        $params = [
            'age' => ['gt' => 20],
            'status' => ['eq' => 'draft']
        ];
    
        $filter = $this->get_test_filter($params);
    
        $sql = $filter->get_conditions('u');
        $query_params = $filter->get_parameters();
    
        $this->assertStringContainsString('u.age > :age__gt', $sql);
        $this->assertStringContainsString('u.status = :status__eq', $sql);
        $this->assertStringContainsString('AND', $sql);
    
        $this->assertArrayHasKey('age__gt', $query_params);
        $this->assertArrayHasKey('status__eq', $query_params);
        $this->assertEquals(20, $query_params['age__gt']);
        $this->assertEquals('draft', $query_params['status__eq']);
    }
    
    public function test_field_definition_cache_is_used_across_instances() {
        $classname = new class([]) extends lhs_filter {
            protected static int $define_field_calls_counter = 0;
    
            public function __construct(array $query_params = []) {
                parent::__construct($query_params);
            }
    
            protected function define_fields(): array {
                self::$define_field_calls_counter++;
                return [
                    'id' => [
                        'type' => PARAM_INT,
                        'operators' => ['eq']
                    ]
                ];
            }

            public function get_define_field_calls_count() : int {
                return self::$define_field_calls_counter;
            }
        };
    
        $instance1 = clone $classname;
        $instance1->set_condition('eq', 'id', 1);
        $this->assertEquals(1, $instance1->get_define_field_calls_count());

        $instance2 = clone $classname;
        $instance2->set_condition('eq', 'id', 2);
        $this->assertEquals(1, $instance2->get_define_field_calls_count(), "Instance2 should have reused the definition of instance1");
    }


    public function test_to_external_description_returns_correct_structure() {
        $filter = $this->get_test_filter();
    
        $description = $filter->to_external_description();
    
        $this->assertInstanceOf(lhs_filter_structure::class, $description);
    
        $fields = $description->keys;
        $this->assertArrayHasKey('age', $fields);
        $this->assertArrayHasKey('status', $fields);
    
        $this->assertInstanceOf(lhs_filter_field_structure::class, $fields['age']);
        $this->assertInstanceOf(lhs_filter_field_structure::class, $fields['status']);
    }
    
    public function test_to_external_description_contains_expected_operators() {
        $filter = $this->get_test_filter();
        $description = $filter->to_external_description();
    
        /** @var lhs_filter_field_structure $age */
        $age = $description->keys['age'];
        $this->assertArrayHasKey('gt', $age->keys);
        $this->assertArrayHasKey('eq', $age->keys);
    
        /** @var lhs_filter_field_structure $status */
        $status = $description->keys['status'];
        $this->assertArrayHasKey('eq', $status->keys);
        $this->assertArrayNotHasKey('gt', $status->keys);
    }
    
    public function test_to_external_description_supports_custom_description() {
        $filter = new class([]) extends lhs_filter {
            protected function define_fields(): array {
                return [
                    'score' => [
                        'type' => PARAM_FLOAT,
                        'operators' => ['gt', 'eq']
                    ]
                ];
            }
        
            public function to_external_description(): lhs_filter_structure {
                return new class extends lhs_filter_structure {
                    public function make_filter_description(string $key, string $type, array $operators = []): string {
                        return "Operators for {$key}: " . implode('|', $operators);
                    }
                };
            }
        };
    
        $description = $filter->to_external_description();
        $field = $description->add_filter('score', PARAM_FLOAT, ['gt', 'eq'])->keys['score'];
        
        $this->assertStringContainsString('Operators for score:', $field->desc);
    }
}
