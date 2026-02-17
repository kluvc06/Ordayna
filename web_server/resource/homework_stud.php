<?php include 'header.php'?>


<style>

    .container {height: 90%;width: 95%;margin-left: 1%!important;}
/* .container{border: 3px purple solid;} */

</style>


<h1>Házi feladat</h1>
<div class="container" >

    <div class="row g-1 rowcols-md-4 rowcols-sm-2">

        <div class="col-md-2 col-sm-1" id="border">
            <div class="box-long">
                <p class="h6">Tantárgyak<hr></p>
                <select name="sometext" size="15" class="listbox" id="tantargy_option" onchange="loadHomework(this)">

            </div>
        </div>
        <div class="col-md-8 co-sm-3 g-1">
            <div class="col" id="border">
                <div class="box-small">
                    <p class="h6">Feladatok<hr></p>
                </div>
            </div>
            <div class="row rowcols-2 g-0">
                <div class="col-md-9 col-sm-2" id="border">
                    <div class="box">
                        <p class="h6">Feladat leírás<hr></p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-1" id="border">
                    <div class="box">
                        <p class="h6">Leadás<hr></p>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-2 col-sm-1" id="border">
            <div class="box-long">
                <p class="h6">Várható Dolgozatok<hr></p>
            </div>
        </div>
    </div>


<button onclick="location.href='index.php'" id="home_button">home</button>
<button onclick="location.href='profile.php'" id="user_button">Felhasználó</button>
</div>