<?php

include_once "lib/bookrewind.php";
require_once "lib/user.php";
require_once "lib/template.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$libri = new Bookrewind($sql);

$user->accessPage(User::$permissions['ADMIN']);

if(isset($_POST['add-user'])){
    $r = false;
    $ID = $user->register($_POST['add-user-name'], $_POST['add-user-surname'], $_POST['add-user-mail'], $_POST['add-user-parentmail'], $_POST['add-user-class']);
    if($ID){
        throw_success("Utente registrato");
	    $_SESSION['add-user'] = ['name' => $_POST['add-user-name'], 'surname' => $_POST['add-user-surname'], 'mail' => $_POST['add-user-mail'], 'parentmail' => $_POST['add-user-parentmail'], 'class' => $_POST['add-user-class']];
        $r = true;
    }

    checkPostAjaxCall($r, $_SESSION['add-user']);
}
else if(isset($_POST['search-user'])){
    $r = false;
    $data = $user->find($_POST['search-user-mail']);
    if($data){
        $r = true;
    }

    checkPostAjaxCall($r, ['user' => $data]);
}
else if(isset($_POST['manage-user'])){
    $r = false;
    $data = $user->find($_POST['edit-user-ID'], true);
    if($data){
        $_SESSION['manage-user'] = $data;

        $r = true;
    }

    checkPostAjaxCall($r, ['ID' => $data['ID']]);
}
else if(isset($_POST['edit-user'])) {
    $r = false;
    if ($user->edit($_POST['edit-user-ID'], [2 => $user->generatePermissionFlag($_POST['edit-user-permission']), 3 => $_POST['edit-user-mail'], 4 => $_POST['edit-user-parentmail'], 5 => $_POST['edit-user-name'],
            6 => $_POST['edit-user-surname'], 8 => $_POST['edit-user-class']])) {
        $r = true;
        throw_success("Dati utente modificati");
    }

    checkPostAjaxCall($r);
}
else if(isset($_POST['reset-user-psw'])){
    $r = false;
    if($user->resetPsw($_POST['edit-user-ID'])){
        $r = true;
        throw_success("Password resettata");
    }

    checkPostAjaxCall($r);
}

