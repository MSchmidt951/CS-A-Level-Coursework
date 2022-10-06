<?php

/*Classes*/
class Game {
	//game->info items: type, name, developer, description, img, tags, rating[likes, dislikes, percentage], created, reported
	//For downloadable game->info also has minRequirements, recRequirements, size, images, downloads, platform, multiplayer
	public function __construct($name, $folder, $admin=false){ //assign and load all of the objects attributes
		$this->folder = '/opt/gameBloc/games/' . $folder . '/' . $name . '/';
		if($admin and !file_exists($this->folder)){ //If the game is not found check the pending folder
			$this->folder = '/opt/gameBloc/games/pending/' . $name . '/';
		}
		if(file_exists($this->folder)){ //If the game exists set up the class
			$this->name = $name;
			$this->info = json_decode(file_get_contents($this->folder.'info.json'), true);
			$this->File = $this->info['type'] == 'playable' ? $this->folder.'game.js' : $this->folder.'game.zip';
			$this->admin = $admin;
			$this->report = new Report($name, $this->info['type'], $this);
			if(isset($_GET['report'])){ //If the game is being reported report the game
				$this->report->reportGame($_GET['report']);
			}
		} else { //If the game is not found log it and show an error message
			LogFunc("$name in $folder failed to load!");
			echo "<br /> Failed to get game $name of type $folder <br />";
		}
	}

	public function rate($rating){
		$userIP = $_SERVER['REMOTE_ADDR']; //Get the users IP address
		if(!isset($_COOKIE['rating'.$this->name])){ //If the rating cookie is not set on the computer, set it
			//Set a cookie that stores the rating that the user has given the game
			//It expires after 10 years because cookies cannot last forever
			setcookie('rating'.$this->name, $rating, time()+(10*365*24*60*60));
			//Make changes to rating in the info attribute
			$this->info['rating'][$rating] += 1;
			$totalRatings = $this->info['rating']['likes']+$this->info['rating']['dislikes'];
			$this->info['rating']['percentage'] = (int)(100*($this->info['rating']['likes']/$totalRatings));
			$this->updateInfo(); //Save the changes
			//Log the IP address and the rating so that the admins can track who rates it in the case of any automatic rating
			LogFunc($this->name.' rated '.$rating, true);
		}
	}

	public function allow(){
		if($this->info['created'] == 'none' and $this->admin){ //If the game has not been allowed yet, allow it
			//Move files into correct folder
			rename($this->folder, '/opt/gameBloc/games/'.$this->info['type'].'/'.$this->name);
			$this->folder = '/opt/gameBloc/games/'.$this->info['type'].'/'.$this->name;
			//Log the date created in the games info file
			$this->info['created'] = date('Y/m/d');
			//Save the changes made
			$this->updateInfo();
			LogFunc('Accepted '.$this->name.' into the '.$this->folder.' folder', true); //Write it to a Log file
			return true;
		} else {
			return false;
		}
	}

	public function deny(){
		if($this->admin){ //Only deny the game if there are administrator privilages
			//Delete the games folders
			$success = unlink($this->folder) and unlink('/var/www/html/img/'.$this->name);
			//Log deletion
			LogFunc('Denied '.$this->name, true);
			LogFunc($sucess ? 'Removal successful of'.$this->folder : 'Removal unsuccessful of'.$this->folder);
			//Return wether the deletion was successful
			return $success;
		} else {
			return false;
		}
	}

	public function updateInfo(){
		$infoFile = fopen($this->folder.'info.json', 'w'); //Open the games info file
		fwrite($infoFile, json_encode($this->info)); //Update the games info file with the new info about the game
		fclose($infoFile); //Close the games info file
	}
	
