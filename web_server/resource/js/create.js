import { validateNumber, validateString } from "./validate.js";
import { url, getCookie } from "./cookie.js";

let intezmeny_id = getCookie("intezmeny_id");
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

async function prepareCreate() {
  if (typeof document.getElementById("create_choice").options[document.getElementById("create_choice").selectedIndex] === 'undefined') {
    document.getElementById("create_choice").selectedIndex = 0;
  }
  const val = document.getElementById("create_choice").options[document.getElementById("create_choice").selectedIndex].getAttribute("value");
  let data;
  if (val === "class") {
    data = `<select id="another_choice" size="3" onchange="prepareClass()">`;
  } else if (val === "group") {
    data = `<select id="another_choice" size="3" onchange="prepareGroup()">`;
  } else if (val === "lesson") {
    data = `<select id="another_choice" size="3" onchange="prepareLesson()">`;
  } else if (val === "room") {
    data = `<select id="another_choice" size="3" onchange="prepareRoom()">`;
  } else if (val === "teacher") {
    data = `<select id="another_choice" size="3" onchange="prepareTeacher()">`;
  } else if (val === "user") {
    document.getElementById("form").innerHTML = `
      <select id="another_choice" size="2" onchange="prepareUser()">
        <option value="invite">Meghívás</option>
        <option value="fire">Kidobás</option>
      </select>
      <span id="actual_form"></span>
    `;
    return;
  }
  data += `
      <option value="create">Új</option>
      <option value="modify">Módosítás</option>
      <option value="delete">Törlés</option>
    </select>
    <span id="actual_form"></span>
  `;
  document.getElementById("form").innerHTML = data;
}

async function prepareClass() {
  if (typeof document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex] === 'undefined') {
    document.getElementById("another_choice").selectedIndex = 0;
  }
  const val = document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex].getAttribute("value");
  if (val === "create") {
    document.getElementById("actual_form").innerHTML = `
      
      <input id="class_name" placeholder="Osztály név">
      <input id="class_count" placeholder="Osztály létszám">
      <button onclick="createClass()">Osztály létrehozása</button>
      <div class="errors">
        <span class="err" id="class_name_err"></span>
        <span class="err" id="class_count_err"></span>
      </div>
    `;
  } else if (val === "modify") {
    const response = await fetch(url + "intezmeny/get/classes", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let classes = await response.json();
    let data = `
      <select id="orig" size="3" onchange="modifyClassUpdate()">
    `;
    for (let i = 0; i < classes.length; i++) {
      data += `<option value="${classes[i].id}">${classes[i].name}</option>`
    }
    data += `
      </select>
      <input id="class_name" placeholder="Módositott név">
      <button onclick="modifyClass()">Osztály módosítása</button>
      <div class="errors">
        <span class="err" id="class_name_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  } else if (val === "delete") {
    const response = await fetch(url + "intezmeny/get/classes", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let classes = await response.json();
    let data = `
      <select id="orig" size="3">
    `;
    for (let i = 0; i < classes.length; i++) {
      data += `<option value="${classes[i].id}">${classes[i].name}</option>`
    }
    data += `
      </select>
      <button onclick="deleteClass()">Osztály törlése</button>
      <div class="errors">
        <span class="err" id="class_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  }
}

async function createClass() {
  const class_name = validateString("class_name", "class_name_err", 200, 1, "név");
  const headcount = validateNumber("class_count", "class_count_err", Number.MAX_SAFE_INTEGER, 0, "létszám");
  if (class_name === false || headcount === false) return;

  const response = await fetch(url + "intezmeny/create/class", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: class_name,
      headcount: headcount + "",
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    if (result === "Already exists") {
      document.getElementById("class_name_err").innerHTML = "Osztály vagy csoport már létezik";
    } else {
      document.getElementById("class_name_err").innerHTML = result;
    }
    return;
  }
  document.getElementById("class_name").value = "";
  document.getElementById("class_count").value = "";
}

async function modifyClassUpdate() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const class_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  const response = await fetch(url + "intezmeny/get/classes", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok === false) {
    return;
  }
  document.getElementById("class_name_err").innerHTML = "";
  let classes = await response.json();
  for (let i = 0; i < classes.length; i++) {
    if (classes[i].id === parseInt(class_id)) {
      document.getElementById("class_name").value = classes[i].name;
      break;
    }
  }
}

