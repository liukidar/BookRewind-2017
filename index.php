<?php
require_once "lib/template.php";
require_once "lib/user.php";
require_once "lib/bookrewind.php";

session_start();
$sql = new MySQL();
$user = new User($sql);

if (isset($_POST['ajax-login'])) {
    $r = false;
    $ip = get_client_ip();
    if($sql->lock($ip, 0)){
        if($user->login($_POST['user'], $_POST['psw'])){
            throw_success("Login Effettuato");
            $r = true;
        }
        $sql->unlock_wait($ip, 1000000, 500000);

    }
    checkPostAjaxCall($r, NULL, !$r);
} elseif (isset($_GET['logout'])) {
    $user->logout();
    throw_msg("Logout effettuato");
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
	<?php echo getHead("Home", "index, follow"); ?>
    <style>
        .btn-margin {
            margin: 1rem;
        }
    </style>
</head>
<body class="white">
<?php echo getNavbar($user->get("mail"),
	["color" => "green", "title" => "Home", "redirect" => "/index.php"]); ?>
<main style="margin-bottom: 40px;">
	<div style="margin:70px 0 29px 0;">
		<div class="container">
			<?php
			if(MAINTENANCE){
				echo "<div class='row card-panel red center-align' style='margin: 20px 0 40px 0'>
					<h5 class='white-text'>!!! Servizio in manutenzione !!!</h5>
					<h6 class='white-text'>- Non sarà temporaneamente possibile accedere al servizio -</h6>
				</div>";
			}
			?>
			<div class="row center-align">
				<img src="resources/logo.png">
				<h5 class="title">Liceo Scientifico A. Calini &bull; Brescia</h5>
				<h3 style="letter-spacing: 3px; margin-bottom: 0;">CALIBRI</h3>
				<div class="grey-text" style="padding-left: 25%; letter-spacing: 1px;">BookRewind &bull; powered by Me</div>
			</div>
		</div>
	</div>
	<div class="green" style="padding: 40px 0 20px 0; box-shadow: 0 0 11px rgba(0, 0, 0, 0.18) inset, 0 0 15px rgba(0, 0, 0, 0.15) inset;">
		<div class="container">
            <div class="material-search-bar" data-target="searchbar.php" data-data="search-query"></div>
		</div>
	</div>
    <div class="tabs-wrapper row">
        <div class="container">
            <div class="col s12" style="margin-bottom: 20px;">
                <ul class="tabs">
                    <li class="tab col s4 m2"><a href="#chi-siamo">IL PROGETTO</a></li>
                    <li class="tab col s4 m2"><a href="#dove-siamo">LA SCUOLA</a></li>
                    <li class="tab col s4 m2"><a href="#gestione">GESTIONE</a></li>
                    <li class="tab col s4 m2"><a href="#orari-e-date">ORARI E DATE</a></li>
                    <li class="tab col s4 m2"><a href="#istruzioni">ISTRUZIONI</a></li>
                    <li class="tab col s4 m2"><a href="#CONTATTI">CONTATTI</a></li>
                </ul>
            </div>
        </div>
        <div id="chi-siamo" class="tab col s12">
            <div class="container">
                <div class="card white">
                    <div class="card-content ">
                        <span class="card-title blue-grey-text"><b>Chi Siamo?</b></span>
                        <hr class="white">
                        <p class="flow-text">
                            <small>
                                Il CALIBRI è un nuovo servizio organizzato dal Comitato Genitori del Calini con la collaborazione della scuola
                                stessa e di alcuni studenti, il cui scopo è di permettere lo scambio di libri usati in buono stato e dare loro
                                nuova vita.<br>
                                Il servizio è rivolto alle famiglie per contenere i costi d’acquisto dei libri scolastici e costituisce anche
                                un’attività formativa per gli studenti.<br>
                                Offre la possibilità di vendere e acquistare i libri di testo al 50% del prezzo di copertina.<br><br>
                                Assicurati di leggere le <b style="cursor: pointer" onclick="$('.tabs').tabs('select_tab', 'istruzioni');">ISTRUZIONI</b>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div id="dove-siamo" class="tab col s12">
            <div class="container">
                <div class="card white darken-1">
                    <div class="card-content ">
                        <span class="card-title blue-grey-text"><b>Dove Siamo?</b></span>
                        <p class="flow-text">
                            <small>
                                All’interno del Liceo Calini in uno spazio al piano interrato cui si accede a destra, dopo l’entrata, nel
                                secondo cortile.
                            </small>
                        </p>
                    </div>
                    <div class="card-image">
                        <div id="map" style="width:100%; height:400px"></div>
                        <script>
                            function setupMap() {
                                var mapPosition = {lat: 45.5474411, lng: 10.2214499};
                                var markerPosition = {lat: 45.5470000, lng: 10.2214500};
                                var mapOptions = {
                                    center: mapPosition,
                                    zoom: 18,
                                    mapTypeId: google.maps.MapTypeId.ROADMAP
                                };
                                var map = new google.maps.Map(document.getElementById("map"), mapOptions);
                                var marker = new google.maps.Marker({ position: markerPosition,
                                    map: map,
                                    title: 'Calibri - Mercatino Libro Usato'
                                });
                            }
                        </script>
                        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCAt_o8FK7xvcAMkD4hPp3ucXxrsDZT_dU&callback=setupMap"></script>
                    </div>
                </div>
            </div>
        </div>
        <div id="gestione" class="tab col s12">
            <div class="container">
                <div class="card white darken-1">
                    <div class="card-content ">
                        <span class="card-title blue-grey-text"><b>Chi lo gestisce?</b></span>
                        <hr class="white">
                        <p class="flow-text">
                            <small>
                                Gli studenti che aderiscono al progetto di Alternanza Scuola-Lavoro supportati dal Comitato dei Genitori.
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div id="orari-e-date" class="tab col s12">
            <div class="container">
                <div class="card white darken-1">
                    <div class="card-content ">
                        <span class="card-title blue-grey-text"><b>Quando?</b></span>
                        <hr class="white">
                        <p class="flow-text">
                            <small>
                                Il <b>mercoledì</b> dalle 17.00 alle 19.00 (mercoledi 11/09 dalle 14 alle 16,30)<br>
                                Il <b>sabato</b> dalle 09.00 alle 12.00<br>
                            </small>
                        </p>
                        <hr class="white">
                        <p class="flow-text">
                            <small>
                                <b>Giugno</b>: 15 - 22- 26 - 29<br><br>
                                <b>Luglio</b>: 03 – 06 – 10 – 13 – 17 – 20 – 24 – 27 - 31<br><br>
                                <b>Agosto</b>: 28 - 31<br><br>
                                <b>Settembre</b>: 04 – 07 – 09(*) – 11(**) – 14<br>
                                (*)Apertura straordinaria lunedì 09 dalle 17.00 alle 19.00<br>
                                (**)Orario speciale mercoledì 11 dalle 14.00 alle 16.00<br><br>
                                La <b>riconsegna</b> dei libri invenduti avverrà sabato 21/09  e sabato 28/09.
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div id="istruzioni" class="tab col s12">
            <div class="container">
                <div class="card white darken-1">
                    <div class="card-content">
                        <span class="card-title blue-grey-text"><b>Come funziona?</b></span>
                        <hr class="white">
                        <div class="row">
                            <p class="flow-text">
                                <small>
                                    Per vendere o acquistare si accede iscrivendosi di persona presso il liceo Calini nei giorni di apertura
                                    e versando una <b>quota di € 5</b> a copertura delle spese di gestione.<br>
                                    Si riceve una password di accesso che permette di accedere al servizio da casa, su apposito link,
                                    per prenotare i libri da acquistare.<br>
                                    Chi vuole vendere i propri libri dovrà depositarli presso la sede di Calibri. I libri
                                    dovranno essere ben conservati e saranno accettati libri privi di codice e­book, mentre non saranno
                                    accettati libri privi di CD o fascicoli originari.<br>
                                    Dopo l&#39;iscrizione si potranno prenotare i libri adottati dalla propria classe, se disponibili; inoltre si potranno
                                    portare (se ancora utilizzati dall&#39;Istituto), uno per tipo, i libri utilizzati dallo studente gli anni precedenti e
                                    renderli disponibili alla vendita. Prima di procedere all&#39;acquisto il libri prenotati potranno essere visionati
                                    presso la sede di Calibri (e si potranno scegliere quelli in migliori condizioni).
                                </small>
                            </p>
                        </div>
                        <div>
                            <blockquote class="flow-text">
                                <small>
                                    Qui sono presenti i file contententi tutte le informazioni.
                                    E' necessario <b>leggere</b> unicamente il <b>regolamento</b> per effettuare l'iscrizione in tranquillità.<br>
                                    Le istruzioni sono una breve guida al funzionamento del sito.<br>
                                    Il modulo privacy non è altro che una copia di ciò che vi verrà fatto firmare all'iscrizione.
                                </small>
                            </blockquote>
                            <a target='_blank' href='/v2_0/resources/modulo_privacy_calibri_2019.pdf' class="btn btn-large pink btn-margin">Modulo privacy<i class="material-icons right">file_download</i></a>
                            <a target='_blank' href='/v2_0/resources/manuale_calibri_2019.pdf' class="btn btn-large pink btn-margin">Istruzioni per l'utente<i class="material-icons right">file_download</i></a>
                            <a target='_blank' href='/v2_0/resources/regolamento_calibri_2019.pdf' class="btn btn-large pink btn-margin">Volantino<i class="material-icons right">file_download</i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="CONTATTI" class="tab col s12">
            <div class="container">
                <div class="card white darken-1">
                    <div class="card-content">
                        <span class="card-title blue-grey-text"><b>Contatti</b></span>
                        <hr class="white">
                        <p class="flow-text">
                            <small>
                                <i class='material-icons inline blue-text'>mail</i> genitori.calini@gmail.com
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
    <div id="modal-login" class="modal container small">
        <div class="card white">
            <form id="form-login" method="post" action="index.php" autocomplete="off" data-preloader="login-preloader">
                <div class="card-content">
                    <span class="card-title blue-grey-text">Effettua il Login</span>
                    <div class='row no-margin'>
                        <div class="input-field col s12">
                            <i class="material-icons prefix">account_circle</i>
                            <input id="user" name="user" type="text" class="validate" required autocorrect="off" autocapitalize="off" spellcheck="false">
                            <label for="user">Mail</label>
                        </div>
                        <div class="input-field col s12">
                            <i class="material-icons prefix">lock</i>
                            <input id="psw" name="psw" type="password" class="validate" required>
                            <label for="psw">Password</label>
                        </div>
                    </div>
                </div>
                <div class="card-action">
                    <a><button type="submit" class="waves-effect waves-light btn-large pink" name="ajax-login" value="true"> LOGIN <i class="material-icons right">exit_to_app</i> </button></a>
                    <a class="modal-close">Annulla</a>
                    <div id="login-preloader" class="right" style="padding-top: 6px; display: none">
                        <div class="preloader-wrapper small active">
                            <div class="spinner-layer spinner-teal-only">
                                <div class="circle-clipper left">
                                    <div class="circle"></div>
                                </div><div class="gap-patch">
                                    <div class="circle"></div>
                                </div><div class="circle-clipper right">
                                    <div class="circle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<script>
    $(document).ready(function(){
        $('#form-login').ajaxFormEx(function(){window.location.href = "/v2_0/home.php";});
    });
</script>
<?php
if($user->is()){
	echo "<div class='fixed-action-btn'>
			<a class='btn-floating btn-large pink pulse'  href='home.php'>
				<i class='small material-icons'>exit_to_app</i>
			</a>
		</div>";
}
echo getFooter("green");
?>
</body>
</html>
