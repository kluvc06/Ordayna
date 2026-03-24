import {url, getCookie} from "./cookie.js";

const intezmeny_id = getCookie("intezmeny_id");
if (intezmeny_id === null) location.replace("profile.html");

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

// This is intentionally not awaited since nothing else depends on this
loadClasses();
await loadHomeworks();
