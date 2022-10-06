<?php
	session_start();
	
	function decrypt($text, $key){
		$newStr = '';
		//Take out the padding on the left side of the encrypted text
		$padding = intVal(substr($key, 0,2), 36);
		$text = substr($text, $padding);
		$key = substr($key, 2); //Delete the used part of the key
		//Take out the padding on the right side of the encrypted text
		$padding = intVal(substr($key, 0,2), 36);
		$text = substr($text, 0,strlen($text)-$padding);
		$key = substr($key, 2); //Delete the used part of the key
		//Replace each of the encrypted characters with the decrypted version
		for($i=0; $i<strlen($key); $i++){
			$char = ord($text[$i]); //Get the value of the current character
			$diff = intVal($key[$i%strlen($key)], 36); //Use the key to get the difference 
			$newStr .= chr($char-$diff); //Append the original character to newString
		}
		return $newStr; //Return the original plaintext
	}

	function isLoggedIn(){
		//Check if login details are set
		if(isset($_COOKIE['key']) and isset($_SESSION['pass']) and isset($_SESSION['name'])){
			//Decrypt the username and password
			$username = decrypt($_SESSION['user'], $_COOKIE['key']);
			$password = decrypt($_SESSION['pass'], $_COOKIE['key']);
			//Check if the decrypted username and password are correct
			if($password == 'EpicGamerMoment123' and ($username == 'Sven' or $username == 'Jeb')){
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
?>
