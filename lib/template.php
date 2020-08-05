<?php
require_once "lib.php";

/**
 * @param string $title
 * @param string $index
 * @return string
 */
function getHead($title = "Bookrewind", $index = 'noindex, follow')
{
	$rand = rand(1, 9999);
	$head = "
		<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'/>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
		<meta name='theme-color' content=''>
		<title>$title &bull; Calibri</title>

		<meta name='robots' content='$index'>
		<meta name='keywords' content='Calibri, Calini, Liceo Scientifico, Book, Rewind, bookrewind, libro, usato, scambio, vendita, acquisto, brescia, mercatino'>
		<meta name='description' content='Sito scambio libro usato: BookRewind'>
		<meta name='author' content='Pinchetti Luca'>

		<link rel='shortcut icon' type='image/png' href='/resources/logo.png' />
		<link rel=\"icon\" href=\"favicon.png\" type=\"image/png\" />

		<link type='text/css' rel='stylesheet' href='/v2_0/materialize/css/materialize.css?$rand' media='screen,projection'/>
		<link type='text/css' rel='stylesheet' href='/v2_0/materialize/css/mystyle.css?$rand' media='screen,projection'/>

		<link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
		<link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>

		<script type='text/javascript' src='https://code.jquery.com/jquery-2.1.4.min.js'></script>
		<script type='text/javascript' src='/v2_0/materialize/js/materialize.js?$rand'></script>
		<script type='text/javascript' src='/v2_0/materialize/js/jquery.form.min.js'></script>
		<script type='text/javascript' src='/v2_0/materialize/js/myscript.js?$rand'></script>
		<script type='text/javascript' src='/v2_0/searchbar.js?$rand'></script>
		
		<style>#iubenda-cs-banner{top:0!important;left:0!important;position:fixed!important;width:100%!important;z-index:99999998!important;background:#000;background:rgba(0,0,0,.85)}.iubenda-cs-content{display:block;margin:0 auto;padding:10px 50px 10px 20px;width:auto;font-family:Helvetica,Arial,FreeSans,sans-serif;font-size:12px;color:#fff!important}.iubenda-cs-rationale{max-width:900px;position:relative;margin:0 auto}.iubenda-banner-content>p{font-family:Helvetica,Arial,FreeSans,sans-serif;line-height:1.5}.iubenda-cs-close-btn{color:#fff!important;text-decoration:none;font-size:12px;position:absolute;top:-5px;right:-20px;border:1px solid #fff!important;display:inline-block;width:20px;height:20px;line-height:20px;text-align:center;border-radius:10px}.iubenda-cs-cookie-policy-lnk{text-decoration:underline;color:#fff!important;font-size:12px;font-weight:900}</style>
		<script type=\"text/javascript\">
			/*<![CDATA[*/
			var _iub = _iub || [];
			_iub.csConfiguration = {
			    siteId: 834201, cookiePolicyId: 8143334, lang: 'it', localConsentDomain: 'bookrewind.altervista.org',
			    banner: { applyStyles: false, content: \"Questo sito utilizza cookie di terze parti eseguire statistiche, raccogliendo dati anonimi (spero) sulla tua navigazione; essendo quelli utilizzati di default da altervista non sono in grado di disattivarli, spero, quindi, di aver compilato correttamente la privacy policy. Se qualcuno trovasse qualche errore e imprecisione sarebbe gentile se me lo comunicasse. Se vuoi saperne di pi&ugrave; o negare il consenso a tutti o ad alcuni cookie, %{cookie_policy_link}. Chiudendo questo banner, scorrendo questa pagina, cliccando su un link o proseguendo la navigazione in altra maniera, acconsenti all&apos;uso dei cookie.\", cookiePolicyLinkCaption: \"clicca qui\"}
			};
			(function (w, d) {
			    var loader = function () { var s = d.createElement(\"script\"), tag = d.getElementsByTagName(\"script\")[0]; s.src = \"//cdn.iubenda.com/cookie_solution/iubenda_cs.js\"; tag.parentNode.insertBefore(s, tag); };
			    if (w.addEventListener) { w.addEventListener(\"load\", loader, false); } else if (w.attachEvent) { w.attachEvent(\"onload\", loader); } else { w.onload = loader; }
			})(window, document);
			/*]]>*/
		</script>";

	return $head;
}

/**
 * @param $user
 * @param null $params
 * @return string
 */
function getNavbar($user, $params = NULL)
{
	$title = $params['title'] ? $params['title'] : "< Bookrewind >";
	$color = $params['color'] ? $params['color'] : "blue";
	$redirect = $params['redirect'] ? $params['redirect'] : "/v2_0/index.php";

	$head = "
	<div class='navbar-fixed'>
            <nav class='$color darken-4'>
			<div class='nav-wrapper'>
				<div class='container'>
					<a class='brand-logo center' href='$redirect'> $title </a>
					<ul class='right'>";
	if($user){
                $head .= "<li><a class='waves-effect waves-light' href='/v2_0/index.php?logout'><i class='material-icons'>close</i></a></li>";
                $head .= "<li><a class='hide-on-small-only waves-effect waves-light' href='/v2_0/home.php'><i class='material-icons'>exit_to_app</i></a></li>";
    } else {
                $head .= "<li><a class='waves-effect waves-light btn-login modal-trigger' href='#modal-login'><i class='material-icons'>person</i></a></li>";
    }
	$head .= "
						<li><a class='waves-effect waves-light hide-on-small-only' onclick='window.location = window.location.href;'><i class='material-icons'>refresh</i></a></li>
					</ul>
					<ul class='left'>
						<li><a class='waves-effect waves-light' href='/v2_0/index.php'><i class='material-icons'>home</i></a></li>
						<li><a class='hide-on-small-only waves-effect waves-light modal-trigger' data-target='modal-first-access'><i class='material-icons'>info_outline</i></a></li>
					</ul>
				</div>
			</div>
		</nav>
	</div>
	<script>
	    $('.dropdown-button').dropdown({hover:true, constrainWidth:false, belowOrigin:true});
		$('.button-collapse').sideNav();
		$('meta[name=theme-color]').attr('content', $('nav').css('background-color'));
	</script>";
	echo "
	<div id=\"modal-first-access\" class=\"modal\">
	    <div class=\"card white\">
	        <div class=\"card-content\">
	            <span class=\"card-title blue-grey-text\" style=\"zoom: 1.25\">Regolamento</span>
	            <div class='row no-margin flow-text'>
	                <p style=\"zoom: 0.9\">Ecco un paio di informazioni che dovresti tenere bene a mente :)</p>
	                <ul class=\"collection\">
	                    <li class=\"collection-item\"><small>Il “Mercatino deI Libri di testo usati Liceo A. Calini” è un'iniziativa del Comitato di Supporto Comitato Genitori Liceo A. Calini, a scopo mutualistico e non a fini di lucro, gestita in collaborazione con gli studenti del Liceo A. Calini nell’ambito di un progetto di “Alternanza Scuola Lavoro”.
                        I libri verranno ritirati in conto vendita e verranno restituiti su richiesta, se non venduti.
                        </small></li>
	                    <li class=\"collection-item\"><small>Verranno accettati solamente libri adottati e utilizzati dalla scuola Liceo A. Calini in buone condizioni.</small></li>
	                    <li class=\"collection-item\"><small>I libri verranno rivenduti al 50% del prezzo di copertina, e al proprietario verrà corrisposto l’intero importo ricavato dalla vendita.</small>
	                    <li class=\"collection-item\"><small>I libri invenduti potranno essere ritirati dal proprietario entro il 19 settembre 2018. Successivamente alla data del 19 settembre 2018, i libri invenduti non ritirati si intenderanno gratuitamente devoluti al Liceo A. Calini, che potrà liberamente disporne nell’ambito delle proprie attività didattiche.
                        </small></li>
                        <li class=\"collection-item\"><small>Il ricavato della vendita potrà essere ritirato dal venditore dopo la vendita fino al 19 Settembre. Successivamente alla data del 19 settembre 2018, il ricavato della vendita non ritirato si intenderà in via definitiva gratuitamente devoluto al Liceo A. Calini, che potrà liberamente disporne per il miglioramento dell’offerta formativa.
                        </small></li>
                        <li class=\"collection-item\"><small>Il prezzo di vendita dei libri sarà il 50% di quello originale di copertina.</small></li>
                        <li class=\"collection-item\"><small>Prima di effettuare l'acquisto, l'acquirente è tenuto a controllare l'integrità dei libri per evitare richieste di restituzione e/o cambio successivi, che non saranno consentiti.</small></li>
	                    <li class=\"collection-item\"><small><span class=\"card-title blue-grey-text\">Privacy</span>I dati raccolti dal sito vengono unicamente utilizzati per garantire il funzionamento e il miglioramento del servizio stesso. Vengono 
	                    inoltre raccolti dati anonimi sull'utilizzo a fine statistico (principalmente perchè non si possono disattivare e sono inclusi nel servizio di hosting stesso).<br>Alla registrazione l'utente maggiorenne dà il proprio consenso al trattamento dei propri
                        dati personali; questi sono:<ul><li> - nome</li><li> - cognome</li><li> - mail</li><li> - classe</li></ul>Inoltre verranno registrate le azioni che lo stesso utente compie relativamente alla consegna, 
                        prenotazione, acquisto, ritiro di libri.<br>I dati raccolti sono utili esclusivamente al funzionamento del servizio e non verranno comunicati a terzi: l'accesso ad essi è riservato al personale Calibri e al gestore del sito.<br>
                        In qualsiasi momento è possibile richiedere la cessazione del trattamento dei dati raccolti. Questo terminerà l'iscrizione al servizio, ma i dati passati raccolti relativi all'utente verranno mantenuti fino alla 
                        chiusura del servizio durante l'anno corrente per garantire un corretto funzionamento del servizio per gli altri utenti e per il tracciamento della contabilità. Ad esclusione delle ricevute firmate dall'utente, 
                        utili appunto alla contabilità, verrà garantito l'anonimato dei dati raccolti relativi all'utente richiedente.<br></small></li>
	                </ul>
	            </div>
	        </div>
	        <div class=\"card-action\">
	            <a class=\"modal-close\" id=\"first-access-ok\">OK!</a>
	            <a target='_blank' href='/v2_0/resources/regolamento_calibri_2019.pdf'>PDF <i class='material-icons inline'>file_download</i></a>
	            <a target='_blank' href='/v2_0/resources/manuale_calibri_2019.pdf'>MANUALE <i class='material-icons inline'>file_download</i></a>
	            <span class='right' style='padding-left:0.5rem; vertical-align:middle; display:inline-block; height: 22px;'><a href=\"https://www.iubenda.com/privacy-policy/784848/cookie-policy\" class=\"iubenda-white iubenda-embed \" title=\"Cookie Policy\">Cookie Policy</a> <script type=\"text/javascript\">(function (w,d) {var loader = function () {var s = d.createElement(\"script\"), tag = d.getElementsByTagName(\"script\")[0]; s.src=\"https://cdn.iubenda.com/iubenda.js\"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener(\"load\", loader, false);}else if(w.attachEvent){w.attachEvent(\"onload\", loader);}else{w.onload = loader;}})(window, document);</script></span>
	            <span class='right' style='vertical-align:middle; display:inline-block; height: 22px;'><a href=\"//www.iubenda.com/privacy-policy/8143334\" class=\"iubenda-white iubenda-embed\" title=\"Privacy Policy\">Privacy Policy</a><script type=\"text/javascript\">(function (w,d) {var loader = function () {var s = d.createElement(\"script\"), tag = d.getElementsByTagName(\"script\")[0]; s.src = \"//cdn.iubenda.com/iubenda.js\"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener(\"load\", loader, false);}else if(w.attachEvent){w.attachEvent(\"onload\", loader);}else{w.onload = loader;}})(window, document);</script></span>
	        </div>
	    </div>
	</div>";

	return $head;
}

/**
 * @param string $color
 * @return string
 */
function getFooter($color = "blue")
{
	$footer = "
	<footer class='page-footer $color darken-3 z-depth-5'>
		<div class='container'>
			<div class='row'>
				<div class='col l6 s12'>
					<h5 class='white-text'>CaLibri - BookRewind</h5>
					<span style='vertical-align:middle; display:inline-block; height: 22px;'><a href=\"//www.iubenda.com/privacy-policy/8143334\" class=\"iubenda-white iubenda-embed\" title=\"Privacy Policy\">Privacy Policy</a><script type=\"text/javascript\">(function (w,d) {var loader = function () {var s = d.createElement(\"script\"), tag = d.getElementsByTagName(\"script\")[0]; s.src = \"//cdn.iubenda.com/iubenda.js\"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener(\"load\", loader, false);}else if(w.attachEvent){w.attachEvent(\"onload\", loader);}else{w.onload = loader;}})(window, document);</script></span>
					<span style='padding-left:0.5rem; vertical-align:middle; display:inline-block; height: 22px;'><a href=\"https://www.iubenda.com/privacy-policy/784848/cookie-policy\" class=\"iubenda-white iubenda-embed \" title=\"Cookie Policy\">Cookie Policy</a> <script type=\"text/javascript\">(function (w,d) {var loader = function () {var s = d.createElement(\"script\"), tag = d.getElementsByTagName(\"script\")[0]; s.src=\"https://cdn.iubenda.com/iubenda.js\"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener(\"load\", loader, false);}else if(w.attachEvent){w.attachEvent(\"onload\", loader);}else{w.onload = loader;}})(window, document);</script></span>
				</div>
				<div class='col l4 offset-l2 s12'>
					<h5 class='white-text'>Contatti</h5>
					<ul>
						<li><a class='grey-text text-lighten-3'><i class='material-icons inline'>mail</i> genitori.calini@gmail.com</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class='footer-copyright $color darken-4 z-depth-5'>
			<div class='container'>
			    © 2017 - Luca Pinchetti - pincoluca1@gmail.com
			    <span class='right'>v2.0.0</span>
			</div>
		</div>
	</footer>";
	$footer .= "<script>";
	$msg = get_msg();
	foreach($msg as $item){
		$footer .= "Materialize.toast('{$item['message']}', '{$item['duration']}', '{$item['style']}', 0);";
	}
	$footer .= "</script>";

	return $footer;
}

