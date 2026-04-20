import {getCookie} from "./cookie.js";

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

window.returnHome = returnHome;
