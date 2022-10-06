<?php
require 'admin.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes.php';
if(!isLoggedIn()){
	header('Location: '.$_SERVER['DOCUMENT_ROOT'].'/home.php');
	die();
}

define('MB', 1024*1024);

function checkFile($formName, $destination, $wantedFileType, $maxSize, &$err, $extractFile=false){
	if($err == 'Success' and $_FILES[$formName]['error'] != UPLOAD_ERR_NO_FILE){ //If there has been no previous errors, do not check if the file is correct
		if(!isset($_FILES[$formName]['error']) or is_array($_FILES[$formName]['error'])){
			$err = 'Invalid parameters';
		} else if($_FILES[$formName]['size'] > $maxSize or $_FILES[$formName]['error'] == UPLOAD_ERR_INI_SIZE or $_FILES[$formName]['error'] == UPLOAD_ERR_FORM_SIZE){
			$err = 'File is too large.';
		} else if(disk_free_space('/') < $_FILES[$formName]['size']){
			$err = 'Not enough space on the server';
		} else {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$fileType = $finfo->file($_FILES[$formName]['tmp_name']); //Get the file type
			if(in_array($fileType, $wantedFileType)){
				//move it to the destination
				if(move_uploaded_file($_FILES[$formName]['tmp_name'], $destination)) {
					$err = 'Success';
				} else {
					$err = 'There was an error moving the file onto the server';
				}
			} else {
				$err = 'Wrong file type: must be a '.$wantedFileType[0].' file';
			}
		}
	}
}

if(isset($_GET['g'])){
	//find the file type of the game
	foreach(['pending', 'downloadable', 'playable'] as $i){
		//check if the game exists
		if(file_exists('/opt/gameBloc/games/'.$i.'/'.$_GET['g'])){
			$infoFile = json_decode(file_get_contents("/opt/gameBloc/games/$i/".$_GET['g']."/info.json"), true);
			if($infoFile['type'] == 'playable'){
				$g = new WebGame($_GET['g'], true);
			} else {
				$g = new DownloadableGame($_GET['g'], true);
			}
			if(isset($_GET['d'])){
				$g->download();
			}
			if(isset($_GET['r'])){
				$g->info['reported'] = 'none';
				LogFunc($g->name.' has been un-reported');
			}
		}
	}
	//If the game does not exist make the error message be displayed
	if(!isset($g)){
		$g = false;
	}
} else if(isset($_POST['allow'])){
	if(file_exists('/opt/gameBloc/games/pending/'.$_POST['allow'])){
		$infoFile = json_decode(file_get_contents('/opt/gameBloc/games/pending/'.$_POST['allow'].'/info.json'), true);
		$g = new Game($_POST['allow'], $infoFile['type'], true);
		$g->allow()
	}
} else if(isset($_POST['deny'])){
	if(file_exists('/opt/gameBloc/games/pending/'.$_POST['deny'])){
		$infoFile = json_decode(file_get_contents('/opt/gameBloc/games/pending/'.$_POST['deny'].'/info.json'), true);
		$g = new Game($_POST['deny'], $infoFile['type'], true);
		$g->deny()
	}
} else if(isset($_POST['g'])){ //Post is to change game
	$formComplete = false;
	foreach(['pending', 'downloadable', 'playable'] as $i){
		if(file_exists('/opt/gameBloc/games/'.$i.'/'.$_POST['g'])){
			$formComplete = true;
			$g = new Game($_POST['g'], $i, true);
			$downloadable = $g->info['type']=='downloadable';
		}
	}
	//Check that the form is complete
	foreach(['tags', 'description'] as $i){
		if(!isset($_POST[$i])){
			$formComplete = false;
		}
	}
	//Check that the downloadable part of the form is complete
	if($downloadable){
		foreach(['minReq', 'recReq', 'multiplayer', 'platform'] as $i){
			if(!isset($_POST[$i])){
				$formComplete = false;
			}
		}
	}

	//check each file
	if($formComplete){
		$error = 'Success';
		$gameName = strtolower($_POST['name']);
		$imgFolder = '/var/www/img/'.$gameName;
		checkFile('thumbnail', $imgTarget.'/thumbnail.jpg', ['image/jpeg', 'image/pjpeg'], 0.5*MB, $errorMessage);
		for($i=0; $i<5; $i++){ //move all the screenshots
			checkFile('screenshot'.$i, $imgTarget.'/screenshot'.$i.'.jpg', ['image/jpeg', 'image/pjpeg'], MB, $errorMessage);
		}
		if($error == 'Success'){
			//Update the amount of screenshots that the game stores
			if($downloadable){
				$g->info['images'] = [];
				for($i=0; i<$scrotCount; $i++){
					$g->info['images'] += $target.'/'.$i.'.jpg';
				}
				//Update the requirements, multiplayer compatibility and the platform of the game
				foreach(['minReq', 'recReq', 'multiplayer', 'platform'] as $i){
					$g->info[$i] = $_POST[$i];
				}
			}
			//Update the tags and description of the game
			$g->info['tags'] = $_POST['tags'];
			$g->info['description'] = $_POST['description'];
			$g->updateInfo(); //Save the updates that has been made
		}
	}
} else {
	$g = false;
}

