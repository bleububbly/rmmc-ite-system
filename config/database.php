<?php

    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "ite_event_system";

    $conn=mysqli_connect(
        $host,
        $user,
        $password,
        $db
    );

    if(!$conn){
        die(
            "Database connection failed"
        );
    }

    mysqli_set_charset(
        $conn,
        "utf8"
    );

?>