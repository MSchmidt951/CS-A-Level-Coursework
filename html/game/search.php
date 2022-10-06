<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>Search</title>
	</head>
	<body>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/navBar.html'; ?>
		<div class="h"><header>Search</header></div>
		<?php
		$searched = false;
		if(count($_GET) > 0){
			if(isset($_GET['rnd'])){ //Go to a random game on the site
				rndGame();
			} else if(isset($_GET['s'])){ //If someone has searched something create the appropriate search class
				$s = new Search(['downloadable', 'playable'], true, true, 'self', $_GET['s'], true);
				$searched = true;
			} else {
				echo 'Search error';
			}
		} else {
			//Create a blank search class if there has been no search
			$s = new Search(['downloadable', 'playable'], true, true, 'self', '', true);
		}
		?>
		<div id="results" style="clear:both">
			<div class="h"><h1>Search Results</h1></div>
			<?php
				if($searched){
					if(isset($_GET['orderBy'])){
						//Check if the results will be reversed
						if(isset($_GET['reverse'])){
							$reverse = $_GET['reverse'] == 'true';
						} else {
							$reverse = false;
						}
						//Display the results
						$s->displayResult($_GET['s'], [$_GET['orderBy'], $reverse]);
					} else {
						//Display the results
						$s->displayResult($_GET['s']);
					}
				} else {
					//Show all games if there has not been a search
					$s->displayResult('');
				}
			?>
		</div>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/footer.html' ?>
	</body>
</html>