?>
<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<title>Manage Game</title>
	</head>
	<body>
	<?php
		if($g != false){
			//Echo the parts of game that can be edited
			echo '<div class="h"><header>Edit: '.$g->name.' <h1>by '.$g->info['developer'].'</h1></header></div>';
			echo '<div style="float:left; width:80%" id="gameInfo">';
			echo '<pre>Tags:		 <input type="textbox" name="tags" value="'.implode(',', $g->info['tags']).'" required /></pre>';
			echo '<pre>Description:	 <input type="textbox" name="description" value="'.$g->info['description'].'" required /></pre>';
			echo '<pre>Thumbnail:  <input type="file" name="thumbnail" />   Current thumbnail: ';
			echo '<img src="'.$g->info['img'].'" width="240" height="120" alt="thumbnail" /></pre>'; //Output the image
			if($g->info['type'] == 'downloadable'){
				//If the game is a downloadable game show the extra information to edit
				echo '<pre>';
				for($i=0; $i<5; $i++){
					echo 'Screenshot '.$i.':  <input type="file" name="screenshot'.$i.'" /><br />';
				}
				foreach($g->info['images'] as $img){
					echo '<img src="'.$img.'" style="margin:2px; width:200px;" alt="Images here" />';
				}
				echo '</pre><pre>Minimum system requirements:	  <input type="textbox" name="minReq" value="'.$g->info['minRequirements'].'" required /></pre>';
				echo '<pre>Recommended system requirements: <input type="textbox" name="recReq" value="'.$g->info['recRequirements'].'" required /></pre>';
				echo '<pre>Multiplayer: None<input type="radio" name="multiplayer" value="none" '.($g->info['multiplayer'] == 'none' ? 'checked' : '');
				echo ' />, Local<input type="radio" name="multiplayer" value="local" '.($g->info['multiplayer'] == 'local' ? 'checked ': '');
				echo ' />, Online<input type="radio" name="multiplayer" value="online" '.($g->info['multiplayer'] == 'online' ? 'checked' : '').' /></pre>';
				echo '<pre>Platform: Windows<input type="checkbox" name="platform" value="Windows" '.(strpos($g->info['platform'], 'Windows') !== false ? 'checked' : '');
				echo ' />, Mac<input type="checkbox" name="platform" value="Mac" '.(strpos($g->info['platform'], 'Mac') !== false ? 'checked' : '');
				echo ' />, Linux<input type="checkbox" name="platform" value="Linux" '.(strpos($g->info['platform'], 'Linux') !== false ? 'checked' : '').' /></pre>';
				echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?d&g='.$g->name.'">Download Gamme</a>'; //download link
			}
			echo '<input type="submit" value="Update" /></form>';
			//Output other info
			echo '</div><div style="border:2px solid red; float:left; margin:10px; padding:5px">';
			echo 'Rating: '.$g->info['rating']['percentage'].'%';
			echo '<br />Reported: '.$g->info['reported'];
			if($g->info['reported'] != 'none'){ //If the game has been reported show a link to un-report the game
				echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?r&g='.$g->name.'">Un-report game</a>';
			}
			echo '<br />Uploaded on: '.$g->info['created'].'</div>';
		} else {
			echo '<header>No game found</header>';
		}
	?>
	</body>
</html>
