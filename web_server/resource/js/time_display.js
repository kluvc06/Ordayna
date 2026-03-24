const clas = ["Class A", "Class B", "Class C", "Class D", "Class E", "Class F", "Class G", "Class H", "Class I", "Class J", "Class K", "Class L", "Class M", "Class N", "Class O", "Class P", "Class Q", "Class R", "Class S", "Class T", "Class U", "Class V", "Class W", "Class X", "Class Y", "Class Z"];

const clas_tag = document.getElementById("classes_");

function generateContentForDisplay() {
  console.log("loads")

  clas_tag.innerHTML = clas.map(t => `<option onclick value="${t}">${t}</option>`).join("");

  console.log("works")
}

function onSelectLoad() {
  alert("'hi'");
}

//  CREATE OR REPLACE TABLE timetable (
//             id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
//             duration   TIME NOT NULL,
//             day        TINYINT UNSIGNED NOT NULL,
//             from_      DATE NOT NULL,
//             until      DATE NOT NULL,
//             group_id   INT UNSIGNED DEFAULT NULL,
//             lesson_id  INT UNSIGNED DEFAULT NULL,
//             teacher_id INT UNSIGNED DEFAULT NULL,
//             room_id    INT UNSIGNED DEFAULT NULL,

function loadTimetable(e) {
  data = e.options[e.selectedIndex].getAttribute("value");
  console.log(data)
}
