//This function creates a form then submits it automatically
//This lets the page redirect the page with post/get information
createForm = (url, data, method)=>{
    //Start building the HTML of the form
    var form = '<form id="f" action="' + url + '" method="' + method + '">';
    for(let key in data){ //Add the data into the form
        form += '<input type="hidden" name="' + key + '" value="' + data[key] + '">';
    }
    form += '</form>'; //Finish the forms HTML
    $('body').append(form); //Put the form on the page
    $('#f').submit(); //Submit the form
};

//Check if there is a navigation bar
$(()=>{
    if($('#navBar').length){
	//create the functionality to the buttons
	var buttons = [['home','home.php', {}], ['downloadLink','game/search.php', {'type':'downloadable', 's':''}], ['web','game/search.php', {'type':'web', 's':''}], ['shooter','game/search.php', {'s':'shooters'}], ['rnd','game/search.php', {'rnd':''}], ['uploadLink','game/upload.php', {}]];
	buttons.forEach((i)=>{
	    //create a function that redirects the page to the correct link when the button is pressed
	    $('#'+i[0]).click(()=>{
		createForm('/'+i[1], i[2], 'GET');
	    });
	});
    }
});
