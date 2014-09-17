<?php

namespace Hexagon\system\db;

use Hexagon\system\log\Logging;
use PDO;
use PDOStatement;

class DBAgentStatement {

    use Logging;

    const PARAM_BOOL = PDO::PARAM_BOOL;
    const PARAM_INT = PDO::PARAM_INT;
    const PARAM_STMT = PDO::PARAM_STMT;
    const PARAM_STR = PDO::PARAM_STR;
    const PARAM_NULL = PDO::PARAM_NULL;
    const PARAM_LOB = PDO::PARAM_LOB;

    private static $paramNames = [
        PDO::PARAM_BOOL => 'PARAM_BOOL',
        PDO::PARAM_INT => 'PARAM_LOB',
        PDO::PARAM_STMT => 'PARAM_STMT',
        PDO::PARAM_STR => 'PARAM_STR',
        PDO::PARAM_NULL => 'PARAM_NULL',
        PDO::PARAM_LOB => 'PARAM_LOB',
    ];

    /**
     * @var PDOStatement
     */
    private $stmt = NULL;

    private $sql = NULL;

    private $argCount = 0;

    private $args = [];

    public function __construct($sql, DBAgent $dbagent) {
        $this->sql = $sql;
        try {
            $this->stmt = $dbagent->getPDOInstance()->prepare($sql);
        } catch (DBAgentException $e) {
            self::_logErr($e);
            throw new DBAgentException('SQL prepared error [' . $e->getCode() . '], Message: ' . $e->getMessage() . '. SQL: ' . $sql . PHP_EOL . ' With PDO Message:' . $dbagent->getPDOInstance()->errorInfo()[2]);
        }
    }

    public function addStatementArg($data, $type = self::PARAM_STR) {
        $this->argCount++;
        $this->stmt->bindValue($this->argCount, $data, $type);
        $this->args[] = [$data, $type];
    }

    public function reset() {
        $this->stmt->closeCursor();
        $this->argCount = 0;
        $this->args = [];
    }

    /**
     * @return PDOStatement
     */
    public function getPDOStatement() {
        return $this->stmt;
    }

    public function getSQL() {
        return $this->sql;
    }

    public function buildArgsDebugInfo() {
        $debugInfo = $this->args;
        foreach ($debugInfo as &$arg) {
            $arg = [$arg[0], self::$paramNames[$arg[1]]];
        }
        return $debugInfo;
    }

    /**
     * Get the prepared SQL for debugging
     * MySQL only
     *
     * @return string
     */
    public function buildSQLDebugCode() {
        $count = count($this->args);

        $varNames = [];
        for ($i = 0; $i < $count; $i++) {
            $varNames[] = 'param' . $i;
        }

        $result = "\n" . 'PREPARE statement FROM "' . $this->sql . '"' . ";\n";
        $i = 0;
        $vars = [];
        $using = [];
        foreach ($this->args as $arg) {
            $p = '@p' . $i . '=';
            $p .= is_null($arg[0]) ? 'NULL' : '"' . addslashes($arg[0]) . '"';
            $vars[] = $p;
            $using[] = '@p' . $i;
            $i++;
        }
        $result .= 'SET ' . implode(', ', $vars) . ";\n";

        $result .= 'EXECUTE statement USING ' . implode(',', $using) . ";\n";

        return $result;
    }

}