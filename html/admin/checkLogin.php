<?php
	require 'admin.php';
	if(isLoggedIn()){ //If the user is logged in already redirect to the admin search page
		header('Location: search.php');
		die();
	}

	$manageAttemptsFile = '/opt/gameBloc/serverScripts/attempts';
	function loginFailed(){
		//manage login attempts
		$attempts = (int)file_get_contents($manageAttemptsFile);
		file_put_contents($manageAttemptsFile, $attempts-1);
		//Redirect to the login page with appropriate error
		if($attempts-1 <= 0){
			echo "<script>createForm('login.php', {'err':'attempts'}, 'POST')";
		} else {
			echo "<script>createForm('login.php', {'err':'incorrect'}, 'POST')";
		}
	}
?>
<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>Checking login ...</title>
	</head>
	<body>
		<?php
			//Check that the form has been completed and the form is completed correctly
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
				echo '<div class="h"><header>Processing your login attempt<br />Please stand by ...</header></div>';
				if(isset($_POST['k']) and isset($_POST['p']) and isset($_POST['u'])){
					//Decrypt username and password with the key
					$username = decrypt(htmlspecialchars_decode($_POST['u']), htmlspecialchars_decode($_POST['k']));
					$password = decrypt(htmlspecialchars_decode($_POST['p']), htmlspecialchars_decode($_POST['k']));
					//Check if the username and password are correct and that the number of login attempts left are greater than 0
					if($password == 'EpicGamerMoment123' and ($username == 'Sven' or $username == 'Jeb') and (int)file_get_contents($manageAttemptsFile)>0){
						//If they are correct set the cookies and session variables
						setcookie('key', $_POST['k'], time()+(24*60*60));
						$_SESSION['pass'] = htmlspecialchars_decode($_POST['p']);
						$_SESSION['name'] = htmlspecialchars_decode($_POST['u']);
						//Redirect to the admin search page
						header('Location: search.php');
						die();
					} else {
						loginFailed();
					}
				} else {
					loginFailed();
				}
			} else {
				loginFailed();
			}
		?>
	</body>
</html>
