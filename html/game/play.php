<!DOCTYPE html>

<?php
	require $_SERVER['DOCUMENT_ROOT'].'/classes.php';
	if(isset($_GET['g'])){
		$g = new WebGame($_GET['g']); //Create the game class
		if(isset($_GET['rate'])){
			$g->rate($_GET['rate']);
		}
	}
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<title>Play <?php echo $g->game; ?></title>
	</head>
	<body>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/navBar.html'; ?>
		<div>
			<?php
				if(isset($g)){
					$g->createGame(); //Ouput the game and inforamtion about it on the screen
				} else {
					echo '<header>No Game Found!</header>';
				}
			?>
		</div>
		<div id="similarGames">
			<?php
				if(isset($g)){
					$g->similarGames->displayResult($_GET['g']); //Output similar games
				}
			?>
		</div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/footer.html'; ?>
	</body>
</html>
