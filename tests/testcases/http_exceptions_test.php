<?php

defined('MOODLE_INTERNAL') || die();

use \moodle_dev_utils\http\exceptions\http_exception;
use \moodle_dev_utils\http\exceptions\validation_exception;
use \moodle_dev_utils\http\filters\exceptions\condition_exception;
use \moodle_dev_utils\http\filters\exceptions\forbidden_operator_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_choice_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_condition_value_exception;
use \moodle_dev_utils\http\filters\exceptions\invalid_operator_exception;
use \moodle_dev_utils\http\filters\exceptions\missing_required_field_exception;
use \moodle_dev_utils\http\filters\exceptions\operator_exception;
use \moodle_dev_utils\http\filters\exceptions\context\filter_context;

/**
 * Tests for HTTP and validation exceptions.
 */
class http_exceptions_test extends advanced_testcase {

    public function test_http_exception_basic_properties() {
        $exception = new http_exception('Something failed', 503);
        $this->assertEquals('Something failed', $exception->getMessage());
        $this->assertEquals(503, $exception->get_status_code());

        $exception->set_status_code(404);
        $this->assertEquals(404, $exception->get_status_code());
    }

    public function test_http_exception_context_handling() {
        $ctx = new \stdClass();
        $ctx->info = 'extra';

        $exception = new http_exception('With context');
        $exception->set_context($ctx);

        $this->assertEquals($ctx, $exception->get_context());
        $this->assertObjectHasProperty('info', $exception->get_context());
    }

    public function test_http_exception_static_constructors() {
        $previous = new \Exception('root');
        $original = new \Exception('wrapped', 0, $previous);

        $from = http_exception::from_exception($original);
        $this->assertInstanceOf(http_exception::class, $from);
        $this->assertEquals('wrapped', $from->getMessage());
        $this->assertSame($previous, $from->getPrevious());

        $new = http_exception::new('custom', 403);
        $this->assertEquals('custom', $new->getMessage());
        $this->assertEquals(403, $new->get_status_code());
    }

    public function test_validation_exception_defaults() {
        $ex = new validation_exception('Invalid input');
        $this->assertInstanceOf(http_exception::class, $ex);
        $this->assertEquals(422, $ex->get_status_code());
    }

    public function test_condition_exceptions() {
        $classes = [
            condition_exception::class,
            forbidden_operator_exception::class,
            invalid_condition_choice_exception::class,
            invalid_condition_value_exception::class,
            invalid_operator_exception::class,
            missing_required_field_exception::class
        ];
    
        foreach ($classes as $classname) {
            $ex = new $classname('Test message');
            $this->assertInstanceOf(validation_exception::class, $ex);
            
            $status = $ex->get_status_code();
            $this->assertGreaterThanOrEqual(400, $status);
            $this->assertLessThan(500, $status);
        }
    }

    public function test_exception_context_can_be_set_fluently() {
        $ctx = new filter_context('myfield', 'eq', ['a', 'b']);
        $ex = invalid_operator_exception::new()->set_context($ctx);

        $this->assertEquals($ctx, $ex->get_context());
        $this->assertEquals('myfield', $ex->get_context()->field);
    }
}
