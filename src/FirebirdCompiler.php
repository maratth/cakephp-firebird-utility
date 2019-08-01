<?php
/**
 * Copyright 2016 Maicon Amarante
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 Maicon Amarante
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakephpFirebird;

use Cake\Database\QueryCompiler;
use Cake\Database\Schema\Collection;
use Cake\Database\Type;

/**
 * Responsible for compiling a Query object into its SQL representation
 * for Firebird
 *
 * @internal
 */
class FirebirdCompiler extends QueryCompiler
{

    /**
     * {@inheritDoc}
     */
    protected $_templates = [
        'delete' => 'DELETE',
        'update' => 'UPDATE %s',
        'where' => ' WHERE %s',
        'group' => ' GROUP BY %s ',
        'having' => ' HAVING %s ',
        'order' => ' %s',
        'offset' => '',
        'epilog' => ' %s'
    ];

    /**
     * {@inheritDoc}
     */
    protected $_selectParts = [
        'select', 'from', 'join', 'where', 'group', 'having', 'epilog',
        'order', 'union'
    ];

    /**
     * If fb3 bool is managed.
     *
     * @var bool
     */
    protected $_fb3Boolean = false;

    public function __construct()
    {
        $this->_fb3Boolean = Type::getMap('boolean') === '\CakephpFirebird\Type\BooleanType';
    }

    /**
     * @param array $parts
     * @param \Cake\Database\Query $query
     * @param \Cake\Database\ValueBinder $generator
     * @return string
     */
    protected function _buildSelectPart($parts, $query, $generator)
    {
        $driver = $query->connection()->driver();
        $select = 'SELECT %s%s%s';
        $distinct = $query->clause('distinct');
        $modifiers = $query->clause('modifier') ?: [];
		$modifiers += $this->_extractLimitOffsetPart($query);

        $normalized = [];
        $parts = $this->_stringifyExpressions($parts, $generator);

        $defaultTypes = $query->getDefaultTypes();
        foreach ($parts as $k => $p) {
            if ($this->_fb3Boolean && isset($defaultTypes[$p]) && $defaultTypes[$p] === 'boolean') {
                $p = 'CAST(' . $p . ' AS VARCHAR(5))';
            }

            if (!is_numeric($k)) {
                $p = $p . ' AS "' . $k . '"';
            }
            $normalized[] = $p;
        }

        if ($distinct === true) {
            $distinct = 'DISTINCT ';
        }

        if (is_array($distinct)) {
            $distinct = $this->_stringifyExpressions($distinct, $generator);
            $distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $distinct));
        }
        if ($modifiers !== null) {
            $modifiers = $this->_stringifyExpressions($modifiers, $generator);
            $modifiers = implode(' ', $modifiers) . ' ';
        } else {
			$modifiers = null;
		}

        return sprintf($select, $modifiers, $distinct, implode(', ', $normalized));
    }

    /**
     * Builds the SQL string for all the UNION clauses in this query, when dealing
     * with query objects it will also transform them using their configured SQL
     * dialect.
     *
     * @param array $parts list of queries to be operated with UNION
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildUnionPart($parts, $query, $generator) {
        $parts = array_map(function ($p) use ($generator) {
            $p['query'] = $p['query']->sql($generator);
            $p['query'] = $p['query'][0] === '(' ? trim($p['query'], '()') : $p['query'];
            $prefix = $p['all'] ? 'ALL ' : '';
            if ($this->_orderedUnion) {
                return "{$prefix} {$p['query']}";
            }

            return $prefix . $p['query'];
        }, $parts);

        if ($this->_orderedUnion) {
            return sprintf("\nUNION %s", implode("\nUNION ", $parts));
        }

        return sprintf("\nUNION %s", implode("\nUNION ", $parts));
    }

    /**
     * Generates the INSERT part of a SQL query
     *
     * To better handle concurrency and low transaction isolation levels,
     * we also include an OUTPUT clause so we can ensure we get the inserted
     * row's data back.
     *
     * @param array $parts The parts to build
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildInsertPart($parts, $query, $generator)
    {
        $table = $parts[0];
        $columns = $this->_stringifyExpressions($parts[1], $generator);

        return sprintf('INSERT INTO %s (%s) ', $table, implode(', ', $columns));
    }

    /**
     * @param array $parts
     * @param \Cake\Database\Query $query
     * @param \Cake\Database\ValueBinder $generator
     * @return string
     */
    protected function _buildValuesPart($parts, $query, $generator)
    {
        $values = $parts[0];

        if (strpos( $parts[0], 'UNION ALL')) {
            $values = str_replace('(SELECT ', 'SELECT ', $values);
            $values = str_replace(')', ' FROM RDB$DATABASE', $values);
        }

        // Add returning with primary key column
        $schema = $query->connection()->schemaCollection();
        $primaryKey = $schema->describe($query->clause('insert')[0])->primaryKey();
        if (count($primaryKey)) {
            $values .= ' RETURNING ';
            $primaryKey = array_map(function ($key) {
                return $key . ' AS "' . $key . '"';
            }, $primaryKey);

            $values .= join(', ', $primaryKey);
        }

        return trim($values);
    }

    /**
     * Generates the LIMIT part of a Firebird
     *
     * @param int $limit the limit clause
     * @param \Cake\Database\Query $query The query that is being compiled
     * @return string
     */
    protected function _buildLimitPart($limit, $query)
    {
        return false;
    }

	/**
     * Extract Firebird limit / offset part instance of modifiers clause.
     * This function is executed in _buildSelectPart
     *  because First and Skip must be after SELECT but before selected fields.
     *
     * @param Query $query
     * @return array Limit and Skip clause.
     */
    private function _extractLimitOffsetPart($query) {
        $modifiers = [];

        $skip = false;
        $limit = $query->clause('limit');
        $offset = $query->clause('offset');

        if (isset($query->clause('select')['count'])) {
            //TODO instanceof \Cake\Database\Expression\FunctionExpression)
            $skip = true;
        }

        if ($limit && !$offset && !$skip) {
            $modifiers = ['_auto_top_' => sprintf('FIRST %d', $limit)];
        }

        if ($limit && $offset && !$skip) {
            $modifiers = ['_auto_top_' => sprintf('FIRST %d SKIP %d', $limit, $offset)];
        }

        if ($skip) {
            $modifiers = ['_auto_top_' => ''];
        }

        return $modifiers;
    }
}
