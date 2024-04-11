<?php

require_once "./utils/dotEnv.php";

DotEnv::loadEnv();

/**
 * Gives a singleton instance to create a connection to a
 * MySQL database.
 * 
 * @author Edward Alexander Rodríguez Londoño
 */
class PhORM
{
    private static $instance;

    private mixed $dbConnection;
    private string $tableName;
    private object $stmt;
    private string $query = "";
    private array $placeholders = [];
    private string $types = "";
    private array $operators = ["=", "!=", "<", "<=", ">", ">="];

    private bool $selectFlag = false;
    private bool $insertFlag = false;
    private bool $updateFlag = false;
    private bool $deleteFlag = false;
    private bool $whereFlag = false;

    private function __construct()
    {
        $this->dbConnection = new mysqli(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_USER"],
            $_ENV["DATABASE_PASSWORD"],
            $_ENV["DATABASE_NAME"],
            $_ENV["DATABASE_PORT"]
        );

        if ($this->dbConnection->connect_errno) {
            throw new Exception(
                "Database connection failed: {$this->dbConnection->connect_error}"
            );
        }
    }

    /**
     * Returns an instance of the class.
     * @return PhORM
     * 
     * # Example:
     * 
     * ```
     * <?php
     * 
     * $databaseConnector = PhORM::getInstance();
     * ```
     */
    public static function getInstance(): PhORM
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Get the name of the table that is being used.
     * @return string The name of the table that is being used.
     */
    public function getTable(): string
    {
        return $this->tableName;
    }

