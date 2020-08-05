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
$data = [];

//User actions
//----------------------------------------------------

$user->accessPage(User::$permissions['']);

if(isset($_POST['change-psw'])){
    $r = false;
    $ip = get_client_ip();
    if($sql->lock($ip, 0)) {
        if ($_POST['change-psw-new'] == $_POST['change-psw-repeat']) {
            if ($user->changePsw($_POST['change-psw-old'], $_POST['change-psw-new'])) {
                throw_success("Password modificata");
                $r = true;
            }
        } else {
            throw_error("Le nuove password non coincidono");
        }

        $sql->unlock_wait($ip, 500000, 500000);
    }
}

//----------------------------------------------------

//Admin actions
//----------------------------------------------------

if($user->hasAccess(User::$permissions['ADMIN'], false)){
    if(isset($_POST['user-unregister'])){
        if($r = $user->unregister($_SESSION['manage-user']['ID'])){
            throw_success("Utente rimosso");
            /*$_SESSION['remove-user'] = ['name' => $targetUser['name'], 'surname' => $targetUser['surname'],
                'mail' => $targetUser['mail'], 'class' => $targetUser['class']];*/
        }
    }
    elseif(isset($_POST['user-pay-proceeds'])){
        $books = $bookrewind->payProceeds($_SESSION['manage-user']['ID']);
        if(count($books)){
            $r = true;
            throw_success("Transazione eseguita con successo");
            $_SESSION['invoice-proceeds']['books'] = $books;
            $_SESSION['invoice-proceeds']['user'] = $_SESSION['manage-user'];
            $data['user'] = $_SESSION['manage-user']['mail'];
        }
        else {
            $r = false;
        }
    }
    elseif (isset($_POST['user-return-selected-books'])) {
        $bookrewind->returnBooksByOwner($_SESSION['manage-user']['ID'], $_POST['return-books']);
        throw_success("Libri restituiti al proprietario");
        $r = true;
    }
}

$json['r'] = $r;
$json['msgs'] = get_msg();
$json['data'] = $data;

$responseCode = 500;
if($r == true){
    $responseCode = 200;
}
else {
    if(count($json['msgs'])){
        $responseCode = 400;
    }
    else {
        $responseCode = 404;
    }
}

http_response_code($responseCode);
header('Content-Type: application/json');
echo json_encode($json);
exit(0);