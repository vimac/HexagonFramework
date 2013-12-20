<?php

namespace Hexagon\system\db;

use \Closure;
use \PDO;
use \PDOException;
use \PDOStatement;
use \Hexagon\system\log\Logging;

/**
 * DB access layer
 * @author Mac Chow, vifix.mac@gmail.com
 */
class DBAgent {
    
    use Logging;
    
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
            if ($this->pdo === NULL) {
                extract($this->parameters);
                try{
                    $this->pdo = new PDO($dsn, $username, $password, $options);
                } catch (PDOException $e) {
                    self::_logErr($e);
                    throw new DBAgentException('PDO Initalized Failed. ' . $e->getCode() . ' ' . $e->getMessage());
                }
            }
            return $this->pdo;
        } else {
            throw new DBAgentException('PDO module is not exists.');
        }
    }
    
    public function prepare($sql) {
        return new DBAgentStatement($sql, $this);
    }
    
    /**
     * Execute update SQL
     * @param DBAgentStatement $st
     * @throws DBAgentException
     * @return integer Affected lines
     */
    public function executeUpdate(DBAgentStatement $st) {
        self::_logDebug('SQL: [' . $st->getSQL() . ']. with Params: ' . json_encode($st->buildArgsDebugInfo()));
        
        $pdo = $this->getPDOInstance();
        $lines = -1;
        
        $pdoStatement = $st->getPDOStatement();
        
        $result = $pdoStatement->execute();
        
        if ($result) {
            $this->lastInsertId = $pdo->lastInsertId();
            $lines = $pdoStatement->rowCount();
        } else {
            $sql = $st->buildSQLDebugCode();
            throw new DBAgentException('SQL exec error, with PDO error message: "' . $pdoStatement->errorInfo()[2] . '", check SQL below:' . $sql);
        }
        
        $st->reset();
        
        return $lines;
    }
    
    
    /**
     * Execute query SQL with callback
     * @param DBAgentStatement $st
     * @param Closure $callback
     * @throws DBAgentException 
     */
    public function queryWithCallback(DBAgentStatement $st, Closure $callback) {
        self::_logDebug('SQL: [' . $st->getSQL() . ']. with Params: ' . json_encode($st->buildArgsDebugInfo()));
        
        $pdo = $this->getPDOInstance();
        $lines = -1;
        $ret = TRUE;
        
        $pdoStatement = $st->getPDOStatement();

        $result = $pdoStatement->execute();
        
        if ($result) {
            $index = 0;
            while ($line = $pdoStatement->fetch(PDO::FETCH_ASSOC)) {
                $r = $callback($line, $index);
                $index++;
                if ($r === FALSE) {
                    break;
                }
            }
        } else {
            $ret = FALSE;
        }
        
        $st->reset();
        
        return $ret;
    }
    
    /**
     * Execute SQL statement and return the result set
     * @param DBAgentStatement $st
     * @throws DBAgentException
     * @return array Result set
     */
    public function query(DBAgentStatement $st) {
        self::_logDebug('SQL: [' . $st->getSQL() . ']. with Params: ' . json_encode($st->buildArgsDebugInfo()));
        
        $pdo = $this->getPDOInstance();
        
        $pdoStatement = $st->getPDOStatement();
    
        $result = $pdoStatement->execute();
        
        if ($result) {
            $rs = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = $st->buildSQLDebugCode();
            throw new DBAgentException('SQL exec error, with PDO error message: "' . $pdoStatement->errorInfo()[2] . '", check SQL below:' . $sql);
        }
        
        return $rs;
    }
    
    /**
     * Execute SQL statement and return the first line
     * @param DBAgentStatement $sql
     * @throws DBAgentException
     * @return array Result set
     */
    public function queryOne(DBAgentStatement $st) {
        return current($this->query($st));
    }
        
    
    public function beginTransaction() {
        return $this->getPDOInstance()->beginTransaction();
    }
    
    public function commit() {
        return $this->getPDOInstance()->commit();
    }
    
}

class DBAgentException extends \Exception{
}