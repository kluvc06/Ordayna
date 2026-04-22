import { url, getCookie } from "./cookie.js";
import { validateDateTime, validateString } from "./validate.js";

const intezmeny_id = getCookie("intezmeny_id");
if (intezmeny_id === null) location.replace("profile.html");
let intezmeny_name = getCookie("intezmeny_name");
if (intezmeny_name === null) location.replace("profile.html");
let user_role = getCookie("user_role");
if (user_role === null) location.replace("profile.html");
document.getElementById("i-name").innerHTML = `${intezmeny_name} ${user_role}`;
let groups = [];
let lessons = [];
let teachers = [];
let homeworks = [];

function returnHome() {
  if (user_role === "Diák") {
    location.replace("home_stud.html");
  } else if (user_role === "Tanár") {
    location.replace("home_teach.html");
  } else if (user_role === "Adminisztrátor") {
    location.replace("home.html");
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

  document.getElementById("new_lesson").innerHTML = `<option value="-1">-</option>`;
  for (let i = 0; i < lessons.length; i++) {
    document.getElementById("new_lesson").innerHTML += `<option value="${lessons[i].id}">${lessons[i].name}</option>`;
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

  document.getElementById("new_teacher").innerHTML = `<option value="-1">-</option>`;
  for (let i = 0; i < teachers.length; i++) {
    document.getElementById("new_teacher").innerHTML += `<option value="${teachers[i].id}">${teachers[i].name}</option>`;
  }
}

async function loadHomeworks() {
  const response = await fetch(url + "intezmeny/get/homeworks", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  homeworks = await response.json();

  let choice_html = `<option value="-1">Új</option>`;
  for (let i = 0; i < homeworks.length; i++) {
    choice_html += `<option value="${homeworks[i].id}">${homeworks[i].id}</option>`;
  }
  document.getElementById("choice").innerHTML = choice_html;
  updateHomeworks();
}

function updateHomeworks() {
  if (typeof document.getElementById("groups").options[document.getElementById("groups").selectedIndex] === 'undefined') {
    return;
  }
  const group_id = parseInt(document.getElementById("groups").options[document.getElementById("groups").selectedIndex].getAttribute("value"));

  let homeworks_html = "";
  for (let i = 0; i < homeworks.length; i++) {
    if (homeworks[i].group.id !== group_id) continue;
    homeworks_html += `
      <div class="homework" onclick="refillEx(${i + 1})" style="cursor:pointer">
        <div>Csoport: ${homeworks[i].group.name.length < 25 ? homeworks[i].group.name : (homeworks[i].group.name.slice(0, 25) + "...")}</div>
        <div>Tárgy: ${homeworks[i].lesson.name.length < 25 ? homeworks[i].lesson.name : (homeworks[i].lesson.name.slice(0, 25) + "...")}</div>
        <div>Kiadva: ${homeworks[i].published}</div>
        <div>Kiadta: ${homeworks[i].teacher.name.length < 25 ? homeworks[i].teacher.name : (homeworks[i].teacher.name.slice(0, 25) + "...")}</div>
        <div>Határidő: ${homeworks[i].due}</div>
        <div>${homeworks[i].description.length < 25 ? homeworks[i].description : (homeworks[i].description.slice(0, 25) + "...")}</div>
      </div>
    `;
  }
  document.getElementById("homeworks").innerHTML = homeworks_html;
}

function refill() {
  if (typeof document.getElementById("choice").options[document.getElementById("choice").selectedIndex] === 'undefined') {
    return;
  }
  const homework_id = parseInt(document.getElementById("choice").options[document.getElementById("choice").selectedIndex].getAttribute("value"));
  if (homework_id === -1) return;

  for (let i = 0; i < homeworks.length; i++) {
    if (homework_id !== homeworks[i].id) continue;
    document.getElementById("new_description").value = homeworks[i].description;
    document.getElementById("new_due").value = homeworks[i].due;
    let group_i = 0;
    for (let j = 0; j < groups.length; j++) {
      if (groups[j].id === homeworks[i].group.id) break;
      group_i++;
    }
    document.getElementById("groups").selectedIndex = group_i;
    let lesson_i = 1;
    for (let j = 0; j < lessons.length; j++) {
      if (lessons[j].id === homeworks[i].lesson.id) break;
      lesson_i++;
    }
    document.getElementById("new_lesson").selectedIndex = lesson_i;
    let teacher_i = 1;
    for (let j = 0; j < teachers.length; j++) {
      if (teachers[j].id === homeworks[i].teacher.id) break;
      teacher_i++;
    }
    document.getElementById("new_teacher").selectedIndex = teacher_i;
    break;
  }

  updateHomeworks();
}

function refillEx(choice_id) {
  document.getElementById("choice").selectedIndex = choice_id;
  refill();
}

async function newHomework() {
  document.getElementById("err").innerHTML = `
    <span id="empty_err"></span>
    <span id="new_description_err"></span>
    <span id="new_due_err"></span>
  `;

  const homework_id =
    typeof document.getElementById("choice").options[document.getElementById("choice").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy házi feladatot<br>"; return false; })() :
      document.getElementById("choice").options[document.getElementById("choice").selectedIndex].getAttribute("value");
  const description = validateString("new_description", "new_description_err", Number.MAX_SAFE_INTEGER, 1, "leírás");
  const due = validateDateTime("new_due", "new_due_err", "határidő");
  const group_id =
    typeof document.getElementById("groups").options[document.getElementById("groups").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy csoportot<br>"; return false; })() :
      document.getElementById("groups").options[document.getElementById("groups").selectedIndex].getAttribute("value");
  const lesson_id =
    typeof document.getElementById("new_lesson").options[document.getElementById("new_lesson").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy tantárgyat<br>"; return false; })() :
      document.getElementById("new_lesson").options[document.getElementById("new_lesson").selectedIndex].getAttribute("value");
  const teacher_id =
    typeof document.getElementById("new_teacher").options[document.getElementById("new_teacher").selectedIndex] === 'undefined' ?
      (() => { document.getElementById("empty_err").innerHTML += "Válasszon ki egy tanarat<br>"; return false; })() :
      document.getElementById("new_teacher").options[document.getElementById("new_teacher").selectedIndex].getAttribute("value");
  if (
    homework_id === false || description === false || due === false ||
    group_id === false || lesson_id === false || teacher_id === false
  ) return;

  if (homework_id === "-1") {
    const response = await fetch(url + "intezmeny/create/homework", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
        description: description,
        due: due,
        group_id: group_id,
        lesson_id: lesson_id,
        teacher_id: teacher_id,
      })
    });
    if (response.ok !== true) {
      document.getElementById("empty_err").innerHTML = await response.text();
      return;
    }
  } else {
    const response = await fetch(url + "intezmeny/update/homework", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
        homework_id: homework_id,
        description: description,
        due: due,
        group_id: group_id,
        lesson_id: lesson_id,
        teacher_id: teacher_id,
      })
    });
    if (response.ok !== true) {
      document.getElementById("empty_err").innerHTML = await response.text();
      return;
    }
  }

  await loadHomeworks();
}

await loadGroups();
await loadHomeworks();
await loadLessons();
await loadTeachers();

window.returnHome = returnHome;
window.updateHomeworks = updateHomeworks;
window.refill = refill;
window.refillEx = refillEx;
window.newHomework = newHomework;
