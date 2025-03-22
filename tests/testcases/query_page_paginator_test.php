<?php

defined('MOODLE_INTERNAL') || die();

use \moodle_dev_utils\database\query\moodle_query;
use \moodle_dev_utils\database\query\pagination\query_page_paginator;

/**
 * Test cases for query_page_paginator class.
 */
class query_page_paginator_test extends advanced_testcase {

    /**
     * Prepare test data.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('mdl_devutils_page_test')) {
            $table = new xmldb_table('devutils_page_test');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('label', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        for ($i = 1; $i <= 25; $i++) {
            $DB->insert_record('devutils_page_test', ['label' => "Item {$i}"]);
        }
    }

    public function test_pagination_basic() {
        $query = (new moodle_query())->from('devutils_page_test')->order_by('id');
        $paginator = new query_page_paginator($query);
        $paginator->set_limit(10)->set_page(1);

        $results = iterator_to_array($paginator->get_generator());
        $this->assertCount(10, $results);
        $this->assertEquals('Item 1', $results[0]->label);
    }

    public function test_total_and_total_pages() {
        $query = (new moodle_query())->from('devutils_page_test');
        $paginator = new query_page_paginator($query);
        $paginator->set_limit(10)->set_page(2);

        iterator_to_array($paginator->get_generator());

        $this->assertEquals(25, $paginator->get_total());
        $this->assertEquals(3, $paginator->get_total_pages());
    }

    public function test_empty_first_page_returns_zero_total() {
        global $DB;
        $DB->delete_records('devutils_page_test');

        $query = (new moodle_query())->from('devutils_page_test');
        $paginator = new query_page_paginator($query);
        $paginator->set_limit(10)->set_page(1);

        $results = iterator_to_array($paginator->get_generator());
        $this->assertCount(0, $results);
        $this->assertEquals(0, $paginator->get_total());
    }

    public function test_setters_and_getters() {
        $query = (new moodle_query())->from('devutils_page_test');
        $paginator = new query_page_paginator($query);

        $paginator->set_limit(15)->set_page(3);

        $this->assertEquals(15, $paginator->get_limit());
        $this->assertEquals(3, $paginator->get_page());
    }
}
