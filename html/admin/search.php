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
		<title>Administrator Search</title>
	</head>
	<body>
		<div class="h"><header>Administrator Search</header></div>
		<?php
		$searched = false;
		if(count($_GET) > 0){
			if(isset($_GET['s'])){ //If someone has searched something create the appropriate search class
				$s = new Search(['pending', 'downloadable', 'playable'], true, true, 'self', $_GET['s'], true);
				$searched = true;
			} else {
				echo 'Search error';
			}
		} else {
			//Create a blank search class if there has been no search
			$s = new Search(['pending', 'downloadable', 'playable'], true, true, 'self', '', true);
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
						$s->displayResult($_GET['s'], [$_GET['orderBy'], $reverse], true);
					} else {
						//Display the results
						$s->displayResult($_GET['s'], ['name', true], true);
					}
				} else {
					//Show all games if there has not been a search
					$s->displayResult('', ['name', true], true);
				}
			?>
		</div>
	</body>
</html>