	public function displayPreview(){ //Creates the button that will lead to the game
		//Start creating the preview
		echo '<button class="gamePreview" id="'.$this->name.'">';
		echo '<img src="'.$this->info['img'].'" width="120" height="60" alt="thumbnail" />'; //Output the image
		echo '<div style="float:left"><div><b>'.$this->name.'</b> '.$this->info['developer'].'</div>'; //Output the name and developer
		echo '<div class="gameDescription">'.$this->info['description'].'</div></div>'; //Output the description
		echo '<div style="display:inline-block; padding-top:3px; float:right;"><b>Rating:</b> '.$this->info['rating']['percentage'].'%'; //Output the rating
		if($this->admin){ //If the class has been called by an admin add extra information 
			if($this->info['created'] == 'none'){ //If the game has not been created choose to allow or deny the game
				echo '<div style="float:right"><a id="allow">Allow</a><br /><a id="deny">Deny</a></div>';
				//Add functionality to the allow and deny buttons
				echo "<script>$(()=>{";
				echo "$('#allow').click(createForm('manageGame.php', {'allow':'".$this->name."'}, 'POST'));";
				echo "$('#deny' ).click(createForm('manageGame.php', {'deny' :'".$this->name."'}, 'POST'))});</script>";
			} else { //Show if the game has been reported and when it was created
				echo '<div><br /><b>Reported:</b> '.$this->info['reported'].'<br /><b>Created:</b> '.$this->info['created'].'</div></div></div>';
			}
			//Create a link to test and edit the game
			echo '<div style="float:right"><a href="./gameTest.php?g='.$this->name.'">test</a><br /><a href="./manageGame.php?g='.$this->name.'">edit</a></div>';
		}
		//Get the tags for the game
		echo '<div><b>Tags:</b> '.implode(', ', $this->info['tags']).'</div></div></button>';
		//Add functionality to the preview button
		if($this->info['type'] == 'playable'){
			$url = $this->info['created']=='none' ? '/admin/gameTest.php'   : '/game/play.php';
		} else {
			$url = $this->info['created']=='none' ? '/admin/manageGame.php' : '/game/download.php';
		}
		echo "<script>$(()=>{
			    $('#".$this->name."').click(()=>{createForm('$url', {'g':'".$this->name."'}, 'GET');});
			  });</script>";
	}
	
	protected function addReportButton(){ //This function returns a string with the HTML of the report button
		$reportButton = '<button id="reportBtn">Report</button>';
		$reportButton .= '<form id="reportForm" method="GET" hidden><input type="text" name="report" placeholder="Add reason" />';
		$reportButton .= '<input type="text" name="g" value="'.$this->name.'" hidden />';
		$reportButton .= '<input type="submit" value="Report Game" /></form>';
		//This adds a script that toggles the visibility of the report form is the report button is clicked
		$reportButton .= "<script>$('#reportBtn').click(()=>{
							$('#reportForm').toggle();
						  });</script>";
		return $reportButton;
	}
}

class WebGame extends Game {
	public function __construct($name, $test=false) {
		parent::__construct($name, 'playable', $test);
		if(!$test){
			$this->similarGames = new Search(['playable']);
		}
	}