    /**
     * Set the table to work on it.
     * @param string $tableName The name of the table to be used.
     */
    public function setTable(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Performs a SELECT operation.
     * @param string|array $columns The columns to be selected.
     * 
     * # Example:
     * 
     * ```
     * <?php
     * 
     * $db = PhORM::getInstance();
     * 
     * $db->setTable("users");
     * 
     * // Select all columns.
     * $result = $db->select()->execute();
     * 
     * // Select some columns passing a string as parameter.
     * $result = $db->select("id,username")->execute();
     * 
     * // Select some columns passing an array as parameter.
     * $result = $db->select(array("id", "username"))->execute();
     * 
     * ```
     */
    public function select(string|array $columns = "*")
    {
        if (gettype($columns) == "string") {
            $this->query = "SELECT {$columns} FROM {$this->tableName} ";
        } elseif (gettype($columns) == "array") {
            $columns = implode(", ", $columns);
            $this->query = "SELECT {$columns} FROM {$this->tableName} ";
        } else {
            throw new Exception(
                "The columns parameter must be of type string or array"
            );
        }

        $this->selectFlag = true;

        return $this;
    }

    /**
     * Performs an INSERT operation.
     * @param array $insertValues An associative array that contains the name of the column (key) 
     * and the value to be inserted (value).
     * 
     * # Example:
     * 
     * ```
     * <?php
     * 
     * $insertArray = array(
     *    "username" => "johnwick123",
     *    "first_name" => "John",
     *    "last_name" => "Wick"
     * );
     * 
     * $db = PhORM::getInstance();
     * 
     * $db->setTable("users");
     * 
     * $result = $db->insert($insertArray)->execute();
     * 
     * ```
     */
    public function insert(array $insertValues)
    {
        if ($this->selectFlag || $this->updateFlag || $this->deleteFlag) {
            throw new Exception(
                "This method can't be called after a SELECT, UPDATE or DELETE operation"
            );
        }

        $fields = '';
        $values = '';

        if (count($insertValues) == 1) {

        } else {
            foreach ($insertValues as $key => $value) {
                if (in_array($key, $this->operators)) {
                    throw new Exception(
                        "The first parameter specified in the array must be a valid column"
                    );
                } elseif (is_numeric($key)) {
                    throw new Exception(
                        "The first parameter specified in the array must be a valid column"
                    );
                }

                if ($fields == '') {
                    $fields = "`{$key}`";
                    $values = "?";
                } else {
                    $fields .= ", `{$key}`";
                    $values .= ", ?";
                }

                array_push($this->placeholders, $value);
                $this->types .= $this->getType($value);
            }
        }

        $this->query = "INSERT INTO {$this->tableName} ({$fields}) VALUES ({$values})";

        $this->insertFlag = true;

        return $this;
    }

    /**
     * Performs an UPDATE operation.
     * @param array $updateParams An associative array that contains the name of the column (key)
     * and the value to be updated (value).
     * 
     * # Example:
     * 
     * ```
     * <?php
     * 
     * $updateArray = array(
     *    "active" => 1
     * );
     * 
     * $db = PhORM::getInstance();
     * 
     * $db->setTable("users");
     * 
     * $result = $db->update($updateArray)->execute();
     * 
     * ```
     */
    public function update(array $updateParams)
    {
        if ($this->selectFlag || $this->insertFlag || $this->deleteFlag) {
            throw new Exception(
                "This method can't be called after a SELECT, INSERT or DELETE operation"
            );
        }

        if (count($updateParams) == 0) {
            throw new Exception("No params specified");  
        } 

        foreach ($updateParams as $key => $value) {
            if (in_array($key, $this->operators)) {
                throw new Exception(
                    "The first parameter specified in the array must be a valid column"
                );
            } elseif (is_numeric($key)) {
                throw new Exception(
                    "The first parameter specified in the array must be a valid column"
                );
            } elseif (!is_numeric($value) && !is_string($value)) {
                throw new Exception(
                    "The last parameter specified in the array must be a number or string"
                );
            }

            if (strpos($this->query, "UPDATE") === false) {
                $this->query = "UPDATE {$this->tableName} SET {$key} = ?";
            } else {
                $this->query .= ", {$key} = ? ";
            }

            array_push($this->placeholders, $value);
            $this->types .= $this->getType($value);
        }

        $this->updateFlag = true;

        return $this;
    }

    /**
     * Performs a DELETE operation.
     * 
     * # Example:
     * 
     * ```
     * <?php
     * 
     * $db = PhORM::getInstance();
     * 
     * $db->setTable("users");
     * 
     * $result = $db->delete()->execute();
     * 
     * ```
     */
    public function delete()
    {
        if ($this->insertFlag || $this->updateFlag || $this->deleteFlag) {
            throw new Exception(
                "This method can't be called after a INSERT, UPDATE or DELETE operation"
            );
        }

        $this->query = "DELETE FROM {$this->tableName} ";

        $this->deleteFlag = true;

        return $this;
    }

    /**
     * Sets the conditions to be executed in a SELECT, UPDATE or DELETE operation.
     * @param array $whereParams A three positions array that contains the column name, the condition operator and the value to filter.
     * 
     * # Example:
     * 
     * ```
     * <?php
     * 
     * $whereCondition1 = array(
     *    "username", "=", "johnwick123"
     * );
     * 
     * $whereCondition2 = array(
     *    "active", "=", 1
     * );
     * 
     * $db = PhORM::getInstance();
     * 
     * $db->setTable("users");
     * 
     * $result = $db->select()->where($whereCondition1, $whereCondition2)->execute();
     * 
     * // Using OrSql
     * 
     * $whereCondition3 = array(
     *    "username", "=", "johndoe"
     * );
     * 
     * $result = $db->select()->where(OrSql($whereCondition1, $whereCondition3))->execute();
     * 
     * ```
     */
    public function where(array ...$whereParams)
    {
        if ($this->insertFlag) {
            throw new Exception(
                "This method must be called after a SELECT, UPDATE or DELETE operation"
            );
        } else {
            if (count($whereParams) > 1) {
                foreach ($whereParams as $params) {
                    if (
                        (count($params) < 3 &&
                            !key_exists("sqlString", $params)) ||
                        count($params) > 3
                    ) {
                        throw new Exception(
                            "The array only can have 3 parameters: a column name, the operator and the filter value"
                        );
                    } elseif (count($params) == 2) {
                        if (strpos($this->query, "WHERE") === false) {
                            $this->query .= "WHERE ({$params["sqlString"]}) ";
                        } else {
                            $this->query .= "AND ({$params["sqlString"]}) ";
                        }

                        foreach ($params["placeholders"] as $placeholder) {
                            array_push($this->placeholders, $placeholder);
                            $this->types .= $this->getType($placeholder);
                        }
                    } else {
                        if (in_array($params[0], $this->operators)) {
                            throw new Exception(
                                "The first parameter specified in the array must be a valid column"
                            );
                        } elseif (is_numeric($params[0])) {
                            throw new Exception(
                                "The first parameter specified in the array must be a valid column"
                            );
                        } elseif (!in_array($params[1], $this->operators)) {
                            throw new Exception(
                                "The second parameter specified in the array must be a valid operator ('=', '!=', '<', '<=', '>', '>=')"
                            );
                        } elseif (
                            !is_numeric($params[2]) &&
                            !is_string($params[2])
                        ) {
                            throw new Exception(
                                "The last parameter specified in the array must be a number or string"
                            );
                        }

                        if (strpos($this->query, "WHERE") === false) {
                            $this->query .= "WHERE {$params[0]} {$params[1]} ? ";
                        } else {
                            $this->query .= "AND {$params[0]} {$params[1]} ? ";
                        }

                        array_push($this->placeholders, $params[2]);
                        $this->types .= $this->getType($params[2]);
                    }
                }

                $this->whereFlag = true;

                return $this;
            } elseif (count($whereParams) == 1) {
                $params = $whereParams[0];

                if (
                    (count($params) < 3 && !key_exists("sqlString", $params)) ||
                    count($params) > 3
                ) {
                    throw new Exception(
                        "The array only can have 3 parameters: a column name, the operator and the filter value"
                    );
                } elseif (count($params) == 2) {
                    $this->query .= "WHERE {$params["sqlString"]} ";

                    foreach ($params["placeholders"] as $placeholder) {
                        array_push($this->placeholders, $placeholder);
                        $this->types .= $this->getType($placeholder);
                    }
                } else {
                    if (in_array($params[0], $this->operators)) {
                        throw new Exception(
                            "The first parameter specified in the array must be a valid column"
                        );
                    } elseif (is_numeric($params[0])) {
                        throw new Exception(
                            "The first parameter specified in the array must be a valid column"
                        );
                    } elseif (!in_array($params[1], $this->operators)) {
                        throw new Exception(
                            "The second parameter specified in the array must be a valid operator ('=', '!=', '<', '<=', '>', '>=')"
                        );
                    } elseif (
                        !is_numeric($params[2]) &&
                        !is_string($params[2])
                    ) {
                        throw new Exception(
                            "The last parameter specified in the array must be a number or string"
                        );
                    }

                    $this->query .= "WHERE {$params[0]} {$params[1]} ? ";
                    array_push($this->placeholders, $params[2]);
                    $this->types .= $this->getType($params[2]);
                }

                $this->whereFlag = true;

                return $this;
            } else {
                throw new Exception("There's no filter params");
            }
        }
    }

    /**
     * Executes the specified SQL operation (SELECT, INSERT, UPDATE or DELETE).
     * @param bool $asJson Specify if the result should be returned as a JSON string or an associative array.
     * @return array|string The result of the execution of the SQL operation.
     */
    public function execute(bool $asJson = false): array|string
    {
        $this->prepare();

        if ($this->selectFlag || ($this->selectFlag && $this->whereFlag)) {
            $this->stmt->execute();
            $result = $this->stmt->get_result();
            $this->stmt->close();

            $this->selectFlag = false;
            $this->insertFlag = false;
            $this->updateFlag = false;
            $this->deleteFlag = false;
            $this->whereFlag = false;

            $dbResponse = "";

            if ($asJson) {
                $dbResponse = json_encode($result->fetch_all(MYSQLI_ASSOC));
            } else {
                $dbResponse = $result->fetch_all(MYSQLI_ASSOC);
            }

            return $dbResponse;
        } elseif (
            $this->insertFlag ||
            $this->updateFlag ||
            ($this->updateFlag && $this->whereFlag) ||
            $this->deleteFlag ||
            ($this->deleteFlag && $this->whereFlag)
        ) {
            $this->stmt->execute();
            $affectedRows = $this->stmt->affected_rows;
            $lastId = $this->stmt->insert_id;
            $this->stmt->close();

            $this->selectFlag = false;
            $this->insertFlag = false;
            $this->updateFlag = false;
            $this->deleteFlag = false;
            $this->whereFlag = false;

            $dbResponse = "";

            if ($asJson) {
                $dbResponse = json_encode([
                    "affectedRows" => $affectedRows,
                    "lastId" => $lastId,
                ]);
            } else {
                $dbResponse = [
                    "affectedRows" => $affectedRows,
                    "lastId" => $lastId,
                ];
            }

            return $dbResponse;
        } else {
            throw new Exception(
                "This method must be called after a SQL operation"
            );
        }
    }

    private function prepare(): void
    {
        echo $this->query . "\n";
        $this->stmt = $this->dbConnection->prepare($this->query);

        if ($this->types != "") {
            $this->stmt->bind_param($this->types, ...$this->placeholders);
        }
    }

    private function getType($value): string
    {
        $type = gettype($value);

        switch ($type) {
            case "integer":
                return "i";

            case "double":
                return "d";

            case "string":
                return "s";

            default:
                return "b";
        }
    }
}

?>
