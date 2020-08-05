<?php

require_once "lib/bookrewind.php";
require_once "lib/user.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$bookrewind = new Bookrewind($sql);
$isAdmin = $user->hasAccess(User::$permissions['ADMIN']);

$STATUS_KEYWORDS = [
    "misc" => ["libri", "libro", "da"],
    0 => ["d","disponibili","disponibile","liberi","libero"],
    1 => ["p","prenotati","prenotato","prenotazioni"],
    2 => ["a","acquistati","acquistato","acquisto","acquisti", "v", "venduti", "venduto", "vendite", "vendita", "pagare"],
    3 => ["€","pagati","pagato"],
    4 => ["r", "restituiti", "restituito", "ritirati", "ritirato"],
    5 => ["i", "invalidati", "restituire", "invalidato", "ritirare", "invalidi", "invalido"]
];

function requestStatus($query, $keywords){
    $query = explode(' ', strtolower($query));
    if(count($query) > 3){
        return -1;
    }

    $r = [];
    foreach($query as $q){
        $found = false;
        foreach ($keywords as $key => $val){
            foreach($val as $s){
                if($s == $q){
                    if(is_numeric($key)) $r[] = $key;
                    $found = true;
                }
            }
        }
        if($found == false){
            return [];
        }
    }

    return $r;
}
if(isset($_POST['search-query']) && strlen($_POST['search-query'])){
    $query = $_POST['search-query'];
    if(is_numeric($query)){
        if(strlen($query) == 13) {
            $category = $bookrewind->getCategory(['where' => ['ISBN' => $query]])->next();
            if ($category) {
                $books = $bookrewind->getBooks(User::$t_users, [
                    'where' => ["ID_adozione" => $category['ID']],
                    'fields' => ["book.ID" => "ID", "book.status" => "status", "buyer.mail" => "buyer_mail", "owner.mail" => "owner_mail"],
                    'join' => 1 | 2
                ]);
	            $free = false;
	            $status = $bookrewind->STATUS_INFO[1];
	            while($book = $books->next()) {
	            	if($book['status'] == DISPONIBILE) {
			            $free = true;
			            break;
		            }
	            }
	            $books->reset();
	            if($free){
	            	$status = $bookrewind->STATUS_INFO[0];
	            }
                echo "<li class='collection-item'>
                    <div>
                        <div class='valign-wrapper'>
                            <div style='margin-right:1rem;'><a class='btn btn-small blue' onclick='gotoCategory(\"{$category['ISBN']}\")'><i class='material-icons'>search</i></a></div>
                            <div><b>{$category['title']}</b></div>
                        </div>
                        <div class='hide-on-small-only' style='margin-top: 1rem;'>
                            <i class='material-icons inline blue-text'>title</i> {$category['subtitle']}<br>
                            <i class='material-icons inline blue-text'>person</i> {$category['author']}
                            <i class='material-icons inline blue-text'>home</i> {$category['publisher']}
                            <i class='material-icons inline blue-text'>info</i> {$category['ISBN']}
                         </div>
                    </div>
                    <div class='valign-wrapper' style='margin-right:1rem;'>";
	            if($isAdmin && $free && $_SESSION['manage-user']){
		            echo "<a data-category='{$category['ID']}' class='btn-search-bar-reserve btn-icon highlight-on-hover transparent'>
                            <i class='material-icons {$status[2]} small'>{$status[3][0]}</i>
                        </a>";
	            }
	            else {
		            echo "<a class='btn-icon highlight-on-hover transparent'>
                            <i class='material-icons {$status[2]} small'>{$status[3][0]}</i>
                        </a>";
	            }
                echo"<i class='material-icons small blue-text' style='padding: 0 0.5rem;'>euro_symbol</i><b>{$category['price']}</b>
					</div>
                </li>";
                if($isAdmin){
	                if ($books->size()) {
		                echo "<li class='collection-item'>
                        <table class='highlight'>
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Proprietario</th>
                                <th>Stato</th>
                                <th>Acquirente</th>
                            </tr>
                            </thead>
                            <tbody>";
		                while ($b = $books->next()) {
			                echo "<tr>
                            <td class='look-for'>{$b['ID']}</td>
                            <td class='look-for'>{$b['owner_mail']}</td>
                            <td class='look-for'>{$bookrewind->STATUS_INFO[$b['status']][1]}</td>
                            <td class='look-for'>{$b['buyer_mail']}</td>
                          </tr>";
		                }
		                echo "  </tbody>
                        </table>
                    </li>";
	                }
	                if (!$books->size()) {
		                echo "<li class='collection-item'><div><b>Nessuna Corrispondenza</b></div></li>";
	                }
                }
            } else {
                echo "<li class='collection-item'>
                    <div><b>Nessuna Corrispondenza</b></div>
                </li>";
            }
        }
        elseif($isAdmin) { //ADMIN
            $book = $bookrewind->getBooks(User::$t_users, [
                'where' => ["book.ID" => $query],
                'fields' => ["book.ID" => "ID", "book.status" => "status",
                    "buyer.mail" => "buyer_mail", "owner.mail" => "owner_mail",
                    'category.title' => 'title', 'category.ISBN' => 'ISBN', 'category.price' => 'price',
                    'book.modify_date' => 'modify_date'],
                'join' => 1 | 2 | 4
            ]);
            $book = $book->next();
            if($book){
                echo "
                 <li class='collection-item'>
                    <div>
                        <div>
                            <i class='material-icons inline blue-text' style='margin-right:1rem;'>filter_drama</i><b>$query</b>
                            <i class='material-icons inline blue-text' style='margin-right:1rem;'>title</i><b>{$book['title']}</b>
                            <i class='material-icons inline blue-text'>info</i> <b class='look-for'>{$book['ISBN']}</b>
                        </div>
                    </div>
                    <div class='valign-wrapper hide-on-small-only' style='margin-right:1rem;'>
                        <i class='material-icons small blue-text' style='margin-right:0.5rem;'>euro_symbol</i><b>{$book['price']}</b>
                    </div>
                </li>
                <li class='collection-item'>
                    <table class='highlight'>
                        <thead>
                        <tr>
                            <th>Proprietario</th>
                            <th>Status</th>
                            <th>Acquirente</th>
                            <th class='hide-on-small-only'>Data Modifica</th>
                        </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><i class='material-icons inline blue-text'>person</i> <span class='look-for'>{$book['owner_mail']}</span></td>
                            <td><i class='material-icons inline blue-text'>book</i> <span class='look-for'>{$bookrewind->STATUS_INFO[$book['status']][1]}</span></td>
                            <td><i class='material-icons inline blue-text'>person_outline</i> <span class='look-for'>{$book['buyer_mail']}</span></td>
                            <td  class='hide-on-small-only'><i class='material-icons inline blue-text'>date_range</i> {$book['modify_date']}</td>
                          </tr>
                        </tbody>
                    </table>
                </li>
                <li class='collection-item'>
                    <div clasS='valign-wrapper' style='padding-top:0.5rem;'>";
                if(in_array($book['status'], [DISPONIBILE, PRENOTATO, INVALIDATO])){
                    echo "<form id='form-book' method='post' action='api/book.php'>
                        <input type='hidden' name='book-ID' value='{$book['ID']}'>";
                        if($book['status'] == INVALIDATO){
                            echo "<button class='btn btn-large pink waves-effect waves-light' type='submit' name='book-validate'><i class='material-icons right'>add</i>Valida </button>";
                        }
                        else {
                            echo "<button class='btn btn-large pink waves-effect waves-light' type='submit' name='book-invalidate'><i class='material-icons right'>close</i>Invalida </button>";
                        }
                        echo"<button class='btn btn-large pink waves-effect waves-light' type='submit' name='book-delete' style='margin-left: 1.5rem;'><i class='material-icons right'>delete_forever</i>Elimina </button>
                    </form>
                    <script>$('#form-book').ajaxFormEx(function(data, obj){".'$'."('#'+'{$_POST['id']}').trigger('change')})</script>";
                }
                echo"</div>
                </li>";
            } else {
                echo "<li class='collection-item'>
                    <div><b>Nessuna Corrispondenza</b></div>
                </li>";
            }
        }
    }
    else if($isAdmin && strpos($query, '@')){ //ADMIN
        $r = $user->search($query);

        //Mail
        echo "
        <li class='collection-item'>
            <div>
                <i class='material-icons inline blue-text' style='margin-right:1rem;'>mail</i><b>Mail</b>
            </div>
        </li><li class='collection-item'>";
        if(count($r)){
            echo "
            <table class='highlight'>
                <thead>
                <tr>
                    <th style='width:1rem;'></th>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Mail</th>
                    <th>Classe</th>
                </tr>
                </thead>
                <tbody>";
        foreach($r as $u) {
	        echo "
                  <tr>
                    <td><a class='btn btn-small blue' onclick='gotoUser(\"{$u['mail']}\")'><i class='material-icons'>person</i></a></td>
                    <td>{$u['name']}</td>
                    <td>{$u['surname']}</td>
                    <td >{$u['mail']}</td>
                    <td class='look-for'>{$u['class']}</td>
                  </tr>";
        }
        echo "  </tbody>
            </table>";
        }
        else {
            echo "<div><b>Nessuna Corrispondenza</b></div>";
        }
        echo "</li>";
    }
    else if($isAdmin && count($status = requestStatus($query, $STATUS_KEYWORDS)) != 0){ //ADMIN
        //Status

        foreach ($status as $s) {
	        $books = $bookrewind->getBooks(User::$t_users, [
		        'where' => ['status' => $s],
		        'fields' => ['book.ID' => 'ID', 'category.ISBN' => 'ISBN', 'owner.mail' => 'owner_mail', 'buyer.mail' => 'buyer.mail', 'book.modify_date' => 'modify_date'],
		        'join' => 1 | 2 | 4
	        ]);

	        echo "
            <li class='collection-item'>
                <div>
                    <i class='material-icons inline blue-text' style='margin-right:1rem;'>book</i><b>Libri {$STATUS_KEYWORDS[$s][1]}</b>
                </div>
            </li>";
	        if ($books->size()) {
		        echo "
                <li class='collection-item'>
                    <table class='centered highlight'>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>ISBN</th>
                            <th>Proprietario</th>
                            <th>Acquirente</th>
                            <th class='hide-on-small-only'>Data Modifica</th>
                        </tr>
                        </thead>
                        <tbody>";
		        while ($b = $books->next()) {
			        echo "<tr>
                            <td class='look-for'>{$b['ID']}</td>
                            <td class='look-for'>{$b['ISBN']}</td>
                            <td class='look-for'>{$b['owner_mail']}</td>
                            <td class='look-for'>{$b['buyer_mail']}</td>
                            <td>{$b['modify_date']}</td>
                          </tr>";
		        }
		        echo "  </tbody>
                    </table>
                </li>
                ";
	        } else {
		        echo "<li class='collection-item'><div><b>Nessuna Corrispondenza</b></div></li>";
	        }
        }
    }
    else if(strlen($query) == 2){
        $categories = $bookrewind->getClassbooks([
            'where' => ['class' => $query, 'status' => 1],
            'fields' => ['category.*' => ''],
            'join' => 1
        ]);
        if ($categories->size()) {
            echo"
            <li class='collection-item'>
                <div>
                    <i class='material-icons inline blue-text' style='margin-right:1rem;'>book</i><b>Libri della ".strtoupper($query)."</b>
                </div>
            </li>
            <li class='collection-item'>
                <table class='highlight'>
                    <thead>
                    <tr>
												<th>ISBN</th>
                        <th>Titolo</th>
                        <th>Sottotitolo</th>
                        <th>Prezzo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>";
            while($c = $categories->next()){
                $books = $bookrewind->getBooksEx(User::$t_users, [
                    'where' => [['status', EQUAL, DISPONIBILE], ['ID_adozione', EQUAL, $c['ID']]],
                    'fields' => ['ID' => '']
                ]);
                $status = $bookrewind->STATUS_INFO[1];
                if($books->size()){
                    $status = $bookrewind->STATUS_INFO[0];
                }
                echo "
                    <tr>
												<td class='look-for'>{$c['ISBN']}</td>
                        <td>{$c['title']}</td>
                        <td>{$c['subtitle']}</td>
												<td>{$c['price']}&euro;</td>";
                if($isAdmin && $books->size() && $_SESSION['manage-user']){
                    echo "<td>
                        <a data-category='{$c['ID']}' class='btn-search-bar-reserve btn-icon highlight-on-hover transparent'>
                            <i class='material-icons {$status[2]} inline'>{$status[3][0]}</i>
												</a>";
										if ($books->size()) echo " (".$books->size().")";
                    echo "</td>";
                }
                else {
                    echo "<td>
                        <a class='btn-icon highlight-on-hover transparent'>
                            <i class='material-icons {$status[2]} inline'>{$status[3][0]}</i>
                        </a>
                    </td>";
										if ($books->size())	echo " (".$books->size().")";
                    echo "</td>";
                }
                echo"</tr>";
            }
            echo"   </tbody>
                </table>
            </li>";
        }
    }
    else { //ADMIN
        $r = false;
        $keywords = explode(' ', $query);

        $where = [];
        foreach ($keywords as $key){
            $key = preg_replace("/[^[:alnum:][:space:]]/u", '', $key);
            if(strlen($key) > 2){
                $where[] = "%$key%";
            }
        }
        if($isAdmin) {
            $usersBySurname = $user->getUsers([
                'where' => ['%surname' => $where]
            ]);

            //Users
            //where name %like% $keywords[0] or name %like% $keywords[n] or surname %like% $keywords[0]...
            echo "
            <li class='collection-item'>
                <div>
                    <i class='material-icons inline blue-text' style='margin-right:1rem;'>person</i><b>Utenti</b>
                </div>
            </li>";
            if($usersBySurname->size()){
                echo "<li class='collection-item'>
                <table class='highlight'>
                    <thead>
                    <tr>
												<th>Mail</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                    </tr>
                    </thead>
                    <tbody>";
                while($u = $usersBySurname->next()){
                    echo "<tr>
												<td class='look-for'>{$u['mail']}</td>
                        <td>{$u['name']}</td>
                        <td>{$u['surname']}</td>
                    </tr>";
                }
            echo "  </tbody>
                </table>
            </li>";
            }
            else {
                echo "<li class='collection-item'><div><b>Nessuna Corrispondenza</b></div></li>";
            }
        }

        //Books
        //only with char count > 4
        //where title %like% $keywords[0] or $keywords[n]

        $categories = $bookrewind->getCategory([
            'where' => ['%title' => $where]
        ]);

        echo "
        <li class='collection-item'>
            <div>
                <i class='material-icons inline blue-text' style='margin-right:1rem;'>book</i><b>Adozioni</b>
            </div>
        </li>";
        if($categories->size()){
            echo "<li class='collection-item'>
            <table class='highlight'>
                <thead>
                <tr>
										<th>ISBN</th>
                    <th>Titolo</th>
                    <th>Sottotitolo</th>
                    <th>Prezzo</th>
                </tr>
                </thead>
                <tbody>";
            while($c = $categories->next()) {
                $books = $bookrewind->getBooksEx(User::$t_users, [
                    'where' => [['status', EQUAL, DISPONIBILE], ['ID_adozione', EQUAL, $c['ID']]],
                    'fields' => ['ID' => '']
                ]);
                $status = $bookrewind->STATUS_INFO[1];
                if($books->size()){
                    $status = $bookrewind->STATUS_INFO[0];
                }
                echo "
                    <tr>
												<td class='look-for'>{$c['ISBN']}</td>
                        <td>{$c['title']}</td>
                        <td>{$c['subtitle']}</td>
                        <td>{$c['price']}&euro;</td>";
                if($isAdmin && $books->size() && $_SESSION['manage-user']){
                    echo "<td>
                        <a data-category='{$c['ID']}' class='btn-search-bar-reserve btn-icon highlight-on-hover transparent'>
                            <i class='material-icons {$status[2]} inline'>{$status[3][0]}</i>
                        </a>
                    </td>";
                }
                else {
                    echo "<td>
                        <a class='btn-icon highlight-on-hover transparent'>
                            <i class='material-icons {$status[2]} inline'>{$status[3][0]}</i>
                        </a>
                    </td>";
                }
                echo"</tr>";
            }
            echo "
                </tbody>
            </table>
        </li>";
        }
        else {
            echo "<li class='collection-item'><div><b>Nessuna Corrispondenza</b></div></li>";
        }
    }
    echo "<script>
        $('.btn-search-bar-reserve').on('click', function(e) {
            var btn = $(this);
            $.ajax({
                url: 'api/book.php',
                method: 'POST',
                data: {'action': 'book-reserve', 'book-free_reserve-category-ID': btn.data('category')}
            }).done(function (r) {
                if(r.r){
                    var data = r.data;
                    if(data.action === \"book-free\") btn.val(\"book-reserve\");
                    else if(data.action === \"book-reserve\") btn.val(\"book-free\");
                    btn.html(data.icon);
                    window.location.reload();
                }
                printMsg(r.msgs);             
            });
        });</script>";
}
exit(0);