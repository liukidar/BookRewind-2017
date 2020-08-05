<?php
require_once "lib/template.php";
require_once "lib/user.php";
require_once "lib/bookrewind.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$bookrewind = new BookRewind($sql);

if(isset($_GET['show-table'])){
    echo $sql->showTable($_GET['show-table']);
}
elseif(isset($_GET['add-classes'])){
    $user->addClasses(explode('-', $_GET['add-classes']));
}

if(isset($_POST['upload-categories'])){
    throw_success("Adozioni caricate");
    $handle = fopen($_FILES["category-csv"]["tmp_name"], "r");
    $bookrewind->loadCategories($user, $handle);
    fclose($handle);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php echo getHead("Setup", "index, follow"); ?>
</head>
<body class="white">
<?php echo getNavbar($user->get("mail"),
    ["color" => "blue-grey", "title" => "Setup", "redirect" => "/index.php"]); ?>
<main style="margin-bottom: 40px;">
    <div class="row" style="margin-top: 40px;">
        <div class="card white">
            <div class="container">
                <form id="form-category-upload" action="setup.php" method="post" autocomplete="off" enctype="multipart/form-data">
                    <div class="card-content">
                        <span class="card-title blue-grey-text">Carica Adozioni</span>
                        <blockquote class="flow-text">
                            <small>
                                Formato <b>file csv:</b><br>
                                classe;ISBN;titolo,sottotitolo;autore;casa editrice;prezzo intero
                            </small>
                        </blockquote>
                        <div class='row no-margin'>
                            <div class="file-field input-field">
                                <div class="btn blue-grey lighten-1">
                                    <span>File</span>
                                    <input type="file" name="category-csv">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" name="category-csv" type="text">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <a><button class='btn-large waves-effect waves-light pink' name="upload-categories" type='submit'>Upload<i class="material-icons right">add</i></button></a>
                        <a><button class="inherit-style" type="reset">Annulla</button></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
</body>
<?php
echo getFooter("blue-grey");
?>
</html>