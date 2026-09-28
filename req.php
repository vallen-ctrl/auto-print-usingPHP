<?php 
    $gender = $_POST["ori"] ?? null;
    echo "echo $gender";
    header("location: ini.php");
    exit;
?>