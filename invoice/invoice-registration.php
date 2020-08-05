<?php
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";
require_once "../lib/fpdf/invoice.php";
require_once "../lib/transaction.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$transaction = new Transaction($sql);

if($_SESSION['add-user']){
    $to = $_SESSION['add-user'];
    $price = 5;
    $_SESSION['add-user'] = null;
	$pdf = new Invoice("A5");
	$n_invoice = $transaction->add($transaction->TYPE[TR_REGISTRATION], $price, $to['mail']);
	$pdf->SetInvoice($n_invoice, date('d-m-Y'));
	$pdf->mail = $to['mail'];
	$pdf->AddPage();
	$pdf->Student("{$to['name']} {$to['surname']}, {$to['mail']} - {$to['class']}");
	$pdf->Reason("Registrazione - Erogazione Liberale");

	$pdf->Total($price);
	$dir = 'files/';
	$filename = $n_invoice.'_R_'.substr($to['mail'], 0, strpos($to['mail'].'@', '@'))."_d".date('d-m-Y').'.pdf';
	$pdf->Output();
    $pdf->Output('F', $dir.$filename);
    mail_html($pdf->mail, "Ricevuta Registrazione Bookrewind",
        "{$to['name']} {$to['surname']}<br>Benvenuto/a nel <a href='http://www.bookrewind.altervista.org'>portale BookRewind</a>.", $dir.$filename);

}else {
	header("Location: /404-not-found.php");
	exit(0);
}