	public function createGame(){ //This is to be called in the place that the game screen will be
		//Get the HTML of the game header and info box
		$gameHeader = sprintf('<h1>%s <h2>by %s</h2></h1>', $this->name, $this->info['developer']);
		$gameHeader .= '<button id="like" style="float:right">like</button>'; //Add the like button to the header
		$gameHeader .= '<button id="dislike" style="float:right">dislike</button>'; //Add the dislike button to the header
		$gameInfoBox = sprintf("<h2>Game rating: %s%% <br />%s</h2><div>%s</div>", $this->info['rating']['percentage'], $this->addReportButton(), $this->info['description']);
		//Output the game header to the web page
		echo '<div style="float:left"><div>', $gameHeader, '</div><canvas></canvas><script>';
		//Add functionality to the like and dislike buttons
		echo "$(()=>{
		$('#like').click(()=>{
			createForm('play.php', {'g':'".$this->name."', 'rate':'likes'}, 'GET');
		});
		$('#dislike').click(()=>{
			createForm('play.php', {'g':'".$this->name."', 'rate':'dislikes'}, 'GET');
		});
		});\n\n";
		include $this->File; //Output the games code into the page
		echo '</script></div><div style="float:right">', $gameInfoBox, '</div>'; //Output the games information
	}

	public function testGame(){ //This is to be called in the place that the game screen will be
		//create the header for the game
		echo '<div style="width:1000px; float:left; margin:4px">';
		echo sprintf('<div><h1>%s <h2>by %s</h2></h1>', $this->name, $this->info['developer']);
		echo ' Thumbnail: <img src="'.$this->info['img'].'" width="120" height="60" alt="thumbnail" /></div>';
		//Make the game appear on the page
		echo '<canvas></canvas><script>';
		include $this->File; //Output the games code into the page
		echo '</script>';
		//show the information about the game
		echo '</div><div style="margin:10px; padding:5px; margin-top:50px; vertical-align:top; align-item:right; white-space:pre-wrap; width:auto">';
		$rating = $this->info['rating'];
		echo '<p>'.$this->info['description'].'<br />Rating: '.$rating['percentage'].'%<br />';
		echo 'likes: '.$rating['likes'].'<br />dislikes: '.$rating['dislikes'].'</p>';
		echo '</div><div style="clear:both">';
		echo 'Tags: '.implode(', ', $this->info['tags']);
		echo '<br />Date uploaded: '.$this->info['created'];
		echo '<br />Reported: '.$this->info['reported'].'</div>';
	}
}

class DownloadableGame extends Game {
	public function __construct($name, $test=false){
		parent::__construct($name, 'downloadable', $test);
	}

	public function showInfo(){ //This function is to be called where the information is to be displayed
		//Display the main information about the game
		echo '<div style="float:left; width:75%">';
		echo '<h1>'.$this->name.' </h1><h3>'.$this->info['developer'].'</h3>';
		//Create the like and dislike buttons
		echo '<button id="like">like</button><button id="dislike">dislike</button><br />';
		//Show the games images
		echo '<img id="mainImage" style="width:80%; float:left; margin:3px;" src="'.$this->info['images'][0].'" alt="Enlarged image here"/>';
		echo '<div id="otherImages" style="width:19%; float:left">Other images of the game: <br />';
		foreach($this->info['images'] as $img){
			echo '<img src="'.$img.'" style="width:99%;" alt="Images here" /><br />';
		}
		//Find similar games
		echo '</div><div class="h"><h2>Similar Games</h2>';
		$searchObj = new Search(['downloadable']);
		$similarGames = $searchObj->displayResult($_GET['g']);
		echo '<div id="similarGames">'.$similarGames.'</div></div>';
		//Get the side bar information
		echo '</div><div style="float:right; width:23%; margin:5px"><div class="h"><h3>Game rating: '.$this->info['rating']['percentage'].'%</h3>';
		echo $this->addReportButton().'<br />';
		echo '<p style="clear:both">'.$this->info['description'].'</p>'; //Show the description
		echo '<button id="downloadBtn">Download: '.$this->name.'</button>'; //Add download button
		echo '<p>Download size: '.$this->info['size'].', file name: '.$this->name.'</p>'; //Show download information
		//Add game requirements
		echo '<p><b>Requirements</b><br /><u>Minimum:</u><br />'.$this->info['minRequirements'].'<br /><u>';
		echo 'Recommended:</u><br />'.$this->info['recRequirements'].'</p>';
		//Add online and supprt info
		echo '<p>Online: '.$this->info['multiplayer'].'</p>';
		echo '<p>Operating systems supported: '.$this->info['platform'].'</p></div></div>';
	}

	public function download(){
		LogFunc($this->name.' downloaded'); //Log download
		//Add download to game info file
		$this->info['downloads'] += 1;
		$this->updateInfo();
		//Add the headers which give the information about the file to be downloaded
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($this->File).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize($this->File));
		flush();
		readfile($this->File);
		die();
	}
}

