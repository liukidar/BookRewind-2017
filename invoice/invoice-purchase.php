<?php
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";
require_once "../lib/fpdf/invoice.php";
require_once "../lib/transaction.php";

session_start();

if($_SESSION['invoice-purchase']){
    $sql = new MySQL();
    $user = new User($sql);
    $transaction = new Transaction($sql);
    $bookrewind = new BookRewind($sql);

    $target = $_SESSION['invoice-purchase']['user'];
    $bookIDs = $_SESSION['invoice-purchase']['books'];
    $_SESSION['invoice-purchase'] = NULL;

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
            $category['title'] = substr($category['title'], 0, 50)."...";
        }
        $category['ID'] = $book;
        $books[] = $category;

        $price += $category['price'];
    }

    $pdf = new Invoice("A5");
    $n_invoice = $transaction->add(TR_PURCHASE, $price, $target['mail']);
    $pdf->SetInvoice($n_invoice, date('d-m-Y'));
    $pdf->mail = $target['mail'];
    $pdf->AddPage();
    $pdf->Student("{$target['name']} {$target['surname']}, {$target['mail']} - {$target['class']}");
    $pdf->Reason("Libri Conto Vendita");

    $pdf->Ln(6);
    $pdf->BeginTable(["ID", "Titolo", "Prezzo"], [0.1, 0.75, 0.15]);
    foreach($books as $book) {
        $pdf->AddRow($book['ID'], $book['title'], $book['price'].EURO);
    }

    $pdf->Total($price);
    $dir = 'files/';
    $filename = $n_invoice.'_A_'.substr($target['mail'], 0, strpos($target['mail'].'@', '@'))."_d".date('d-m-Y').'.pdf';
    $pdf->Output();
    $pdf->Output('F', $dir.$filename);
    mail_html($target['mail'], "Ricevuta acquisti Bookrewind",
        "In allegato la ricevuta d'acquisto.", $dir.$filename);
}else {
    header("Location: /404-not-found.php");
    exit(0);
}