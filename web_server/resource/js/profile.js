import { validateString, validateTelefon } from "./validate.js";
import { url } from "./cookie.js";

async function loadUserData() {
  {
    const response = await fetch(url + "user/profile", { method: "GET" });
    if (response.ok !== true) {
      return;
    }
    const result = await response.json();

    document.getElementById("og_display").innerHTML = result.display_name;
    document.getElementById("mail_add").innerHTML = result.email;
    document.getElementById("og_tel").innerHTML = result.phone_number !== null ? result.phone_number : "Nincs telefonszám megadva";
    document.getElementById("pfp").innerHTML = '<img src="img\\img3.jpg" alt="pfp">';
  }
  {
    const response = await fetch(url + "get_intezmenys", { method: "GET" });
    if (response.ok !== true) {
      return;
    }
    const result = await response.json();
    document.getElementById("intezmeny_list").innerHTML = "";
    for (let i = 0; i < result.length; i++) {
      document.getElementById("intezmeny_list").innerHTML += result[i].id + ": " + "<a class='kerek' onclick='loadIntezmeny(" + result[i].id + ")'>" + result[i].name + "</a>" + "<br>";
    }
  }
  {
    const response = await fetch(url + "intezmeny/user/get_invites", { method: "GET" });
    if (response.ok !== true) {
      return;
    }
    const result = await response.json();
    document.getElementById("invites").innerHTML = "";
    for (let i = 0; i < result.length; i++) {
      document.getElementById("invites").innerHTML += `<option value="${result[i].intezmeny_id}">${result[i].intezmeny_id}: ${result[i].intezmeny_name}</option>`;
    }
  }
}

async function loadIntezmeny(id) {
  document.cookie = "intezmeny_id=" + id + "";
  location.replace("home.html");
}

function hide_show() {
  document.getElementById("hide").style.display = document.getElementById("hide").style.display === "block" ? "none" : "block";
}

function changePfp(a) {
  switch (a) {
    case 1:
      document.getElementById("pfp").innerHTML = '<img src="img\\img' + a + '.jpg" alt="pfp" >'
      break;
    case 2:
      document.getElementById("pfp").innerHTML = '<img src="img\\img' + a + '.jpg" alt="pfp" >'
      break;
    case 3:
      document.getElementById("pfp").innerHTML = '<img src="img\\img' + a + '.jpg" alt="pfp" >'
      break;
    default:
      document.getElementById("hide").style = "display:none"
      break;
  }
}

async function signout() {
  const response = await fetch(url + "user/logout", { method: "GET" });
  if (response.ok === true) {
    location.replace('login.html');
  }
}

await loadUserData();

function prepareNameChange() {
  document.getElementById("name_change").innerHTML = `
    <input type='text' id='inp_name_change'><button onclick='nameChange()'>Megváltoztatás</button><br>
    <span id='inp_name_change_err' class='err'></span><br>
  `;
}

function preparePhoneChange() {
  document.getElementById("phone_change").innerHTML = `
    <input type='text' id='inp_phone_change'><button onclick='phoneChange()'>Megváltoztatás</button><br>
    <span id='inp_phone_change_err' class='err'></span><br>
  `;
}

function preparePassChange() {
  document.getElementById("pass_change").innerHTML = `
    <input type='text' id='inp_cur_pass_change'><input type='text' id='inp_new_pass_change'><button onclick='passChange()'>Megváltoztatás</button><br>
    <span id='inp_cur_pass_change_err' class='err'></span><br>
    <span id='inp_new_pass_change_err' class='err'></span><br>
  `;
}

async function nameChange() {
  let name = validateString("inp_name_change", "inp_name_change_err", 200, 0, "felhasználónév");
  if (name === false) return;

  const response = await fetch(url + "user/change/display_name", {
    method: "POST",
    body: JSON.stringify({
      new_disp_name: name,
    })
  });
  if (response.ok === false) {
    document.getElementById("inp_name_change_err").innerHTML = "Sikertelen felhasználónév változtatás<br>";
  } else {
    document.getElementById("name_change").innerHTML = "";
    await loadUserData();
  }
}

async function phoneChange() {
  let phone = validateTelefon("inp_phone_change", "inp_phone_change_err");
  if (phone === false) return;

  const response = await fetch(url + "user/change/phone_number", {
    method: "POST",
    body: phone.length === 0 ? "" : JSON.stringify({
      new_phone_number: phone,
    })
  });
  if (response.ok === false) {
    document.getElementById("inp_phone_change_err").innerHTML = "Sikertelen telefonszám változtatás<br>";
  } else {
    document.getElementById("phone_change").innerHTML = "";
    await loadUserData();
  }
}

async function passChange() {
  let cur_pass = validateString("inp_cur_pass_change", "inp_cur_pass_change_err", Number.MAX_SAFE_INTEGER, 12, "jelszó");
  let new_pass = validateString("inp_new_pass_change", "inp_new_pass_change_err", Number.MAX_SAFE_INTEGER, 12, "jelszó");
  if (cur_pass === false || new_pass === false) return;

  const response = await fetch(url + "user/change/password", {
    method: "POST",
    body: JSON.stringify({
      pass: cur_pass,
      new_pass: new_pass,
    })
  });
  if (response.ok === false) {
    document.getElementById("inp_cur_pass_change_err").innerHTML = "Sikertelen jelszó változtatás<br>";
  } else {
    location.replace('login.html');
  }
}

async function newIntezmeny() {
  let intezmeny_name = validateString("new_intezmeny_name", "new_intezmeny_name_err", 200, 1, "intézmény név");
  if (intezmeny_name === false) return;

  const response = await fetch(url + "create_intezmeny", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_name: intezmeny_name,
    })
  });
  if (response.ok === false) {
    document.getElementById("new_intezmeny_name_err").innerHTML = "Sikertelen intézmény létrehozás<br>";
  } else {
    await loadUserData();
  }
}

async function acceptInvite() {
  if (typeof document.getElementById("invites").options[document.getElementById("invites").selectedIndex] === 'undefined') {
    return;
  }
  const intezmeny_id = document.getElementById("invites").options[document.getElementById("invites").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/user/accept_invite", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok === false) {
    return;
  }
  document.getElementById("invites").selectedIndex = 0;
  await loadUserData();
}

window.hide_show = hide_show;
window.changePfp = changePfp;
window.signout = signout;
window.prepareNameChange = prepareNameChange;
window.nameChange = nameChange;
window.preparePhoneChange = preparePhoneChange;
window.phoneChange = phoneChange;
window.preparePassChange = preparePassChange;
window.passChange = passChange;
window.newIntezmeny = newIntezmeny;
window.loadIntezmeny = loadIntezmeny;
window.acceptInvite = acceptInvite;
