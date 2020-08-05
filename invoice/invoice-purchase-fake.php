<?php
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";
require_once "../lib/fpdf/invoice.php";
require_once "../lib/transaction.php";

session_start();

if(isset($_GET['purchase-fake'])){
    $sql = new MySQL();
    $user = new User($sql);
    $transaction = new Transaction($sql);
    $bookrewind = new BookRewind($sql);

    $targetMail = $_GET['mail'];
    $targetName = $_GET['name'];
    $targetSurname = $_GET['surname'];
    $targetClass = $_GET['class'];
    $bookIDs = $_GET['books'];
    
    $price = 0;
    $books = [];
    foreach($bookIDs as $book){
        $category = $bookrewind->getBooksEx(User::$t_users, [
            'where' => [['book.ID', EQUAL, $book]],
            'fiedls' => ["category.title" => '', "category.price" => ''],
            'join' => 4
        ]);
        $category = $category->next();
        if(strlen($category['title']) > 50){
            $category['title'] = substr($category['title'], 0, 40)."...";
        }
        $category['ID'] = $book;
        $books[] = $category;

        $price += $category['price'];
    }

    $pdf = new Invoice("A5");
    if($_GET['save'] == true) {
    	$n_invoice = $transaction->add(TR_PURCHASE, $price, $targetMail);
    }
    
    $pdf->SetInvoice($n_invoice, date('d-m-Y'));
    $pdf->mail = $targetMail;
    $pdf->AddPage();
    $pdf->Student("{$targetName} {$targetSurname}, {$targetMail} - {$targetClass}");
    $pdf->Reason("Libri Conto Vendita");

    $pdf->Ln(6);
    $pdf->BeginTable(["ID", "Titolo", "Prezzo"], [0.1, 0.75, 0.15]);
    foreach($books as $book) {
        $pdf->AddRow($book['ID'], $book['title'], $book['price'].EURO);
    }

    $pdf->Total($price);
    $dir = 'files/';
    $filename = $n_invoice.'_A_'.substr($targetMail, 0, strpos($targetMail.'@', '@'))."_d".date('d-m-Y').'.pdf';
    $pdf->Output();
    if($_GET['save'] == true) {
    	$pdf->Output('F', $dir.$filename);
    }
}else {
    header("Location: /404-not-found.php");
    exit(0);
}