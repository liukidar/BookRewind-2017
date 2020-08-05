<?php

require_once "../lib/template.php";
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$bookrewind = new BookRewind($sql);
$target = $_SESSION['manage-user'] ? $_SESSION['manage-user'] : $user->data;

$r = false;
$data = ['action' => $_POST['action']];

//User actions
//----------------------------------------------------

$user->accessPage(User::$permissions['']);

if($data['action'] == 'book-reserve'){
    $r = $bookrewind->reserveBook($target['ID'], $target['class'],
        $_POST['book-free_reserve-category-ID'], $user->hasAccess(User::$permissions['ADMIN']));
    if($r){
        throw_success("Libro prenotato");
        $data['icon'] = "<i class='material-icons inline {$bookrewind->STATUS_INFO[1][2]}'>{$bookrewind->STATUS_INFO[1][3][1]}</i>";
    }
}
elseif ($data['action'] == 'book-free'){
    $r = $bookrewind->freeBook($target['ID'], $_POST['book-free_reserve-category-ID']);
    if($r){
        throw_success("Prenotazione annullata");
        $data['icon'] = "<i class='material-icons inline {$bookrewind->STATUS_INFO[0][2]}'>{$bookrewind->STATUS_INFO[0][3][1]}</i>";
    }
}

//----------------------------------------------------

//Admin actions
//----------------------------------------------------

elseif($user->hasAccess(User::$permissions['ADMIN'], false)){
    if($_POST['action'] == 'book-add'){
        $r = false;
        $owner = $user->find($_POST['book-add-user'], true);
        if($owner){
            $r = $bookrewind->addBook($owner['ID'], $_POST['book-add-ISBN'],
                $_POST["book-add-duplicate"], true);
            if($r[0]){
                $ID = intval($r[0]);
                $title = htmlentities($r[1], ENT_QUOTES);
                throw_msg("\"$title\" inserito con successo.<br>ID: $ID", 10000, false);

                $r = true;
            }
        }
    }
    elseif(isset($_POST['book-delete'])){
        $r = $bookrewind->deleteBook($_POST['book-ID']);
        if($r){
            throw_success("Libro eliminato con successo");
        }
    }
    elseif(isset($_POST['book-invalidate'])){
        $r = $bookrewind->invalidateBook($_POST['book-ID']);
        if($r){
            throw_success("Libro invalidato con successo");
        }
    }
    elseif(isset($_POST['book-validate'])){
        $r = $bookrewind->validateBook($_POST['book-ID']);
        if($r){
            throw_success("Libro validato con successo");
        }
    }
    elseif(isset($_POST['book-return'])){
        $r = $bookrewind->returnBook($_POST['book-ID']);
        if($r){
            throw_success("Libro restituito con successo");
        }
    }
    elseif(isset($_POST['book-free-all-by-date'])){
        $r = $bookrewind->freeAllByDate(8);
        if($r){
            throw_success("Libri nuovamente disponibili");
        }
    }
    elseif(isset($_POST['book-get-by-date'])){
        $where = NULL;
        $fields = ['book.ID' => '', 'book.status' => '', 'owner.mail' => 'owner_mail', 'buyer.mail' => 'buyer_mail', 'book.date_in' => '', 'book.date_out' => ''];
        $books = [];
        if(!$_POST['book-get-by-date-from']){
            $_POST['book-get-by-date-from'] = "1998-10-08";
        }
        if(!$_POST['book-get-by-date-to']){
            $_POST['book-get-by-date-to'] = "2998-10-08";
        }

        if($_POST['book-get-by-date-type'] == 'in'){
            $where = [['date_in', LESS_EQUAL_THAN, $_POST['book-get-by-date-to']], ['date_in', MORE_EQUAL_THAN, $_POST{'book-get-by-date-from'}]];
        }
        elseif($_POST['book-get-by-date-type'] == 'out'){
            $where = [['date_out', LESS_EQUAL_THAN, $_POST['book-get-by-date-to']], ['date_out', MORE_EQUAL_THAN, $_POST{'book-get-by-date-from'}]];
        }
        elseif($_POST['book-get-by-date-type'] == 'modify'){
            $where = [['modify_date', LESS_EQUAL_THAN, $_POST['book-get-by-date-to']], ['modify_date', MORE_EQUAL_THAN, $_POST{'book-get-by-date-from'}]];
        }

        $res = $bookrewind->getBooksEx(User::$t_users, [
            'where' => $where,
            'fields' => $fields,
            'join' => 1 | 2
        ]);
        while($book = $res->next()){
            $books[] = $book;
        }

        $data['books'] = $books;
        $data['type'] = $_POST['book-get-by-date-type'];
        $r = true;
    }
}

$json['r'] = $r;
$json['msgs'] = get_msg();
$json['data'] = $data;

header('Content-Type: application/json');
echo json_encode($json);
exit(0);