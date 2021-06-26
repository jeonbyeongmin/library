

<!-----------------------------------------------------------------------------------
    
    [README] all.php

    전체도서에 대한 php이다. 로그인한 사용자에게만 열람될 수 있으며 DB에 있는 모든 도서가
    특정 조건이나 필터없이 이름의 오름차순으로 회원들에게 보여진다. 
    도서의 이름을 누르면 bookview.php?해당도서 페이지가 새 창으로 열린다.

    all.php ==> bookview.php

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
    <title>전체 도서</title>
</head>
<body>
    <div id="divWrapper">

        <!-- HEADER -->
        <div id="divHeader">
            <div id="divGlobalMenu">
                <?php 
                    // 로그인을 하기 전에는 로그인 버튼이, 로그인 이후에는 회원의 이름과 로그아웃 버튼이 보여지도록 하였다.
                    if(!isset($_SESSION["cno"])) {
                        echo "<a href='login.php' id='login'>로그인</a>";
                    } 
                    else {
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

        <!-- index -->
        <div id="selectIndex">
            <h1>전체도서</h1>
        </div>
        
        <!-- 전체 도서가 들어갈 테이블 -->
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ISBN</th>
                    <th scope="col">도서명</th>
                    <th scope="col">저자</th>
                    <th scope="col">출판사</th>
                    <th scope="col">발행연도</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $stmt = $conn -> prepare("SELECT EBOOK.ISBN, EBOOK.TITLE, EBOOK.PUBLISHER, AUTHORS.AUTHOR, EBOOK.YEAR
                                              FROM EBOOK, AUTHORS 
                                              WHERE EBOOK.ISBN = AUTHORS.ISBN
                                              ORDER BY EBOOK.TITLE");
                                              
                    $stmt -> execute();
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
                        <td><?= $row['YEAR'] ?></td>
                    </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
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