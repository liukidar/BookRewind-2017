<?php

include_once "lib/bookrewind.php";
require_once "lib/user.php";
require_once "lib/template.php";
require_once "lib/transaction.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$libri = new Bookrewind($sql);
$transaction = new Transaction($sql);

if(isset($_GET['get-unsold-books'])){
    $books = $libri->getBooks(User::$t_users, [
    		'where' => ["status" => [0, 1, 5]],
		    'fields' => ["book.ID" => "ID", "book.status" => "status", "buyer.mail" => "buyer_mail", "owner.mail" => "owner_mail"],
	        'join' => 1 | 2
        ]);

    $output = '"ID";"PROPRIETARIO";"ACQUIRENTE";"STATO"'."\r\n";

    while($book = $books->next()){
        $output.= "\"{$book['ID']}\"".";"."\"{$book['owner_mail']}\"".";"."\"{$book['buyer_mail']}\"".";"."\"{$libri->STATUS_INFO[$book['status']][1]}\""."\r\n";
    }

    header("Content-type: application/octet-stream");
    header('Content-Disposition: attachment; filename="libri_invenduti.csv"');
    header('Content-Type: text/plain');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Connection: close');
    echo $output;

    exit(0);
}
elseif(isset($_GET['get-creditors'])){
	$creditors = $libri->getBooks(User::$t_users, [
		'where' => ["status" => ACQUISTATO],
		'fields' => ["SUM(category.price)" => "total", "owner.mail" => "mail"],
		'join' => 1 | 4,
		'group' => ['owner.ID']
	]);

	$output = '"MAIL";"TOTALE"'."\r\n";
	while ($c = $creditors->next()) {
		$output.= "\"{$c['mail']}\"".";"."\"{$c['total']}\""."\r\n";
	}

	header("Content-type: application/octet-stream");
	header('Content-Disposition: attachment; filename="utenti_creditori.csv"');
	header('Content-Type: text/plain');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Connection: close');
	echo $output;

	exit(0);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php echo getHead("Admin"); ?>
</head>
<body class="green">
<?php echo getNavbar($user->get('mail'), ["title" => "Gestione Ricevute", "color" => "green"]); ?>
<main style="padding-top:40px;">
    <div class="material-search-bar" data-target="searchbar.php" data-data="search-query"></div>
    <div class="row" style="margin-top: 40px;">
        <div class="card white">
            <div class="container">
                <form id="form-edit-invoice" action="api/invoice.php" method="post" autocomplete="off">
                    <div class="card-content">
                        <span class="card-title blue-grey-text">Ricevute</span>
                        <blockquote>
                            Ricordo che ad ogni ricevuta corrisponde una precisa transazione (legata tramite ID), queste però non sono collegate fra loro nel database, pertanto è necessario agire su entrambe.<br>
                            Ad esempio nel caso di una ricevuta errata sarà
                            necessario eliminare sia la ricevuta che la corrispettiva transazione.<br>
                            In caso di modifica bisognerà agire esclusivamente sulla transazione, essendo le ricevute non modificabili (eventualmente si opererà sul foglio stampato).<br><br>
                            Pertanto ci si dovrà riferire <b>unicamente alle transazioni</b> per monitorare le entrate e le uscite.
                        </blockquote>
                        <div class='row no-margin'>
                            <div class='input-field col s12'>
                                <i class="material-icons prefix">find_in_page</i>
                                <input type="text" name="invoice-filename" id="invoices-list" class="autocomplete" required>
                                <label for="invoices-list">Descrizione</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn-large waves-effect waves-light pink' name="action" value='invoice-download' type='submit'>Scarica<i class="material-icons right">file_download</i></button></a>
                        <a><button class='btn-large waves-effect waves-light pink' name="action" value='invoice-remove' type='submit'>Elimina<i class="material-icons right">delete_forever</i></button></a>
                        <a class="modal-close"><button class="inherit-style" type="reset">Annulla</button></a>
                    </div>
                </form>
                <script>$('#form-edit-invoice').ajaxFormEx(function(d){if(d.action === "invoice-download") download(d.filename, d.file, true);})</script>
                <form id="form-edit-transaction" action="api/transaction.php" method="post" autocomplete="off">
                    <div class="card-content">
                        <span class="card-title blue-grey-text">Transazioni</span>
                        <div class='row no-margin'>
                            <div class='input-field col s4 m2'>
                                <i class="material-icons prefix">info_outline</i>
                                <input type="number" name="transaction-remove-ID">
                                <label for="">ID</label>
                            </div>
                            <div class='input-field col s8 m4'>
                                <i class="material-icons prefix">find_in_page</i>
                                <select id="transaction-add-type" name="transaction-add-type">
                                    <option class="disabled" disabled selected>Categoria</option>
                                    <?php
                                    foreach($transaction->TYPE as $ty){
                                        echo "<option value='$ty'>{$transaction->INFO[$ty]}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class='input-field col s8 m4'>
                                <i class="material-icons prefix">receipt</i>
                                <input type="text" name="transaction-add-info">
                                <label for="">Info</label>
                            </div>
                            <div class='input-field col s4 m2'>
                                <i class="material-icons prefix">euro_symbol</i>
                                <input type="number" name="transaction-add-price">
                                <label for="">Euro</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn-large waves-effect waves-light pink' name="action" value='transaction-remove' type='submit'>Elimina<i class="material-icons right">delete</i></button></a>
                        <a><button class='btn-large waves-effect waves-light pink' name="action" value='transaction-add' type='submit'>Aggiungi<i class="material-icons right">add</i></button></a>
                        <a class="modal-close"><button class="inherit-style" type="reset">Annulla</button></a>
                    </div>
                </form>
                <script>$('#form-edit-transaction').ajaxFormEx(function(d, o){o.trigger('reset')})</script>
            </div>
        </div>
    </div>
    <div class="row" style="margin-top: 40px;">
        <div class="card white">
            <div class="container">
                <form id="form-transaction-generate-csv" method="post" autocomplete="off" action="api/transaction.php" target="_blank" onsubmit="return $('#transaction-generate-csv-type').val() !== null">
                    <div class="card-content">
                        <span class="card-title blue-grey-text">Resoconti</span>
                        <blockquote>
                            L'opzione <b>Custom</b> permette di selezionare esclusivamente le transazioni inserite manualmente.<br>
                            Lasciando i campi data vuoti si selezionerà l'intero anno corrente.
                        </blockquote>
                        <div class='row no-margin'>
                            <div class='input-field col s12 m3'>
                                <i class="material-icons prefix">find_in_page</i>
                                <select id="transaction-generate-csv-type" multiple name="transaction-generate-csv-type[]">
                                    <option class="disabled" disabled>Categorie</option>
                                    <?php
                                    foreach($transaction->TYPE as $ty){
                                        echo "<option value='$ty'>{$transaction->INFO[$ty]}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class='input-field col s6 m3'>
                                <i class="material-icons prefix">date_range</i>
                                <input id="transaction-generate-csv-from" type="text" class="datepicker" name="transaction-generate-csv-from">
                                <label for="transaction-generate-csv-from">From</label>
                            </div>
                            <script>
                                $('#transaction-generate-csv-from').change(function(){o = $('#transaction-generate-csv-to'); if(o.val().length === 0) o.val($(this).val()).siblings('label').addClass('active')});
                            </script>
                            <div class='input-field col s6 m3'>
                                <i class="material-icons prefix">date_range</i>
                                <input id="transaction-generate-csv-to" type="text" class="datepicker" name="transaction-generate-csv-to">
                                <label for="transaction-generate-csv-to">To</label>
                            </div>
                            <div class="col s6 m3">
                                <div class="switch" style="margin-top: 1.5rem;">
                                    <label>
                                        <input type="checkbox" name="transaction-generate-csv-custom">
                                        <span class="lever"></span>
                                        Custom
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn-large waves-effect waves-light pink' name="action" value='transaction-generate-csv' type='submit'>Genera CSV<i class="material-icons right">file_download</i></button></a>
                        <a><button class="inherit-style" type="reset">Annulla</button></a>
                    </div>
                </form>
                <script>$('#form-transaction-generate-csv').ajaxFormEx(function(data) {download("report_" + currentDate() + ".csv", data.file)})</script>
	            <form id="generate-book-csv" action="api/book.php" method="post" autocomplete="off" onsubmit="return $('#generate-book-csv-type').val() !== null">
		            <div class="card-content">
			            <div class='row no-margin'>
				            <div class='input-field col s12 m3'>
					            <i class="material-icons prefix">find_in_page</i>
					            <select id="generate-book-csv-type" name="book-get-by-date-type">
						            <option class="disabled" disabled selected>Libri</option>
						            <option value="in">In Entrata</option>
						            <option value="out">In Uscita</option>
					            </select>
				            </div>
				            <div class='input-field col s6 m3'>
					            <i class="material-icons prefix">date_range</i>
					            <input id="book-get-by-date-from" type="text" class="datepicker" name="book-get-by-date-from">
					            <label for="book-get-by-date-from">From</label>
				            </div>
				            <script>
					            $('#book-get-by-date-from').change(function(){o = $('#book-get-by-date-to'); if(o.val().length === 0) o.val($(this).val()).siblings('label').addClass('active')});
				            </script>
				            <div class='input-field col s6 m3'>
					            <i class="material-icons prefix">date_range</i>
					            <input id="book-get-by-date-to" type="text" class="datepicker" name="book-get-by-date-to">
					            <label for="book-get-by-date-to">To</label>
				            </div>
			            </div>
		            </div>
		            <div class="card-action">
			            <a><button class='btn-large waves-effect waves-light pink' name='book-get-by-date' type='submit'>Genera CSV<i class="material-icons right">file_download</i></button></a>
			            <a><button class="inherit-style" type="reset">Annulla</button></a>
		            </div>
	            </form>
                <script>$('#generate-book-csv').ajaxFormEx(function(data) {download("libri_" + data.type + "_" + currentDate() + ".csv", JSONtoCSV(data.books))})</script>
            </div>
        </div>
    </div>
    <div class="row" style="margin-top: 40px;">
        <div class="card white">
            <div class="container">

            </div>
        </div>
    </div>
</main>
<div class="fixed-action-btn toolbar">
    <a class="btn-floating btn-large teal darken-4">
    </a>
    <ul>
        <li class="waves-effect waves-light tooltipped" data-position="top" data-delay="20" data-tooltip="Libri Invenduti">
            <a target="_blank" href="manage-invoices.php?get-unsold-books=true"><i class="material-icons">close</i><i class="material-icons">book</i></a>
        </li>
        <li class="waves-effect waves-light tooltipped" data-position="top" data-delay="20" data-tooltip="Utenti Creditori">
            <a target="_blank" href="manage-invoices.php?get-creditors=true"><i class="material-icons">euro_symbol</i><i class="material-icons">person</i></a>
        </li>
        <!--<li class="waves-effect waves-light modal-trigger tooltipped" href="#modal-delete-user" data-position="top" data-delay="20" data-tooltip="Totale Transazioni">
            <a><i class="material-icons">euro_symbol</i><i class="material-icons">euro_symbol</i></a>
        </li>-->
        <li>
            <a></a>
        </li>
    </ul>
</div>
<div class="fixed-action-btn">
    <a class="btn-floating btn-large pink waves-effect waves-light" onclick="$('.fixed-action-btn.toolbar').openToolbar();">
        <i class="large material-icons">add</i>
    </a>
    <ul>
        <li>
            <a class="btn-floating blue waves-effect waves-light tooltipped" href="manage-books.php" data-position="left" data-delay="20" data-tooltip="Gestione Libri"><i class="material-icons">book</i></a>
        </li>
        <li>
            <a class="btn-floating blue waves-effect waves-light tooltipped" href="manage-users.php" data-position="left" data-delay="20" data-tooltip="Gestione Utenti"><i class="material-icons">supervisor_account</i></a>
        </li>
    </ul>
</div>
<script>
    $(document).ready(function() {
        $('#invoices-list').autocomplete({
		    data: {
			    <?php
			    $files = scandir("invoice/files");
			    foreach($files as $item){
				    $item = htmlspecialchars($item, ENT_QUOTES);
				    if(strpos($item, '.pdf')){
					    echo "'$item' : null,";
				    }
			    }
			    ?>
		    },
		    limit: 10,
		    minLength: 3
	    });
    });
    $('.datepicker').pickadate({
        selectMonths: false,
        selectYears: false,
        today: 'Today',
        clear: '',
        close: '',
        closeOnSelect: true,
        format: 'yyyy-mm-dd'
    });
</script>
<?php echo getFooter("green"); ?>
</body>
</html>