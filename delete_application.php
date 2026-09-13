<?php
require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
if ($id) {
    $statement = $conn->prepare("DELETE FROM job_applications WHERE id = ?");
    $statement->bind_param("i", $id);
    $statement->execute();
    $statement->close();
}

header("Location: index.php?deleted=1");
exit;
