<!DOCTYPE html>

<?php
	require $_SERVER['DOCUMENT_ROOT'].'/classes.php';
	if(isset($_GET['g'])){
		$g = new DownloadableGame($_GET['g']); //Create the games class
		if(isset($_GET['rate'])){
			$g->rate($_GET['rate']);
		}
	}
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="/main.css">
		<title>Download game name</title>
		<script>
			$(()=>{ //This creates a function that runs when the whole web page has loaded
				let gameName = '<?php echo $g->name; ?>';
				//Add the download functionality to the download button
				$('#downloadBtn').click(()=>{
					createForm('download.php', {'g':gameName, 'd':'true'}, 'GET');
				});
				//Handle liking and disliking
				$('#like').click(()=>{
					createForm('download.php', {'g':gameName, 'rate':'likes'}, 'GET');
				});
				$('#dislike').click(()=>{
					createForm('download.php', {'g':gameName, 'rate':'dislikes'}, 'GET');
				});
				//Handle image switching
				$('#otherImages img').click(e=>{
					$('#mainImage').attr('src', e.target.src);
				})
			});
		</script>
	</head>
	<body>
		<?php
			require $_SERVER['DOCUMENT_ROOT'].'/navBar.html';
			if(isset($g)){
				$g->showInfo(); //Show the games information
				if($_GET['d'] == 'true'){
					$g->download(); //Download the game
				}
			} else {
				echo '<header>No Game Found!</header>';
			}
			require $_SERVER['DOCUMENT_ROOT'].'/footer.html';
		?>
	</body>
</html>
