<?php
require_once "lib/template.php";
require_once "lib/user.php";
require_once "lib/bookrewind.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$bookrewind = new BookRewind($sql);

$user->accessPage(User::$permissions['']);
$targetUser = $_SESSION['manage-user'] ? $_SESSION['manage-user'] : $user->data;

if(isset($_POST['getBookedBooks'])){
	$data = [];
	$books = $bookrewind->getBooksEx(User::$t_users, [
		'where' => [['status', EQUAL, 1], ['buyer', EQUAL, $targetUser['ID']]],
		'fields' => ['category.*' => ''],
		'join' => 4
	]);

	while($b = $books->next()){
		$tmp = $bookrewind->getBooksEx(User::$t_users, [
			'where' => [['status', LESS_THAN, 2], ['ID_adozione', EQUAL, $b['ID']]],
			'field' => ['book.ID' => '']
		]);
		$b['books'] = [];
		while($t = $tmp->next()){
			$b['books'][] = $t;
		}
		$data[] = $b;
	}

	checkPostAjaxCall(true, $data);
}
/*elseif(isset($_POST['delete-user'])){
	$r = false;
	if($user->delete($targetUser['ID'])){
		$r = true;
		throw_success("Utente eliminato");

		header('Location: manage-users.php?clear-manage-user');

		exit(0);
	}
	throw_success("Impossibile eliminare l'utente");

	checkPostAjaxCall($r);
}*/
elseif(isset($_POST['buy-books'])){
    $purchased = [];
    $total = 0;
    $ok = true;
    foreach($_POST['booked-books'] as $book){
        if($book){
            if($bookrewind->buyBook($user, $book, $targetUser['ID'])){
                $purchased[] = $book;
            }
        }
        $total += 1;
    }
    if($total == count($purchased) && $total > 0){
        throw_success("Libri acquistati");
    }else {
        if(count($purchased)){
            throw_error("Non tutti i libri sono stati acquistati");
        }else {
            throw_error("Nessun libro acquistato");
            $ok = false;
        }
    }
    if($ok){
        $bookrewind->freeAllByBuyer($targetUser['ID']);
        $_SESSION['invoice-purchase']['books'] = $purchased;
        $_SESSION['invoice-purchase']['user'] = $targetUser;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<?php echo getHead("Profile"); ?>
    <script>
        var user = <?php $targetUser['psw'] = NULL; $targetUser['flag'] = NULL; echo json_encode($targetUser) ?>;
    </script>
</head>
<body class="green">
<?php echo getNavbar($user->get("mail"),
    ["color" => "green", "title" => "Profilo Utente", "redirect" => "/index.php"]); ?>
<main style="padding-top:40px;">
    <div class="material-search-bar" data-target="searchbar.php" data-data="search-query"></div>
    <div class="card-panel white row">
        <div class="container flow-text">
            <div class="left">
                <small><i class="material-icons blue-text inline small">person</i> <b class="margin-right-small"><?php echo $user->toString();?></b></small><br>
                <small><i class="material-icons blue-text inline small">email</i> <?php echo $user->mail();?></small><br>
                <small><i class="material-icons blue-text inline small">euro_symbol</i> <?php $p = $bookrewind->getProceeds($user->get('ID')); echo  $p[PAGATO]."&euro; / ".($p[PAGATO] + $p[ACQUISTATO])."&euro;"?></small>
            </div>
            <div class="right">
                <a class="btn-floating waves-effect waves-light pink margin-right-small modal-trigger" href="#modal-change-psw"><i class="material-icons tooltipped"  data-position='top' data-delay='20' data-tooltip='Modifica Password'>lock</i></a>
            </div>
            <div class="clearfix"></div>
            <?php
            if(isset($_SESSION['manage-user'])){
                echo "
                <div style=''>
                    <div>Gestione:</div>
                    <div class=\"left\" style='border-left: 5px #2196F3 solid; padding-left: 0.5rem; margin-left:1rem;'>
                        <small><i class=\"material-icons blue-text inline small\">supervisor_account</i> <b class=\"margin-right-small\">{$targetUser['name']} {$_SESSION['manage-user']['surname']} - {$targetUser['class']}</b></small><br>
                        <small><i class=\"material-icons blue-text inline small\">email</i> {$targetUser['mail']}</small><br>
                        <small><i class=\"material-icons blue-text inline small\">attach_money</i>";
                $p = $bookrewind->getProceeds($targetUser['ID']);
                    echo $p[PAGATO]."&euro; / ".($p[PAGATO]+$p[ACQUISTATO])."&euro;</small>
                    </div>
                    <div class='clearfix'></div>
                </div>
                <form id='form-book-add' action='api/book.php' method='post' autocomplete='off' style='padding-top:20px;'>
                    <div class='card-content'>
                        <div class='row no-margin'>
                            <input id='book-add-user' type='hidden' name='book-add-user' autocorrect='off' autocapitalize='off' spellcheck='false' required value='{$_SESSION['manage-user']['mail']}'> 
                            <div class='input-field col s6 m8'>
                                <i class='material-icons prefix'>book</i>
                                <input id='book-add-ISBN' name='book-add-ISBN' type='number' class='prevent-keys' autocorrect='off' autocapitalize='off' spellcheck='false' required>
                                <label for='book-add-ISBN'>ISBN</label>
                            </div>
                            <div class='col s6 m4' style='padding-left:24px; padding-top: 4px;'>
                                <div class='switch'>
                                    <label>
                                        Off
                                        <input type='checkbox' name='book-add-duplicate'>
                                        <span class='lever'></span>
                                        On
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='card-action'>
                        <a><button class='btn-large waves-effect waves-light pink' name='action' value='book-add' type='submit'>Aggiungi<i class='material-icons right'>book</i></button></a>
                    </div>
                </form>
                <script>$('#form-book-add').ajaxFormEx(function(){ $('#book-add-ISBN').val('').focus()})</script>";
            }
            ?>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="card-panel green darken-3" style="margin-bottom: 0;">
                <h5 style="vertical-align:middle;" class="white-text"><i class="material-icons small inline">library_books</i> I Tuoi Libri</h5>
            </div>
            <div class="tabs-wrapper row">
                <div class="col s12">
                    <ul class="tabs tabs-fixed-width">
                        <li class="tab col s3"><a class="waves-effect" href="#in-vendita"><i class="material-icons inline">book</i> In Vendita</a></li>
                        <li class="tab col s3"><a class="waves-effect" href="#da-acquistare"><i class="material-icons inline">shopping_cart</i> Da Acquistare</a></li>
                        <li class="tab col s3"><a class="waves-effect" href="#restituiti"><i class="material-icons inline">replay</i> restituiti</a></li>
                    </ul>
                </div>
                <!-- Libri in Vendita -->
                <div id="in-vendita" class="tab col s12 active">
	                <ul class="collection capitalize z-depth-2 collapsible popout" data-collapsible="expandable">
                        <li class="collection-item">
                            <div class="primary-content valign-wrapper flex-line">
                                <?php
                                foreach($bookrewind->STATUS_INFO as $s){
                                    if($s[3][0]){
                                        echo "
				                    <div style='margin-right: 2rem;; margin-top: 6px;'>
				                        <i class='inline material-icons {$s[2]}'>{$s[3][0]}</i> {$s[1]}
				                    </div>";
                                    }
                                }
                                ?>
                            </div>
                        </li>
		                <?php
		                $toSell = $bookrewind->getBooksEx(User::$t_users, [
													'where' => [['owner', EQUAL, $targetUser['ID']], ['status', NOT_EQUAL, RITIRATO]],
			                    'fields' => ['category.*' => '', 'book.status' => 'status', 'book.ID' => 'bookID'],
			                    'join' => 4
		                ]);
		                while($item = $toSell->next()) {
			                echo "
                                <li>
                                    <div class='collapsible-header waves-effect'>
                                        <div style='display: flex'>
                                            <i class='material-icons inline'>filter_drama</i><b>#{$item['bookID']} - {$item['title']}</b>
                                        </div>
                                        <div class='valign-wrapper'>
                                            <span class='black-text' style='padding:0 1rem;'>{$item['price']}&euro;</span>";
			                                if($user->hasAccess(User::$permissions['ADMIN'], false) && $item['status'] == INVALIDATO){
			                                    echo "<button value='{$item['bookID']}' name='book-return' type='submit' class='book-return btn-icon {$bookrewind->STATUS_INFO[$item['status']][2]} transparent secondary-content'><i class='material-icons inline'>{$bookrewind->STATUS_INFO[$item['status']][3][0]}</i></button>";
                                            }
                                            else {
                                                echo "<button class='btn-icon {$bookrewind->STATUS_INFO[$item['status']][2]} transparent secondary-content'><i class='material-icons inline'>{$bookrewind->STATUS_INFO[$item['status']][3][0]}</i></button>";
                                            }
                            echo "      </div>
                                    </div>
                                    <div class='collapsible-body'>
                                        <i class='material-icons inline teal-text'>title</i> {$item['subtitle']}<br>
                                        <i class='material-icons inline teal-text'>person</i> {$item['author']}
                                        <i class='material-icons inline teal-text'>home</i> {$item['publisher']}
                                        <i class='material-icons inline teal-text'>info</i> {$item['ISBN']}
							         </div>
                                </li>";
		                }
		                if (!$toSell->size()) {
			                echo "<li class=\"collection-item\">
                                <div class=\"primary-content\">
                                        <div style=\"padding:5px 0;\"><b>Nessun Libro Trovato</b></div>
                                </div></li>";
		                }
		                ?>
	                </ul>
                </div>
                <!-- Da acquistare -->
                <div id="da-acquistare" class="tab col s12">
                    <ul class="collection capitalize z-depth-2 collapsible popout" data-collapsible="expandable">
		                <li class="collection-item">
			                <div class="primary-content valign-wrapper flex-line">
				                <?php
                                foreach($bookrewind->STATUS_INFO as $s){
                                    if($s[3][1]){
                                        echo "
				                    <div style='margin-right: 2rem;; margin-top: 6px;'>
				                        <i class='inline material-icons {$s[2]}'>{$s[3][1]}</i> {$s[1]}
				                    </div>";
                                    }
                                }
				                ?>
			                </div>
		                </li>
		                <?php
		                $book = $bookrewind->getBooksEx(User::$t_users, [
			                'where' => [['buyer', EQUAL, $targetUser['ID']]],
			                'fields' => ['category.*' => '', 'book.status' => ''],
			                'join' => 4
		                ]);
		                $classbooks = $bookrewind->getClassbooks([
			                'where' => ['class' => $targetUser['class'], 'classbook.status' => 1],
			                'fields' => ['category.*' => ''],
			                'join' => 1
		                ]);
		                $categories = [];
                        while($item = $book->next()) {
														if ($item['status'] == INVALIDATO || $item['status'] == RESTITUITO) {
															continue;
														}
                        		$categories[] = $item['ID'];
                            $status = $bookrewind->STATUS_INFO[$item['status']];
                            echo "
                                <li>
                                    <div class='collapsible-header waves-effect'>
                                        <div style='display: flex'>
                                            <i class='material-icons inline'>filter_drama</i><b>{$item['title']}</b>
                                        </div>
                                        <div class='valign-wrapper'>
                                            <span class='black-text' style='padding:0 1rem;'>{$item['price']}&euro;</span>
                                            <button "; if($item['status'] == PRENOTATO) echo "data-category='{$item['ID']}' value='book-free'"; echo "class='book-action btn-icon highlight-on-hover transparent secondary-content' >
                                                <i class='material-icons {$status[2]} inline' title=''>{$status[3][1]}</i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class='collapsible-body'>
                                        <i class='material-icons inline teal-text'>title</i> {$item['subtitle']}<br/>
                                        <i class='material-icons inline teal-text'>person</i> {$item['author']}
                                        <i class='material-icons inline teal-text'>home</i> {$item['publisher']}
                                        <i class='material-icons inline teal-text'>info</i> {$item['ISBN']}
							        </div>
                                </li>";
                        }
		                while($item = $classbooks->next()) {
                        	if(!in_array($item['ID'], $categories)){
		                        $books = $bookrewind->getBooksEx(User::$t_users, [
			                        'where' => [['status', EQUAL, DISPONIBILE], ['ID_adozione', EQUAL, $item['ID']]],
			                        'fields' => ['ID' => '']
		                        ]);
		                        $status = $bookrewind->STATUS_INFO[1];
		                        if($books->size()){
			                        $status = $bookrewind->STATUS_INFO[0];
		                        }
		                        echo "
                                <li>
                                    <div class='collapsible-header waves-effect'>
                                        <div style='display: flex'>
                                            <i class='material-icons inline'>filter_drama</i><b>{$item['title']}</b>
																				</div>
																				<div class='valign-wrapper'>";
														if ($books->size()) {
															echo "<span class='black-text' style='padding:0 1rem;'>(#copie: ".$books->size()." )</span>";
														}
                            echo "<span class='black-text' style='padding:0 1rem;'>{$item['price']}&euro;</span>";
		                        if($books->size()){
																echo "<button data-category='{$item['ID']}' class='book-action btn-icon highlight-on-hover transparent secondary-content' value='book-reserve'>
                                                <i class='material-icons {$status[2]} inline'>{$status[3][0]}</i>
                                            </button>";
		                        }
		                        else {
                                    echo "<button class='btn-icon highlight-on-hover transparent secondary-content'>
                                                <i class='material-icons red-text inline'>{$status[3][0]}</i>
                                            </button>";
                                }
                                echo"   </div>
                                    </div>
                                    <div class='collapsible-body'>
                                        <i class='material-icons inline teal-text'>title</i> {$item['subtitle']}<br/>
                                        <i class='material-icons inline teal-text'>person</i> {$item['author']}
                                        <i class='material-icons inline teal-text'>home</i> {$item['publisher']}
                                        <i class='material-icons inline teal-text'>info</i> {$item['ISBN']}
                                        <hr class='grey lighten-2' style='margin: 10px auto 15px auto;'>
                                    <div>";
		                        $max = 10;
		                        $i = 0;
		                        $book = $books->next();
		                        while($book && $i < $max){
			                        echo "<div class='chip'>{$book['ID']}</div>";
			                        $book = $books->next();
			                        $i++;
		                        }
		                        if(!$books->size()){
			                        echo "<div>Non disponibile</div>";
		                        }
		                        echo "      </div>
							         </div>
                                </li>";
	                        }
		                }
		                if (!$classbooks->size() && !$book->size()) {
			                echo "<li class=\"collection-item\">
								<div class=\"primary-content\">
									<div style=\"padding:5px 0;\"><b>Nessun Libro Trovato</b></div>
								</div></li>";
		                }
		                ?>
	                </ul>
                </div>
                <!-- restituiti -->
                <div id="restituiti" class="tab col s12">
	                <ul id="a" class="collection capitalize z-depth-2 collapsible popout" data-collapsible="expandable">
		                <li class="collection-item">
			                <div class="primary-content valign-wrapper flex-line">
				                <?php
                                foreach($bookrewind->STATUS_INFO as $s){
				                    if($s[3][2]){
                                        echo "
				                    <div style='margin-right: 2rem;; margin-top: 6px;'>
				                        <i class='inline material-icons {$s[2]}'>{$s[3][2]}</i> {$s[1]}
				                    </div>";
                                    }
                                }
                                ?>
			                </div>
		                </li>
		                <?php
		                $returned = $bookrewind->getBooksEx(User::$t_users, [
			                'where' => [['owner', EQUAL, $targetUser['ID']], ['status', EQUAL, RITIRATO]],
			                'fields' => ['category.*' => '', 'book.status' => 'status', 'book.ID' => 'bookID'],
			                'join' => 4
		                ]);
		                while($item = $returned->next()){
                            echo "
                                <li>
                                    <div class='collapsible-header waves-effect'>
                                        <div style='display: flex'>
                                            <i class='material-icons inline'>filter_drama</i><b>#{$item['bookID']} - {$item['title']}</b>
                                        </div>
                                    </div>
                                    <div class='collapsible-body'>
                                        <i class='material-icons inline teal-text'>title</i> {$item['subtitle']}<br>
                                        <i class='material-icons inline teal-text'>person</i> {$item['author']}
                                        <i class='material-icons inline teal-text'>home</i> {$item['publisher']}
                                        <i class='material-icons inline teal-text'>info</i> {$item['ISBN']}
							         </div>
                                </li>";
		                }
		                if (!$returned->size()) {
			                echo "<li class='collection-item'>
								<div class='primary-content'>
									<div style='padding:5px 0;'><b>Nessun Libro Trovato</b></div>
								</div>
							</li>";
		                }
		                ?>
	                </ul>
                </div>
            </div>
            <script>
	            $(document).ready(function(){
		            $('.book-action').on('click', function(e) {
			            var btn = $(this);
			            $.ajax({
                            url: 'api/book.php',
				            method: "POST",
				            data: {'action': btn.val(), 'book-free_reserve-category-ID': btn.data('category')}
			            }).done(function (r) {
			                if(r.r){
                                var data = r.data;
                                if(data.action === "book-free") btn.val("book-reserve");
                                else if(data.action === "book-reserve") btn.val("book-free");
                                btn.html(data.icon);
                            }
				            printMsg(r.msgs);
			            });
			            e.stopPropagation();
		            });
	            });
                $(document).ready(function(){
                    $('.book-return').on('click', function(e) {
                        var btn = $(this);
                        $.ajax({
                            url: 'api/book.php',
                            method: "POST",
                            cache: false,
                            data: {'book-ID': btn.val(), 'book-return':true}
                        }).done(function (r) {
                            console.log(r);
                            if(r.r){
                                $('#restituiti').children('ul').append(btn.closest('li'));
                            }
                            printMsg(r.msgs);
                        });
                        e.stopPropagation();
                    });
                });
            </script>
        </div>
	</div>
</main>
<div id='modal-change-psw' class='container small modal'>
    <div class="card white">
        <form id="form-change-psw" action="api/user.php" method="post" autocomplete="off" data-preloader="change-psw-preloader">
            <div class="card-content">
                <span class="card-title blue-grey-text">Modifica La Tua Password</span>
                <div class='row no-margin'>
                    <div class='input-field col s12'>
                        <i class="material-icons prefix">lock</i>
                        <input id="change-psw-old" type="password" class="validate" name="change-psw-old" required>
                        <label for="change-psw-old">Vecchia Password</label>
                    </div>
                    <div class='input-field col s12 m6 l6'>
                        <input id="change-psw-new" type="password" class="validate" name="change-psw-new" required>
                        <label for="change-psw-new">Nuova Password</label>
                    </div>
                    <div class='input-field col s12 m6 l6'>
                        <input id="change-psw-repeat" type="password" class="validate" name="change-psw-repeat" required>
                        <label for="change-psw-repeat">Ripeti Nuova Password</label>
                    </div>
                </div>
            </div>
            <div class="card-action">
                <a><button type="submit" class='btn btn-large waves-effect waves-light pink' name='change-psw'>Invia<i class="material-icons right">edit</i></button></a>
                <a class="modal-close"><button class="inherit-style" type="reset">Annulla</button></a>
                <div id="change-psw-preloader" class="right" style="padding-top: 6px; display: none">
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
        <script>$('#form-change-psw').ajaxFormEx(function(data, form){form.trigger('reset'); $('#modal-change-psw').modal('close')})</script>
    </div>
</div>
<?
if($user->hasAccess(User::$permissions["ADMIN"], false) && isset($_SESSION['manage-user'])) {
    $proceeds = $bookrewind->getProceeds($targetUser['ID']);
 echo "
<!--<div id='modal-delete-user' class='container small modal'>
    <div class='card white'>
        <form id='form-delete-user' method='post' autocomplete='off'>
            <div class='card-content'>
                <span class='card-title blue-grey-text'>Eliminazione Utente</span>
                <div class='row' style='margin-bottom: 0;'>
                    <div class='col s12'>
                        L'utente verrà rimosso dal database, ma ogni suo libro/ricevuta verranno mantenuti. Procedere?<br>
                        <br>
                        (Il contributo di iscrizione andrà restituito interamente, la ricevuta invalidata. Funzione utile per errori di iscrizione/doppioni)
                    </div>
                </div>
            </div>
            <div class='card-action'>
                <a><button class='btn btn-large waves-effect waves-light pink' name='delete-user' type='submit'>Invia<i class='material-icons right'>delete_forever</i></button></a>
                <a class='modal-close'>Annulla</a>
            </div>
        </form>
    </div>
</div>-->
<div id='modal-unregister-user' class='container small modal'>
    <div class='card white'>
        <form id='form-unregister-user' action='api/user.php' method='post' autocomplete='off'>
            <div class='card-content'>
                <span class='card-title blue-grey-text'>Annulla Registrazione</span>
                <div class='row' style='margin-bottom: 0;'>
                    <div class='col s12'>
                        L'utente verrà bloccato, ma mantenuto nel database
                    </div>
                </div>
            </div>
            <div class='card-action'>
                <a><button class='btn btn-large waves-effect waves-light pink modal-close' name='user-unregister' type='submit'>Invia<i class='material-icons right'>delete</i></button></a>
                <a class='modal-close'>Annulla</a>
            </div>
        </form>
        <script>$('#form-unregister-user').ajaxFormEx()</script>
    </div>
</div>
<div id='modal-money' class='container small modal'>
    <div class='card white'>
        <form id='form-pay-proceeds' action='api/user.php' method='post' autocomplete='off'>
            <div class='card-content'>
                <span class='card-title blue-grey-text'>Consegna Conto Vendita</span>
                <div class='row' style='margin-bottom: 0;'>
                    <div class='col s12'>
                        All'utente {$targetUser['mail']} verranno restituiti i {$proceeds[ACQUISTATO]}&euro; guadagnati, procedere?
                    </div>
                </div>
            </div>
            <div class='card-action'>
                <a><button class='btn btn-large waves-effect waves-light pink modal-close' type='submit' name='user-pay-proceeds'>Invia<i class='material-icons right'>attach_money</i></button></a>
                <a class='modal-close'>Annulla</a>
            </div>
        </form>
        <script>$('#form-pay-proceeds').ajaxFormEx(function()".'{$("#modal-invoice-proceeds").modal("open")})'."</script>
    </div>
</div>
<div id='modal-buy-books' class='modal bottom-sheet'>
    <form method='post'>
        <div class='modal-content row'>
            <h4>Elenco Prenotazioni</h4>
            <ul class='collection border collection-flex' id='modal-buy-books-content' style='overflow: visible;'></ul>
        </div>
        <div class='modal-footer'>
            <a class='modal-close waves-effect btn-flat orange-text' style='margin-left: 8px;'>Annulla</a>
            <button class='modal-close waves-effect waves-light btn btn-large pink' name='buy-books' type='submit'>Acquista<i class='material-icons right'>shopping_cart</i></button>
        </div>
    </form>
</div>
<div id='modal-invoice-purchase' class='container small modal'>
    <div class='card white'>
        <div class='card-content'>
            <span class='card-title blue-grey-text'><i class='material-icons small inline'>print</i> Ricevuta di Acquisto</span>
            <div class='row'>
                <p class='flow-text'><small>Utente <b>{$targetUser['mail']}</b></small></p>
            </div>
        </div>
        <div class='card-action'>
            <a target='_blank' onclick='setTimeout(() => { window.location = window.location.href; }, 2000)' href='invoice/invoice-purchase.php' class='waves-effect waves-light btn btn-large pink'>Stampa</a>
        </div>
    </div>
</div>
<div id='modal-invoice-proceeds' class='container small modal'>
    <div class='card white'>
        <div class='card-content'>
            <span class='card-title blue-grey-text'><i class='material-icons small inline'>print</i> Ricevuta di Ritiro Ricavo</span>
            <div class='row'>
                <p class='flow-text'><small>Utente <b>{$targetUser['mail']}</b></small></p>
            </div>
        </div>
        <div class='card-action'>
            <a target='_blank' onclick='setTimeout(() => { window.location = window.location.href; }, 2000)' href='invoice/invoice-proceeds.php' class='waves-effect waves-light btn btn-large pink'>Stampa</a>
        </div>
    </div>
</div>
<div id='modal-return-books' class='container small modal'>
    <div class='card white'>
        <form method='post' id='from-return-books' action='api/user.php'>
            <div class='card-content'>
                <span class='card-title blue-grey-text'><i class='material-icons small inline'>replay</i> Restituzione libri</span>
                <div class='row'>
                    <p class='flow-text'><small>Utente <b>{$targetUser['mail']}</b></small></p>
                </div>
            </div>
            <div class='card-action'>
                <button type='submit' name='user-return-books' value='true' class='modal-close waves-effect waves-light btn btn-large pink'>Restituisci</button>
            </div>
        </form>
        <script>$('#from-return-books').ajaxFormEx(function(){window.location = window.location.href;})</script>
    </div>
</div>
<div id='modal-return-selected-books' class='container modal'>
    <div class='card white'>
        <form method='post' id='form-return-selected-books' action='api/user.php'>
            <div class='card-content'>
                <span class='card-title blue-grey-text'><i class='material-icons small inline'>replay</i> Restituzione libri</span>
                <div class='row'>
                    <p class='flow-text'><small>Utente <b>{$targetUser['mail']}</b></small></p>
								</div>
								<div>";
								$toSell->reset();
								while($item = $toSell->next()) {
									if ($item['status'] == DISPONIBILE || $item['status'] == PRENOTATO) echo "
									<p>
										<input id='return-book-".$item['bookID']."' name='return-books[]' value='".$item['bookID']."' type='checkbox' />
										<label for='return-book-".$item['bookID']."'>".$item['title'];
									if ($item['status'] == PRENOTATO) echo " <b>(prenotato)</b>";
						echo "</label>
									</p>
									";
								}
echo						"</div>
            </div>
            <div class='card-action'>
                <button type='submit' name='user-return-selected-books' value='true' class='modal-close waves-effect waves-light btn btn-large pink'>Restituisci</button>
            </div>
        </form>
        <script>$('#form-return-selected-books').ajaxFormEx(function(){window.location = window.location.href;})</script>
    </div>
</div>
<div class='fixed-action-btn toolbar'>
    <a class='btn-floating btn-large teal darken-4'>
    </a>
    <ul>
        <li class='waves-effect waves-light modal-trigger tooltipped' href='#modal-buy-books' data-position='top' data-delay='20' data-tooltip='Acquista libri'>
            <a><i class='material-icons'>shopping_cart</i></a>
        </li>
        <li class='waves-effect waves-light modal-trigger tooltipped' href='#modal-money' data-position='top' data-delay='20' data-tooltip='Consegna ricavo'>
            <a><i class='material-icons'>euro_symbol</i></a>
        </li>
        <!--<li class='waves-effect waves-light modal-trigger tooltipped' href='#modal-delete-user' data-position='top' data-delay='20' data-tooltip='Elimina utente'>
            <a><i class='material-icons'>delete</i></a>
        </li>-->
        <li class='waves-effect waves-light modal-trigger tooltipped' href='#modal-unregister-user' data-position='top' data-delay='20' data-tooltip='Annulla iscrizione'>
            <a><i class='material-icons'>history</i></a>
        </li>
        <li class='waves-effect waves-light modal-trigger tooltipped' href='#modal-return-selected-books' data-position='top' data-delay='20' data-tooltip='Restituisci libri'>
            <a><i class='material-icons'>book</i><i class='material-icons'>replay</i></a>
        </li>
        <li>
            <a></a>
        </li>
    </ul>
</div>";
}
?>
<?php
if($user->hasAccess(User::$permissions["ADMIN"], false)) {
    echo '<div class="fixed-action-btn">';
    if (isset($_SESSION['manage-user'])) {
        echo "<a class='btn-floating btn-large pink waves-effect waves-light' onclick=\"$('.fixed-action-btn.toolbar').openToolbar();\"><i class='large material-icons'>add</i ></a>";
    } else {
        echo "<a class='btn-floating btn-large pink'><i class='large material-icons'>link</i ></a>";
    }
    echo '<ul><li>';
    if (isset($_SESSION['manage-user'])) {
        echo "<a class='btn-floating blue waves-effect waves-light tooltipped' href='manage-users.php?clear-manage-user' data-position='left' data-delay='20' data-tooltip='Termina gestione'><i class='material-icons'>close</i></a>";
    } else {
        echo "<a class='btn-floating blue waves-effect waves-light tooltipped' href='manage-users.php' data-position='left' data-delay='20' data-tooltip='Gestione utenti'><i class='material-icons'>supervisor_account</i></a>";
    }
    echo "</li></ul></div>";
}
?>
<script>
    $(document).ready(function(){
        $('#modal-buy-books').modal({
	        ready: function() {
	        	$.ajax({
			        method: "POST",
			        data: {ajax:true, "getBookedBooks": true}
		        }).done(function(r){
		        	var s = "";
			        for (var i = 0, len = r.data.length; i < len; i++){
			        	var book = r.data[i];
			        	s += "<li class='collection-item capitalize' style='width:100%;'>" +
					        "<div style='float: left;'>" +
					            "<span class='c-title'>"+book.title+"</span><br><span class='hide-on-small-only'>"+book.subtitle+"</span>" +
					        "</div>" +
					        "<div class='secondary-content valign-wrapper'>" +
						        "<div class='col s4' style='font-size: 120%;'><i class='material-icons inline'>attach_money</i>"+book.price+"</div>" +
						        "<div class='col s8'>" +
						            "<select class='new-select' name='booked-books[]'><option value=''>...</option>";
			        	for(var j = 0, lenj = book.books.length; j < lenj; j++){
			        		s += "<option>"+book.books[j].ID+"</option>";
				        }
				        s +=        "</select>" +
					            "</div>" +
					        "</div></li>";
			        }
			        if(!r.data.length){
			        	s = "<li class='collection-item capitalize' style='width:100%;'>Nesssun libro prenotato</li>";
			        }
			        $('#modal-buy-books-content').html(s).find('select').material_select();
		        });
	        }
        });
        <?php
        echo "mail='".$user->get("mail")."';";
        ?>
        if(!getCookie('first-access-' + mail)){
            $('#modal-first-access').modal('open');
        }
        $('#first-access-ok').on('click', function(){setCookie('first-access-' + mail, 'true', 304);});
        $('.tabs').tabs('select_tab', 'da-acquistare');
    });
</script>
<?php
echo getFooter("green");
if($_SESSION['invoice-purchase']) {
    echo '<script>$(document).ready(function(){$(\'#modal-invoice-purchase\').modal({dismissible: false}).modal(\'open\');})</script>';
}
if($_SESSION['invoice-proceeds']) {
	echo '<script>$(document).ready(function(){$(\'#modal-invoice-proceeds\').modal({dismissible: false}).modal(\'open\');})</script>';
}
?>
</body>
</html>