async function modifyClass() {
  const class_name = validateString("class_name", "class_name_err", 200, 1, "név");
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const class_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  if (class_name === false) return;

  const response = await fetch(url + "intezmeny/update/class", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      class_id: class_id,
      name: class_name,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("class_name_err").innerHTML = result;
    return;
  }
  document.getElementById("class_name").value = "";
  await prepareClass();
}

async function deleteClass() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const class_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/delete/class", {
    method: "DELETE",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      class_id: class_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("class_err").innerHTML = result;
    return;
  }
  await prepareClass();
}

async function prepareGroup() {
  if (typeof document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex] === 'undefined') {
    document.getElementById("another_choice").selectedIndex = 0;
  }
  const val = document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex].getAttribute("value");
  if (val === "create") {
    const response = await fetch(url + "intezmeny/get/classes", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let classes = await response.json();
    let html = `
      <input id="group_name" placeholder="Group név">
      <input id="group_count" placeholder="Group létszám">
      <select id="group_class">
        <option value="-1">-</option>
    `;
    for (let i = 0; i < classes.length; i++) {
      html += `<option value="${classes[i].id}">${classes[i].name}</option>`;
    }
    html += `
      </select>
      <button onclick="createGroup()">Csoport létrehozása</button>
      <div class="errors">
        <span class="err" id="group_name_err"></span>
        <span class="err" id="group_count_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = html;
  } else if (val === "modify") {
    const response = await fetch(url + "intezmeny/get/groups", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let groups = await response.json();
    const response_2 = await fetch(url + "intezmeny/get/classes", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response_2.ok !== true) {
      return;
    }
    let classes = await response_2.json();
    let html = `<select id="orig" size="3" onchange="modifyGroupUpdate()">`;
    for (let i = 0; i < groups.length; i++) {
      html += `<option value="${groups[i].id}">${groups[i].name}</option>`;
    }
    html += `
      </select>
      <input id="group_name" placeholder="Módosított név">
      <input id="group_count" placeholder="Létszám">
      <select id="group_class">
        <option value="-1">-</option>
    `;
    for (let i = 0; i < classes.length; i++) {
      html += `<option value="${classes[i].id}">${classes[i].name}</option>`;
    }
    html += `
      </select>
      <button onclick="modifyGroup()">Csoport módosítása</button>
      <div class="errors">
        <span class="err" id="group_name_err"></span>
        <span class="err" id="group_count_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = html;
  } else if (val === "delete") {
    const response = await fetch(url + "intezmeny/get/groups", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let groups = await response.json();
    let data = `
      <select id="orig" size="3">
    `;
    for (let i = 0; i < groups.length; i++) {
      data += `<option value="${groups[i].id}">${groups[i].name}</option>`
    }
    data += `
      </select>
      <button onclick="deleteGroup()">Csoport törlése</button>
      <div group="errors">
        <span group="err" id="group_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  }
}

async function createGroup() {
  const group_name = validateString("group_name", "group_name_err", 200, 1, "név");
  const headcount = validateNumber("group_count", "group_count_err", Number.MAX_SAFE_INTEGER, 0, "létszám");
  if (typeof document.getElementById("group_class").options[document.getElementById("group_class").selectedIndex] === 'undefined') {
    document.getElementById("group_class").selectedIndex = 0;
  }
  const class_id = document.getElementById("group_class").options[document.getElementById("group_class").selectedIndex].getAttribute("value");
  if (group_name === false || headcount === false) return;

  const response = await fetch(url + "intezmeny/create/group", {
    method: "POST",
    body: class_id === "-1" ? JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: group_name,
      headcount: headcount + "",
    }) : JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: group_name,
      headcount: headcount + "",
      class_id: class_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    if (result === "Already exists") {
      document.getElementById("group_name_err").innerHTML = "A csoport már létezik";
    } else {
      document.getElementById("group_name_err").innerHTML = result;
    }
    return;
  }
  document.getElementById("group_name").value = "";
  document.getElementById("group_count").value = "";
  document.getElementById("group_class").selectedIndex = 0;
}

async function modifyGroupUpdate() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const group_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  const response = await fetch(url + "intezmeny/get/groups", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok === false) {
    return;
  }
  document.getElementById("group_name_err").innerHTML = "";
  let groups = await response.json();
  for (let i = 0; i < groups.length; i++) {
    if (groups[i].id === parseInt(group_id)) {
      document.getElementById("group_name").value = groups[i].name;
      document.getElementById("group_count").value = groups[i].headcount;
      if (groups[i].class !== null) {
        let group_classes = document.getElementById("group_class").options;
        for (let j = 0; j < group_classes.length; j++) {
          if (parseInt(group_classes[j].value) === groups[i].class.id) {
            document.getElementById("group_class").selectedIndex = j;
            break;
          }
        }
      } else {
        document.getElementById("group_class").selectedIndex = 0;
      }
      break;
    }
  }
}

async function modifyGroup() {
  const group_name = validateString("group_name", "group_name_err", 200, 1, "név");
  const group_count = validateString("group_count", "group_count_err", 200, 1, "csoport");
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const group_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  if (typeof document.getElementById("group_class").options[document.getElementById("group_class").selectedIndex] === 'undefined') {
    document.getElementById("group_class").selectedIndex = 0;
  }
  const class_id = document.getElementById("group_class").options[document.getElementById("group_class").selectedIndex].getAttribute("value");
  if (group_name === false || group_count === false) return;

  const response = await fetch(url + "intezmeny/update/group", {
    method: "POST",
    body: class_id === "-1" ? JSON.stringify({
      intezmeny_id: intezmeny_id,
      group_id: group_id,
      name: group_name,
      headcount: group_count,
    }) : JSON.stringify({
      intezmeny_id: intezmeny_id,
      group_id: group_id,
      name: group_name,
      headcount: group_count,
      class_id: class_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("group_name_err").innerHTML = result;
    return;
  }
  document.getElementById("group_name").value = "";
  document.getElementById("group_count").value = "";
  document.getElementById("group_class").selectedIndex = 0;
  await prepareGroup();
}

async function deleteGroup() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const group_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/delete/group", {
    method: "DELETE",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      group_id: group_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("group_err").innerHTML = result;
    return;
  }
  await prepareGroup();
}

async function prepareLesson() {
  if (typeof document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex] === 'undefined') {
    document.getElementById("another_choice").selectedIndex = 0;
  }
  const val = document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex].getAttribute("value");
  if (val === "create") {
    document.getElementById("actual_form").innerHTML = `
      <input id="lesson_name" placeholder="Óra név">
      <button onclick="createLesson()">Tanóra létrehozása</button>
      <div class="errors">
        <span class="err" id="lesson_name_err"></span>
      </div>
    `;
  } else if (val === "modify") {
    const response = await fetch(url + "intezmeny/get/lessons", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let lessons = await response.json();
    let data = `
      <select id="orig" size="3" onchange="modifyLessonUpdate()">
    `;
    for (let i = 0; i < lessons.length; i++) {
      data += `<option value="${lessons[i].id}">${lessons[i].name}</option>`
    }
    data += `
      </select>
      <input id="lesson_name" placeholder="Módosított óra név">
      <button onclick="modifyLesson()">Tanóra módosítása</button>
      <div class="errors">
        <span class="err" id="lesson_name_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  } else if (val === "delete") {
    const response = await fetch(url + "intezmeny/get/lessons", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let lessons = await response.json();
    let data = `
      <select id="orig" size="3">
    `;
    for (let i = 0; i < lessons.length; i++) {
      data += `<option value="${lessons[i].id}">${lessons[i].name}</option>`
    }
    data += `
      </select>
      <button onclick="deleteLesson()">Tanóra törlése</button>
      <div class="errors">
        <span class="err" id="lesson_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  }
}

async function createLesson() {
  const lesson_name = validateString("lesson_name", "lesson_name_err", 200, 1, "név");
  if (lesson_name === false) return;

  const response = await fetch(url + "intezmeny/create/lesson", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: lesson_name,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    if (result === "Already exists") {
      document.getElementById("lesson_name_err").innerHTML = "A tanóra már létezik";
    } else {
      document.getElementById("lesson_name_err").innerHTML = result;
    }
    return;
  }
  document.getElementById("lesson_name").value = "";
}

async function modifyLessonUpdate() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const lesson_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  const response = await fetch(url + "intezmeny/get/lessons", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok === false) {
    return;
  }
  document.getElementById("lesson_name_err").innerHTML = "";
  let lessons = await response.json();
  for (let i = 0; i < lessons.length; i++) {
    if (lessons[i].id === parseInt(lesson_id)) {
      document.getElementById("lesson_name").value = lessons[i].name;
      break;
    }
  }
}

async function modifyLesson() {
  const lesson_name = validateString("lesson_name", "lesson_name_err", 200, 1, "név");
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const lesson_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  if (lesson_name === false) return;

  const response = await fetch(url + "intezmeny/update/lesson", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      lesson_id: lesson_id,
      name: lesson_name,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("lesson_name_err").innerHTML = result;
    return;
  }
  document.getElementById("lesson_name").value = "";
  await prepareLesson();
}

async function deleteLesson() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const lesson_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/delete/lesson", {
    method: "DELETE",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      lesson_id: lesson_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("lesson_err").innerHTML = result;
    return;
  }
  await prepareLesson();
}

async function prepareRoom() {
  if (typeof document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex] === 'undefined') {
    document.getElementById("another_choice").selectedIndex = 0;
  }
  const val = document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex].getAttribute("value");
  if (val === "create") {
    document.getElementById("actual_form").innerHTML = `
      <input id="room_name" placeholder="Terem név">
      <input id="room_type" placeholder="Terem type">
      <input id="room_space" placeholder="Capacity">
      <button onclick="createRoom()">Szoba létrehozása</button>
      <div class="errors">
        <span class="err" id="room_name_err"></span>
        <span class="err" id="room_type_err"></span>
        <span class="err" id="room_space_err"></span>
      </div>
    `;
  } else if (val === "modify") {
    const response = await fetch(url + "intezmeny/get/rooms", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let rooms = await response.json();
    let data = `
      <select id="orig" size="3" onchange="modifyRoomUpdate()">
    `;
    for (let i = 0; i < rooms.length; i++) {
      data += `<option value="${rooms[i].id}">${rooms[i].name}</option>`
    }
    data += `
      </select>
      <input id="room_name" placeholder="Módosított név">
      <input id="room_type" placeholder="Módosított type">
      <input id="room_space" placeholder="Módosított capacity">
      <button onclick="modifyRoom()">Szoba módosítása</button>
      <div class="errors">
        <span class="err" id="room_name_err"></span>
        <span class="err" id="room_type_err"></span>
        <span class="err" id="room_space_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  } else if (val === "delete") {
    const response = await fetch(url + "intezmeny/get/rooms", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let rooms = await response.json();
    let data = `
      <select id="orig" size="3">
    `;
    for (let i = 0; i < rooms.length; i++) {
      data += `<option value="${rooms[i].id}">${rooms[i].name}</option>`
    }
    data += `
      </select>
      <button onclick="deleteRoom()">Szoba törlése</button>
      <div class="errors">
        <span class="err" id="room_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  }
}

async function createRoom() {
  const room_name = validateString("room_name", "room_name_err", 200, 1, "név");
  const room_type = validateString("room_type", "room_type_err", 200, 1, "tipus");
  const room_space = validateNumber("room_space", "room_space_err", 99999, 0, "férőhely");
  if (room_name === false || room_type === false || room_space === false) return;

  const response = await fetch(url + "intezmeny/create/room", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: room_name,
      type: room_type,
      space: room_space + "",
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("room_name_err").innerHTML = result;
    return;
  }
  document.getElementById("room_name").value = "";
  document.getElementById("room_type").value = "";
  document.getElementById("room_space").value = "";
}

async function modifyRoomUpdate() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const room_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  const response = await fetch(url + "intezmeny/get/rooms", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok === false) {
    return;
  }
  document.getElementById("room_name_err").innerHTML = "";
  let rooms = await response.json();
  for (let i = 0; i < rooms.length; i++) {
    if (rooms[i].id === parseInt(room_id)) {
      document.getElementById("room_name").value = rooms[i].name;
      document.getElementById("room_type").value = rooms[i].type;
      document.getElementById("room_space").value = rooms[i].space;
      break;
    }
  }
}

async function modifyRoom() {
  const room_name = validateString("room_name", "room_name_err", 200, 1, "név");
  const room_type = validateString("room_name", "room_type_err", 200, 1, "tipus");
  const room_space = validateNumber("room_space", "room_space_err", 99999, 0, "férőhely");
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const room_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  if (room_name === false || room_type === false || room_space === false) return;

  const response = await fetch(url + "intezmeny/update/room", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      room_id: room_id,
      name: room_name,
      type: room_type,
      space: room_space + "",
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("room_name_err").innerHTML = result;
    return;
  }
  document.getElementById("room_name").value = "";
  document.getElementById("room_type").value = "";
  document.getElementById("room_space").value = "";
  await prepareRoom();
}

async function deleteRoom() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const room_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/delete/room", {
    method: "DELETE",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      room_id: room_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("room_err").innerHTML = result;
    return;
  }
  await prepareRoom();
}

async function prepareTeacher() {
  if (typeof document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex] === 'undefined') {
    document.getElementById("another_choice").selectedIndex = 0;
  }
  const val = document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex].getAttribute("value");
  if (val === "create") {
    const response = await fetch(url + "intezmeny/user/get_all", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let users = await response.json();
    let html = `
      <input id="teacher_name" placeholder="Tanár név">
      <input id="teacher_job" placeholder="Szak">
      <select id="teacher_user" size="3">
        <option value="-1">-</option>
    `;
    for (let i = 0; i < users.length; i++) {
      if (users[i].role !== "student") continue;
      html += `<option value="${users[i].id}">${users[i].display_name}</option>`;
    }
    html += `
      </select>
      <button onclick="createTeacher()">Tanár létrehozása</button>
      <div class="errors">
        <span class="err" id="teacher_name_err"></span>
        <span class="err" id="teacher_job_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = html;
  } else if (val === "modify") {
    const response_2 = await fetch(url + "intezmeny/user/get_all", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response_2.ok !== true) {
      return;
    }
    let users = await response_2.json();
    const response = await fetch(url + "intezmeny/get/teachers", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let teachers = await response.json();
    let data = `
      <select id="orig" size="3" onchange="modifyTeacherUpdate()">
    `;
    for (let i = 0; i < teachers.length; i++) {
      data += `<option value="${teachers[i].id}">${teachers[i].name}</option>`
    }
    data += `
      </select>
      <input id="teacher_name" placeholder="Név módosítása">
      <input id="teacher_job" placeholder="Szak módosítása">
      <select id="teacher_user" size="3">
        <option value="-1">-</option>
    `;
    for (let i = 0; i < users.length; i++) {
      if (users[i].role !== "student") continue;
      data += `<option value="${users[i].id}">${users[i].display_name}</option>`;
    }
    data += `
      </select>
      <button onclick="modifyTeacher()">Tanár módosítása</button>
      <div class="errors">
        <span class="err" id="teacher_name_err"></span>
        <span class="err" id="teacher_job_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  } else if (val === "delete") {
    const response = await fetch(url + "intezmeny/get/teachers", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let teachers = await response.json();
    let data = `<select id = "orig" size = "3">`;
    for (let i = 0; i < teachers.length; i++) {
      data += `<option value="${teachers[i].id}"> ${teachers[i].name}</option>`
    }
    data += `
      </select>
      <button onclick="deleteTeacher()">Tanár törlése</button>
      <div class="errors">
        <span class="err" id="teacher_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  }
}

async function createTeacher() {
  const teacher_name = validateString("teacher_name", "teacher_name_err", 200, 1, "név");
  const teacher_job = validateString("teacher_job", "teacher_job_err", 200, 1, "szakma");
  if (typeof document.getElementById("teacher_user").options[document.getElementById("teacher_user").selectedIndex] === 'undefined') {
    document.getElementById("teacher_user").selectedIndex = 0;
  }
  const user_id = document.getElementById("teacher_user").options[document.getElementById("teacher_user").selectedIndex].getAttribute("value");
  if (teacher_name === false || teacher_job === false) return;

  const response = await fetch(url + "intezmeny/create/teacher", {
    method: "POST",
    body: user_id === "-1" ? JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: teacher_name,
      job: teacher_job,
    }) : JSON.stringify({
      intezmeny_id: intezmeny_id,
      name: teacher_name,
      job: teacher_job,
      teacher_uid: user_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("teacher_name_err").innerHTML = result;
    return;
  }
  document.getElementById("teacher_name").value = "";
  document.getElementById("teacher_job").value = "";
  document.getElementById("teacher_user").selectedIndex = 0;
  await prepareTeacher();
}

async function modifyTeacherUpdate() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const teacher_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  const response_2 = await fetch(url + "intezmeny/user/get_all", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response_2.ok !== true) {
    return;
  }
  let users = await response_2.json();
  const response = await fetch(url + "intezmeny/get/teachers", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
    })
  });
  if (response.ok === false) {
    return;
  }
  let teachers = await response.json();
  for (let i = 0; i < teachers.length; i++) {
    if (teachers[i].id === parseInt(teacher_id)) {
      document.getElementById("teacher_name").value = teachers[i].name;
      document.getElementById("teacher_job").value = teachers[i].job;
      let select_data = `<option value="-1">-</option>`;
      for (let j = 0; j < users.length; j++) {
        if (users[j].role !== "student" && teachers[i].uid !== users[j].id) continue;
        select_data += `<option value="${users[j].id}">${users[j].display_name}</option>`;
      }
      document.getElementById("teacher_user").innerHTML = select_data;
      if (teachers[i].uid !== null) {
        let teacher_users = document.getElementById("teacher_user").options;
        for (let j = 0; j < teacher_users.length; j++) {
          if (parseInt(teacher_users[j].value) === teachers[i].uid) {
            document.getElementById("teacher_user").selectedIndex = j;
            break;
          }
        }
      } else {
        document.getElementById("teacher_user").selectedIndex = 0;
      }
      break;
    }
  }
}

async function modifyTeacher() {
  const teacher_name = validateString("teacher_name", "teacher_name_err", 200, 1, "név");
  const teacher_job = validateString("teacher_job", "teacher_job_err", 200, 1, "szakma");
  if (typeof document.getElementById("teacher_user").options[document.getElementById("teacher_user").selectedIndex] === 'undefined') {
    document.getElementById("teacher_user").selectedIndex = 0;
  }
  const user_id = document.getElementById("teacher_user").options[document.getElementById("teacher_user").selectedIndex].getAttribute("value");
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const teacher_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");
  if (teacher_name === false || teacher_job === false) return;

  const response = await fetch(url + "intezmeny/update/teacher", {
    method: "POST",
    body: user_id === "-1" ? JSON.stringify({
      intezmeny_id: intezmeny_id,
      teacher_id: teacher_id,
      name: teacher_name,
      job: teacher_job,
    }) : JSON.stringify({
      intezmeny_id: intezmeny_id,
      teacher_id: teacher_id,
      name: teacher_name,
      job: teacher_job,
      teacher_uid: user_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("teacher_name_err").innerHTML = result;
    return;
  }
  document.getElementById("teacher_name").value = "";
  document.getElementById("teacher_job").value = "";
  document.getElementById("teacher_user").selectedIndex = 0;
  await prepareTeacher();
}

async function deleteTeacher() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    return;
  }
  const teacher_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/delete/teacher", {
    method: "DELETE",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      teacher_id: teacher_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("teacher_err").innerHTML = result;
    return;
  }
  await prepareTeacher();
}

async function prepareUser() {
  if (typeof document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex] === 'undefined') {
    document.getElementById("another_choice").selectedIndex = 0;
  }
  const val = document.getElementById("another_choice").options[document.getElementById("another_choice").selectedIndex].getAttribute("value");
  if (val === "invite") {
    document.getElementById("actual_form").innerHTML = `
      <input id="user_email" placeholder="E-mail cím">
      <button onclick="inviteUser()">Felhasználó meghívása</button>
      <div class="errors">
        <span class="err" id="user_email_err"></span>
      </div>
    `;
  } else if (val === "fire") {
    const response = await fetch(url + "intezmeny/user/get_all", {
      method: "POST",
      body: JSON.stringify({
        intezmeny_id: intezmeny_id,
      })
    });
    if (response.ok !== true) {
      return;
    }
    let users = await response.json();
    const response_2 = await fetch(url + "user/profile", { method: "GET" });
    if (response_2.ok !== true) {
      return;
    }
    let profile = await response_2.json();
    let data = `
      <select id="orig" size="3">
    `;
    for (let i = 0; i < users.length; i++) {
      if (users[i].id === profile.id) continue;
      data += `<option value="${users[i].id}">${users[i].display_name}</option>`
    }
    data += `
      </select>
      <button onclick="fireUser()">Felhasználó kidobása</button>
      <div class="errors">
        <span class="err" id="user_err"></span>
      </div>
    `;
    document.getElementById("actual_form").innerHTML = data;
  }
}

async function inviteUser() {
  const user_email = validateString("user_email", "user_email_err", 200, 1, "email");
  if (user_email === false) return;

  const response = await fetch(url + "intezmeny/user/invite", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      email: user_email,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    if (result === "Already exists") {
      document.getElementById("user_email_err").innerHTML = "Ez a felhasználó már meg van hívva";
    } else {
      document.getElementById("user_email_err").innerHTML = result;
    }
    return;
  }
  document.getElementById("user_email").value = "";
  await prepareUser();
}

async function fireUser() {
  if (typeof document.getElementById("orig").options[document.getElementById("orig").selectedIndex] === 'undefined') {
    document.getElementById("orig").selectedIndex = 0;
  }
  const user_id = document.getElementById("orig").options[document.getElementById("orig").selectedIndex].getAttribute("value");

  const response = await fetch(url + "intezmeny/user/fire", {
    method: "POST",
    body: JSON.stringify({
      intezmeny_id: intezmeny_id,
      uid: user_id,
    })
  });
  if (response.ok !== true) {
    let result = await response.text();
    document.getElementById("user_err").innerHTML = result;
    return;
  }
  await prepareUser();
}

await prepareCreate();

window.prepareCreate = prepareCreate;
window.prepareClass = prepareClass;
window.createClass = createClass;
window.modifyClassUpdate = modifyClassUpdate;
window.modifyClass = modifyClass;
window.deleteClass = deleteClass;
window.prepareGroup = prepareGroup;
window.createGroup = createGroup;
window.modifyGroupUpdate = modifyGroupUpdate;
window.modifyGroup = modifyGroup;
window.deleteGroup = deleteGroup;
window.prepareLesson = prepareLesson;
window.createLesson = createLesson;
window.modifyLessonUpdate = modifyLessonUpdate;
window.modifyLesson = modifyLesson;
window.deleteLesson = deleteLesson;
window.prepareRoom = prepareRoom;
window.createRoom = createRoom;
window.modifyRoomUpdate = modifyRoomUpdate;
window.modifyRoom = modifyRoom;
window.deleteRoom = deleteRoom;
window.prepareTeacher = prepareTeacher;
window.createTeacher = createTeacher;
window.modifyTeacherUpdate = modifyTeacherUpdate;
window.modifyTeacher = modifyTeacher;
window.deleteTeacher = deleteTeacher;
window.prepareUser = prepareUser;
window.inviteUser = inviteUser;
window.fireUser = fireUser;
window.returnHome = returnHome;
