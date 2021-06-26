/*******************************************************
 *
 *  logout.js
 *
 *  사용자 화면 ==> logout.js ==> logout_php.php
 *
 *  순으로 데이터가 전달되며, php 파일에서 세션을 끊어
 *  로그아웃되도록 구현하였다.
 *
 *****************************************************/

$("#logout").click(function () {
  $.ajax({
    url: "logout_php.php",
    dataType: "json",
    success: function (data) {
      if (data.state == "logout") {
        alert("로그아웃이 되었습니다.");
        window.location.reload();
      } else {
        alert("이미 로그아웃 상태입니다.");
      }
    },
  });
});
