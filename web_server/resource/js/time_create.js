const teac = ["Kovács Anna", "Nagy Bence", "Szabó Csilla", "Tóth Dávid", "Varga Eszter", "Kiss Feri", "Horváth Gábor", "Balogh Hanna", "Papp István", "Takács Jázmin", "Molnár Katalin", "Németh László", "Farkas Márk", "Orbán Nóra", "Lukács Oliver", "Bodó Petra", "Pintér Rita", "Gulyás Sándor", "Hegedűs Tamás", "Veres Vivien", "Szalai Zsolt", "Fodor Balázs", "Barta Dóra", "Csonka Erik", "Vincze Fanni", "Borbély Gergő", "Hajdu Helga", "Somogyi József", "Bíró Krisztián", "Juhász Laura"];
const room = ["101", "102", "103", "104", "105", "201", "202", "203", "204", "205", "301", "302", "303", "304", "305", "A1", "A2", "B1", "B2", "Lab1"];
const subj = ["matematika", "kémia", "biológia", "magyar nyelv", "irodalom", "történelem", "földrajz", "angol", "német", "informatika", "testnevelés", "ének-zene", "rajz", "technika", "etika", "digitális kultúra", "gazdaságtan", "programozás", "fizika"];
const clas = ["Class A", "Class B", "Class C", "Class D", "Class E", "Class F", "Class G", "Class H", "Class I", "Class J", "Class K", "Class L", "Class M", "Class N", "Class O", "Class P", "Class Q", "Class R", "Class S", "Class T", "Class U", "Class V", "Class W", "Class X", "Class Y", "Class Z"];


const teac_tag = document.getElementById("tanar_option");
const subj_tag = document.getElementById("tantargy_option");
const room_tag = document.getElementById("terem_option");
const clas_tag = document.getElementById("classes");

const day = document.getElementById("days");
const id = document.getElementById("get_id")

const orarend = document.getElementById("orarend_box");

const targy = document.getElementById('targy');
targy.value = "";
const tanar = document.getElementById('tanar');
tanar.value = "";
const terem = document.getElementById('terem');
terem.value = "";



const err = document.getElementById("err");

err.innerHTML = "";

function generateContentForCreate() {
    console.log("loads")
    teac_tag.innerHTML = teac.map(t => `<option value="${t}" >${t}</option>`).join("");
    subj_tag.innerHTML = subj.map(t => `<option value="${t}" >${t}</option>`).join("");
    room_tag.innerHTML = room.map(t => `<option value="${t}" >${t}</option>`).join("");

    clas_tag.innerHTML = clas.map(t => `<option value="${t}">${t}</option>`).join("");


    console.log("works")
}

let db = 1;
let id_arr = [];
let dataArray = [];

function lockData() {

    if (terem.value && tanar.value && targy.value) {
        if(db<10){
            db=`0${db}`
        }

        const entry = {
            id: `ora_${db}`,
            text: `${db}. ${terem.value} | ${tanar.value} | ${targy.value} | ${day.value}`
        };

        dataArray.push(entry);

        orarend.innerHTML = dataArray
            .map(item => `<option value="${item.id}">${item.text}</option>`)
            .join("");
        id_arr.push(db)
        db++;
        err.innerHTML = "";

        terem.value = "";
        tanar.value = "";
        targy.value = "";
        addId()
    } else {
        err.innerHTML = "Hiányos adatok";
    }
}


function addItem(e, z) {
    document.getElementById(z).value = e.options[e.selectedIndex].getAttribute("value");
    e.selectedIndex=-1;
}

function addId() {
    id.innerHTML = id_arr.map(t => `<option value="${t}" >${t}</option>`).join("");
    id.style="display:block"

}

function deleteData() {
    let del_id = id.value; 

    if (del_id) {
        dataArray = dataArray.filter(item => item.id !== `ora_${del_id}`);
        id_arr = id_arr.filter(t => t != del_id);

        orarend.innerHTML = dataArray
            .map(item => `<option value="${item.id}">${item.text}</option>`)
            .join("");
        
        addId(); 
    } else {
        err.innerHTML = "Nincs kiválasztott ID elem!";
        err.style.color = "red";
    }
    if(id_arr.length===0){
        id.style="display:none"
    }
}

function allClear() {
    db = 1;
    id_arr = [];
    dataArray = [];
    orarend.innerHTML = "";
    terem.value = "";
    targy.value = "";
    tanar.value = "";
    err.innerHTML = "";
    id.style="display:none"

}
function done(){
    //loads to database
}

