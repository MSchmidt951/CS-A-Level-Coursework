<?php
	require 'admin.php';
	if(!isLoggedIn()){
		header('Location: '.$_SERVER['DOCUMENT_ROOT'].'/home.php');
		die();
	}
?>
<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>Test Game</title>
	</head>
	<body>
		<?php
			if(isset($_GET['g'])){
				//If there is a game create the object and ouput it
				$g = new webGame($_GET['g'], true);
				$g->testGame();
			} else {
				//Show an error message
				echo '<div class="h"><header>No Game Found!</header></div>';
			}
		?>
	</body>
</html>
