<?php
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";
require_once "../lib/fpdf/invoice.php";
require_once "../lib/transaction.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$transaction = new Transaction($sql);
if($_SESSION['remove-user']){
	$to = $_SESSION['remove-user'];
	$price = 4;
	$_SESSION['remove-user'] = null;
	$pdf = new Invoice("A5");
	$n_invoice = $transaction->add($transaction->TYPE[TR_UNREGISTRATION], $price, $to['mail']);
	$pdf->SetInvoice($n_invoice, date('d-m-Y'));
	$pdf->mail = $to['mail'];
	$pdf->AddPage();
	$pdf->Student("{$to['name']} {$to['surname']}, {$to['mail']} - {$to['class']}");
	$pdf->Reason("Annullamento Registrazione");

	$pdf->Total($price);
	$dir = 'files/';
	$filename = $n_invoice.'_U_'.substr($to['mail'], 0, strpos($to['mail'].'@', '@'))."_d".date('d-m-Y').'.pdf';
	$pdf->Output();
	$pdf->Output('F', $dir.$filename);
}else {
	header("Location: /404-not-found.php");
	exit(0);
}