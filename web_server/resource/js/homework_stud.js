import { url, getCookie } from "./cookie.js";

const intezmeny_id = getCookie("intezmeny_id");
if (intezmeny_id === null) location.replace("profile.html");
let intezmeny_name = getCookie("intezmeny_name");
if (intezmeny_name === null) location.replace("profile.html");
let user_role = getCookie("user_role");
if (user_role === null) location.replace("profile.html");
document.getElementById("i-name").innerHTML = `${intezmeny_name} ${user_role}`;
let homeworks = [];
let groups = [];
let lessons = [];

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

  let html = "";
  for (let i = 0; i < groups.length; i++) {
    html += `<option value="${groups[i].id}">${groups[i].name}</option>`;
  }
  document.getElementById("groups").innerHTML = html;
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

  let html = "";
  for (let i = 0; i < lessons.length; i++) {
    html += `<option value="${lessons[i].id}">${lessons[i].name}</option>`;
  }
  document.getElementById("tantargy").innerHTML = html;
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

  updateHomeworks();
}

function updateHomeworks() {
  if (typeof document.getElementById("groups").options[document.getElementById("groups").selectedIndex] === 'undefined') {
    return;
  }
  const group_id = parseInt(document.getElementById("groups").options[document.getElementById("groups").selectedIndex].getAttribute("value"));
  if (typeof document.getElementById("tantargy").options[document.getElementById("tantargy").selectedIndex] === 'undefined') {
    return;
  }
  const lesson_id = parseInt(document.getElementById("tantargy").options[document.getElementById("tantargy").selectedIndex].getAttribute("value"));

  let homeworks_html = "";
  for (let i = 0; i < homeworks.length; i++) {
    if (homeworks[i].group.id !== group_id || homeworks[i].lesson.id !== lesson_id) continue;
    homeworks_html += `
      <div class="homework" onclick="expandDescription(${i})" style="cursor:pointer">
        <div>Csoport: ${homeworks[i].group.name.length < 25 ? homeworks[i].group.name : (homeworks[i].group.name.slice(0, 25) + "...")}</div>
        <div>Tárgy: ${homeworks[i].lesson.name.length < 25 ? homeworks[i].lesson.name : (homeworks[i].lesson.name.slice(0, 25) + "...")}</div>
        <div>Kiadva: ${homeworks[i].published}</div>
        <div>Kiadta: ${homeworks[i].teacher.name.length < 25 ? homeworks[i].teacher.name : (homeworks[i].teacher.name.slice(0, 25) + "...")}</div>
        <div>Határidő: ${homeworks[i].due}</div>
        <div>${homeworks[i].description.length < 25 ? homeworks[i].description : (homeworks[i].description.slice(0, 25) + "...")}</div>
      </div>
    `;
  }
  document.getElementById("feladatok").innerHTML = homeworks_html;
  document.getElementById("feladatok_leiras").innerText = "";
}

function expandDescription(homework_array_id) {
  document.getElementById("feladatok_leiras").innerText = homeworks[homework_array_id].description;
}

await loadLessons();
await loadGroups();
await loadHomeworks();

window.returnHome = returnHome;
window.updateHomeworks = updateHomeworks;
window.expandDescription = expandDescription;
