/*********************************************************************
 *
 * search.js
 *
 * index.php 에서 검색버튼을 눌렀을 때 각 조건에 맞게 검색된
 * 결과가 화면에 표시하도록 php에 조건 정보들을 전달하는 역할을 한다.
 *
 * index.php ==> search.js ==> search.php ==> searchedBook.php
 *
 ********************************************************************/

function SearchInfo(
  title,
  author,
  publisher,
  minDate,
  maxDate,
  titleSelect,
  authorSelect,
  publisherSelect
) {
  this.title = title;
  this.author = author;
  this.publisher = publisher;
  this.minDate = minDate;
  this.maxDate = maxDate;
  this.titleSelect = titleSelect;
  this.authorSelect = authorSelect;
  this.publisherSelect = publisherSelect;
}

$(document).ready(() => {
  $("#searchButton").click(() => {
    const title = $("#bookTitle").val();
    const author = $("#bookAuthor").val();
    const publisher = $("#bookPubli").val();
    const minDate = $("#minDate").val();
    const maxDate = $("#maxDate").val();

    const titleSelect = $(':radio[name="options_1"]:checked').val();
    const authorSelect = $(':radio[name="options_2"]:checked').val();
    const publisherSelect = $(':radio[name="options_3"]:checked').val();

    const searchInfo = new SearchInfo(
      title,
      author,
      publisher,
      minDate,
      maxDate,
      titleSelect,
      authorSelect,
      publisherSelect
    );
    const json = JSON.stringify(searchInfo);

    $.ajax({
      url: "search.php",
      type: "POST",
      data: { data: json },
      dataType: "json",
      success: (data) => {
        if (data.state === "success") {
          window.location.href = "searchedBook.php";
        }
      },
      error: () => {
        alert("서버와의 통신이 원할하지 않습니다.");
      },
    });
  });
});