class Search {
	public function __construct($folders=['playable','downloadable'], $searchBar=false, $sortByOption=false, $pageDir='self', $defautText='', $filter=false, $placeholder='Search here...'){
		$this->folders = [];
		foreach($folders as $f){ //Get an array of all the folders to search in
			array_push($this->folders, '/opt/gameBloc/games/'.$f);
		}
		if($pageDir == 'self'){ //Get the target page
			$this->page = $_SERVER['PHP_SELF'];
		} else {
			$this->page = $pageDir;
		}
		//Store the objects variables
		$this->filterOption = $filter;
		$this->sortBy = $sortByOption;
		$this->placeholder = $placeholder;
		$this->defaultText = $defaultText;
		if($searchBar){ //Make the search bar if one is needed
			$this->makeSearchBar();
		}
	}

	private function search($searchTerm, $filterOptions, $sortBy, $admin){
		$searchResults = [];
		if($searchTerm == ''){
			$searchTerms = false;
		} else {
			$searchTerms = explode(' ', $searchTerm);
		}
		foreach($this->folders as $f){
			$subfolders = glob($f.'/*', GLOB_ONLYDIR);
			foreach($subfolders as $subfolder){
				$fileData = json_decode(file_get_contents("$subfolder/info.json"), true);
				$searchedFor = false;
				if($searchTerms == false){
					$searchedFor = true; //If there are no search terms include all results
				} else {
					foreach($searchTerms as $t){
						foreach($fileData as $i){
							$i = strtolower($i);
							$t = strtolower($t);
							if(strpos($i, $t) !== false or strpos($t, $i) !== false){
								$searchedFor = true;
								break;
							}
						}
					}
				}
				//If there are options to filter out some results, filter them
				if($this->filterOption and count($filterOptions) > 0){
					$display = false;
					if(isset($_GET['minrating']) and isset($_GET['maxrating'])){
						$rating = intval($filterOptions['rating']['percentage'])
						if(intval($_GET['minrating'])<$rating and intval($_GET['maxrating'])>$rating){
							$correctRating = true;
						} else {
							$correctRating = false;
						}
					} else {
						$correctRating = true;
					}
					if($correctRating){
						foreach($filterOptions as $key=>$val){ //val is the box that has been ticked
							//This checks if the game complies with the checked boxes
							if(strpos($fileData[$key], $val) !== false or strtolower($val) == 'all'){
								$display = true;
								break;
							}
						}
					}
				} else {
					$display = true;
				}
				if($searchedFor and $display){
					array_push($searchResults, new Game(basename($subfolder), basename($f), $admin));
				}
			}
		}
		sortGames($searchResults, $sortBy[0], $sortBy[1]);
		return $searchResults;
	}

	public function displayResult($searchTerm, $sortBy=['name', true], $admin=false){
		$results = $this->search($searchTerm, $this->getFilterInfo(), $sortBy, $admin);
		foreach($results as $r){
			$r->displayPreview();
		}
	}

	private function getFilterInfo(){
		$filters = [];
		foreach(['type', 'genre', 'multiplayer'] as $i){
			if(isset($_GET[$i])){
				$filters += [$i=>$_GET[$i]];
			}
		}
		return $filters;
	}

	private function makeSearchBar(){
		//create search element HTML code
		echo '<form method="GET" action="/game/search.php"><pre style="width:330px; display:inline-block; float:left; font-size:18px">';
		echo 'Search: <input style="height:50px" type="search" name="s" placeholder="Search here ..." required />';
		echo '<input style="height:50px; font-size:18px" type="submit" value="Search" required /></pre>';
		//If the sortBy attribute is true create the buttons to set how the search results are sorted
		if($this->sortBy){
			//Output the options to change wether the user can change the output options
			echo '<div style="width:220px; display:inline-block; float:left">';
			echo '<pre><input type="radio" name="orderBy"  value="alphabetically" />Order alphabetically<br />';
			echo '<input type="radio" name="orderBy"  value="date" />Order by newest first<br />';
			echo '<input type="checkbox" name="reverse" value="true" /> Reverse order</pre></div>';
		}
		if($this->filterOption){
			$this->createFilter();
		}
		echo '</div></form>';
	}

