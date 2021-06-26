<?php

    session_start();
    
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $result = new stdClass();

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

    $title = "";
    $author = "";
    $publisher = "";
    $minDate = "";
    $maxDate = "";
    $titleSelect = "";
    $authorSelect = "";
    $publisherSelect = "";

    if (isset($_POST["data"])){
        $data = json_decode($_POST["data"], true);
        $title = test_input($data["title"]);
        $author = test_input($data["author"]);
        $publisher = test_input($data["publisher"]);
        $minDate = test_input($data["minDate"]);
        $maxDate = test_input($data["maxDate"]);
        $titleSelect = test_input($data["titleSelect"]);
        $authorSelect = test_input($data["authorSelect"]);
        $publisherSelect = test_input($data["publisherSelect"]);
    }

    $tempArr = array();

    $line1 = "SELECT ISBN FROM EBOOK WHERE TITLE LIKE '%' || '$title' || '%' $titleSelect";
    $line2 = "SELECT EBOOK.ISBN FROM EBOOK, AUTHORS WHERE EBOOK.ISBN = AUTHORS.ISBN AND AUTHORS.AUTHOR LIKE '%' || '$author' || '%' $authorSelect";
    $line3 = "SELECT ISBN FROM EBOOK WHERE PUBLISHER LIKE '%' || '$publisher' || '%' $publisherSelect";

    if($title == "") {
        $line1 = "";
    }
    if($author == "") {
        $line2 = "";
    }
    if($publisher == "") {
        $line3 = "";
    }

    if($minDate == "" && $maxDate != "") {
        $stmt = $conn -> prepare("$line1
                                  $line2
                                  $line3
                                  SELECT ISBN
                                  FROM EBOOK
                                  WHERE YEAR <= '$maxDate'");
    }
    else if($minDate != "" && $maxDate == "") {
        $stmt = $conn -> prepare("$line1
                                  $line2
                                  $line3
                                  SELECT ISBN
                                  FROM EBOOK
                                  WHERE YEAR >= '$minDate'");
    }
    else if($minDate != "" && $maxDate != "") {
        $stmt = $conn -> prepare("$line1
                                  $line2
                                  $line3
                                  SELECT ISBN
                                  FROM EBOOK
                                  WHERE YEAR <= '$maxDate' AND YEAR >= '$minDate'");
    }
    else {
        $stmt = $conn -> prepare("$line1
                                  $line2
                                  $line3
                                  SELECT ISBN
                                  FROM EBOOK");
    }

    $stmt -> execute();

    while($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
        array_push($tempArr, $row['ISBN']) ;
    }
    $_SESSION["search"] = $tempArr;
    $result->state = "success";
    echo(json_encode($result));
    exit;
?>