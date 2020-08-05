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
    if($data['action'] == 'transaction-generate-csv'){
        if(count($_POST['transaction-generate-csv-type']) == 0){
            echo "Nessuna categoria richiesta";
        }
        else {
            if(!$_POST['transaction-generate-csv-from']){
                $_POST['transaction-generate-csv-from'] = "1998-10-08";
            }
            if(!$_POST['transaction-generate-csv-to']){
                $_POST['transaction-generate-csv-to'] = "2998-10-08";
            }
            $data['file'] = $transaction->createCSV($_POST['transaction-generate-csv-type'], $_POST['transaction-generate-csv-from'], $_POST['transaction-generate-csv-to'], $_POST['transaction-generate-csv-custom'] == "on");
            $r = true;
        }
    }
    elseif($data['action'] == 'transaction-add'){
        if($_POST['transaction-add-type'] && $_POST['transaction-add-price'] && $_POST['transaction-add-info']){
            $r = $transaction->add($_POST['transaction-add-type'], $_POST['transaction-add-price'], $_POST['transaction-add-info'], 1);
            if($r){
                throw_success("Transazione $r inserita con successo");
            }
        }
        else {
            throw_error("Parametri non validi");
        }
    }
    elseif($data['action'] == 'transaction-remove'){
        $r = $transaction->remove($_POST['transaction-remove-ID']);
        if($r){
            throw_success("Transazione eliminata con successo");
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
        $responseCode = 404;
    }
    else {
        $responseCode = 400;
    }
}

http_response_code($responseCode);
header('Content-Type: application/json');
echo json_encode($json);
exit(0);