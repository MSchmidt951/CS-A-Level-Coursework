<?php
	require 'admin.php';
	if(!isLoggedIn()){
		header('Location: '.$_SERVER['DOCUMENT_ROOT'].'/home.php');
		die();
	}

	//This function checks if a file has been submitted properly through the html form
	function checkFile($formName, $destination, $wantedFileType, $maxSize, &$err){
		if(file_exists($destination)){
			$err = 'FAQ already exists';
		} else if(!isset($_FILES[$formName]['error']) or is_array($_FILES[$formName]['error'])){
			$err = 'Invalid parameters';
		} else if($_FILES[$formName]['error'] == UPLOAD_ERR_NO_FILE){
			$err = 'No file found';
		} else if($_FILES[$formName]['size'] > $maxSize or $_FILES[$formName]['error'] == UPLOAD_ERR_INI_SIZE or $_FILES[$formName]['error'] == UPLOAD_ERR_FORM_SIZE){
			$err = 'File is too large.';
		} else if(disk_free_space('/') < $_FILES[$formName]['size']){
			$err = 'Not enough space on the server';
		} else {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$fileType = $finfo->file($_FILES[$formName]['tmp_name']); //Get the file type
			if(in_array($fileType, $wantedFileType)){
				if(move_uploaded_file($_FILES[$formName]['tmp_name'], $destination)) {
					$err = 'Success';
				} else {
					$err = 'There was an error uploading the file';
				}
			} else {
				$err = 'Wrong file type: must be a '.$wantedFileType.' file';
			}
		}
	}

	$uploadStatus = '';
	if(isset($_POST['name'])){
		//Get the file name
		$fileName = str_replace(' ', '-', $_POST['name']);
		//Check that the input file is correct and no larger than 4KB
		//If the checks succeed then the file will automaticaly be placed on the server
		checkFile('f', $_SERVER['DOCUMENT_ROOT'].'/faq/FAQs/'.$fileName, ['text/plain', 'text/html'], 4*1024, $uploadStatus);
	}
?>

<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>Add FAQ</title>
	</head>
	<body>
		<?php include 'navBar.html'; ?>
		<div class="h"><header>Add an FAQ</header></div>
		<?php
			if($uploadStatus){
				echo '<div class="h"><header>Upload status: '.$uploadStatus.'</header></div>';
			}
		?>
		<form method="POST" enctype="multipart/form-data">
			<pre>Title:							<input type="text" name="name" placeholder="Enter article name here ..." required /></pre>
			<pre>Article: (must be text or html file):	<input type="file" name="f" required /></pre>
			<input type="submit" value="Submit" />
		</form>
		<?php include 'footer.html'; ?>
	</body>
</html>
