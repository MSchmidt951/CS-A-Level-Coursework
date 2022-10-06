<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>View FAQ</title>
	</head>
	<body>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/navBar.html'; ?>
		<a href="faq.php"><button>Back to FAQ page</button></a>
		<?php
			if(isset($_GET['f'])){ //Output the FAQ if one has been set in the URL info
				$faq = new FAQ($_GET['f']);
			} else {
				echo '<div class="h"><header>No FAQ found!</header></div>';
			}
			include $_SERVER['DOCUMENT_ROOT'].'/footer.html';
		?>
	</body>
</html>
