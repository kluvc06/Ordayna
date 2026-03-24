import { } from "./validate.js";
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
    document.getElementById("tanar_option").innerHTML += `<option value="${teachers[i].name}">${teachers[i].name}</option>`;
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
    document.getElementById("tantargy_option").innerHTML += `<option value="${lessons[i].name}">${lessons[i].name}</option>`;
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
    document.getElementById("terem_option").innerHTML += `<option value="${rooms[i].name}">${rooms[i].name}</option>`;
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
    document.getElementById("groups").innerHTML += `<option value="${groups[i].name}">${groups[i].name}</option>`;
  }
}

let db = 1;
let id_arr = [];
let dataArray = [];

function lockData() {

  if (document.getElementById('terem').value && document.getElementById('tanar').value && document.getElementById('targy').value) {
    if (db < 10) {
      db = `0${db}`
    }

    const entry = {
      id: `ora_${db}`,
      text: `${db}. ${document.getElementById('terem').value} | ${document.getElementById('tanar').value} | ${document.getElementById('targy').value} | ${document.getElementById("days").value}`
    };

    dataArray.push(entry);

    document.getElementById("orarend_box").innerHTML = dataArray
      .map(item => `<option value="${item.id}">${item.text}</option>`)
      .join("");
    id_arr.push(db)
    db++;
    document.getElementById("err").innerHTML = "";

    document.getElementById('terem').value = "";
    document.getElementById('tanar').value = "";
    document.getElementById('targy').value = "";
    addId()
  } else {
    document.getElementById("err").innerHTML = "Hiányos adatok";
  }
}


function addItem(e, z) {
  document.getElementById(z).value = e.options[e.selectedIndex].getAttribute("value");
  e.selectedIndex = -1;
}

function addId() {
  document.getElementById("get_id").innerHTML = id_arr.map(t => `<option value="${t}" >${t}</option>`).join("");
  document.getElementById("get_id").style = "display:block";
}

function deleteData() {
  let del_id = document.getElementById("get_id").value;

  if (del_id) {
    dataArray = dataArray.filter(item => item.id !== `ora_${del_id}`);
    id_arr = id_arr.filter(t => t != del_id);

    document.getElementById("orarend_box").innerHTML = dataArray
      .map(item => `<option value="${item.id}">${item.text}</option>`)
      .join("");

    addId();
  } else {
    document.getElementById("err").innerHTML = "Nincs kiválasztott ID elem!";
    document.getElementById("err").style.color = "red";
  }
  if (id_arr.length === 0) {
    document.getElementById("get_id").style = "display:none"
  }
}

function allClear() {
  db = 1;
  id_arr = [];
  dataArray = [];
  document.getElementById("orarend_box").innerHTML = "";
  document.getElementById('terem').value = "";
  document.getElementById('targy').value = "";
  document.getElementById('tanar').value = "";
  document.getElementById("err").innerHTML = "";
  document.getElementById("get_id").style = "display:none"
}

// This is intentionally not awaited since nothing else depends on this
loadTeachers();
loadLessons();
loadRooms();
await loadGroups();

window.lockData = lockData;
window.addItem = addItem;
window.deleteData = deleteData;
window.allClear = allClear;
