<!-----------------------------------------------------------------------------------
    
    [README] bookview.php

    해당 페이지에서 해당 도서의 대출이나 예약, 반납, 예약취소, 대출 연장이 가능하다.

    1. 대출 : 
    한 도서는 정확히 한명의 회원에 의해서만 대출될 수 있다. 이미 대출한 사람이 있으면
    대출이 불가능하다. 또한 한명 당 총 3권의 도서를 대출할 수 있다. 
    대출 권 수가 3권 이상이면 대출이 불가능하다.
    + 예약에 있던 도서가 대출이 되면 예약 목록에서 제거하고 대출이 되도록 한다.

    2. 예약 :
    대출할 수 없는 도서는 예약이 가능하다. 도서의 관점에서 예약에는 제한이 없으나,
    사용자 관점에서 예약은 한 사람당 3권까지 가능하다.

    3. 반납 :
    대출한 도서에 대해서 대출 기간이 도래하기 전까지 반납 버튼을 누르면 언제든지 반납이
    가능하다. 반납 날짜가 되면 자동으로 반납이 된다. 반납이 될 때 예약자가 있다면
    우선 예약자에게 메일을 보낸다. 해당 예약자가 하루 안에 예약하지 않는다면
    예약이 취소되고 우선권은 다음 사람에게 넘어간다.
    
    4. 예약취소 :
    예약한 도서에 대해서 언제든지 예약 취소가 가능하다.

    5. 대출 연장 :
    대출한 도서에 대해서 대출 기간이 도래하기 전까지 대출 연장이 최대 2회 10일씩 가능하다.

    ** 메일과 자동반납, 자동 예약취소 :
    모든 대출과 예약, 반납, 예약취소, 대출 연장은 bookView 페이지에서 일어나기 때문에
    bookview 페이지가 로드되면 메일 전송, 자동반납, 자동 예약 취소와 같은 기능들을
    하도록 하였다.

 -------------------------------------------------------------------------------------->

