<?php 
session_start();

if(isset($_SESSION["cno"])){
    $data = new stdClass();
    session_destroy();          // 세션을 끊어준다.
    $data->state = "logout";
    echo(json_encode($data));
    exit;
}
else {
    $data->state = "error";
    echo(json_encode($data));
    exit;
}
?>