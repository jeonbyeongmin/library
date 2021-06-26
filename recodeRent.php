

<!-----------------------------------------------------------------------------------
    
    [README] recodeRent.php

    대출 기록이라고 부르는 페이지이다. 해당 페이지는 관리자만 열람 가능하고
    관리자는 cno(id) = 100 인 계정만 접근 가능하다.

    이 페이지에서는 사용자들이 대출, 반납한 기록을 통해 다양한 통계 자료를
    확인할 수 있다.

    1. 빌린 횟수 순위
    가장 많이 빌려진 도서부터 내림차순으로 출력된다.

    2. 도서/회원 빌린횟수
    빌린 횟수 순위보다 더 세부적으로 어떤 책이 어떤 회원에 의해 몇번 빌려졌는지 확인할 수 있다.

    3. 도서/회원 오래된 대출 기록
    오래된 도서의 대출 기록을 확인할 수 있다.

 -------------------------------------------------------------------------------------->


 <?php
    session_start();
    
    if(!isset($_SESSION["cno"])) {
        echo("<script src='log_fail.js'></script>");
    }
    if($_SESSION["cno"] != 100) {
        echo("<script src='admin_fail.js'></script>");
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
        <title>Statistics</title>
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
                <h1>대출 기록</h1>
            </div>

            <!-- 빌린 횟수 순위 -->
            <div id="rankOfRent">
                <div class="info">
                    <h5>빌린횟수 순위</h5>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ISBN</th>
                            <th scope="col">도서명</th>
                            <th scope="col">저자</th>
                            <th scope="col">출판사</th>
                            <th scope="col">빌린횟수</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $stmt = $conn -> prepare("SELECT B.ISBN, B.TITLE, B.PUBLISHER, B.YEAR, E.BOOK_COUNT
                                                      FROM (SELECT ISBN, COUNT(*) BOOK_COUNT
                                                            FROM PREVIOUSRENTAL
                                                            GROUP BY ISBN) E, EBOOK B
                                                      WHERE E.ISBN = B.ISBN
                                                      ORDER BY E.BOOK_COUNT DESC");
                            $stmt -> execute();
                            while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <tr>
                                <th scope="row"><?= $row['ISBN'] ?></th>
                                <td><?= $row['TITLE'] ?></td>
                                <td><?= $row['PUBLISHER'] ?></td>
                                <td><?= $row['YEAR'] ?></td>
                                <td><?= $row['BOOK_COUNT'] ?></td>
                            </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- 도서/회원 빌린 횟수 -->
            <div id="rankOfRent">
                <div class="info">
                    <h5>도서/회원 빌린횟수</h5>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">도서명</th>
                            <th scope="col">회원 아이디</th>
                            <th scope="col">빌린 횟수</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $stmt = $conn -> prepare("SELECT 
                                                        CASE GROUPING(E.TITLE)
                                                            WHEN 1 THEN 'All TITLE' ELSE E.TITLE END AS TITLE,
                                                        CASE GROUPING(P.CNO)
                                                            WHEN 1 THEN 'All CNO' ELSE TO_CHAR(P.CNO) END AS CNO,
                                                        COUNT(*) 빌린횟수
                                                      FROM PREVIOUSRENTAL P, EBOOK E
                                                      WHERE P.ISBN = E.ISBN
                                                      GROUP BY CUBE(E.TITLE, P.CNO)");
                            $stmt -> execute();
                            while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <tr>
                                <th scope="row"><?= $row['TITLE'] ?></th>
                                <td><?= $row['CNO'] ?></td>
                                <td><?= $row['빌린횟수'] ?></td>
                            </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- 도서/회원 오래된 대출 기록 -->
            <div id="rankOfRent">
                <div class="info">
                    <h5>도서/회원 오래된 대출 기록</h5>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ISBN</th>
                            <th scope="col">회원 아이디</th>
                            <th scope="col">대출 날짜</th>
                            <th scope="col">전체 랭크</th>
                            <th scope="col">회원별 랭크</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $cno = $_SESSION["cno"];
                            $stmt = $conn -> prepare("SELECT ISBN, CNO, DATERENTED,
                                                        DENSE_RANK() OVER (ORDER BY DATERENTED) ALL_RANK, 
                                                        DENSE_RANK() OVER (PARTITION BY CNO ORDER BY DATERENTED) CNO_RANK 
                                                      FROM PREVIOUSRENTAL");
                            $stmt -> execute();
                            while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <tr>
                                <th scope="row"><?= $row['ISBN'] ?></th>
                                <td><?= $row['CNO'] ?></td>
                                <td><?= $row['DATERENTED'] ?></td>
                                <td><?= $row['ALL_RANK'] ?></td>
                                <td><?= $row['CNO_RANK'] ?></td>
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
                                <a 
                                    class="fb-ic" 
                                    href="https://github.com/jeonbyeongmin" 
                                    target="_blank"
                                > 
                                    <i class="fab fa-github white-text"></i>
                                </a>
                                <a 
                                    class="ins-ic" 
                                    href="https://www.instagram.com/jeonbyeongm1n/" 
                                    target="_blank"
                                >
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