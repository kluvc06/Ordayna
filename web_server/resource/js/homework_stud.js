import {url, getCookie} from "./cookie.js";

const intezmeny_id = getCookie("intezmeny_id");
if (intezmeny_id === null) location.replace("profile.html");
let intezmeny_name = getCookie("intezmeny_name");
if (intezmeny_name === null) location.replace("profile.html");
let user_role = getCookie("user_role");
if (user_role === null) location.replace("profile.html");
document.getElementById("i-name").innerHTML = `${intezmeny_name} ${user_role}`;

function returnHome() {
  if (user_role === "Diák") {
    location.replace("home_stud.html");
  } else if (user_role === "Tanár") {
    location.replace("home_teach.html");
  } else if (user_role === "Adminisztrátor") {
    location.replace("home.html");
  }
}

async function loadClasses() {
  const response = await fetch(url + "intezmeny/get/lessons", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok !== true) {
    return;
  }
  const tantargyak = await response.json();

  document.getElementById("tantargy").innerHTML = "";
    document.getElementById("tantargy").innerHTML += `<option value="">Összes</option>`;
  for (let i = 0; i < tantargyak.length; i++) {
    document.getElementById("tantargy").innerHTML += `<option value="${tantargyak[i].name}">${tantargyak[i].name}</option>`;
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
  const homeworks = await response.json();

  document.getElementById("feladatok").innerHTML = "";
  for (let i = 0; i < homeworks.length; i++) {
    // Not sure how this is supposed to look like so I just left it like this
    document.getElementById("feladatok").innerHTML += ``;
    document.getElementById("feladatok_leiras").innerHTML += ``;
  }
}

await loadClasses();
await loadHomeworks();

window.returnHome = returnHome;
