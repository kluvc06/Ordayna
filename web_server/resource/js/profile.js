const usre={
        "display_name": "admin1",
        "email": "forTest@email.com",
        "tel": "06 20 666 1939",
        "img":'<img src="img\\img3.jpg" alt="pfp" >'
    }


const display= document.getElementById("og_display");
const email= document.getElementById("mail_add");
const tel= document.getElementById("og_tel");
const img= document.getElementById("pfp");


function loadUserData(){
    display.innerHTML=usre.display_name;
    email.innerHTML=usre.email;
    tel.innerHTML=usre.tel;
    img.innerHTML=usre.img;
}


