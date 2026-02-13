<!--I DONT KNOW-->
<?php include 'header.php'?>
<?php  ?>
<style>
    .container {height: 90%;width: 90%;margin-left: 2%!important;}
    
</style>

<body onload="generateContentForCreate()">

<h1>Órarend tervező </h1> 
<select name="class_id" id="classes" >
    
</select>


<div class="container ">
    <div class="row ">

        <div class="col-2">
            <div class="box">
                <p class="h6">Tanárok<hr></p>
                <select name="sometext" size="15" class="listbox" id="tanar_option" onchange="addItem(this, 'tanar')">
                    
                </select>
            </div>
        </div>
        <div class="col-2">
            <div class="box">
                <p class="h6">Tantárgy<hr></p>
                <select name="sometext" size="15" class="listbox" id="tantargy_option" onchange="addItem(this, 'targy')">
                    
                    
                </select>
            </div>
        </div>
        <div class="col-8">
            <div class="box" >
                <p class="h6">Órarend<hr></p>
                <div class="box-md-long" id="orarend_box"></div>
                <div class="input-div">
                    <button ><-</button>
                    <button onclick="lockData()">-></button>
                    <input type="text" id="targy" readonly><input type="text" id="terem" readonly><input type="text" id="tanar" readonly>
                </div>
            </div>
        </div>
    </div>
    <div class="row"><p></p></div>
    <div class="row">
        <div class="col-5 ">
            <div class="box-small">
                <p class="h6">Termek<hr></p>
                <select name="sometext" size="5" class="listbox" id="terem_option" onchange="addItem(this, 'terem')">
                    
                </select>
            </div>
        </div>
        <div class="col-3">
            <div class="box-small">
                <p class="h6">Órák száma: <span id="orak_szama_tc"></span><hr></p>
                <p class="h6">Osztály: <span id="osztaly_tc"></span><hr></p>
                <p class="h6">Nyelv: <span id="nyelv_tc"></span><hr></p>
                <p class="h6">Csoport: <span id="csoport_tc"></span></p>
            </div>
        </div>
        <div class="col-4">
            <div class="box-small">
                <p class="h6">Visszajelzés<hr></p>
                <span id="err"></span>
                
            </div>
        </div>

    </div>
</div>

<br><button onclick="location.href='index.php'"  id="home_button">home</button>
<button onclick="location.href='profile.php'"  id="user_button">Felhasználó</button>

<script>
        
</script>


<script src="js/time_create.js"></script>
</body>