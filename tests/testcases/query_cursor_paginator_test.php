<?php

defined('MOODLE_INTERNAL') || die();

use \moodle_dev_utils\database\query\moodle_query;
use \moodle_dev_utils\database\query\pagination\query_cursor_paginator;

/**
 * Test cases for query_cursor_paginator class.
 */
class query_cursor_paginator_test extends advanced_testcase {

    /**
     * Prepare test data.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('mdl_devutils_cursor_test')) {
            $table = new xmldb_table('devutils_cursor_test');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('value', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        for ($i = 1; $i <= 15; $i++) {
            $DB->insert_record('devutils_cursor_test', ['value' => "Data {$i}"]);
        }
    }

    public function test_paginate_first_page() {
        $query = (new moodle_query())->from('devutils_cursor_test');
        $paginator = new query_cursor_paginator($query, 'id');

        $paginator->set_limit(5);
        $results = iterator_to_array($paginator->get_generator());

        $this->assertCount(5, $results);
        $this->assertEquals('Data 1', $results[0]->value);
        $this->assertEquals('Data 5', $results[4]->value);
        $this->assertEquals(6, $paginator->get_next_cursor());
    }

    public function test_paginate_second_page_using_cursor() {
        $query = (new moodle_query())->from('devutils_cursor_test');
        $paginator = new query_cursor_paginator($query, 'id');

        $paginator->set_limit(5)->set_cursor(5);
        $results = iterator_to_array($paginator->get_generator());

        $this->assertCount(5, $results);
        $this->assertEquals('Data 6', $results[0]->value);
        $this->assertEquals('Data 10', $results[4]->value);
        $this->assertEquals(11, $paginator->get_next_cursor());
    }

    public function test_last_page_returns_no_next_cursor() {
        $query = (new moodle_query())->from('devutils_cursor_test');
        $paginator = new query_cursor_paginator($query, 'id');

        $paginator->set_limit(10)->set_cursor(10);
        $results = iterator_to_array($paginator->get_generator());

        $this->assertCount(5, $results);
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_set_cursor_field() {
        $query = (new moodle_query())->from('devutils_cursor_test');
        $paginator = new query_cursor_paginator($query, 'id');

        $paginator->set_cursor_field('id');
        $this->assertEquals(5, iterator_count($paginator->set_limit(5)->get_generator()));
    }
}
