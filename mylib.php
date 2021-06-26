

<!-----------------------------------------------------------------------------------
    
    [README] mylib.php

    '내 서재' 라고 불리는 사용자 개인 페이지이다. 해당 페이지에서 대출현황과 예약현황을
    확인할 수 있으며 도서의 이름을 누르면 bookview.php 페이지가 새창으로 열려서
    반납이나 연장, 대출이나, 예약취소가 가능하다.

    1. 대출 현황
    내가 빌린 도서에 대해서 도서의 정보가 표시된다.
    도서 이름을 누르면 반납이나 대출 연장이 가능하다.

    2. 예약 현황
    내가 예약한 도서에 대해서 도서의 정보가 표시된다. 특히 예약한 날짜와 시간이 표시된다.
    도서 이름을 누르면 대출이나 예약취소가 가능하다.

 -------------------------------------------------------------------------------------->


<?php
    session_start();

    if(!isset($_SESSION["cno"])) {
        echo("<script src='log_fail.js'></script>");
    }
    
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v5.2.0/js/all.js"></script>
    <title>내 서재</title>
</head>
<body>
    <div id="divWrapper">

        <!-- Header -->
        <div id="divHeader">
            <div id="divGlobalMenu">
                <?php 
                    if(!isset($_SESSION["cno"])) {
                        echo "<a href='login.php' id='login'>로그인</a>";
                    } else {
                        echo "<button id='logout'>로그아웃</button>";
                        echo '<span id="currentName">'. $_SESSION['name'].'&nbsp;님 환영합니다'. '</span> &nbsp;&nbsp;';
                    }
                ?>
            </div>
                <div id="divMenu">
                    <ul class="menu">
                        <li id="logo"><a href="index.php">LIBRARY</a></li>
                        <li><a href="index.php">자료검색</a></li>
                        <li><a href="all.php">전체도서</a></li>
                        <li><a href="mylib.php">내 서재</a></li>
                        <li><a href="recodeRent.php">대출기록</a></li>
                    </ul>
                </div>
        </div>

        <!-- Index -->
        <div id="selectIndex">
            <h1>내 서재</h1>
        </div>

        <!-- 대출 현황 -->
        <div id="rent">
            <div class="info">
                <h5>대출 현황</h5>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ISBN</th>
                        <th scope="col">도서명</th>
                        <th scope="col">저자</th>
                        <th scope="col">출판사</th>
                        <th scope="col">대출날짜</th>
                        <th scope="col">반납날짜</th>
                        <th scope="col">연장횟수</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $cno = $_SESSION["cno"];
                        $stmt = $conn -> prepare("SELECT EBOOK.ISBN, EBOOK.TITLE, EBOOK.PUBLISHER, AUTHORS.AUTHOR, EBOOK.DATERENTED, EBOOK.DATEDUE, EBOOK.EXTTIMES 
                                                  FROM EBOOK, AUTHORS 
                                                  WHERE EBOOK.ISBN = AUTHORS.ISBN AND EBOOK.CNO = ?");
                        $stmt -> execute(array($cno));
                        while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <tr>
                            <th scope="row"><?= $row['ISBN'] ?></th>
                            <td>
                                <a onclick="window.open('bookview.php?bookId=<?= $row['ISBN'] ?>','name','resizable=no width=1040 height=455');return false" href="#">
                                    <?= $row['TITLE'] ?>
                                </a>
                            </td>
                            <td><?= $row['AUTHOR'] ?></td>
                            <td><?= $row['PUBLISHER'] ?></td>
                            <td><?= $row['DATERENTED'] ?></td>
                            <td><?= $row['DATEDUE'] ?></td>
                            <td><?= $row['EXTTIMES'] ?></td>
                        </tr>
                    <?php
                        }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- 예약 현황 -->
        <div id="reservation">
            <div class="info">
                <h5>예약 현황</h5>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ISBN</th>
                        <th scope="col">도서명</th>
                        <th scope="col">저자</th>
                        <th scope="col">출판사</th>
                        <th scope="col">예약날짜</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $cno = $_SESSION["cno"];
                        $stmt = $conn -> prepare("SELECT EBOOK.ISBN, EBOOK.TITLE, EBOOK.PUBLISHER, AUTHORS.AUTHOR, TO_CHAR(RESERVE.DATETIME,'YYYY/MM/DD HH24:MI:SS') AS DATETIME
                                                  FROM EBOOK, AUTHORS, RESERVE 
                                                  WHERE EBOOK.ISBN = RESERVE.ISBN 
                                                    AND EBOOK.ISBN = AUTHORS.ISBN
                                                    AND RESERVE.CNO = ?");
                        $stmt -> execute(array($cno));
                        while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <tr>
                            <th scope="row"><?= $row['ISBN'] ?></th>
                            <td>
                                <a onclick="window.open('bookview.php?bookId=<?= $row['ISBN'] ?>','name','resizable=no width=1040 height=455');return false" href="#">
                                    <?= $row['TITLE'] ?>
                                </a>
                            </td>
                            <td><?= $row['AUTHOR'] ?></td>
                            <td><?= $row['PUBLISHER'] ?></td>
                            <td><?= $row['DATETIME'] ?></td>
                        </tr>
                    <?php
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <footer class="text-center text-lg-start text-muted">
            <div class="icons">
                <div class="row">
                    <div>
                        <div class="mb-5 flex-center">
                            <a class="fb-ic" href="https://github.com/jeonbyeongmin" target="_blank">
                                <i class="fab fa-github white-text"></i>
                            </a>
                            <a class="ins-ic" href="https://www.instagram.com/jeonbyeongm1n/" target="_blank">
                                <i class="fab fa-instagram white-text"> </i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-copyright text-center py-3">© 2021 Copyright:
                <a href="https://github.com/jeonbyeongmin/"> jeonbyeongmin</a>
            </div>
        </footer>
    </div>
    <script src="logout.js"></script>
</body>
</html>