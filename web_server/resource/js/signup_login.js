import { validateEmail, validateString, validateTelefon } from "./validate.js";

let url = window.location.protocol + "//" + window.location.host + "/";

async function refresh() {
  const response = await fetch(url + "token/refresh_refresh_token", { method: "GET" });
  if (response.ok === true) {
    location.href = 'profile.html';
  }
}

refresh();


async function signup() {
  let disp_name = validateString("signup_display_name", "signup_display_name_err", 200, 0, "felhasználónév");
  let email = validateEmail("signup_email", "signup_email_err", 254, 0, "email");
  let tel = validateTelefon("signup_tel", "signup_tel_err", 15, 0, "telefonszám");
  let pass_1 = validateString("signup_pass_1", "signup_pass_1_err", Number.MAX_SAFE_INTEGER, 12, "jelszó");
  let pass_2 = validateString("signup_pass_2", "signup_pass_2_err", Number.MAX_SAFE_INTEGER, 12, "jelszó");

  if (disp_name === false || email === false || tel === false || pass_1 === false || pass_2 === false) return;

  if (pass_1 !== pass_2) {
    document.getElementById("signup_pass_2_err").innerHTML = "A jelszavaknak egyeznie kell<br>";
    return;
  }

  const response = await fetch(url + "user/create", {
    method: "POST",
    body: tel.length === 0 ? JSON.stringify({
      disp_name: disp_name,
      email: email,
      pass: pass_1,
    }) : JSON.stringify({
      disp_name: disp_name,
      email: email,
      pass: pass_1,
      phone_number: tel,
    })
  });
  if (response.ok === false) {
    const result = await response.text();
    if (result === "Already exists") {
      document.getElementById("signup_display_name_err").innerHTML = "User already exists with this email<br>";
    } else {
      document.getElementById("signup_display_name_err").innerHTML = result + "<br>";
    }
  } else {
    document.getElementById("signup_display_name_err").innerHTML = "";
    location.href = 'login.html';
  }
}

async function login() {
  let email = validateEmail("login_email", "login_email_err", 254, 0, "email");
  let pass = validateString("login_pass", "login_pass_err", Number.MAX_SAFE_INTEGER, 12, "jelszó");

  if (email === false || pass === false) return;

  const response = await fetch(url + "token/get_refresh_token", {
    method: "POST",
    body: JSON.stringify({
      email: email,
      pass: pass,
    })
  });
  if (response.status === 403) {
    document.getElementById("login_email_err").innerHTML = "Username or password is incorrect<br>";
  } else if (response.ok === false) {
    result = await response.text();
    document.getElementById("login_email_err").innerHTML = "Unexpected error:" + result + "<br>";
  } else {
    document.getElementById("login_email_err").innerHTML = "";
    location.href = 'profile.html';
  }
}

window.login = login;
window.signup = signup;