	private function createFilter(){ //settings: genre, type, players, rating
		//Create HTML code for the filter
		echo '<div style="display:inline-block; float:left; margin-right:5px"><button type="button" id="filterButton">Filter results</button></div>';
		echo '<div id="filter" style="display:inline-block; float:left; word-break:break-word; overflow:auto;">';
		//Filter the type
		echo '<pre>Type:		web<input type="checkbox" name="type" value="playable" />, ';
		echo 'downloadable<input type="checkbox" name="type" value="downloadable" /><br />';
		//Filter the genre
		echo 'Genre:		all<input type="checkbox" name="genre" value="all" />, shooter<input type="checkbox" name="genre" value="shooter" />, ';
		echo 'puzzle<input type="checkbox" name="genre" value="puzzle" />, stratergy<input type="checkbox" name="genre" value="stratergy" />, ';
		echo 'sports<input type="checkbox" name="genre" value="sports" />, platformer<input type="checkbox" name="genre" value="platformer" /><br />';
		//Filter the amount of players
		echo 'Players:	All amounts of players<input type="checkbox" name="multiplayer" value="all" />, ';
		echo 'singleplayer<input type="checkbox" name="multiplayer" value="none" />, ';
		echo 'local multiplayer<input type="checkbox" name="multiplayer" value="local" />, ';
		echo 'online multiplayer<input type="checkbox" name="multiplayer" value="online" /><br />';
		//Filter the rating
		echo 'Rating:	minimum <input type="number" name="minrating" value="0" min="0" max="100" />, ';
		echo 'maximum <input type="number" name="maxrating" value="100" min="0" max="100" /></pre></div>';
		//Create JS func that toggles visibility of filters
		echo '<script>$("#filterButton").click(()=>{$("#filter").toggle();})</script>';
	}
}

class FAQ {
	public function __construct($name, $createArticle=true){
		$this->location = $_SERVER['DOCUMENT_ROOT'].'/faq/FAQs/'.$name;
		$this->name = $name;
		if(file_exists($this->location)){ //If there is an FAQ get the information
			$this->data = file_get_contents($this->location);
			if($createArticle){
				$this->showArticle();
			}
		} else { //Show an error message if there is no FAQ
			echo '<div class="h"><header>FAQ file not found!</header></div>';
		}
	}

	private function showArticle(){
		//Output the article to the page
		echo '<div class="h"><header>'.str_replace('-', ' ', $this->name).'</header></div>';
		echo '<p>'.$this->data.'</p>';
	}

	static function allFAQs(){ //This is to be called where the FAQs will be displayed
		//Get all FAQs
		$FAQs = [];
		foreach(scandir($_SERVER['DOCUMENT_ROOT'].'/faq/FAQs') as $file){
			if(strpos($file, '.php') === false and strlen($file) > 2){
				array_push($FAQs, $file); //Add the FAQ to the FAQ array
			}
		}
		//Display all FAQs
		foreach($FAQs as $FAQ){
			echo '<a class="faq" href="/faq/viewFAQ.php?f='.$FAQ.'"><button>'.str_replace('-', ' ', $FAQ).'</button></a><br />';
		}
	}

	static function search($searchTerm){
		$result = false;
		$searchTerms = explode(' ', $searchTerm); //Split up the search terms
		$files = glob($_SERVER['DOCUMENT_ROOT'].'/faq/FAQs/*');
		foreach($files as $f){ //Iterate through each FAQ
			$searchedFor = false;
			foreach($searchTerms as $t){ //Check each search term against the FAQ to see if they are similar
				if(strpos(strtolower($f), strtolower($t)) !== false and strpos($f, '.php') === false and strlen($f) > 2){
					//If the FAQ has been searched for output a preview to the screen
					echo '<div class="h"><a href="viewFAQ.php?f='.basename($f).'"><h2>'.str_replace('-', ' ', basename($f)).'</h2></a></div>';
					$result = true;
					break;
				}
			}
		}
		if(!$result){
			//If there has been no results output the error message
			echo 'No search results found!';
		}
	}
}

class Report {
	public function __construct($gameName, $gameType, &$game){
		$this->name = $gameName;
		$this->gameType = $gameType;
		$this->gameObj = $game;
	}

