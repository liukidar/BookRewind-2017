<?php
include_once "lib/template.php";
include_once "lib/user.php";

session_start();
$sql = new MySQL();
$user = new User($sql);

if (isset($_POST['login'])) {
	$user->login($_POST['user'], $_POST['psw']);
	throw_msg("Login effettuato");
} elseif (isset($_GET['logout'])) {
	$user->logout();
	throw_msg("Logout effettuato");
}
if($user->is()) {
	$page = $_GET['to'] ? urldecode($_GET['to']) : "home.php";
	$page = str_replace("http", "", $page);

	header("Location: ".URL."/$page");
	exit(0);
}
?>
<!DOCTYPE html>
<html lang="it">
<?php echo getHead("Login"); ?>
<body class="grey lighten-5">
<?php echo getNavbar($user->get("mail"), ["title" => "< Calini > "]); ?>
<main>
	<div class="section container small">
		<div class="card white" style="padding:20px 40px; margin-top:60px;">
            <div class="row">
                <p class='center flow-text'>Effettua il Login</p>
            </div>
			<form class="col s12" method="post" autocomplete="off">
				<div class="row">
					<div class="row">
						<div class="input-field col s12">
							<i class="material-icons prefix">account_circle</i>
							<input id="user" name="user" type="text" class="validate" required autocorrect="off" autocapitalize="off" spellcheck="false">
							<label for="user">Mail</label>
						</div>
					</div>
					<div class="row">
						<div class="input-field col s12">
							<i class="material-icons prefix">lock</i>
							<input id="psw" name="psw" type="password" class="validate" required>
							<label for="psw">Password</label>
						</div>
					</div>
					<button class="btn-large col s12 pink" name="login">LOGIN<i class="material-icons right">exit_to_app</i></button>
				</div>
			</form>
		</div>
	</div>
</main>
<?php echo getFooter(); ?>
</body>
</html>