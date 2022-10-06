<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>FAQ Search</title>
	</head>
	<body>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/navBar.html'; ?>
		<div class="h"><header>FAQ page</header></div>
		<div>
			<div class="h"><h1>Recent FAQs</h1></div>
			<div>
				<?php
					//Ouput all FAQs
					FAQ::allFAQs();
				?>
			</div>
		</div>
		<div>
			<form method="get">
				<pre>     Search:  <input type="search" name="s" placeholder="Search here ..." required /><input type="submit" value="Search" required /></pre>
			</form>
			<div>
				<?php
					if(isset($_GET['s'])){
						//If something has been searched output the results
						FAQ::search(htmlspecialchars_decode($_GET['s']));
					} else {
						echo 'Your search results will go here';
					}
				?>
			<div>
		</div>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/footer.html'; ?>
	</body>
</html>
