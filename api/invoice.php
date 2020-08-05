<?php

require_once "../lib/template.php";
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";
require_once "../lib/transaction.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$bookrewind = new BookRewind($sql);
$transaction = new Transaction($sql);
$target = $_SESSION['manage-user'] ? $_SESSION['manage-user'] : $user->data;

$r = false;
$data = ['action' => $_POST['action']];

//User actions
//----------------------------------------------------

$user->accessPage(User::$permissions['']);

//----------------------------------------------------

//Admin actions
//----------------------------------------------------

if($user->hasAccess(User::$permissions['ADMIN'], false)){
    if($_POST['action'] == 'invoice-download'){
        $dir = '../invoice/files/';
        $file = $dir.str_replace('\\', '', str_replace('/', '', $_POST['invoice-filename']));

        if (file_exists($file)) {
            $data['file'] = base64_encode(file_get_contents($file));
            $data['filename'] = $_POST['invoice-filename'];
            $r = true;
        }
        else {
            throw_error("File '$file' non trovato");
        }
    }
    elseif($_POST['action'] == 'invoice-remove'){
        $dir = '../invoice/files/';
        $file = $dir.str_replace('\\', '', str_replace('/', '', $_POST['invoice-filename']));
        if (file_exists($file)) {
            unlink($file);

            throw_success("Ricevuta eliminata con successo");
            $r = true;
        }
        else {
            throw_error("File '$file' non trovato");
        }
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