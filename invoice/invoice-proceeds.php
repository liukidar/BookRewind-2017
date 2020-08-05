<?php
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";
require_once "../lib/fpdf/invoice.php";
require_once "../lib/transaction.php";

session_start();

if($_SESSION['invoice-proceeds']){
	$sql = new MySQL();
	$user = new User($sql);
	$transaction = new Transaction($sql);
	$bookrewind = new BookRewind($sql);

	$target = $_SESSION['invoice-proceeds']['user'];
	$books = $_SESSION['invoice-proceeds']['books'];
	$_SESSION['invoice-proceeds'] = NULL;

	$price = 0;
	foreach($books as &$book){
		if(strlen($book['title']) > 50){
			$book['title'] = substr($book['title'], 0, 50)."...";
		}
		else {
			$book['title'] = strlen($book['title']);
		}
		$book['ID'] = $book['bookID'];

		$price += $book['price'];
	}

	$pdf = new Invoice("A5");
	$n_invoice = $transaction->add(TR_USER_PAYMENT, $price, $target['mail']);
	$pdf->SetInvoice($n_invoice, date('d-m-Y'));
	$pdf->mail = $target['mail'];
	$pdf->AddPage();
	$pdf->Student("{$target['name']} {$target['surname']}, {$target['mail']} - {$target['class']}");
	$pdf->Reason("Consegna ricavo");

	$pdf->Ln(6);
	$pdf->BeginTable(["ID", "Titolo", "Prezzo"], [0.1, 0.75, 0.15]);
	foreach($books as $book) {
		$pdf->AddRow('-', $book['title'], $book['price'].EURO);
	}

	$pdf->Total($price);
	$dir = 'files/';
	$filename = $n_invoice.'_P_'.substr($target['mail'], 0, strpos($target['mail'].'@', '@'))."_d".date('d-m-Y').'.pdf';
	$pdf->Output();
	$pdf->Output('F', $dir.$filename);
	mail_html($target['mail'], "Ricevuta acquisti Bookrewind",
		"In allegato la ricevuta d'acquisto.", $dir.$filename);
}else {
	header("Location: /404-not-found.php");
	exit(0);
}