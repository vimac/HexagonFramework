<?php

namespace Hexagon\system\db;

use \Closure;
use \PDO;
use \PDOException;
use \PDOStatement;
use Hexagon\system\log\Logging;

/**
 * DB access layer
 * @author Mac Chow, vifix.mac@gmail.com
 */
class DBAgent {
    
    use Logging;
	
	const PARAM_BOOL = PDO::PARAM_BOOL;
	const PARAM_INT = PDO::PARAM_INT;
	const PARAM_STMT = PDO::PARAM_STMT;
	const PARAM_STR = PDO::PARAM_STR;
	const PARAM_NULL = PDO::PARAM_NULL;
	const PARAM_LOB = PDO::PARAM_LOB;
	
	/**
	 * Update fields
	 * @var array
	 */
	private $updateField = [];
	
	/**
	 * Where fields
	 * @var array
	 */
	private $whereField = [];
	
	/**
	 * Prepared arguments, format:
	 * 
	 * [
	 *   $index => [
	 *       'value' => $value,
	 *       'type' => $type
	 *   ]
	 * ]
	 * 
	 * @var array
	 */
	private $argValue = [];
	
	/**
     * @var number
	 */
	public $lastInsertId = NULL;

	/**
	 * @var PDO
	 */
	protected $pdo;
	
	/**
     * @var array
	 */
	protected $parameters;
	
	/**
	 * @see DBAgent::getInstance()
	 */
	public function __construct($databaseParameters) {
	    $this->parameters = $databaseParameters;
	}
	
	/**
	 * Return current PDO instance
	 * @return PDO
	 */
	public function getPDOInstance() {
		if (class_exists('PDO', FALSE)) {
			extract($this->parameters);
			if ($this->pdo === NULL) {
				try{
					$this->pdo = new PDO($dsn, $username, $password, $options);
				} catch (PDOException $e) {
					self::logErr($e);
					throw new DBAgentException('PDO Initalized Failed. ' . $e->getCode() . ' ' . $e->getMessage());
				}
			}
			return $this->pdo;
		} else {
			throw new DBAgentException('PDO module is not exists.');
		}
	}
	
	
	/**
	 * Execute update SQL
	 * @param string $sql
	 * @param boolean $clearField clear fields after execution
	 * @throws DBAgentException
	 * @return integer Affected lines
	 */
	public function executeUpdate($sql, $clearField = TRUE) {
		self::logDebug('SQL: ' . $sql);
		
		$pdo = $this->getPDOInstance();
		$lines = -1;
		
		try {
			$statement = $pdo->prepare($sql);
			foreach ($this->argValue as $key => $val) {
				$statement->bindParam($key + 1, $val['val'], $val['type']);
			}
			self::logDebug('Params: ' . json_encode($this->argValue));
		} catch(PDOException $e) {
			self::logErr($e);
			throw new DBAgentException('SQL prepared error ' . $e->getCode() . ' ' . $e->getMessage() . ', SQL: [' . $sql . ']');
		}
		
		$result = $statement->execute();
		
		if ($result) {
			$lines = $statement->rowCount();
    		$this->lastInsertID = $pdo->lastInsertID();
		} else {
			throw new DBAgentException('SQL exec error, SQL: [' . $sql . '], arguments: [' . json_encode($this->argValue) . ']');
		}
		
		$statement->closeCursor();
		
		if ($clearField) {
			$this->clearFields();
		}
		
		return $lines;
	}
	
	
	/**
	 * Execute query SQL
	 * @param string $sql
	 * @param callable $callback
	 * @param mixed $args
	 * @param boolean $clearField clear fields after execution
	 * @throws DBAgentException 
	 */
	public function queryWithCallback($sql, $callback, $args = NULL, $clearField = TRUE) {
		
		self::logDebug('SQL: ' . $sql);
		
		$pdo = $this->getPDOInstance();
		$lines = -1;
		$ret = TRUE;
		
		try {
			$statement = $pdo->prepare($sql);
			foreach ($this->argValue as $key => $val) {
				$statement->bindParam($key+1, $val['val'], $val['type']);
			}
			self::logDebug('Params: ' . json_encode($this->argValue));
		} catch(PDOException $e) {
			self::logErr($e);
			throw new DBAgentException('SQL prepared error ' . $e->getCode() . ' ' . $e->getMessage() . ', SQL: [' . $sql . ']');
		}

		$result = $statement->execute();
		
		if ($result) {
		    if ($callback instanceof Closure) {
		        while ($line = $statement->fetch(PDO::FETCH_ASSOC)) {
		            $r = $callback($line, $args);
		            if ($r === FALSE) {
		                break;
		            }
		        }
		    } elseif (is_array($callback)) {
				while ($line = $statement->fetch(PDO::FETCH_ASSOC)) {
					$r = call_user_func_array($callback, array($line, $args));
					if ($r === FALSE) {
						break;
					}
				}
		    } else {
		        throw new DBAgentException('Error callback type');
		    }
		} else {
			$ret = FALSE;
		}
		
		$statement->closeCursor();
		
		if ($clearField) {
			$this->clearFields();
		}
		
		return $ret;
	}
	
