<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="main.css">
		<?php require 'classes.php' ?>
		<title>Game Bloc</title>
	</head>
	<body>
		<?php include 'navBar.html'; ?>
		<div class="h"><header>Welcome to Game Bloc!</header></div>
		<p style="text-align:center">Game Bloc is a site that allows people to upload, play and download community made games, all for free!</p>
		<div id="newGames" style="width:49%; display:inline-block;">
			<div class="h"><h1>New games</h1></div>
			<div id="newGamesList">
				<?php
					//Find games and order them by newest first
					$newGames = new Search();
					$newGames->displayResult('', ['created', true]);
				?>
			</div>
		</div>
		<div id="popularGames" style="width:49%; display:inline-block">
			<div class="h"><h1>Popular games</h1></div>
			<div id="popularGamesList">
				<?php
					//Find games and order them by the highest amount of likes first
					$popularGames = new Search();
					$popularGames->displayResult('', ['rating', true]);
				?>
			</div>
		</div>
		<?php include 'footer.html'; ?>
	</body>
</html>
