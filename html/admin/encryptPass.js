randInt = (min, max)=>{
    return Math.floor(Math.random() * (max-min) + min);
};

encrypt = (text, key)=>{
  //Create an empty string which will be added to in the function
  newStr = '';
  //Add the padding at the start of the string
  //parseInt(key.slice(0,2), 36) gets the length of the padding
  for (var i=0; i<parseInt(key.slice(0,2), 36); i++) {
	  //add one character to the string
    newStr += String.fromCharCode(randInt(10, 333)); //This adds a random character to the start of the string
  }
  padding = parseInt(key.slice(2,4), 36); //Store the padding to be used at the end of the encrypted string
  key = key.slice(4, key.length); //Only keep the part of the key that is used for encrypting the password
  //This goes through each letter one by one and gets the new value of it
  for(i=0; i<text.length; i++){
    let unicodeVal = text.charCodeAt(i); //get the unicode of the current character
    let diff = parseInt(key[i%key.length], 36); //Use the key to get the difference in the unicode value of the new character from the old one
    let newUnicodeVal = unicodeVal+diff; //Get the unicode value
    newStr += String.fromCharCode(newUnicodeVal); //Convert the unicode value to a character and add it to newStr
  }
  //Add padding to the end of the string
  for (i=0; i<padding; i++) {
    newStr += String.fromCharCode(randInt(10, 333));
  }
  return newStr;
};
