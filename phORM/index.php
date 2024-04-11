<?php

require_once "./database/PhORM.php";
require_once "./database/utils/OrSql.php";

$db = PhORM::getInstance();

$db->setTable("users");
$testInsert = array(
    "first_name" => "Luke",
    "last_name" => "Skywalker",
    "username" => "lw123",
    "password" => md5("holamundo123")
);

// SELECT
$result = $db->select()->execute();
var_dump($result);

// SELECT ONE
$result = $db->select()->where(["username", "=", "johnwick123"])->execute();
var_dump($result);

// INSERT
$result = $db->insert($testInsert)->execute();
var_dump($result);

// UPDATE
$result = $db->update(["username", "luke123"])->where(["id", "=", "3"])->execute();
var_dump($result);

// DELETE
$result = $db->delete()->where(["id", "=", "3"])->execute();
var_dump($result);

?>
