<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2013, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 * Extends CDbCommand
 */
class DbCommand extends \CDbCommand
{
	/**
	 * Returns the total number of rows matched by the query.
	 *
	 * @param string $column The column to count.
	 * @return int The total number of rows matched by the query.
	 */
	public function count($column)
	{
		if (is_object($column))
		{
			$column = (string) $column;
		}
		else if (strpos($column, '(') === false)
		{
			if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $column, $matches))
			{
				$column = craft()->db->quoteColumnName($matches[1]).' AS '.craft()->db->quoteColumnName($matches[2]);
			}
			else
			{
				$column = craft()->db->quoteColumnName($column);
			}
		}

		return (int) $this->select("count({$column})")->queryScalar();
	}

	/**
	 * Adds additional select columns.
	 *
	 * @param string $columns
	 * @return DbCommand
	 */
	public function addSelect($columns = '*')
	{
		$oldSelect = $this->getSelect();
		if ($oldSelect)
		{
			$columns = str_replace('`', '', $oldSelect).','.$columns;
		}
		$this->setSelect($columns);
		return $this;
	}

	/**
	 * @param $tables
	 * @return \CDbCommand
	 */
	public function from($tables)
	{
		$tables = DbHelper::addTablePrefix($tables);
		return parent::from($tables);
	}

	/**
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function where($conditions, $params = array())
	{
		if (!$conditions)
		{
			return $this;
		}

		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::where($conditions, $params);
	}

	/**
	 * Adds an additional "and where" condition.
	 *
	 * @param mixed $conditions
	 * @param array|null $params
	 * @return DbCommand
	 */
	public function andWhere($conditions, $params = array())
	{
		if (!$conditions)
		{
			return $this;
		}

		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::andWhere($conditions, $params);
	}

	/**
	 * Adds an additional "or where" condition.
	 *
	 * @param mixed $conditions
	 * @param array|null $params
	 * @return DbCommand
	 */
	public function orWhere($conditions, $params = array())
	{
		if (!$conditions)
		{
			return $this;
		}

		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::orWhere($conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function join($table, $conditions, $params = array())
	{
		$table = DbHelper::addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::join($table, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function leftJoin($table, $conditions, $params = array())
	{
		$table = DbHelper::addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::leftJoin($table, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function rightJoin($table, $conditions, $params = array())
	{
		$table = DbHelper::addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::rightJoin($table, $conditions, $params);
	}

	/**
	 * @param $table
	 * @return DbCommand
	 */
	public function crossJoin($table)
	{
		$table = DbHelper::addTablePrefix($table);
		return parent::crossJoin($table);
	}

	/**
	 * @param $table
	 * @return DbCommand
	 */
	public function naturalJoin($table)
	{
		$table = DbHelper::addTablePrefix($table);
		return parent::naturalJoin($table);
	}

	/**
	 * @param mixed $conditions
	 * @param array $params
	 * @return DbCommand
	 */
	public function having($conditions, $params = array())
	{
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::having($conditions, $params);
	}

	/**
	 * @param mixed $columns
	 * @return DbCommand
	 */
	public function addOrder($columns)
	{
		$oldOrder = $this->getOrder();
		if ($oldOrder)
		{
			return $this->order(array($oldOrder, $columns));
		}
		else
		{
			return $this->order($columns);
		}
	}

	/**
	 * @param string $table
	 * @param array  $columns
	 * @param bool   $includeAuditColumns
	 * @return int
	 */
	public function insert($table, $columns, $includeAuditColumns = true)
	{
		$table = DbHelper::addTablePrefix($table);

		if ($includeAuditColumns)
		{
			$columns['dateCreated'] = DateTimeHelper::currentTimeForDb();
			$columns['dateUpdated'] = DateTimeHelper::currentTimeForDb();
			$columns['uid']         = StringHelper::UUID();
		}

		return parent::insert($table, $columns);
	}

	/**
	 * @param string $table
	 * @param array  $columns
	 * @param array  $rows
	 * @param bool   $includeAuditColumns
	 * @return int
	 */
	public function insertAll($table, $columns, $rows, $includeAuditColumns = true)
	{
		if (!$rows)
		{
			return 0;
		}

		$table = DbHelper::addTablePrefix($table);

		if ($includeAuditColumns)
		{
			$columns[] = 'dateCreated';
			$columns[] = 'dateUpdated';
			$columns[] = 'uid';

			foreach ($rows as &$row)
			{
				$row[] = DateTimeHelper::currentTimeForDb();
				$row[] = DateTimeHelper::currentTimeForDb();
				$row[] = StringHelper::UUID();
			}
		}

		$queryParams = $this->getConnection()->getSchema()->insertAll($table, $columns, $rows);
		return $this->setText($queryParams['query'])->execute($queryParams['params']);
	}

	/**
	 * @param string $table
	 * @param array  $keyColumns
	 * @param array  $updateColumns
	 * @param bool   $includeAuditColumns
	 * @return int
	 */
	public function insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns = true)
	{
		if ($includeAuditColumns)
		{
			$keyColumns['dateCreated']    = DateTimeHelper::currentTimeForDb();
			$keyColumns['uid']            = StringHelper::UUID();
			$updateColumns['dateUpdated'] = DateTimeHelper::currentTimeForDb();
		}

		// TODO: This is all MySQL specific

		$allColumns = array_merge($keyColumns, $updateColumns);
		$params = array();

		$table = DbHelper::addTablePrefix($table);
		$sql = 'INSERT INTO '.craft()->db->quoteTableName($table).' (';

		foreach (array_keys($allColumns) as $i => $column)
		{
			if ($i > 0)
			{
				$sql .= ', ';
			}

			$sql .= craft()->db->quoteColumnName($column);

			$params[':'.$column] = $allColumns[$column];
		}

		$sql .= ') VALUES (:'.implode(', :', array_keys($allColumns)).')' .
		        ' ON DUPLICATE KEY UPDATE ';

		foreach (array_keys($updateColumns) as $i => $column)
		{
			if ($i > 0)
			{
				$sql .= ', ';
			}

			$sql .= craft()->db->quoteColumnName($column).' = :'.$column;
		}

		return $this->setText($sql)->execute($params);
	}

	/**
	 * @param string $table
	 * @param array  $columns
	 * @param mixed  $conditions
	 * @param array  $params
	 * @param bool   $includeAuditColumns
	 * @return int
	 */
	public function update($table, $columns, $conditions = '', $params = array(), $includeAuditColumns = true)
	{
		$table = DbHelper::addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);

		if ($includeAuditColumns)
		{
			$columns['dateUpdated'] = DateTimeHelper::currentTimeForDb();
		}

		return parent::update($table, $columns, $conditions, $params);
	}

	/**
	 * @param string $table
	 * @param mixed  $conditions
	 * @param array  $params
	 * @return int
	 */
	public function delete($table, $conditions = '', $params = array())
	{
		$table = DbHelper::addTablePrefix($table);
		$conditions = $this->_normalizeConditions($conditions, $params);
		return parent::delete($table, $conditions, $params);
	}

	/**
	 * Adds `id`, `dateCreated`, `date_update`, and `uid` columns to $columns,
	 * packages up the column definitions into strings,
	 * and then passes it back to CDbCommand->createTable()
	 *
	 * @param string $table
	 * @param array $columns
	 * @param null  $options
	 * @param bool  $addIdColumn
	 * @param bool  $addAuditColumns
	 * @return int
	 */
	public function createTable($table, $columns, $options=null, $addIdColumn = true, $addAuditColumns = true)
	{
		$table = DbHelper::addTablePrefix($table);

		$columns = array_merge(
			($addIdColumn ? array('id' => ColumnType::PK) : array()),
			$columns,
			($addAuditColumns ? DbHelper::getAuditColumnConfig() : array())
		);

		foreach ($columns as $col => $settings)
		{
			$columns[$col] = DbHelper::generateColumnDefinition($settings);
		}

		// Create the table
		return parent::createTable($table, $columns, $options);
	}

	/**
	 * @param $table
	 * @param $newName
	 * @return int
	 */
	public function renameTable($table, $newName)
	{
		$table = DbHelper::addTablePrefix($table);
		$newName = DbHelper::addTablePrefix($newName);
		return parent::renameTable($table, $newName);
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function dropTable($table)
	{
		$table = DbHelper::addTablePrefix($table);
		return parent::dropTable($table);
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function dropTableIfExists($table)
	{
		$table = DbHelper::addTablePrefix($table);
		$sql = $this->getConnection()->getSchema()->dropTableIfExists($table);
		return $this->setText($sql)->execute();
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function truncateTable($table)
	{
		$table = DbHelper::addTablePrefix($table);
		return parent::truncateTable($table);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumn($table, $column, $type)
	{
		// Keep new columns before the dateCreated audit column
		return $this->addColumnBefore($table, $column, $type, 'dateCreated');
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumnFirst($table, $column, $type)
	{
		$table = DbHelper::addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnFirst($table, $column, $type))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 * @return mixed
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		$table = DbHelper::addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnBefore($table, $column, $type, $before))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 * @return mixed
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		$table = DbHelper::addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->addColumnAfter($table, $column, $type, $after))->execute();
	}

	/**
	 * @param $table
	 * @param $column
	 * @return int
	 */
	public function dropColumn($table, $column)
	{
		$table = DbHelper::addTablePrefix($table);
		return parent::dropColumn($table, $column);
	}

	/**
	 * @param $table
	 * @param $name
	 * @param $newName
	 * @return int
	 */
	public function renameColumn($table, $name, $newName)
	{
		$table = DbHelper::addTablePrefix($table);
		return parent::renameColumn($table, $name, $newName);
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param      $after
	 * @return int
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		$table = DbHelper::addTablePrefix($table);
		$type = DbHelper::generateColumnDefinition($type);
		return $this->setText($this->getConnection()->getSchema()->alterColumn($table, $column, $type, $newName, $after))->execute();
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $refTable
	 * @param $refColumns
	 * @param null $delete
	 * @param null $update
	 * @return int
	 */
	public function addForeignKey($table, $columns, $refTable, $refColumns, $delete = null, $update = null)
	{
		$name = DbHelper::getForeignKeyName($table, $columns);
		$table = DbHelper::addTablePrefix($table);
		$refTable = DbHelper::addTablePrefix($refTable);
		return parent::addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function dropForeignKey($table, $columns)
	{
		$name = DbHelper::getForeignKeyName($table, $columns);
		$table = DbHelper::addTablePrefix($table);
		return parent::dropForeignKey($name, $table);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param bool $unique
	 * @return int
	 */
	public function createIndex($table, $columns, $unique = false)
	{
		$name = DbHelper::getIndexName($table, $columns, $unique);
		$table = DbHelper::addTablePrefix($table);
		return parent::createIndex($name, $table, $columns, $unique);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * $param $unique
	 * @param bool   $unique
	 * @return int
	 */
	public function dropIndex($table, $columns, $unique = false)
	{
		$name = DbHelper::getIndexName($table, $columns, $unique);
		$table = DbHelper::addTablePrefix($table);
		return parent::dropIndex($name, $table);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function addPrimaryKey($table, $columns)
	{
		$name = DbHelper::getPrimaryKeyName($table, $columns);
		$table = DbHelper::addTablePrefix($table);
		return parent::addPrimaryKey($name, $table, $columns);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function dropPrimaryKey($table, $columns)
	{
		$name = DbHelper::getPrimaryKeyName($table, $columns);
		$table = DbHelper::addTablePrefix($table);
		return parent::dropPrimaryKey($name, $table);
	}

	/**
	 * Adds support for array('column' => 'value') conditional syntax.
	 * Supports nested conditionals, e.g. array('or', array('column' => 'value'), array('column2' => 'value2'))
	 *
	 * @param mixed $conditions
	 * @param array &$params
	 * @return mixed
	 */
	private function _normalizeConditions($conditions, &$params = array())
	{
		if (!is_array($conditions))
		{
			return $conditions;
		}
		else if ($conditions === array())
		{
			return '';
		}

		$normalizedConditions = array();

		// Find any key/value pairs and convert them to the CDbCommand's conditional syntax
		foreach ($conditions as $key => $value)
		{
			if (!is_numeric($key))
			{
				$param = ':p'.StringHelper::randomString(9);
				$normalizedConditions[] = $key.'='.$param;
				$params[$param] = $value;
				unset($conditions[$key]);
			}
			else
			{
				$conditions[$key] = $this->_normalizeConditions($value, $params);
			}
		}

		if ($normalizedConditions)
		{
			// Were there normal conditions in there as well?
			if ($conditions)
			{
				// Is this already an AND conditional?
				if (strtolower($conditions[0]) == 'and')
				{
					// Just merge our normalized conditions into the $conditions
					$conditions = array_merge($conditions, $normalizedConditions);
				}
				else
				{
					// Append the normalized conditions as nested AND conditions
					array_unshift($normalizedConditions, 'and');
					$conditions[] = $normalizedConditions;
				}
			}
			else
			{
				if (count($normalizedConditions) == 1)
				{
					$conditions = $normalizedConditions[0];
				}
				else
				{
					array_unshift($normalizedConditions, 'and');
					$conditions = $normalizedConditions;
				}
			}
		}

		return $conditions;
	}
}
