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
const hide= document.getElementById("hide");


function loadUserData(){
    display.innerHTML=usre.display_name;
    email.innerHTML=usre.email;
    tel.innerHTML=usre.tel;
    img.innerHTML=usre.img;
}

function hide_show(){
    hide.style="display:block"
}

function changePfp(a){
    switch(a){
        case 1:
            img.innerHTML='<img src="img\\img'+a+'.jpg" alt="pfp" >'
        case 2:
            img.innerHTML='<img src="img\\img'+a+'.jpg" alt="pfp" >'
        case 3:
            img.innerHTML='<img src="img\\img'+a+'.jpg" alt="pfp" >'
        case 0:
            hide.style="display:none"



    }

}

