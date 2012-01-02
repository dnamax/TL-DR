//chrome.browserAction.setBadgeText({text: 'T'});
//alert ("url");
var description;
var metas = document.getElementsByTagName('meta');
for (var x=0,y=metas.length; x<y; x++) {
  if (metas[x].name.toLowerCase() == "description") {
    description = metas[x];
  }
}
//alert(description.content); // Description Meta Tag

//creatediv ('id100', 'Hello World', 200,200,200,200);

var popups = chrome.extension.getViews({type: "popup"});
if (popups.length != 0) {
    var popup = popups[0];
    popup.myFunction(tablink);
}


function creatediv(id, html, width, height, left, top) {
var newdiv = document.createElement('div'); 
newdiv.setAttribute('id', id); 
if (width) { newdiv.style.width = 300; } 
if (height) { newdiv.style.height = 300; } 
if ((left || top) || (left && top)) { newdiv.style.position = "absolute"; 
    if (left) { newdiv.style.left = left; } 
    if (top) { newdiv.style.top = top; } } newdiv.style.background = "#00C"; 
newdiv.style.border = "4px solid #000"; 
if (html) { newdiv.innerHTML = html; } 
else { newdiv.innerHTML = "nothing"; } 
document.body.appendChild(newdiv);
}

