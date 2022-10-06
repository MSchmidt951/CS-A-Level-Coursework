<?php
	require 'admin.php';
	if(isLoggedIn()){
		header('Location: search.php');
		die();
	}
?>
<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<script src="encryptPass.js"></script>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php'; ?>
		<title>Admin Login</title>
	</head>
	<body>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/navBar.html'; ?>
		<div class="h"><header>Administrator Login</header></div>
		<?php
			if(isset($_POST['err'])){ //Show any error messages
				if($_POST['err'] == 'attempts'){
					echo '<div class="h"><h1>Too many login attempts! Please try again later</h1></div>';
				} else {
					echo '<div class="h"><h1>Your login attempt was incorrect!</h1></div>';
				}
			}
		?>
		<form method="post" action="checkLogin.php">
			<pre>Username:	<input id="username" type="text" name="u" placeholder="Enter username here ..." required /></pre>
			<pre>Password:	<input id="pass" type="password" name="p" placeholder="Enter password here ..." required /></pre>
			<button id="loginBtn" type="button">Login</button>
		</form>
		<script>
			//create a function that runs when the user presses the login button
			$('#loginBtn').click(()=>{
				let form = $('form').eq(1); //get values of the form
				//create key
				let key = '';
				for(let i=0; i<randInt(10, 25); i++){ //make the key 10 to 25 digits long
					key += randInt(0, 35).toString(36); //add a one digit base 36 number to the key
				}
				//Update and send the form
				$('#username').val(encrypt($('#username').val(), key)); //Set the value of the username to the encrypted username
				$('#pass').val(encrypt($('#pass').val(), key)); //Set the value of the password to the encrypted password
				form.append('<input type="text" name="k" value="'+key+'" hidden />'); //Add the key to the form
				form.submit(); //Send the form
			});
		</script>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/footer.html'; ?>
	</body>
</html>
