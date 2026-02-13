const teac = ["Kovács Anna","Nagy Bence","Szabó Csilla","Tóth Dávid","Varga Eszter","Kiss Feri","Horváth Gábor","Balogh Hanna","Papp István","Takács Jázmin","Molnár Katalin","Németh László","Farkas Márk","Orbán Nóra","Lukács Oliver","Bodó Petra","Pintér Rita","Gulyás Sándor","Hegedűs Tamás","Veres Vivien","Szalai Zsolt","Fodor Balázs","Barta Dóra","Csonka Erik","Vincze Fanni","Borbély Gergő","Hajdu Helga","Somogyi József","Bíró Krisztián","Juhász Laura"];
const room = ["101", "102", "103", "104", "105", "201", "202", "203", "204", "205", "301", "302", "303", "304", "305", "A1", "A2", "B1", "B2", "Lab1"];
const subj = ["matematika","kémia","biológia","magyar nyelv","irodalom","történelem","földrajz","angol","német","informatika","testnevelés","ének-zene","rajz","technika","etika","digitális kultúra","gazdaságtan","programozás","fizika"];
const clas = ["Class A", "Class B", "Class C", "Class D", "Class E", "Class F","Class G", "Class H", "Class I", "Class J", "Class K", "Class L","Class M", "Class N", "Class O", "Class P", "Class Q", "Class R","Class S", "Class T", "Class U", "Class V", "Class W", "Class X","Class Y", "Class Z"];


const teac_tag = document.getElementById("tanar_option");
const subj_tag = document.getElementById("tantargy_option");
const room_tag = document.getElementById("terem_option");
const clas_tag = document.getElementById("classes");

const orarend = document.getElementById("orarend_box");

const targy =document.getElementById('targy');
targy.value="";
const tanar =document.getElementById('tanar');
tanar.value="";
const terem =document.getElementById('terem');
terem.value="";

function generateContentForCreate() {
    console.log("loads")
    teac_tag.innerHTML = teac.map(t => `<option value="${t}" >${t}</option>`).join("");
    subj_tag.innerHTML = subj.map(t => `<option value="${t}" >${t}</option>`).join("");
    room_tag.innerHTML = room.map(t => `<option value="${t}" >${t}</option>`).join("");

    clas_tag.innerHTML = clas.map(t => `<option value="${t}">${t}</option>`).join("");

    console.log("works")
}

function lockData(){
    if (terem.value!="" && tanar.value!="" && targy.value!="" ){
        let ora_span=document.createElement("p");
        ora_span.innerHTML="hello"
        orarend.appendChild(ora_span)
        
    }
}

function addItem(e, z) {
    document.getElementById(z).value = e.options[e.selectedIndex].getAttribute("value");
}

function deleteData(){
    
}


