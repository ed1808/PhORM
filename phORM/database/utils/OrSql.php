<?php

/**
 * This function creates and returns a string that contains a SQL OR operation.
 * @param array $orParams A three position array.
 * @return array An array that contains the SQL OR statement and the array params to be binded in the prepared statement.
 * @throws Exception If is given just one array as a parameter.
 * 
 * # Example:
 * ```
 * <?php
 * 
 * $fields1 = array("username", "=", "johnwick123");
 * $fields2 = array("username", "=", "johndoe");
 * $or = OrSQL($fields1, $fields2);
 * 
 * var_dump($or); // => array("sqlString" => "username = ? OR username = ?", "placeholders" => array("jhonwick123", "johndoe"))
 * ```
 */
function OrSql(array ...$orParams): array {
    $sqlString = '';
    $operators = array("=", "!=", "<", "<=", ">", ">=");
    $orPlaceholders = array();

    if (count($orParams) > 1) {

        foreach ($orParams as $params) {
            if (count($params) > 3) {
                throw new Exception("The array only can have 3 parameters: a column name, the operator and the filter value");
            } else if (in_array($params[0], $operators)) {
                throw new Exception("The first parameter specified in the array must be a valid column");
            } else if (is_numeric($params[0])) {
                throw new Exception("The first parameter specified in the array must be a valid column");
            } else if (!in_array($params[1], $operators)) {
                throw new Exception("The second parameter specified in the array must be a valid operator ('=', '!=', '<', '<=', '>', '>=')");
            } else if (!is_numeric($params[2]) && !is_string($params[2])) {
                throw new Exception("The last parameter specified in the array must be a number or string");
            }

            if ($sqlString == '') {
                $sqlString .= "{$params[0]} {$params[1]} ? ";
            } else {
                $sqlString .= "OR {$params[0]} {$params[1]} ? ";
            }

            array_push($orPlaceholders, $params[2]);
        }

    } else if (count($orParams) == 1) {
        throw new Exception("You must specify at least two parameters");
    } else {
        throw new Exception("No parameters specified");
    }

    return array(
        "sqlString" => $sqlString,
        "placeholders" => $orPlaceholders
    );
}

?>