	public function reportGame($reason){
		//Update the games information file with the reason for the report
		$this->gameObj->info['reported'] = $reason;
		$this->gameObj->updateInfo();
		//Append to the reports log file
		$f = fopen('/opt/gameBloc/Logs/_reports', 'a');
		fwrite($f, $this->name.' reported by '.$_SERVER['REMOTE_ADDR'].": $reason\n"); //Append to the '_reports' file
		fclose($f);
		LogFunc($this->name.' reported'); //Write to Log file
		$this->sendEmail($reason); //Email admins
	}

	private function sendEmail($reason){
		//Get the email info
		$emailHeader = 'A game ('.$this->name.') has been reported!';
		$emailBody = 'The game'.$this->name.' has been reported by someone for this reason: '.$reason.'\nThe users IP was '.$_SERVER['REMOTE_ADDR'];
		//Clean the email info
		$emailHeader = str_replace(" ", "___", $emailHeader);
		$emailBody = str_replace("\n", "N__N", str_replace(" ", "___", $emailBody));
		//Send the email
		exec("/opt/gameBloc/serverScripts/email.sh $emailHeader $emailBody");
	}
}

/*Functions*/
function LogFunc($info, $writeIP=false){
	$fileName = date('d-m-Y').'.log'; //Make the Log file called the current date
	$f = fopen('/opt/gameBloc/Logs/'.$fileName, 'a'); //This also creates the file if it doesnt exists
	fwrite($f, date('H:i:s').' '); //Write the time
	if($writeIP){
		fwrite($f, $_SERVER['REMOTE_ADDR'].' '); //Write the users IP address
	}
	fwrite($f, "$info\n"); //Write the information passed through the info parameter
	fclose($f);
}

function sortGames(&$arr, $sortBy, $ascending=true){
	$arr = mergeSort($arr, $sortBy); //Perform a merge sort on the list
	if(!$ascending){
		$arr = array_reverse($arr); //Reverse the array if the games are in decending order
	}	
}

function mergeSort($arr, $sortBy){
	if(count($arr) < 2){
		return $arr; //If there is one or no items in the list return it
	} else {
		$finalArray = [];
		$midpoint = (int)(count($arr)/2); //Find the midpoint
		//Merge sort the two halves of the array
		$a = mergeSort(array_slice($arr, $midpoint), $sortBy);
		$b = mergeSort(array_slice($arr, 0, $midpoint), $sortBy);
		$i=$j=0;
		while($i<count($a) and $j<count($b)){
			//Compare the items in the list and add the correct item
			if($a[$i]->info[$sortBy] > $b[$j]->info[$sortBy]){
				array_push($finalArray, $a[$i]);
				$i++;
			} else {
				array_push($finalArray, $b[$j]);
				$j++;
			}
		}
		//Add any remaining parts of the list
		while($i<count($a)){
			array_push($finalArray, $a[$i]);
			$i++;
		}
		while($j<count($b)){
			array_push($finalArray, $b[$j]);
			$j++;
		}
		//Return the sorted array
		return $finalArray;
	}
}

//When the random game button is pressed it links to the play game page URL with ?rnd=true then this function is called
function rndGame(){ //This selects a random game
	//Get all of the games
	$allGames = scandir('/opt/gameBloc/games/downloadable') + scandir('/opt/gameBloc/games/playable');
	$i = rand(0, count($allGames)); //Select a random index of the list
	$gameName = basename($allGames[$i]); //Get the name of the game
	$gameInfo = json_decode(file_get_contents($allGames[$i].'/info.json'), true); //Get the game information
	if($gameInfo['type'] == 'downloadable'){ //Get the correct URL to go to
		$url = '/game/download.php';
	} else {
		$url = '/game/play.php';
	}
	//Redirect to the random game
	echo "<script>createForm($url, {'g':'$gameName'}, 'GET')</script>";
}


//Add the nececcary javasctipt code to the page
echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>';
echo'<script src="/main.js"></script>';

?>