	/**
	 * Execute SQL statement and return the result set
	 * @param string $sql
	 * @param boolean $clearField clear fields after execution
	 * @throws DBAgentException
	 * @return array Result set
	 */
	public function query($sql, $clearField = TRUE) {
		self::logDebug('SQL: ' . $sql);
		
		$pdo = $this->getPDOInstance();
		
		try {
			$statement = $pdo->prepare($sql);
			foreach ($this->argValue as $key => $val) {
				$statement->bindParam($key + 1, $val['val'], $val['type']);
			}
			self::logDebug('Params: ' . json_encode($this->argValue));
		} catch (PDOException $e) {
			self::logErr($e);
			throw new DBAgentException('SQL prepared error ' . $e->getCode() . ' ' . $e->getMessage() . ', SQL: [' . $sql . ']');
		}
	
		$result = $statement->execute();
		
		if ($result) {
			$rs = $statement->fetchAll(PDO::FETCH_ASSOC);
		} else {
			throw new DBAgentException('SQL exec error, SQL: [' . $sql . '], arguments: [' . json_encode($this->argValue) . ']');
		}
		
		if ($clearField) {
			$this->clearFields();
		}
		
		return $rs;
	}
	
	/**
     * Execute SQL statement and return the first line
	 * @param string $sql
	 * @param boolean $clearField clear fields after execution
	 * @throws DBAgentException
	 * @return array Result set
	 */
	public function queryOne($sql, $clearField = TRUE) {
	    return current($this->query($sql, $clearField));
	}
		
	/**
	 * Add update field
	 * @param string $field
	 */
	public function addUpdateField($field) {
		$this->updateField[] = $field;
	}

	/**
	 * Add where field
	 * @param string $field
	 */
	public function addWhereField($field) {
		$this->whereField[] = $field;
	}
	
	/**
	 * Check whether need update statement
	 * @return bool
	 */
	public function needUpdate() {
		return sizeof($this->updateField) > 0;
	}
	
	/**
	 * Get statement of update
	 * @return string SQL fragment
	 */
	public function getUpdateSQL() {
		$sb = '';
		foreach ($this->updateField as $s) {
			$sb .= ', ' . $s . ' ';
		}
		return substr($sb, 1);
	}

	/**
	 * Check whether need where statement
	 * @return bool
	 */
	public function needWhere() {
		return sizeof($this->whereField) > 0;
	}

	/**
	 * Get statement of where
	 * @return string SQL fragment
	 */
	public function getWhereSQL() {
		$sb = '';
		foreach ($this->whereField as $s) {
			$sb .= 'and ' . $s . ' ';
		}
		return substr($sb, 3);
	}
	
	public function addStatementArgs($data) {
	    foreach ($data as $d) {
	        if (is_array($d) && count($d) === 2) {
	            $this->argValue[] = ['val' => $d[0], 'type' => $d[1]];
	        } else {
	            $this->argValue[] = ['val' => $d, 'type' => self::PARAM_STR];
	        }
	    }
	}
	
	/**
	 * Add prepared statement argument
	 * @param mixed $val
	 * @param int $type
	 */
	public function addStatmentArg($val, $type = self::PARAM_STR) {
		$arg = array(
			'val' => $val,
			'type' => $type
		);
		$this->argValue[] = $arg;
	}

	/**
	 * Clear all fields
	 */
	public function clearFields() {
		$this->updateField = [];
		$this->whereField = [];
		$this->argValue = [];
	}
}

class DBAgentException extends \Exception{
}