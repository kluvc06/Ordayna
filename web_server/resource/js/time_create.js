import { validateDate, validateTime } from "./validate.js";
import { url, getCookie } from "./cookie.js";

let intezmeny_id = getCookie("intezmeny_id");
if (intezmeny_id === null) location.replace("profile.html");
let teachers = null;
let lessons = null;
let rooms = null;
let groups = null;

async function loadTeachers() {
  const response = await fetch(url + "intezmeny/get/teachers", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  teachers = await response.json();

  document.getElementById("tanar_option").innerHTML = "";
  for (let i = 0; i < teachers.length; i++) {
    document.getElementById("tanar_option").innerHTML += `<option value="${teachers[i].id}">${teachers[i].name}</option>`;
  }
}

async function loadLessons() {
  const response = await fetch(url + "intezmeny/get/lessons", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  lessons = await response.json();

  document.getElementById("tantargy_option").innerHTML = "";
  for (let i = 0; i < lessons.length; i++) {
    document.getElementById("tantargy_option").innerHTML += `<option value="${lessons[i].id}">${lessons[i].name}</option>`;
  }
}

async function loadRooms() {
  const response = await fetch(url + "intezmeny/get/rooms", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  rooms = await response.json();

  document.getElementById("terem_option").innerHTML = "";
  for (let i = 0; i < rooms.length; i++) {
    document.getElementById("terem_option").innerHTML += `<option value="${rooms[i].id}">${rooms[i].name}</option>`;
  }
}

async function loadGroups() {
  const response = await fetch(url + "intezmeny/get/groups", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  groups = await response.json();

  document.getElementById("groups").innerHTML = "";
  for (let i = 0; i < groups.length; i++) {
    document.getElementById("groups").innerHTML += `<option value="${groups[i].id}">${groups[i].name}</option>`;
  }
}

let timetable = [];

async function loadTimetable() {
  const response = await fetch(url + "intezmeny/get/timetable", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  timetable = await response.json();
  updateTimetable();
}

function updateTimetable() {
  if (typeof document.getElementById("day_filter").options[document.getElementById("day_filter").selectedIndex] === 'undefined') {
    return;
  }
  const day_id = parseInt(document.getElementById("day_filter").options[document.getElementById("day_filter").selectedIndex].getAttribute("value"));
  let table = "<table>";
  for (let i = 0; i < groups.length; i++) {
    table += `<tr><td>${groups[i].name}</td>`;
    for (let j = 0; j < timetable.length; j++) {
      if (groups[i].id !== timetable[j].group_id || (day_id !== timetable[j].day && day_id !== -1)) continue;
      table += "<td>"
      for (let k = 0; k < lessons.length; k++) {
        if (lessons[k].id === timetable[j].lesson_id) {
          table += `<div>${lessons[k].name}</div>`;
          break;
        }
      }
      for (let k = 0; k < teachers.length; k++) {
        if (teachers[k].id === timetable[j].teacher_id) {
          table += `<div>${teachers[k].name}</div>`;
          break;
        }
      }
      for (let k = 0; k < rooms.length; k++) {
        if (rooms[k].id === timetable[j].room_id) {
          table += `<div>${rooms[k].name}</div>`;
          break;
        }
      }

      // I love javascript
      // There has to be a better way right?
      let start_date = new Date("2000-02-02 " + timetable[j].start);
      let duration_date = new Date("2000-02-02 " + timetable[j].duration);
      let end_hour = start_date.getHours() + duration_date.getHours();
      let end_minute = start_date.getMinutes() + duration_date.getMinutes();
      let end_second = start_date.getSeconds() + duration_date.getSeconds();
      if (end_second >= 60) {
        end_second -= 60;
        end_minute += 1;
      }
      if (end_minute >= 60) {
        end_minute -= 60;
        end_hour += 1;
      }
      if (end_hour >= 24) end_hour -= 24;
      if (end_second < 10) end_second = "0" + end_second;
      if (end_minute < 10) end_minute = "0" + end_minute;
      if (end_hour < 10) end_hour = "0" + end_hour;
      table += `<div>${timetable[j].start}-${end_hour}:${end_minute}:${end_second}</div>`

      table += "</td>";
    }
    table += "</tr>";
  }
  table += "</table>";

  let html = `<option value="-1">Új</option>`;
  for (let i = 0; i < timetable.length; i++) {
    html += `<option value="${timetable[i].id}">${timetable[i].id}</option>`;
  }

  document.getElementById("orarend_box").innerHTML = table;
  document.getElementById("get_id").innerHTML = html;
}

async function lockData() {
  document.getElementById("err").innerHTML = `
    <span id="empty_err"></span>
    <span id="start_err"></span>
    <span id="duration_err"></span>
    <span id="from_err"></span>
    <span id="until_err"></span>
  `;
  const elem_id =
    typeof document.getElementById("get_id").options[document.getElementById("get_id").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy elemet<br>"; return false; })() :
      document.getElementById("get_id").options[document.getElementById("get_id").selectedIndex].getAttribute("value");
  const start = validateTime("start", "start_err", "tanóra kezdete");
  const duration = validateTime("duration", "duration_err", "tanóra hossza");
  const day_id =
    typeof document.getElementById("day").options[document.getElementById("day").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy napot a tanórának<br>"; return false; })() :
      document.getElementById("day").options[document.getElementById("day").selectedIndex].getAttribute("value");
  const from = validateDate("from", "from_err", "tanóra érvényességének kezdete");
  const until = validateDate("until", "until_err", "tanóra érvényességének vége");
  const group_id =
    typeof document.getElementById("groups").options[document.getElementById("groups").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy csoportot<br>"; return false; })() :
      document.getElementById("groups").options[document.getElementById("groups").selectedIndex].getAttribute("value");
  const lesson_id =
    typeof document.getElementById("tantargy_option").options[document.getElementById("tantargy_option").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy tantárgyat<br>"; return false; })() :
      document.getElementById("tantargy_option").options[document.getElementById("tantargy_option").selectedIndex].getAttribute("value");
  const teacher_id =
    typeof document.getElementById("tanar_option").options[document.getElementById("tanar_option").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy tanárt<br>"; return false; })() :
      document.getElementById("tanar_option").options[document.getElementById("tanar_option").selectedIndex].getAttribute("value");
  const room_id =
    typeof document.getElementById("terem_option").options[document.getElementById("terem_option").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy termet<br>"; return false; })() :
      document.getElementById("terem_option").options[document.getElementById("terem_option").selectedIndex].getAttribute("value");
  if (
    elem_id === false || start === false || duration === false || from === false || until === false ||
    group_id === false || lesson_id === false || teacher_id === false || room_id === false
  ) return;
  if (elem_id == "-1") {
    const response = await fetch(url + "intezmeny/create/timetable_element", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
        start: start,
        duration: duration,
        day: day_id,
        from: from,
        until: until,
        group_id: group_id,
        lesson_id: lesson_id,
        teacher_id: teacher_id,
        room_id: room_id,
      })
    });
    if (response.ok !== true) {
      document.getElementById("empty_err").innerHTML = await response.text();
      return;
    }
  } else {
    const response = await fetch(url + "intezmeny/update/timetable_element", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
        element_id: elem_id,
        start: start,
        duration: duration,
        day: day_id,
        from: from,
        until: until,
        group_id: group_id,
        lesson_id: lesson_id,
        teacher_id: teacher_id,
        room_id: room_id,
      })
    });
    if (response.ok !== true) {
      document.getElementById("empty_err").innerHTML = await response.text();
      return;
    }
  }
  await loadTimetable();
}

async function deleteData() {
  document.getElementById("err").innerHTML = `<span id="empty_err"></span>`;
  const del_id =
    typeof document.getElementById("get_id").options[document.getElementById("get_id").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy elemet<br>"; return false; })() :
      (document.getElementById("get_id").options[document.getElementById("get_id").selectedIndex].getAttribute("value") === "-1" ?
        (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy elemet<br>"; return false; })() :
        document.getElementById("get_id").options[document.getElementById("get_id").selectedIndex].getAttribute("value"));
  if (del_id === false) return;

  const response = await fetch(url + "intezmeny/delete/timetable_element", {
    method: "DELETE",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      timetable_element_id: del_id,
    })
  });
  if (response.ok !== true) {
    document.getElementById("err").innerHTML = await response.text();
    return;
  }
  await loadTimetable();
}

await loadTeachers();
await loadLessons();
await loadRooms();
await loadGroups();
await loadTimetable();

window.lockData = lockData;
window.deleteData = deleteData;
window.updateTimetable = updateTimetable;
