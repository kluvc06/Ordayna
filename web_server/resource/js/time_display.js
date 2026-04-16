import { getCookie, url } from "./cookie.js";

let intezmeny_id = getCookie("intezmeny_id");
if (intezmeny_id === null) location.replace("profile.html");
let intezmeny_name = getCookie("intezmeny_name");
if (intezmeny_name === null) location.replace("profile.html");
let user_role = getCookie("user_role");
if (user_role === null) location.replace("profile.html");
document.getElementById("i_name").innerHTML = `${intezmeny_name} ${user_role}`;
let timetable = [];
let teachers = [];
let lessons = [];
let rooms = [];
let groups = [];

function returnHome() {
  if (user_role === "Diák") {
    location.replace("home_stud.html");
  } else if (user_role === "Tanár") {
    location.replace("home_teach.html");
  } else if (user_role === "Adminisztrátor") {
    location.replace("home.html");
  }
}

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

  let html = "";
  for (let i = 0; i < groups.length; i++) {
    html += `<div>${groups[i].name}<input type="checkbox" onchange="updateTimetable()" id=${"group_" + groups[i].id}></div>`
  }
  document.getElementById("classes_").innerHTML = html;
}

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
  let group_ids = [];
  for (let i = 0; i < groups.length; i++) {
    if (document.getElementById("group_" + groups[i].id).checked !== false) {
      group_ids[group_ids.length] = groups[i].id;
    }
  }

  for (let day_id = 0; day_id < 7; day_id++) {
    let html = "";
    for (let i = 0; i < timetable.length; i++) {
      if (timetable[i].day !== day_id) continue;
      let group_selected = false;
      for (let k = 0; k < group_ids.length; k++) {
        if (group_ids[k] === timetable[i].group_id) {
          group_selected = true;
          break;
        }
      }
      if (group_selected === false) continue;
      html += `<div class="ora-card">`
      for (let k = 0; k < lessons.length; k++) {
        if (lessons[k].id === timetable[i].lesson_id) {
          html += `<div>${lessons[k].name}</div>`;
          break;
        }
      }
      for (let k = 0; k < teachers.length; k++) {
        if (teachers[k].id === timetable[i].teacher_id) {
          html += `<div>${teachers[k].name}</div>`;
          break;
        }
      }
      for (let k = 0; k < rooms.length; k++) {
        if (rooms[k].id === timetable[i].room_id) {
          html += `<div>${rooms[k].name}</div>`;
          break;
        }
      }

      // I love javascript
      // There has to be a better way right?
      let start_date = new Date("2000-02-02 " + timetable[i].start);
      let duration_date = new Date("2000-02-02 " + timetable[i].duration);
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
      html += `<div>${timetable[i].start}-${end_hour}:${end_minute}:${end_second}</div>`
      html += "</div>"
    }
    document.getElementById("day_" + day_id).innerHTML = html;
  }
}

await loadTeachers();
await loadLessons();
await loadRooms();
await loadGroups();
await loadTimetable();

window.loadTimetable = loadTimetable;
window.updateTimetable = updateTimetable;
window.returnHome = returnHome;
