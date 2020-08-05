<?php

include_once "lib/bookrewind.php";
require_once "lib/user.php";
require_once "lib/template.php";

session_start();
$sql = new MySQL();
$user = new User($sql);

$user->accessPage(User::$permissions['ADMIN']);

$classes = $user->getClasses();
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<?php echo getHead("Admin"); ?>
</head>
<body class="green">
<?php echo getNavbar($user->get('mail'), ["title" => "Gestione Libri", "color" => "green"]); ?>
<main style="padding-top:40px;">
    <div class="material-search-bar" data-target="searchbar.php" data-data="search-query"></div>
    <div class="row" style="margin-top: 40px;">
        <div class="card white">
            <div class="container">
                <form id="form-book-add" action="api/book.php" method="post" autocomplete="off">
                    <div class="card-content">
                        <span class="card-title blue-grey-text">Inserisci Libro</span>
                        <blockquote>
                            Di default non è possibile caricare due copie dello stesso libro ad un singolo utente. <b>Accetta duplicati</b> permette di sovrascrivere questo comportamento<br><br>
                            L'icona blu permette di gestire l'utente corrente.
                        </blockquote>
                        <div class='row no-margin'>
                            <div class='input-field col s12'>
                                <i class="material-icons prefix">person_outline</i>
                                <input id="book-add-user" type="text" name="book-add-user" autocorrect="off" autocapitalize="off" spellcheck="false" required value="<?php echo $_POST['book-add-user']; ?>">
                                <label for="book-add-user">User</label>
                            </div>
                            <div class='input-field col s6 m8'>
                                <i class="material-icons prefix">book</i>
                                <input id="book-add-ISBN" name="book-add-ISBN" type="number" class="prevent-keys" autocorrect="off" autocapitalize="off" spellcheck="false" required <?php if($_POST['book-add-user']) echo "autofocus"?>>
                                <label for="book-add-ISBN">ISBN</label>
                            </div>
                            <div class="col s6 m4" style="padding-left:24px; padding-top: 4px;">
                                <div class="grey-text" style="margin-bottom: 10px;">
                                    Accetta Duplicati
                                </div>
                                <div class="switch">
                                    <label>
                                        Off
                                        <input type="checkbox" name="book-add-duplicate">
                                        <span class="lever"></span>
                                        On
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn-large waves-effect waves-light pink' name="action" value='book-add' type='submit'>Aggiungi<i class="material-icons right">add</i></button></a>
                        <a><button class="inherit-style" type="reset">Annulla</button></a>
                        <a class="btn btn-large waves-effect waves-light blue right" onclick="v = $('#book-add-user').val(); if(v) gotoUser(v)"><i class="material-icons">person</i></a>
                    </div>
                </form>
                <script>$('#form-book-add').ajaxFormEx(function(){$('#book-add-ISBN').val('').focus()})</script>
            </div>
        </div>
    </div>
    <div class="container">
		<div class="row">
            <div class="card-panel green darken-3" style="margin-bottom: 0;">
                <h5 style="vertical-align:middle;" class="white-text"><i class="material-icons small inline">library_books</i> Gestione Adozioni</h5>
            </div>
            <div class="tabs-wrapper row">
                <div class="col s12">
                    <ul class="tabs tabs-fixed-width">
                        <li class="tab col s3"><a class="waves-effect" href="#tab-category-add"><i class="material-icons inline">add</i> Aggiungi</a></li>
                        <li class="tab col s3"><a class="waves-effect" href="#tab-category-edit"><i class="material-icons inline">edit</i> Modifica</a></li>
                        <li class="tab col s3 disabled"><a class="grey-text" href="#tab-category-ex"><i class="material-icons inline">history</i> Adozioni Ex</a></li>
                    </ul>
                </div>
                <div id="tab-category-add" class="tab col s12">
                    <div class="card white">
                        <form id="form-category-add" action="api/category.php" method="post" autocomplete="off">
                            <div class="card-content">
                                <span class="card-title blue-grey-text">Aggiungi Adozione</span>
                                <div class='row no-margin'>
                                    <div class='input-field col s12 m6'>
                                        <i class="material-icons prefix">title</i>
                                        <input id='category-add-title' name="category-add-title" type="text" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-add-title">Titolo</label>
                                    </div>
                                    <div class='input-field col s12 m6'>
                                        <input id='category-add-subtitle' name="category-add-subtitle" type="text" autocorrect="off" autocapitalize="off" spellcheck="false">
                                        <label for="category-add-subtitle">Sottotitolo</label>
                                    </div>
                                    <div class='input-field col s12 m6'>
                                        <i class="material-icons prefix">info_outline</i>
                                        <input id="category-add-ISBN" name="category-add-ISBN" minlength="13" maxlength="13" type="number" class="prevent-keys" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-add-ISBN">ISBN</label>
                                    </div>
                                    <div class='input-field col s6 m3'>
                                        <i class="material-icons prefix">euro_symbol</i>
                                        <input id="category-add-price" name="category-add-price" type="number" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-add-price">Prezzo</label>
                                    </div>
                                    <div class='input-field col s6 m3'>
                                        <select multiple name="category-add-classes[]">
                                            <option class="disabled" disabled>Classi</option>
                                            <?php
                                            foreach ($classes as $class){
                                                echo "<option value='$class'>$class</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class='input-field col s6'>
                                        <i class="material-icons prefix">person</i>
                                        <input id="category-add-author" name="category-add-author" type="text" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-add-author">Autore</label>
                                    </div>
                                    <div class='input-field col s6'>
                                        <i class="material-icons prefix">home</i>
                                        <input id="category-add-publisher" name="category-add-publisher" type="text" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-add-publisher">Editore</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-action">
                                <a><button class='btn-large waves-effect waves-light pink' name='category-add' type='submit'>Aggiungi<i class="material-icons right">add</i></button></a>
                                <a><button class="inherit-style" type="reset">Annulla</button></a>
                            </div>
                        </form>
	                    <script>$('#form-category-add').ajaxFormEx(function(data, form){
												$('#book-add-ISBN-from-category').val($('#category-add-ISBN').val());
												$('#book-add-user-from-category').val($('#book-add-user').val());
												form.trigger('reset');
												$('#modal-add-book-from-category').modal('open');
											})</script>
                    </div>
                </div>
                <div id="tab-category-edit" class="tab col s12">
                    <div class="card white">
                        <div class="card-content">
                            <span class="card-title blue-grey-text">Modifica Adozione</span>
                            <div class='input-field col s12'>
                                <i class="material-icons prefix">search</i>
                                <input id='category-edit-search' maxlength="13" type="text" autocorrect="off" autocapitalize="off" spellcheck="false">
                                <label for="category-edit-search">ISBN</label>
                            </div>
                        </div>
                        <form id="form-category-edit" action="api/category.php" method="post" autocomplete="off">
	                        <input id='category-edit-ID' name="category-edit-ID" type="hidden" value="-1" required>
                            <div class="card-content">
                                <div class='row no-margin'>
                                    <div class='input-field col s12 m6'>
                                        <i class="material-icons prefix">title</i>
                                        <input id='category-edit-title' name="category-edit-title" type="text" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-edit-title">Titolo</label>
                                    </div>
                                    <div class='input-field col s12 m6'>
                                        <input id='category-edit-subtitle' name="category-edit-subtitle" type="text" autocorrect="off" autocapitalize="off" spellcheck="false">
                                        <label for="category-edit-subtitle">Sottotitolo</label>
                                    </div>
                                    <div class='input-field col s12 m6'>
                                        <i class="material-icons prefix">info_outline</i>
                                        <input id="category-edit-ISBN" name="category-edit-ISBN" minlength="13" maxlength="13" type="number" class="prevent-keys" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-edit-ISBN">ISBN</label>
                                    </div>
                                    <div class='input-field col s6 m3'>
                                        <i class="material-icons prefix">euro_symbol</i>
                                        <input id="category-edit-price" name="category-edit-price" type="number" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-edit-price">Prezzo</label>
                                    </div>
                                    <div class='input-field col s6 m3'>
                                        <select multiple id="category-edit-classes" name="category-edit-classes[]">
                                            <option class="disabled" disabled>Classi</option>
                                            <?php
                                            foreach ($classes as $class){
                                                echo "<option value='$class'>$class</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class='input-field col s6'>
                                        <i class="material-icons prefix">person</i>
                                        <input id="category-edit-author" name="category-edit-author" type="text" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-edit-author">Autore</label>
                                    </div>
                                    <div class='input-field col s6'>
                                        <i class="material-icons prefix">home</i>
                                        <input id="category-edit-publisher" name="category-edit-publisher" type="text" autocorrect="off" autocapitalize="off" spellcheck="false" required>
                                        <label for="category-edit-publisher">Editore</label>
                                    </div>
	                                <div class="switch col s4 m3 l2" style="margin-top:2rem;">
		                                <label>
			                                <input type="checkbox" name="category-edit-move">
			                                <span class="lever"></span>
			                                Sposta
		                                </label>
	                                </div>
	                                <div class='input-field col s8 m9 l10'>
		                                <i class="material-icons prefix" style="padding-top:8px;">arrow_right_alt</i>
		                                <input id="category-edit-move-to-ISBN" name="category-edit-move-to-ISBN" maxlength="13" type="number" class="prevent-keys" autocorrect="off" autocapitalize="off" spellcheck="false">
		                                <label for="category-edit-move-to-ISBN">ISBN</label>
	                                </div>
                                </div>
                            </div>
                            <div class="card-action">
                                <a><button class='btn-large waves-effect waves-light pink' name='category-edit' type='submit'>Modifica<i class="material-icons right">edit</i></button></a>
                                <a><button class='btn-large waves-effect waves-light pink' name='category-delete' type='submit'>Elimina<i class="material-icons right">delete_forever</i></button></a>
                                <a><button class="inherit-style" type="reset">Annulla</button></a>
                            </div>
                        </form>
	                    <script>
		                    $('#form-category-edit').ajaxFormEx(function(data){ $('#category-edit-search').val($('#category-edit-ISBN').val()).trigger('change'); });
	                    </script>
                    </div>
                </div>
                <div id="tab-category-ex" class="tab col s12">
                </div>
            </div>
		</div>
	</div>
</main>
<div id='modal-add-book-from-category' class='container small modal'>
    <div class="card white">
        <form id="form-add-book-from-category" action="api/book.php" method="post" autocomplete="off">
            <div class="card-content">
                <span class="card-title blue-grey-text">Registra libro all'utente</span>
                <div class='row' style='margin-bottom: 0;'>
									<div class='input-field col s12'>
											<i class="material-icons prefix active">person_outline</i>
											<input id="book-add-user-from-category" type="text" name="book-add-user" autocorrect="off" autocapitalize="off" spellcheck="false" required value="<?php echo $_POST['book-add-user']; ?>">
									</div>
									<div class='input-field col s12 disabled'>
											<i class="material-icons prefix">book</i>
											<input id="book-add-ISBN-from-category" name="book-add-ISBN" type="number" class="prevent-keys" autocorrect="off" autocapitalize="off" spellcheck="false" required readonly>
									</div>
                </div>
            </div>
            <div class="card-action">
						<a><button class='btn-large waves-effect waves-light pink' name="action" value='book-add' type='submit'>Aggiungi<i class="material-icons right">add</i></button></a>
						<a><button class="inherit-style modal-close" type="reset">Chiudi</button></a>
            </div>
        </form>
        <script>$('#form-add-book-from-category').ajaxFormEx()</script>
    </div>
</div>
<div id='modal-book-free-by-date' class='container small modal'>
    <div class="card white">
        <form id="form-reset-bookings" action="api/book.php" method="post" autocomplete="off">
            <div class="card-content">
                <span class="card-title blue-grey-text">Reset Prenotazioni</span>
                <div class='row' style='margin-bottom: 0;'>
                    <div class="col s12">
                        Tutte le prenotazioni eseguite prima di 13 giorni fa verranno annullate e i libri resi nuovamente disponibili.
                    </div>
                </div>
            </div>
            <div class="card-action">
                <a><button class='btn btn-large pink modal-close waves-effect waves-light' name='book-free-all-by-date' type="submit">Invia</button></a>
                <a class="modal-close">Annulla</a>
            </div>
        </form>
        <script>$('#form-reset-bookings').ajaxFormEx()</script>
    </div>
</div>
<div class="fixed-action-btn toolbar">
    <a class="btn-floating btn-large teal darken-4">
    </a>
    <ul>
        <li class="waves-effect waves-light modal-trigger tooltipped" href="#modal-book-free-by-date" data-position="top" data-delay="20" data-tooltip="Reset Prenotazioni">
            <a><i class="material-icons">history</i> <i class="material-icons">shopping_cart</i></a>
        </li>
    </ul>
</div>
<div class="fixed-action-btn">
    <a class="btn-floating btn-large pink waves-effect waves-light" onclick="$('.fixed-action-btn.toolbar').openToolbar();">
        <i class="large material-icons">add</i>
    </a>
    <ul>
        <li>
            <a class="btn-floating blue waves-effect waves-light tooltipped" href="manage-invoices.php" data-position="left" data-delay="20" data-tooltip="Gestione Ricevute"><i class="material-icons">receipt</i></a>
        </li>
        <li>
            <a class="btn-floating blue waves-effect waves-light tooltipped" href="manage-users.php" data-position="left" data-delay="20" data-tooltip="Gestione Utenti"><i class="material-icons">supervisor_account</i></a>
        </li>
    </ul>
</div>
<script>
    $('.modal').modal();
    var is_searching = 0;
    $('#category-edit-search').on('input change', function (e) {
        var form = $('#form-category-edit');
        var search = $(this).val();
        if(search.length === 13){
            if (is_searching) {
                clearTimeout(is_searching);
            }
            is_searching = setTimeout(function () {
                $.ajax({
                    url: "api/category.php",
                    method: "POST",
                    data: {"category-get": true, "category-get-ISBN": search, ajax : true},
                    timeout: 10000
                }).done(function (jRes) {
                    if(debug){
                        console.log(jRes);
                    }
                    if(jRes.r){
	                    $('#category-edit-ID').val(jRes.data.category.ID);
                        $('#category-edit-ISBN').val(jRes.data.category.ISBN);
                        $('#category-edit-title').val(jRes.data.category.title);
                        $('#category-edit-subtitle').val(jRes.data.category.subtitle);
                        $('#category-edit-price').val(jRes.data.category.price);
                        $('#category-edit-author').val(jRes.data.category.author);
                        $('#category-edit-publisher').val(jRes.data.category.publisher);
                        $('#category-edit-classes').val(jRes.data.category.classes);

                        form.updateTextFields();
                    } else {
                        form.trigger('reset');
                    }

                    form.find('select').material_select();
                });
            }, e.type === 'change' ? 0 : 500);
        }
        else {
            form.trigger('reset');
        }
    });
</script>
<?php
    echo getFooter("green");
    if(isset($_GET['goto-category'])){
        echo "<script>$(document).ready(function(){gotoCategory('{$_GET['goto-category']}')});</script>";
    }
?>
</body>
</html>