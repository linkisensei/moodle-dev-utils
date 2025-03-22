<?php

defined('MOODLE_INTERNAL') || die();

use \moodle_dev_utils\database\query\moodle_query;

/**
 * Test cases for the moodle_query class.
 */
class moodle_query_test extends advanced_testcase {

    /**
     * Prepare test data.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        global $DB;

        // Creating test table
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('mdl_devutils_test')) {
            $table = new xmldb_table('devutils_test');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // Inserting mocked data
        $DB->insert_records('devutils_test', [
            ['name' => 'alpha'],
            ['name' => 'beta'],
            ['name' => 'gamma'],
        ]);
    }

    public function test_empty_select() {
        $query = new moodle_query();
        $query->from('devutils_test');
        $sql = $query->to_sql();

        $this->assertStringContainsString('SELECT *', $sql);
    }

    public function test_basic_select() {
        $query = new moodle_query();
        $query->select('name')->from('devutils_test');
        $sql = $query->to_sql();

        $this->assertStringContainsString('SELECT name', $sql);
        $this->assertStringContainsString('FROM {devutils_test}', $sql);
    }

    public function test_where_and_or_where() {
        $query = new moodle_query();
        $query->from('devutils_test')
              ->where('name = :name1', ['name1' => 'alpha'])
              ->or_where('name = :name2', ['name2' => 'beta']);

        $sql = $query->to_sql();
        $this->assertStringContainsString('(name = :name1 OR name = :name2)', $sql);
    }

    public function test_count() {
        $query = new moodle_query();
        $query->from('devutils_test')
              ->where('name <> :excluded', ['excluded' => 'gamma']);

        $this->assertEquals(2, $query->count());
    }

    public function test_exists() {
        $query = new moodle_query();
        $query->from('devutils_test')
              ->where('name = :name', ['name' => 'beta']);

        $this->assertTrue($query->exists());

        $query->where('name = :name2', ['name2' => 'nonexistent']);
        $this->assertFalse($query->exists());
    }

    public function test_first() {
        $query = new moodle_query();
        $query->from('devutils_test')->order_by('id', 'ASC');

        $first = $query->first();
        $this->assertEquals('alpha', $first->name);
    }

    public function test_join_and_group() {
        $query = new moodle_query();
        $query->from('devutils_test', 't')
              ->inner_join('devutils_test', 't.id = t2.id', 't2')
              ->group_by('t.name');

        $sql = $query->to_sql();
        $this->assertStringContainsString('JOIN {devutils_test} t2 ON (t.id = t2.id)', $sql);
        $this->assertStringContainsString('GROUP BY t.name', $sql);
    }

    public function test_invalid_join_throws_exception() {
        $this->expectException(coding_exception::class);
        $query = new moodle_query();
        $query->inner_join('devutils_test', '1=1');
    }

    public function test_dynamic_get_property() {
        $query = new moodle_query();
        $this->assertIsArray($query->fields);

        $this->expectException(ErrorException::class);
        $query->nonexistent_property;
    }
}