if(isset($_GET['clear-manage-user'])){
    $_SESSION['manage-user'] = null;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php echo getHead("Admin"); ?>
</head>
<body class="green">
<?php echo getNavbar($user->get('mail'), ["title" => "Gestione Utenti", "color" => "green"]); ?>
<div id='modal-invoice-registration' class='modal' style='width:45%;'>
	<div class="card white">
		<div class="card-content">
			<span class="card-title blue-grey-text"><i class="material-icons inline small">receipt</i> Genera la ricevuta di Iscrizione</span>
			<div class='row'>
				<p class="flow-text">
					<small>
						L'utente è stato registrato con successo. Ora stampa la ricevuta.<br/>
						<b>Dati: </b><span id="add-user-invoice-data">
							<?php
							$newUser = $_SESSION['add-user'];
							if($newUser){
								echo $newUser['name']." ".$newUser['surname'].", ".$newUser['mail']." - ".$newUser['class'];
							}
							?>
						</span>
					</small>
				</p>
			</div>
		</div>
		<div class="card-action">
			<a target='_blank' onclick='setTimeout(() => { window.location = window.location.href; }, 2000)' href='invoice/invoice-registration.php'><button class='btn btn-large waves-effect waves-light pink' name='ajax-change-password'>Stampa<i class="material-icons right">print</i></button></a>
		</div>
	</div>
</div>
<!--<div id='modal-invoice-unregistration' class='modal' style='width:45%;'>
	<div class="card white">
		<div class="card-content">
			<span class="card-title blue-grey-text"><i class="material-icons inline small">receipt</i> Genera la ricevuta di Anullamento Iscrizione</span>
			<div class='row'>
				<p class="flow-text">
					<small>
						La registrazione è stata annullata con successo. Ora stampa la ricevuta.<br/>
						<b>Dati: </b>
						<?php
						/*$newUser = $_SESSION['remove-user'];
						if($newUser){

							echo $newUser['name']." ".$newUser['surname'].", ".$newUser['mail']." - ".$newUser['class'];
						}*/
						?>
					</small>
				</p>
			</div>
		</div>
		<div class="card-action">
			<a target='_blank' onclick='setTimeout(() => { window.location = window.location.href; }, 2000)'' href='invoice/invoice-unregistration.php'><button class='btn btn-large waves-effect waves-light pink' name='ajax-change-password'>Stampa<i class="material-icons right">print</i></button></a>
		</div>
	</div>
</div>-->
<?php
if(isset($_SESSION['add-user'])){
	echo '<script>$(document).ready(function(){$("#modal-invoice-registration").modal({dismissible: false}).modal("open");})</script>';
}
/*if(isset($_SESSION['remove-user'])){
	echo '<script>$(document).ready(function(){$("#modal-invoice-unregistration").modal({dismissible: false}).modal("open");})</script>';
}*/
?>
<main style="padding-top:40px;">
    <div class="material-search-bar" data-target="searchbar.php" data-data="search-query"></div>
    <div class="row" style="margin-top: 40px;">
        <div class="card white">
            <div class="container">
                <form id="form-add-user" method="post" autocomplete="off" data-preloader="add-user-preloader">
                    <div class="card-content">
                        <span class="card-title blue-grey-text">Registrazione Nuovo Utente</span>
                        <blockquote>
                            Gli utenti vengono distinti per email. Pertanto non è possibile creare due account associandoli ad una stessa mail.
                        </blockquote>
                        <div class='row no-margin'>
                            <div class='input-field col s12'>
                                <i class="material-icons prefix">mail</i>
                                <input id="add-user-mail" type="email" name="add-user-mail" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                <label for="add-user-mail">Mail</label>
                            </div>
                            <div class='input-field col s12'>
                                <i class="material-icons prefix">mail</i>
                                <input id="add-user-parentmail" type="email" name="add-user-parentmail" autocorrect="off" autocapitalize="off" spellcheck="false">
                                <label for="add-user-parentmail">Mail Genitore</label>
                            </div>
                            <div class='input-field col s5'>
                                <i class="material-icons prefix">person_outline</i>
                                <input id="add-user-name" type="text" name="add-user-name" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                <label for="add-user-name">Nome</label>
                            </div>
                            <div class='input-field col s4'>
                                <input id="add-user-surname" type="text" name="add-user-surname" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                <label for="add-user-surname">Cognome</label>
                            </div>
                            <div class='input-field col s3'>
                                <select name="add-user-class" required>
                                    <option class="disabled" name="00" selected disabled>Classe</option>
                                    <?php
                                    $classes = $user->getClasses();

                                    foreach ($classes as $class){
                                        echo "<option value='$class'>$class</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn-large waves-effect waves-light pink' name='add-user' type='submit'>Aggiungi<i class="material-icons right">add</i></button></a>
                        <a class="modal-close"><button class="inherit-style" type="reset">Annulla</button></a>
                        <div id="add-user-preloader" class="right" style="padding-top: 6px; display: none">
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
                <script>
	                $('#form-add-user').ajaxFormEx(
	                	function(data, form){
	                		form.trigger('reset');
			                $('#modal-invoice-registration').modal({dismissible: false}).modal('open');
			                $('#add-user-invoice-data').html(data['name'] + " " + data['surname'] + ", " + data['mail'] + " - " + data['class']);
	                	});
                </script>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class='card white'>
                <div class='card-content'>
                    <span class='card-title blue-grey-text'>Gestione Utenti</span>
                    <div class='input-field col s12'>
                        <i class='material-icons prefix'>search</i>
                        <input id='search-user-mail' type='text' class='validate' name='search-user-mail' autocorrect='off' autocapitalize='off' spellcheck='false'>
												<label for='search-user-mail hide-on-small-only'>Ricerca per E-Mail (usa la ricerca di inizio pagina per cercare per cognome)</label>
                    </div>
                </div>
                <form id='edit-user' method='post' autocomplete='off'>
                    <div class='card-content'>
                        <div class='row no-margin'>
                            <div class='input-field col s12 m8 l8'>
                                <input id='edit-user-mail' name='edit-user-mail' type='email' class='validate enable-on-modify' required>
                                <label for='edit-user-mail'>E-Mail</label>
                            </div>
                            <div class='input-field col s6 m2 l2' style='pointer-events: none;'>
                                <input class='disabled' name='edit-user-ID' id='edit-user-ID' type='text' readonly>
                                <label for='edit-user-ID'>ID</label>
                            </div>
                            <div class='input-field col s6 m2 l2'>
                                <select id="edit-user-class" name="edit-user-class">
                                    <option name="" selected disabled>Classe</option>
                                    <?php
                                    foreach ($classes as $class){
                                        echo "<option value='$class'>$class</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class='input-field col s12'>
                                <input id='edit-user-parentmail' name='edit-user-parentmail' type='email' class='validate enable-on-modify'>
                                <label for='edit-user-parentmail'>E-Mail Genitore</label>
                            </div>
                            <div class='input-field col s12 m6 l6'>
                                <input id='edit-user-name' name="edit-user-name" type='text' class='validate capitalize' required autocorrect='off' autocapitalize='off' spellcheck='false'>
                                <label for='edit-user-name'>Nome</label>
                            </div>
                            <div class='input-field col s12 m6 l6'>
                                <input id='edit-user-surname' name="edit-user-surname" type='text' class='validate capitalize' required autocorrect='off' autocapitalize='off' spellcheck='false'>
                                <label for='edit-user-surname'>Cognome</label>
                            </div>
                            <?php
                            if($user->hasAccess(User::$permissions['SUPER_USER'], false)){
                                echo "<div class='input-field col s12 m6 l6'>
                                        <select id='typology' required disabled>
                                            <option value=\"\" disabled selected>Scegli la tipologia...</option>
                                            <option value=\"1\">Studente</option>
                                            <option value=\"3\">Gestione</option>
                                        </select>
                                        <label>Tipologia</label>
                                    </div>
                                    <div class='input-field col s12 m6 l6'>
                                        <select id='edit-user-permission' name='edit-user-permission[]' multiple>
                                            <option value='USER' disabled selected>Scegli i permessi...</option>
                                            <option value='SUPER_USER'>Super User</option>
                                            <option value='ADMIN'>Admin</option>
                                        </select>
                                        <label>Permessi</label>
                                    </div>";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn btn-large waves-effect waves-light blue' name='manage-user' type="submit"><i class="material-icons">book</i></button></a>
                        <a><button class='btn btn-large waves-effect waves-light pink' name='edit-user' type="submit"><i class="material-icons right">edit</i>Modifica</button></a>
                        <a><button class='btn btn-large waves-effect waves-light pink' name='reset-user-psw' type="submit"><i class="material-icons right">lock_open</i>Reset Psw</button></a>
                        <a class="hide-on-med-and-down"><button type="button" class="inherit-style" onclick="$('#search-user-mail').trigger('input')">Annulla</button></a>
                    </div>
                </form>
                <script>
                    $('#edit-user').ajaxFormEx(
                        function(data){
                            if(!data)
                                $('#search-user-mail').val($('#edit-user-mail').val()).trigger('input');
                            else
                                window.location = 'home.php?mid='+data.ID;
                        }
                    );
                </script>
            </div>
        </div>
    </div>
</main>
<div class="fixed-action-btn">
    <a class="btn-floating btn-large pink">
        <i class="large material-icons">link</i>
    </a>
    <ul>
        <li>
            <a class="btn-floating blue waves-effect waves-light tooltipped" href="manage-books.php" data-position="left" data-delay="20" data-tooltip="Gestione Libri"><i class="material-icons">book</i></a>
        </li>
        <li>
            <a class="btn-floating blue waves-effect waves-light tooltipped" href="manage-invoices.php" data-position="left" data-delay="20" data-tooltip="Gestione Ricevute"><i class="material-icons">receipt</i></a>
        </li>
    </ul>
</div>
<script>
    $('select').material_select();
    $('.modal').modal();

    var is_searching = 0;
    $('#search-user-mail').on('input change', function (e) {
        var form = $('#edit-user');
        var search = $(this).val();
        if(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(search)){
            if (is_searching) {
                clearTimeout(is_searching);
            }
            is_searching = setTimeout(function () {
                $.ajax({
                    url: "manage-users.php",
                    method: "POST",
                    data: {"search-user": true, "search-user-mail": search, ajax : true},
                    timeout: 10000
                }).done(function (jRes) {
                    if(jRes.r){
                        $('#edit-user-mail').val(jRes.data.user.mail);
                        $('#edit-user-parentmail').val(jRes.data.user.parentmail);
                        $('#edit-user-name').val(jRes.data.user.name);
                        $('#edit-user-surname').val(jRes.data.user.surname);
                        $('#edit-user-ID').val(jRes.data.user.ID);
                        $('#edit-user-class').val(jRes.data.user.class);

                        var permissionTab = $('#edit-user-permission');
                        if(permissionTab){
                            permissionTab.val(jRes.data.user.flag);
                        }
                        form.updateTextFields();
                    } else {
                        form.trigger('reset');
                    }

                    form.find('select').material_select();
                });
            }, event.type === 'input' ? 500 : 0);
        }
        else {
            form.trigger('reset');
        }
    });
</script>
<?php
    echo getFooter("green");
    if(isset($_GET['goto-user'])){
        echo "<script>gotoUser('{$_GET['goto-user']}')</script>";
    }
?>
</body>
</html>