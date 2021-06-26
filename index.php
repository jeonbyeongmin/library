

<!-----------------------------------------------------------------------------------
    
    [README] index.php

    초기화면 & 검색화면.
    로그인을 하지 않으면 검색 기능을 사용하는 것이 불가능하며 로그인 창으로 이동된다.
    검색은 크게 도서명, 저자, 출판사, 날짜 의 4개의 필드로 이뤄져있다.
    NOT, AND, OR를 선택할 수 있는 3쌍의 연산자를 이용해서 검색어를 조합할 수 있다.

    index.php ==> search.js ==> search.php ==> searchedBook.php 

    로 데이터를 전송하며 searchedBook에서 최종 검색 결과를 확인할 수 있다.

 -------------------------------------------------------------------------------------->


<?php

    // 메일을 보내기 위해서 PHPMAILER 추가
    require_once 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

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

    // 메일을 받은 날짜로 부터 하루가 지났으면 예약에서 삭제함
    $stmt = $conn -> prepare("DELETE FROM RESERVE 
                              WHERE ISBN IN (SELECT ISBN
                                             FROM RESERVE
                                             WHERE MAILDATE IS NOT NULL
                                             AND MAILDATE + (INTERVAL '1' DAY) = TO_DATE(:current1, 'YYYY-MM-DD'))");

    $stmt -> bindParam(':current1', $current1);
    $current1 = date("Y-m-d");
    $stmt -> execute();

    // 반납일에 도래한 책들을 반납 처리하기 전 예약 1순위들에게 메일을 보낸다.
    // 이미 메일이 간 예약자들에게는 메일을 보내지 않는다.
    $stmt = $conn -> prepare("SELECT TITLE, EMAIL, NAME
                              FROM (SELECT EBOOK.TITLE,
                                           CUSTOMER.EMAIL, 
                                           CUSTOMER.NAME, 
                                           RESERVE.MAILDATE, 
                                           RANK() OVER (PARTITION BY RESERVE.ISBN ORDER BY RESERVE.DATETIME) AS RANK
                                    FROM EBOOK, RESERVE, CUSTOMER
                                    WHERE EBOOK.ISBN = RESERVE.ISBN
                                    AND RESERVE.CNO = CUSTOMER.CNO
                                    AND EBOOK.DATEDUE <= ?)
                              WHERE RANK = 1 AND MAILDATE IS NULL");

    $stmt -> execute(array(date("Y-m-d")));

    while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        try {
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Port = 465;
            $mail->SMTPSecure = "ssl";
            $mail->Username = "qudals7613@gmail.com";
            $mail->Password ="jmwjs951210";
            $mail->CharSet = 'utf-8';
            $mail->Encoding = "base64";
            $mail->setFrom('qudals7613@gmail.com', 'Library');
            $mail->AddAddress($row["EMAIL"], $row["NAME"]);
            $mail->isHTML(true);
            $mail->Subject = $row["TITLE"].' 도서 대출이 가능합니다.';
            $mail->Body = $row["TITLE"].' 도서 대출이 가능합니다.';
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            $mail->Send();
        } 
        catch (phpmailerException $e) {
            echo $e->errorMessage();
        } 
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // 메시지를 보낸 대상들의 MAILDATE에 현재 날짜를 쓴다.
    $stmt = $conn -> prepare("UPDATE RESERVE 
                              SET MAILDATE = SYSDATE
                              WHERE (CNO, MAILDATE) IN (SELECT CNO, MAILDATE
                                            FROM (SELECT RESERVE.CNO, 
                                                         RESERVE.MAILDATE, 
                                                         RANK() OVER (PARTITION BY RESERVE.ISBN ORDER BY RESERVE.DATETIME) AS RANK
                                                    FROM EBOOK, RESERVE
                                                    WHERE EBOOK.ISBN = RESERVE.ISBN
                                                    AND EBOOK.DATEDUE <= :current1)
                                            WHERE RANK = 1 AND MAILDATE IS NULL)");

    $stmt->bindParam(':current1', $current1);
    $current1 = date("Y-m-d");
    $stmt -> execute();

    // 1순위 예약자들에게 메일을 보내고 난 뒤 반납일에 도래한 책들을 모두 반납 처리한다.
    $stmt = $conn -> prepare("UPDATE EBOOK  
                              SET CNO = :cno, EXTTIMES = :exttimes, DATERENTED = TO_DATE(:daterented,'YYYY-MM-DD'), DATEDUE = TO_DATE(:datedue,'YYYY-MM-DD') 
                              WHERE DATEDUE <= TO_DATE(:current1, 'YYYY-MM-DD')");

    $stmt->bindParam(':cno', $cno);
    $stmt->bindParam(':exttimes', $exttimes);
    $stmt->bindParam(':daterented', $daterented);
    $stmt->bindParam(':datedue', $datedue);
    $stmt->bindParam(':current1', $current1);
    $cno = null;
    $exttimes = null;
    $daterented = null;
    $datedue = null;
    $current1 = date("Y-m-d");
    $stmt -> execute();

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
        <title>Library</title>
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

            <!-- index -->
            <div id="selectIndex">
                <h1>자료 검색</h1>
            </div>

            <!-- 검색 : 타이틀 입력  -->
            <div class="input-group">

                <input 
                    id = "bookTitle"
                    name = "bookTitle"
                    type="search"
                    class="form-control rounded" 
                    placeholder="도서를 입력해주세요" 
                    aria-label="Search" 
                    aria-describedby="search-addon" 
                />

                <div class="btn-group select">
                    <input
                        type="radio"
                        class="btn-check "
                        name="options_1"
                        id="and1"
                        value="INTERSECT"
                        autocomplete="off"
                        checked
                    />
                    <label class="btn btn-secondary" for="and1">AND</label>

                    <input 
                        type="radio" 
                        class="btn-check " 
                        name="options_1" 
                        id="or1"
                        value="UNION"
                        autocomplete="off" 
                    />
                    <label class="btn btn-secondary " for="or1">OR</label>

                    <input 
                        type="radio" 
                        class="btn-check " 
                        name="options_1" 
                        id="not1"
                        value="MINUS"
                        autocomplete="off" 
                    />
                    <label class="btn btn-secondary " for="not1">NOT</label>
                </div>
            </div>

            <!-- 검색 : 저자 입력  -->
            <div class="input-group">
                <input 
                    id = "bookAuthor"
                    name = "bookAuthor"
                    type="search" 
                    class="form-control rounded" 
                    placeholder="저자를 입력해주세요" 
                    aria-label="Search" 
                    aria-describedby="search-addon"
                />
                <div class="btn-group select">
                    <input
                        type="radio"
                        class="btn-check "
                        name="options_2"
                        id="and2"
                        value="INTERSECT"
                        autocomplete="off"
                        checked
                    />
                    <label class="btn btn-secondary " for="and2">AND</label>

                    <input 
                        type="radio" 
                        class="btn-check "
                        name="options_2" 
                        id="or2" 
                        value="UNION"
                        autocomplete="off" 
                    />
                    <label class="btn btn-secondary " for="or2">OR</label>

                    <input 
                        type="radio" 
                        class="btn-check" 
                        name="options_2" 
                        id="not2"
                        value="NOT"
                        autocomplete="off" 
                    />
                    <label class="btn btn-secondary " for="not2">NOT</label>
                </div>
            </div>

            <!-- 검색 : 출판사 입력  -->
            <div class="input-group">
                <input
                    id = "bookPubli"
                    name = "bookPubli"
                    type="search" 
                    class="form-control rounded" 
                    placeholder="출판사를 입력해주세요" 
                    aria-label="Search" 
                    aria-describedby="search-addon" 
                />

                <div class="btn-group select">
                    <input
                        type="radio"
                        class="btn-check "
                        name="options_3"
                        id="and3"
                        value="INTERSECT"
                        autocomplete="off"
                        checked
                    />
                    <label class="btn btn-secondary" for="and3">AND</label>

                    <input 
                        type="radio" 
                        class="btn-check " 
                        name="options_3" 
                        id="or3"
                        value="UNION"
                        autocomplete="off" 
                    />
                    <label class="btn btn-secondary " for="or3">OR</label>

                    <input 
                        type="radio" 
                        class="btn-check" 
                        name="options_3" 
                        id="not3"
                        value="MINUS"
                        autocomplete="off" 
                    />
                    <label class="btn btn-secondary " for="not3">NOT</label>
                </div>  
            </div>

            <!-- 검색 : 날짜 입력  -->
            <div class="input-group">
                <input
                    id = "minDate"
                    name = "minDate";
                    type="date" 
                    class="form-control rounded"
                />
                <input
                    id = "maxDate";
                    name = "maxDate";
                    type="date" 
                    class="form-control rounded"
                />
            </div>

            <!-- 검색 버튼 -->
            <button 
                type="button" 
                class="btn btn-outline-primary searchBnt" 
                id="searchButton"
            > 검색 </button>

            <!-- Footer -->
            <footer class="text-center text-lg-start text-muted">
                <div class="icons">
                    <div class="row">
                        <div>
                            <div class="mb-5 flex-center">

                                <!-- 깃허브 아이콘 -->
                                <a 
                                    class="fb-ic" 
                                    href="https://github.com/jeonbyeongmin" 
                                    target="_blank"
                                > 
                                    <i class="fab fa-github white-text"></i>
                                </a>

                                <!-- 인스타그램 아이콘 -->
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
                <!-- copyright -->
                <div class="footer-copyright text-center py-3">© 2021 Copyright:
                    <a href="https://github.com/jeonbyeongmin/"> jeonbyeongmin</a>
                </div>
            </footer>
        </div>
        <script src="search.js"></script>
        <script src="logout.js"></script>
    </body>
</html>