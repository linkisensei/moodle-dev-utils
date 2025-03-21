<?php namespace linkisensei\moodle_dev_utils\database\query;

use \moodle_database;
use \coding_exception;
use \moodle_recordset;

/**
 * This class is a query builder for the DML
 * 
 * @author Lucas Barreto <lucas.b.fisica@gmail.com>
 * @link https://github.com/linkisensei/moodle-dev-utils
 */
class moodle_query {
    /** @var bool Whether to use DISTINCT in SELECT */
    protected bool $distinct = false;

    /** @var array List of fields to select */
    protected array $fields = ['*'];

    /** @var string FROM clause */
    protected string $from = '';

    /** @var array JOIN clauses */
    protected array $joins = [];

    /** @var array WHERE conditions */
    protected array $where = [];

    /** @var array GROUP BY clauses */
    protected array $group_by = [];

    /** @var array HAVING conditions */
    protected array $having = [];

    /** @var array ORDER BY clauses */
    protected array $order_by = [];

    /** @var int Query result limit */
    protected int $limit = 0;

    /** @var int Query result offset */
    protected int $offset = 0;

    /** @var array Query parameters for prepared statements */
    protected array $params = [];

    /** @var moodle_database Database instance */
    protected moodle_database $db;

    /**
     * Constructor sets default database instance to global $DB
     *
     * @param moodle_database|null $db Optional database instance
     */
    public function __construct(?moodle_database $db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * Override current database instance
     *
     * @param moodle_database $db
     * @return static
     */
    public function use_database(moodle_database $db): static {
        $this->db = $db;
        return $this;
    }

    /**
     * Use DISTINCT in the SELECT clause
     *
     * @return static
     */
    public function distinct(bool $distinct = true): static {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Set fields to select
     *
     * @param array|string $fields
     * @return static
     */
    public function select(array|string $fields): static {
        $this->fields = array_merge($this->fields, is_array($fields) ? $fields : [$fields]);
        return $this;
    }

    /**
     * Reset select fields
     *
     * @return static
     */
    public function reset_select(): static {
        $this->fields = [];
        return $this;
    }

    /**
     * Set FROM clause
     *
     * @param string $table
     * @param string $alias
     * @return static
     */
    public function from(string $table, string $alias = ''): static {
        $this->from = $alias ? "{{$table}} $alias" : "{{$table}}";
        return $this;
    }

    /**
     * Add JOIN clause
     *
     * @param string $type JOIN type (INNER, LEFT, RIGHT)
     * @param string $table Table to join
     * @param string $on_condition ON condition for join
     * @return static
     * @throws coding_exception if FROM clause is not set
     */
    protected function join(string $type, string $table, string $on_condition): static {
        if (!$this->from) {
            throw new coding_exception("Cannot JOIN without FROM clause");
        }
        $this->joins[] = "$type JOIN {{$table}} ON ($on_condition)";
        return $this;
    }

    /**
     * Add INNER JOIN clause
     *
     * @param string $table
     * @param string $on_condition
     * @return static
     */
    public function inner_join(string $table, string $on_condition): static {
        return $this->join('INNER', $table, $on_condition);
    }

    /**
     * Add LEFT JOIN clause
     *
     * @param string $table
     * @param string $on_condition
     * @return static
     */
    public function left_join(string $table, string $on_condition): static {
        return $this->join('LEFT', $table, $on_condition);
    }

    /**
     * Add RIGHT JOIN clause
     *
     * @param string $table
     * @param string $on_condition
     * @return static
     */
    public function right_join(string $table, string $on_condition): static {
        return $this->join('RIGHT', $table, $on_condition);
    }

    /**
     * Add WHERE condition
     *
     * @param string $condition
     * @param array $params Parameters for prepared statement
     * @return static
     */
    public function where(string $condition, array $params = []): static {
        $this->where[] = $condition;
        $this->set_params($params);
        return $this;
    }

    /**
     * Add WHERE condition with OR
     * 
     * Combines the $condition with the last condition using
     * the OR operator.
     * 
     * Like "($last_condition OR condition)"
     *
     * @param string $condition
     * @param array $params Parameters for prepared statement
     * @return static
     */
    public function or_where(string $condition, array $params = []): static {
        if (empty($this->where)) {
            return $this->where($condition, $params);
        }
        
        $last_index = array_key_last($this->where);
        $this->where[$last_index] = "(" . $this->where[$last_index] . " OR $condition)";
        $this->set_params($params);
        return $this;
    }

    /**
     * Add GROUP BY clause
     *
     * @param array|string $fields
     * @return static
     */
    public function group_by(array|string $fields): static {
        $fields = is_array($fields) ? $fields : [$fields];
        $this->group_by = array_merge($this->group_by, $fields);
        return $this;
    }

    /**
     * Reset the GROUP BY clauses
     *
     * @return static
     */
    public function reset_group_by(): static {
        $this->group_by = [];
        return $this;
    }

    /**
     * Add HAVING condition
     *
     * @param string $condition
     * @param array $params Parameters for prepared statement
     * @return static
     */
    public function having(string $condition, array $params = []): static {
        $this->having[] = $condition;
        $this->set_params($params);
        return $this;
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $field
     * @param string $direction Sort direction (ASC|DESC)
     * @param bool $reset if the existing order must be emptied
     * @return static
     */
    public function order_by(string $field, string $direction = 'ASC'): static {
        $this->order_by[] = "$field $direction";
        return $this;
    }

    /**
     * Reset ORDER BY clauses
     *
     * @return static
     */
    public function reset_order_by(): static {
        $this->order_by = [];
        return $this;
    }

    /**
     * Set LIMIT and OFFSET for query
     *
     * @param int $limit
     * @param int $offset
     * @return static
     */
    public function limit(int $limit, int $offset = 0): static {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set a single parameter for prepared statements.
     *
     * @param string $key Parameter placeholder name
     * @param mixed $value Value associated with the placeholder
     */
    protected function set_param(string $key, mixed $value): void {
        $this->params[$key] = $value;
    }

    /**
     * Set multiple parameters for prepared statements at once.
     *
     * @param array $params Associative array of parameters (key => value)
     */
    protected function set_params(array $params): void {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Generate and return the final SQL query string.
     *
     * @return string The complete SQL query
     * @throws coding_exception if the FROM clause is not defined
     */
    public function to_sql(): string {
        if (!$this->from) {
            throw new coding_exception('FROM clause is required');
        }

        $select = 'SELECT ';
        if ($this->distinct) {
            $select .= 'DISTINCT ';
        }
        $select .= implode(', ', $this->fields);

        $sql = [$select, 'FROM ' . $this->from];

        if ($this->joins) {
            $sql = array_merge($sql, $this->joins);
        }

        if ($this->where) {
            $sql[] = 'WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->group_by) {
            $sql[] = 'GROUP BY ' . implode(', ', $this->group_by);
        }

        if ($this->having) {
            $sql[] = 'HAVING ' . implode(' AND ', $this->having);
        }

        if ($this->order_by) {
            $sql[] = 'ORDER BY ' . implode(', ', $this->order_by);
        }

        return implode("\n", $sql);
    }

    /**
     * Execute the generated SQL query and return a moodle_recordset.
     *
     * @return moodle_recordset The resulting set of records
     */
    public function get_recordset(): moodle_recordset {
        return $this->db->get_recordset_sql($this->to_sql(), $this->params, $this->offset, $this->limit);
    }

    /**
     * Count and return the number of records matched by the current query.
     *
     * @return int Number of matching records
     */
    public function count(): int {
        $query = clone $this;
        $query->select('COUNT(1)', true);
        $query->limit(0, 0);
        return $this->db->count_records_sql($query->to_sql(), $query->params);
    }

    /**
     * Check if at least one record matching the query exists.
     *
     * @return bool True if at least one record exists, otherwise false
     */
    public function exists(): bool {
        $query = clone $this;
        $query->select('1', true);
        $query->limit(1);
        return $this->db->record_exists_sql($query->to_sql(), $query->params);
    }

    /**
     * Get the first record from the query
     *
     * @return object|null
     */
    public function first() : ?object {
        $query = clone $this;
        $recordset = $query->limit(1)->get_recordset();
        $record = $recordset->current();
        $recordset->close();
        return $record ?: null;
    }


    /**
     * @param string $name Property name
     * @return mixed Property value
     * @throws \ErrorException if property does not exist
     */
    public function __get(string $name): mixed {
        if(property_exists($this, $name)){
            return $this->$name;
        }
    
        throw new \ErrorException("Property {$name} does not exist");
    }
}