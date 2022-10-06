<!DOCTYPE html>

<?php
define('MB', 1024*1024);
define('GB', MB*1024);
$errorMessage = '';

//This function checks if a file has been submitted properly through the html form
function checkFile($formName, $destination, $wantedFileType, $maxSize, &$err, $extractFile=false){
	if($err == 'Success'){ //If there has been no previous errors, do not check if the file is correct
		if(!isset($_FILES[$formName]['error']) or is_array($_FILES[$formName]['error'])){
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
				if($extractFile){ //If the file is to be extracted, extract it and give any errors if unsuccessful
					$zip = new ZipArchive();
					$res = $zip->open($_FILES[$formName]['tmp_name']);
					if($res === true){
						if($zip->extractTo($destination)){
							$err = 'Success';
						} else {
							$err = 'There was an error extracting the file';
						}
						$zip->close();
					} else {
						$err = 'There was an error uploading the file';
					}
				} else { //If the file is not being extracted simply move it to the destination
					if(move_uploaded_file($_FILES[$formName]['tmp_name'], $destination)) {
						$err = 'Success';
					} else {
						$err = 'There was an error moving the file onto the server';
					}
				}
			} else {
				$err = 'Wrong file type: must be a '.$wantedFileType[0].' file';
			}
		}
	}
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	//check that all of the form was completed
	$formItems = ['name', 'type', 'dev', 'desc'];
	$formCompleted = true;
	foreach($formItems as $i){
		if(!isset($_POST[$i])){
			$formCompleted = false;
		}
	}

	if($formCompleted){
		$isDownloadable = $_POST['type'] == 'downloadable';
		if(!($isDownloadable or $_POST['type'] == 'playable')){ //check that the game type is correct
			$formCompleted = false;
		}
		if($isDownloadable){ //Check the extra parts of the form has been filled out if the game is downloadable
			$downloadFormItems = ['minReq', 'recReq', 'multiplayer', 'platform', 'scrotCount'];
			foreach($downloadFormItems as $i){
				if(!isset($_POST[$i])){
					$formCompleted = false;
				}
			}
		}
	}

	if($formCompleted){
		//Make the variables for creating the files
		$gameName = strtolower($_POST['name']);
		$uploadFolder = '/opt/gameBloc/games/';
		$target = $uploadFolder.'pending/'.$gameName;
		$imgTarget = '/var/www/img/'.$gameName;
		//Check if the game has not been made before
		if(!(is_dir($uploadFolder.'playable/'.$gameName) or is_dir($uploadFolder.'downloadable/'.$gameName) or is_dir($target))){
			//create variables that are needed for the file upload
			$errorMessage = 'Success';
			$scrotCount = $isDownloadable ? (int)$_POST['scrotCount'] : 0;
			
			//check if there are the correct amount of screenshots
			if($scrotCount < 0 or $scrotCount > 5){
				$errorMessage = 'Wrong number of screenshots';
			}

			//Get the game tags
			$extraTags = preg_replace('/\s+/', ',', $_POST['extraTags']); //sterialise extra tags and replace whitespaces with commas
			$tags = explode(',', $extraTags);
			if(isset($_POST['tags'])){
				$tags += $_POST['tags'];
			}
			//build info.json file
			$info = ['type'=>$_POST['type'], 'name'=>$gameName, 'developer'=>$_POST['dev'], 'description'=>$_POST['desc'], 'tags'=>$tags];
			$info += ['rating'=>['likes'=>0, 'dislikes'=>0, 'percentage'=>0], 'created'=>'none', 'reported'=>'none'];

			//if all previous checks are positive check & move game files to the correct places
			mkdir($target); //create the game directorty
			mkdir($imgTarget); //create the image directorty
			$gameLocation = $isDownloadable ? $target.'/game.zip' : $target;
			checkFile('gameFile', $gameLocation, ['application/zip', 'application/x-compressed', 'application/x-zip-compressed', 'multipart/x-zip'], $isDownloadable ? 8*GB : 3*GB, $errorMessage, !$isDownloadable);
			checkFile('thumbnail', $imgTarget.'/thumbnail.jpg', ['image/jpeg', 'image/pjpeg'], 0.5*MB, $errorMessage);
			for($i=1; $i<=$scrotCount; $i++){ //move all the screenshots
				checkFile('screenshot'.$i, $imgTarget.'/screenshot'.$i.'.jpg', ['image/jpeg', 'image/pjpeg'], MB, $errorMessage);
			}

			//If the game is downloadable and there has been no errors add the extra downloading information
			if($isDownloadable and $errorMessage == 'Success'){
				$info += ['images'=>[], 'size'=>$_FILES['gameFile']['size'], 'downloads'=>0, 'minRequirements'=>$_POST['minReq'], 'recRequirements'=>$_POST['recReq'], 'platform'=>$_POST['platform'], 'multiplayer'=>$_POST['multiplayer']];
				for($i=1; $i<=$scrotCount; $i++){
					$info['images'] += $imgTarget.'/'.$i.'.jpg';
				}
			}
			if($errorMessage == 'Success'){ //If all of the other files have been made create the information file
				$info += ['img'=>$imgTarget.'/thumbnail.jpg'];
				file_put_contents($target.'/info.json', json_encode($info));
			} else { //If there has been an error remove the files made previously in this script
				rmdir($target);
				rmdir($imgTarget);
			}
		} else {
			$errorMessage = 'Game already exists';
		}
	} else {
		$errorMessage = 'Not all form items completed correctly';
	}
}
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<?php require $_SERVER['DOCUMENT_ROOT'].'/classes.php' ?>
		<title>Upload game</title>
	</head>
	<body>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/navBar.html'; ?>
		<div class="h"><header>Upload page</header></div>
		<?php
			if($errorMessage){ //If a game has been uploaded, show the success/error message
				echo '<div class="h"><h1>Upload: '.$errorMessage.'</h1></div>';
			}
		?>
		<div style="display:inline-block; width:27%; vertical-align:top; padding:2px">
			<div class="h"><h2>Information about uploading</h2></div>
			<p>
				When you upload your game the administrators will have to review the game before it is put on the website.<br />
				The game may be denied if it does not work or is too offensive so will not be able to be viewed on the site.<br /><br />
				The maximum file size of a web game is 3MB and 8GB for a downloadable game. The games must be uploaded in zipped folders.<br /><br />
				The web games will run on javascript and there must be game.js in the zipped folder for it to run correctly.<br /><br />
				The maximum file size for the screenshots are 1MB each and the maximum file size for the thumbnail is 0.5MB.
			</p>
		</div>
		<form method="post" enctype="multipart/form-data" style="display:inline">
			<div style="display:inline-block; width:42%; vertical-align:top">
				<div class="h"><h1>Upload form</h1></div>
				<pre>Name:          <input type="text" name="name" placeholder="Game name" required /></pre>
				<pre>Developer name:<input type="text" name="dev" placeholder="Developer name" required /></pre>
				<button type="button" id="typeBtn">Game type: downloadable</button>
				<pre>Game file:     <input type="file" name="gameFile" required /></pre>
				<pre style="white-space:pre-wrap; word-wrap:break-word">Tags:          shooter<input type="checkbox" name="tag" value="shooter" />, puzzle<input type="checkbox" name="tag" value="puzzle" />, stratergy<input type="checkbox" name="tag" value="stratergy" />, sports<input type="checkbox" name="tag" value="sports" />, platformer<input type="checkbox" name="tag" value="platformer" /></pre>
				<pre>Custom tags (seperated by commas and/or spaces):   <input type="textbox" name="extraTags" placeholder="tag1,tag2,tag3" /></pre>
				<pre>Description:   <input type="textbox" name="desc" placeholder="Description" required /></pre>
				<pre>Thumbnail:     <input type="file" name="thumbnail" required /></pre>
				<input id="gameType" type="text" name="type" hidden />
				<input type="submit" value="Submit" />
			</div>
			<div id="downloadableSection" style="display:inline-block; width:30%; vertical-align:top">
				<div class="h"><h2>Section for downloadable games</h2></div>
				<pre>Number of screenshots:			  <input id="scrotCount" type="number" min="0" max="5" value="3" name="scrotCount" required></pre>
				<pre>Screenshots (must be .jpg files):  <div id="screenshots"></div></pre>
				<pre>Minimum system requirements:	 <input type="textbox" name="minReq" /></pre>
				<pre>Recommended system requirements: <input type="textbox" name="recReq" /></pre>
				<pre>Multiplayer: None<input type="radio" name="multiplayer" value="none" />, Local<input type="radio" name="multiplayer" value="multiplayer" />, Online<input type="radio" name="multiplayer" value="online" /></pre>
				<pre>Platform: Windows<input type="checkbox" name="platform" value="Windows" />, Mac<input type="checkbox" name="platform" value="Mac" />, Linux<input type="checkbox" name="platform" value="Linux" /></pre>
			</div>
			<script>
				var btn = $('#typeBtn');
				var gameType = $('#gameType');
				gameType.val('downloadable');
				btn.click(()=>{ //This is a function that toggles the visibiliy of the downloadable section
					$('#downloadableSection').toggle(); //toggle the visibiliy of the downloadable section
					if($('#downloadableSection').is(':hidden')){ //If the downloadable section is hidden set the game type to a web game
						gameType.val('playable');
						btn.text('Game type: web');
					} else { //If the downloadable section is visible set the game type to a downloadable game
						gameType.val('downloadable');
						btn.text('Game type: downloadable');
					}
				});

				//This section of code will update the amount of screenshots available to upload when they change the drop down menu
				var scrots = $('#screenshots');
				var scrotNum = 0;
				setInterval(()=>{ //The setInterval will run this function every 100ms so it will constantly update
					let currentScrots = parseInt($('#scrotCount').val());
					let diff = currentScrots-scrotNum;
					if(diff != 0){ //This checks if there has been a change
						if(diff>0){
							//Add screenshot inputs if the amount has been raised
							for(let i=0; i<diff; i++){
								let newNum = scrotNum+i+1;
								scrots.append('<div><input type="file" name="screenshot'+newNum+'" /></div>');
							}
						} else {
							//Remove screenshots if the amount has been lowered
							diff *= -1;
							for(let i=0; i<diff; i++){
								scrots.children().last()[0].remove();
							}
						}
						//Update the number of screenshots
						scrotNum = currentScrots;
					}
				}, 100);
			</script>
		</form>
		<?php require $_SERVER['DOCUMENT_ROOT'].'/footer.html'; ?>
	</body>
</html>