<?php
    // 메일을 보내기 위해서 PHPMAILER 추가
    require_once 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

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
    $stmt = $conn -> prepare("SELECT TITLE, EMAIL, NAME, MAILDATE
                              FROM (SELECT EBOOK.TITLE, 
                                           CUSTOMER.EMAIL, 
                                           CUSTOMER.NAME, 
                                           RESERVE.MAILDATE, 
                                           RANK() OVER (PARTITION BY RESERVE.ISBN ORDER BY RESERVE.DATETIME) AS RANK
                                    FROM EBOOK, RESERVE, CUSTOMER
                                    WHERE EBOOK.ISBN = RESERVE.ISBN
                                    AND RESERVE.CNO = CUSTOMER.CNO
                                    AND EBOOK.DATEDUE <= TO_DATE(?, 'YYYY-MM-DD'))
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
                                                    AND EBOOK.DATEDUE <= TO_DATE(:current1, 'YYYY-MM-DD'))
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

    // bookview 페이지를 구성하기 위해서 DB에서 정보들을 가져온다.
    $bookId = $_GET['bookId'];
    $stmt = $conn -> prepare("SELECT EBOOK.ISBN, 
                                     EBOOK.TITLE, 
                                     EBOOK.PUBLISHER, 
                                     AUTHORS.AUTHOR, 
                                     EBOOK.YEAR, 
                                     EBOOK.CNO, 
                                     EBOOK.EXTTIMES, 
                                     TO_CHAR(EBOOK.DATERENTED, 'yyyy-mm-dd hh24:mi:ss') AS DATERENTED
                              FROM EBOOK, AUTHORS
                              WHERE EBOOK.ISBN = AUTHORS.ISBN AND EBOOK.ISBN = ?"); 

    $stmt -> execute(array($bookId));
    $bookName = '';
    $publisher = '';
    $price = '';
    $bookCno = '';
    $canRented = '';
    $extt = '';
    $dateRented = '';

    if($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
        $isbn = $row["ISBN"];
        $bookName = $row['TITLE'];
        $publisher = $row['PUBLISHER'];
        $author = $row['AUTHOR'];
        $year = $row['YEAR'];
        $bookCno = $row['CNO'];
        $extt = $row['EXTTIMES'];
        $dateRented = $row['DATERENTED'];

        if($row['CNO'] != null) {
            $canRented = 'X';
        }
        else {
            $canRented = 'O';
        }
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
    <title>Book View</title>
</head>
<body>
    <br>
    <div id="divWrapper">
        <table class="table justify-content-center">
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
                <tr>
                    <th scope="row" id="bookId"><?= $isbn ?></th>
                    <td><?= $bookName ?></a></td>
                    <td><?= $author ?></td>
                    <td><?= $publisher ?></td>
                    <td><?= $year ?></td>
                </tr>
            </tbody>
        </table>
        <br>
        <table class="table justify-content-center">
            <thead>
                <tr>
                    <th scope="col">대출가능</th>
                    <th scope="col">예약인원</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    // 예약자가 몇명인지 보여주기 위한 쿼리문이다.
                    $stmt = $conn -> prepare("SELECT COUNT(*) 
                                              FROM EBOOK, RESERVE 
                                              WHERE EBOOK.ISBN = RESERVE.ISBN AND EBOOK.ISBN = ?
                                              GROUP BY EBOOK.ISBN"); 

                    $stmt -> execute(array($bookId));
                    $count = $stmt -> fetch(PDO::FETCH_ASSOC);

                    if(!isset($count['COUNT(*)'])) {
                        $count['COUNT(*)'] = 0;
                    }
                ?>
                <tr>
                    <th scope="row" id="canRented"><?= $canRented ?></th>
                    <td><?= $count['COUNT(*)'] ?></a></td>
                </tr>
            </tbody>
        </table>

        <!-- if 조건에 따라 버튼의 구성이 바뀐다 -->
        <div id="buttons">
            <form method="post" novalidate>
                <?php
                    if($bookCno != $_SESSION['cno']) {
                ?>
                        <input type="submit" class="btn btn-outline-primary bookinfoBT" name="rentBT" id="rentBT" value="대출"/>
                        <?php
                            $stmt = $conn -> prepare("SELECT *
                                                      FROM RESERVE 
                                                      WHERE CNO = ?
                                                      AND ISBN = ?"); 
                            
                            $stmt -> execute(array($_SESSION["cno"], $bookId));
                            $row = $stmt -> fetch(PDO::FETCH_ASSOC);

                            if(!isset($row["CNO"])) {
                                if($bookCno != null) {
                        ?>
                                <input type="submit" class="btn btn-outline-primary bookinfoBT" name="reserveBT" id="reserveBT" value="예약"/>
                        <?php
                                }
                            } 
                            else {
                        ?>
                                <input type="submit" class="btn btn-outline-primary bookinfoBT" name="cancelRes" id="cancelRes" value="예약취소"/>  
                        <?php    
                            } 
                        ?> 
                <?php
                    }
                    else {
                ?>
                        <input type="submit" class="btn btn-outline-primary bookinfoBT" name="returnBT" id="returnBT" value="반납"/>
                        <input type="submit" class="btn btn-outline-primary bookinfoBT" name="extendBT" id="extendBT" value="연장"/> 
                <?php
                    }
                ?>
            </form>
        </div>
        <?php 

            // 대출을 담당하는 함수
            function rent() { 
                global $conn;
                $stmt = $conn -> prepare("UPDATE EBOOK  
                                          SET CNO = :cno, EXTTIMES = :exttimes, DATERENTED = SYSDATE, DATEDUE = :datedue
                                          WHERE ISBN = :bookId");

                $stmt -> bindParam(':cno', $cno);
                $stmt -> bindParam(':exttimes', $exttimes);
                $stmt -> bindParam(':datedue', $datedue);
                $stmt -> bindParam(':bookId', $bookId);
                $cno = $_SESSION["cno"];
                $exttimes = 0;
                $datedue = date("Y-m-d",strtotime ("+10 days"));
                $bookId = $_GET['bookId'];
                $stmt -> execute();

                echo "<script>alert('대출되었습니다.');</script>"; 

                // 대출이 완료되었으면 예약을 취소하는 함수를 호출한다. 
                // 만약 예약이 되어있지 않았더라도 쿼리문에서 필터링되어 문제가 발생하지 않는다.
                cancelReserve();

                echo("<script>
                    opener.document.location.reload();
                    self.close();
                    </script>"
                );
            }

            // 반납을 담당하는 함수
            function returnBook() { 
                global $conn;
                global $dateRented;
                global $bookId;

                // PREVIOUSRENTAL 테이블에 대출 기록을 저장한다.
                $stmt = $conn -> prepare("INSERT INTO PREVIOUSRENTAL (ISBN, DATERENTED, DATERETURNED, CNO)
                                          VALUES (:isbn, TO_DATE(:dateRent, 'yyyy-mm-dd hh24:mi:ss'), SYSDATE, :cno)"); 

                $stmt -> bindParam(':isbn', $bookId);
                $stmt -> bindParam(':dateRent', $dateRent);
                $stmt -> bindParam(':cno', $cno);
                $bookId = $_GET['bookId'];
                $dateRent = $dateRented;
                $cno =  $_SESSION["cno"];
                $stmt -> execute();

                // 실질적인 반납을 담당하는 쿼리문
                $stmt = $conn -> prepare("UPDATE EBOOK  
                                          SET CNO = NULL, EXTTIMES = NULL, DATERENTED = NULL, DATEDUE = NULL
                                          WHERE ISBN = :isbn");

                $stmt -> bindParam(':isbn', $isbn);
                $isbn = $bookId;
                $stmt -> execute();
                
                echo "<script>alert('반납되었습니다.');</script>"; 
                echo("<script>
                    opener.document.location.reload();
                    self.close();
                    </script>"
                );
            }

            // 대출 기간 연장을 담당하는 함수
            function extend() {
                global $conn;
                global $bookId;

                $stmt = $conn -> prepare("UPDATE EBOOK  
                                          SET EXTTIMES = EXTTIMES+1, DATEDUE =  DATEDUE + (INTERVAL '10' DAY)
                                          WHERE ISBN = :isbn");

                $stmt -> bindParam(':isbn', $isbn);
                $isbn = $bookId;
                $stmt -> execute();

                echo "<script>alert('연장되었습니다.');</script>"; 
                echo("<script>
                    opener.document.location.reload();
                    self.close();
                    </script>"
                );
            }

            // 도서 예약을 담당하는 함수
            function reserve() {
                global $conn;
                global $bookId;

                $stmt = $conn -> prepare("INSERT INTO RESERVE (ISBN, CNO, DATETIME) 
                                          VALUES (:isbn, :cno, SYSDATE)"); 

                $stmt -> bindParam(':isbn', $isbn);
                $stmt -> bindParam(':cno', $cno);
                $isbn = $bookId;
                $cno =  $_SESSION["cno"];
                $stmt -> execute();

                echo "<script>alert('예약되었습니다.');</script>"; 
                echo("<script>
                    opener.document.location.reload();
                    self.close();
                    </script>"
                );
            }

            // 예약 취소를 담당하는 함수
            function cancelReserve() {
                global $conn;
                global $bookId;

                $stmt = $conn -> prepare("DELETE FROM RESERVE 
                                          WHERE ISBN = :isbn AND CNO = :cno");

                $stmt -> bindParam(':isbn', $isbn);
                $stmt -> bindParam(':cno', $cno);
                $isbn = $bookId;
                $cno =  $_SESSION["cno"];
                $stmt -> execute();

                echo("<script>
                    opener.document.location.reload();
                    self.close();
                    </script>"
                );
            }

            // 대출 버튼이 눌렸을 때 발생한다. 대출 최대 개수를 3권으로 제한하는 역할을 한다
            if(array_key_exists('rentBT', $_POST)){
                if($bookCno != null) {
                    echo "<script>alert('이미 대출된 도서입니다.');</script>"; 
                }
                else {
                    $stmt = $conn -> prepare("SELECT COUNT(*)
                                              FROM EBOOK
                                              WHERE CNO = ?
                                              GROUP BY CNO"); 

                    $stmt -> execute(array($_SESSION["cno"]));
                    $count = $stmt -> fetch(PDO::FETCH_ASSOC);

                    if(!isset($count['COUNT(*)'])) {
                        $count['COUNT(*)'] = 0;
                    }
                    if($count['COUNT(*)'] < 3) {
                        rent();
                    }
                    else {
                        echo("<script>alert('이미 3개의 도서를 대출하였습니다.');</script>"); 
                    }
                }
            }

            // 반납 버튼이 눌렸을 때 발생한다. 반납을 하고 난 뒤 예약자가 있다면 예약 1순위에게 메일을 보내는 역할을 한다.
            if(array_key_exists('returnBT', $_POST)){
                
                returnBook();

                // 반납된 도서가 예약자가 있다면 예약 1순위에게 메일을 보낸다.
                $stmt = $conn -> prepare("SELECT TITLE, EMAIL, NAME, MAILDATE
                                          FROM (SELECT EBOOK.TITLE, 
                                                       CUSTOMER.EMAIL, 
                                                       CUSTOMER.NAME,
                                                       RESERVE.MAILDATE,
                                                       RANK() OVER (PARTITION BY RESERVE.ISBN ORDER BY RESERVE.DATETIME) AS RANK
                                                FROM EBOOK, RESERVE, CUSTOMER
                                                WHERE EBOOK.ISBN = RESERVE.ISBN
                                                AND RESERVE.CNO = CUSTOMER.CNO
                                                AND EBOOK.CNO IS NULL)
                                          WHERE RANK = 1 AND MAILDATE IS NULL");

                $stmt -> execute();

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

                // 메시지를 보낸 대상들의 MAILDATE를 수정한다.
                $stmt = $conn -> prepare("UPDATE RESERVE 
                                          SET MAILDATE = SYSDATE
                                          WHERE CNO IN (SELECT CNO, MAILDATE
                                                        FROM (SELECT RESERVE.CNO,
                                                                     RESERVE.MAILDATE, 
                                                                     RANK() OVER (PARTITION BY RESERVE.ISBN ORDER BY RESERVE.DATETIME) AS RANK
                                                              FROM EBOOK, RESERVE
                                                              WHERE EBOOK.ISBN = RESERVE.ISBN
                                                              AND EBOOK.CNO IS NULL)
                                                        WHERE RANK = 1 AND MAILDATE IS NULL)");

                $stmt -> execute();
            }

            // 연장 버튼을 눌렀을 때 수행된다. 연장 최대 횟수는 2회로 제한한다. 예약자가 있다면 연장이 불가능하다.
            if(array_key_exists('extendBT', $_POST)){
                if($extt == 2) {
                    echo "<script>alert('연장 2회를 모두 사용하였습니다.');</script>"; 
                }
                else {
                    $stmt = $conn -> prepare("SELECT COUNT(*)
                                              FROM RESERVE
                                              WHERE ISBN = ?
                                              GROUP BY ISBN"); 

                    $stmt -> execute(array($bookId));
                    $count = $stmt -> fetch(PDO::FETCH_ASSOC);

                    if(!isset($count['COUNT(*)'])) {
                        $count['COUNT(*)'] = 0;
                    }
                    if($count['COUNT(*)'] == 0) {
                        extend();
                    }
                    else{
                        echo("<script>alert('예약자가 있어 연장이 불가능합니다.');</script>"); 
                    }
                }
            }

            // 예약 버튼을 눌렀을 때 수행된다. 예약은 최대 3권으로 제한한다.
            if(array_key_exists('reserveBT', $_POST)){
                $stmt = $conn -> prepare("SELECT COUNT(*)
                                          FROM RESERVE
                                          WHERE CNO = ?
                                          GROUP BY CNO"); 

                $stmt -> execute(array($_SESSION["cno"]));
                $count = $stmt -> fetch(PDO::FETCH_ASSOC);

                if(!isset($count['COUNT(*)'])) {
                    $count['COUNT(*)'] = 0;
                }
                if($count['COUNT(*)'] < 3) {
                    reserve();
                }
                else {
                    echo("<script>alert('이미 3개의 도서를 예약하였습니다.');</script>"); 
                }
            }

            // 예약 취소 버튼을 눌렀을 때 수행된다.
            if(array_key_exists('cancelRes', $_POST)){
                cancelReserve();
            }
        ?>
    </div>
</body>
</html>