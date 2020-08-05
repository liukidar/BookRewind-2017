<?php

require_once "../lib/template.php";
require_once "../lib/user.php";
require_once "../lib/bookrewind.php";

session_start();
$sql = new MySQL();
$user = new User($sql);
$bookrewind = new BookRewind($sql);

$r = true;
$data = ['post' => $_POST];

//User actions
//----------------------------------------------------

$user->accessPage(User::$permissions['']);

//----------------------------------------------------

//Admin actions
//----------------------------------------------------

if($user->hasAccess(User::$permissions['ADMIN'], false)){
    /****************************************************************
     * Categories
     */
    if(isset($_POST['category-add'])){
        $r = $bookrewind->addCategory($_POST['category-add-title'], $_POST['category-add-subtitle'],
            $_POST['category-add-ISBN'], $_POST['category-add-price'], $_POST['category-add-classes'],
            $_POST['category-add-author'], $_POST['category-add-publisher']);
        if($r){
            throw_success("Adozione registrata con successo");
        }
    }
    elseif(isset($_POST['category-delete'])){
    	if($_POST['category-edit-move']){
    		$r = $bookrewind->moveCategory($_POST['category-edit-ISBN'], $_POST['categroy-edit-move-to-ISBN']);
    		if($r){
    			throw_success("Adozione spostata con successo");
		    }
	    }
	    else {
    		$r = true;
	    }
	    if($r){
		    $r = $bookrewind->deleteCategory($_POST['category-edit-ID']);
		    if($r){
			    throw_success("Adozione rimossa con successo");
		    }
	    }
    }
    elseif(isset($_POST['category-edit'])) {
        $r = $bookrewind->editCategory($_POST['category-edit-ID'], $_POST['category-edit-ISBN'],
            $_POST['category-edit-title'], $_POST['category-edit-subtitle'],
            $_POST['category-edit-classes'], $_POST['category-edit-author'],
            $_POST['category-edit-publisher'], $_POST['category-edit-price']);
        if($r){
            throw_success("Adozione modificate con successo");
        }
    }
    elseif(isset($_POST['category-get'])){
        $category = $bookrewind->getCategory(['where' => ['ISBN' => $_POST['category-get-ISBN']]]);
        if($category) {
            $r = true;

            $category = $category->next();
            $classbooks = $bookrewind->getClassbooks(['where' => ['typology' => $category['ID'], 'status' => 1], 'fields' => 'class']);
            $classes = [];
            while($class = $classbooks->next()){
                $classes[] = $class['class'];
            }
            $category['classes'] = $classes;
            $data['category'] = $category;
        }
    }
}

$json['r'] = $r;
$json['msgs'] = get_msg();
$json['data'] = $data;

header('Content-Type: application/json');
echo json_encode($json);
exit(0);