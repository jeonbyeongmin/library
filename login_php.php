
<?php

    session_start();

    $tns = "
        (DESCRIPTION =
            (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST= 10.211.55.3)(PORT=1521)))
            (CONNECT_DATA=(SERVICE_NAME=XE))
        )
    ";

    $dsn = "oci:dbname=".$tns.";charset=utf8";
    $username = 'c##Library';
    $password = '7613';
    try {
        $conn = new PDO($dsn, $username, $password);
    }
    catch (PDOException $e) {
        echo("에러 내용: ".$e -> getMessage());
    }

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    function login(){
        global $conn;

        $result = new stdClass();
        $cno = $pw = $email = "";

        if (isset($_POST["data"])){ // validation check
            $data = json_decode($_POST["data"], true);
            $cno = test_input($data["id"]);
            $pw = test_input($data["pw"]);
        }

        $stmt = $conn -> prepare("SELECT NAME, CNO, EMAIL, PASSWD FROM CUSTOMER WHERE CNO = ?");
        $stmt -> execute(array($cno));

        if($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
            if($pw == $row['PASSWD']){
                $cno = $row['CNO'];
                $pw = $row["PASSWD"];
                $email = $row["EMAIL"];
                $name = $row["NAME"];

                $_SESSION["cno"] = $cno;
                $_SESSION["pw"] = $pw;
                $_SESSION["email"] = $email;
                $_SESSION["name"] = $name;

                $result->state = "success";
                echo(json_encode($result));
                exit;
            }
            else {
                $result->state = "fail";
                echo(json_encode($result));
                exit;
            }
        }
    }

    login();

?>