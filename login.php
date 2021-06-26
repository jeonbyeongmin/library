

<!-----------------------------------------------------

    [README] login.php

    login을 하기 위한 페이지이다.
    사용자로부터 아이디와 비밀번호를 입력받고

    login.php ==> login.js ==> login_php.php

    순서로 데이터를 전송하여 DB에서 적합한 계정이
    있는지 찾고 있다면 로그인에 성공하게 된다.

    회원 가입 버튼은 존재하지만 이번 프로젝트에서
    중요하지 않아 회원가입 기능은 구현하지 않았다.

    그외 나머지 html 코드들은 index.php와 유사하며
    로그인을 하기 위한 입력 필드가 존재한다.

------------------------------------------------------->

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
    <title>Login</title>
</head>
<body>
    <div id="divWrapper">
        <div id="divHeader">
            <div id="divGlobalMenu">
                <a href="login.php" id="login">로그인</a>
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

        <div class="container">
            <form class="signForm">
                <div class="form-group row">
                    <label for="inputId" class="col-sm-2 col-form-label">ID</label>
                    <div class="col-sm-10">
                        <input type="text" name="id" id="inputId">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="inputPw" class="col-sm-2 col-form-label">PW</label>
                    <div class="col-sm-10">
                        <input type="password" name="pw" id="inputPw">
                    </div>
                        
                </div>
                <div class="form-group row">
                    <div class="col-sm-6">
                        <input type="button" id="loginButton" value="SIGN-IN">
                    </div>
                    <div class="col-sm-6">
                        <input type="button" id="signUpButton" value="SIGN-UP">
                    </div>
                </div>
            </form>
        </div>
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
    <script src="login.js"></script>
</body>
</html>