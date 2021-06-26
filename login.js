/*******************************************************
 *
 * login.js
 *
 * 서버와 통신을 위한 ajax 파일이다.
 *
 * login.ph ==> login.js ==> login_php.php
 *
 *******************************************************/

function Account(id, pw) {
  this.id = id;
  this.pw = pw;
}

$(document).ready(() => {
  $("#loginButton").click(() => {
    const id = $("#inputId").val();
    const pw = $("#inputPw").val();

    const account = new Account(id, pw);
    const json = JSON.stringify(account);

    $.ajax({
      url: "login_php.php",
      type: "POST",
      data: { data: json },
      dataType: "json",
      success: (data) => {
        if (data.state === "success") {
          alert("로그인되었습니다.");
          window.location.href = "index.php";
        } else if (data.state === "fail") {
          alert("로그인에 실패하였습니다.");
        }
      },
      error: () => {
        alert("서버와의 통신이 원할하지 않습니다.");
      },
    });
  });
});
