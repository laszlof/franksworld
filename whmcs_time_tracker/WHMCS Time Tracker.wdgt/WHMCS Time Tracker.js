// WHMCS Time Tracker v1.0.0 written by Frank Laszlo
// Originally based on Freshbooks Time Tracker

// Copyright (c) 2008 2ndSite Inc. (www.freshbooks.com)
// Licenced under the MIT license (see MITLICENSE.txt).

debug = false;
xmlTimeout = 15 * 1000; // Wait this many milliseconds for WHMCS to reply before cancelling an XML call.

//
// Just sets the given named element's visibility style accordingly.
function setElementVisibility(elementName, setVisible)
{
	$("#"+elementName).css("visibility",setVisible ? "visible" : "hidden");
}


//
// Function: formatTwoDigits(number)
// Format a number as one or two digits with a leading zero if needed
//
// number: The number to format
//
// Returns the formatted number as a string.
//
function formatTwoDigits(number)
{
    var digits = number.toString(10);

    // Add a leading zero if it's only one digit long
    if (digits.length == 1) {
        digits = "0" + digits;
    }
    
    return digits;
}

function updateTimerDisplay()
{
	var hours, minutes, clockedTime = Math.floor(clocker.getTime()/1000);

	// Same for hours, minutes, and seconds
	hours = Math.floor(clockedTime / (60*60));
	clockedTime -= hours * (60*60);
	
	minutes = Math.floor(clockedTime / 60);
	clockedTime -= minutes * 60;

	$("#count")[0].innerText = hours + ":" + formatTwoDigits(minutes) + ":" + formatTwoDigits(clockedTime);
}

var updateTimerDisplayInterval;

//
// Function: startDisplayUpdateTimer()
// Start the interval timer to update the countdown once a second
//
function startDisplayUpdateTimer()
{
    updateTimerDisplay();

    if (!updateTimerDisplayInterval)
        updateTimerDisplayInterval = setInterval(updateTimerDisplay, 200);
}

//
// Function: stopDisplayUpdateTimer()
// Remove the interval timer
//
function stopDisplayUpdateTimer()
{
    if (updateTimerDisplayInterval) {
        clearInterval(updateTimerDisplayInterval);
        updateTimerDisplayInterval = null;
    }
}

//
// Function: load()
// Called by HTML body element's onload event when the widget is ready to start
//
function load()
{
    setupParts();

	$("#url").text("");
	$("#submitStatus").text("");
	$("#statusmsg").text("");

	sync();

	clocker.setStartStopButtons();
	setElementVisibility("updatedTime", false);
	startDisplayUpdateTimer();

	// Default to showing the back when there's no auth info
	var username = document.getElementById("username").value;
	var password = document.getElementById("password").value;
	var whmcsurl = document.getElementById("whmcsurl").value;
	if (!validate(username) || !validate(password) || !validate(whmcsurl))
	{
		setTimeout(showBack, 250);
		return;
	}
}

//
// Function: remove()
// Called when the widget has been removed from the Dashboard
//
function remove()
{
    // Stop any timers to prevent CPU usage
    // Remove any preferences as needed
    widget.setPreferenceForKey(null, createInstancePreferenceKey("clocker"));
	widget.setPreferenceForKey(null, createInstancePreferenceKey("username"));
	widget.setPreferenceForKey(null, createInstancePreferenceKey("password"));
	widget.setPreferenceForKey(null, createInstancePreferenceKey("whmcsurl"));
	widget.setPreferenceForKey(null, createInstancePreferenceKey("Notes"));
	widget.setPreferenceForKey(null, createInstancePreferenceKey("myClients"));
    stopDisplayUpdateTimer();
}

//
// Function: hide()
// Called when the widget has been hidden
//
function hide()
{
    stopDisplayUpdateTimer();
	widget.setPreferenceForKey($("#Notes")[0].value, createInstancePreferenceKey("Notes"));
}

//
// Function: show()
// Called when the widget has been shown
//
function show()
{
	startDisplayUpdateTimer();
}

//
// Function: sync()
// Called when the widget has been synchronized with .Mac
//
function sync()
{
	var c = widget.preferenceForKey(createInstancePreferenceKey("clocker"));
	var uname = widget.preferenceForKey(createInstancePreferenceKey("username"));
	var pw = widget.preferenceForKey(createInstancePreferenceKey("password"));
	var url = widget.preferenceForKey(createInstancePreferenceKey("whmcsurl"));
	var n = widget.preferenceForKey(createInstancePreferenceKey("Notes"));
	var cl = widget.preferenceForKey(createInstancePreferenceKey("myClients"));

	if (c) clocker.setState(c);
	if (uname) {
		document.getElementById("username").value = uname;
	}
	if (pw) {
		document.getElementById("password").value = pw;
	}
	if (url) {
		document.getElementById("whmcsurl").value = url;
		$("#url").text(url);
	}
	if (n) $("#Notes")[0].value = n;
	if (cl) {
		myClients = JSON.parse(cl);
		fillOutClients();
	} 
}

//
// Function: showBack(event)
// Called when the info button is clicked to show the back of the widget
//
// event: onClick event from the info button
//
function showBack(event)
{
    var front = document.getElementById("front");
    var back = document.getElementById("back");

    if (window.widget) {
        widget.prepareForTransition("ToBack");
    }

    front.style.display = "none";
    back.style.display = "block";

    if (window.widget) {
        setTimeout('widget.performTransition();', 0);
    }
	
	$("#username")[0].focus();
}

//
// Function: showFront(event)
// Called when the done button is clicked from the back of the widget
//
// event: onClick event from the done button
//
function showFront(event)
{
    var front = document.getElementById("front");
    var back = document.getElementById("back");

    if (window.widget) {
        widget.prepareForTransition("ToFront");
    }

    front.style.display="block";
    back.style.display="none";

    if (window.widget) {
        setTimeout('widget.performTransition();', 0);
    }
}

if (window.widget) {
    widget.onremove = remove;
    widget.onhide = hide;
    widget.onshow = show;
    widget.onsync = sync;
}


function clickedStartStop(event)
{
	if (clocker.clockRunning)
	{
		clocker.stopClock();
		widget.setPreferenceForKey(clocker.getState(), createInstancePreferenceKey("clocker"));
	}
	else
	{
		if ($("#updatedTime").css("visibility") == "visible") $("#updatedTime")[0].blur();
		clocker.startClock();
		widget.setPreferenceForKey(clocker.getState(), createInstancePreferenceKey("clocker"));
	}
}

function validate(varname) {
	if (varname.length == 0) return false;
	return true;
}

// Since we return objects instead of arrays, it's handy to know if there's anyone home.
function hasItems(o) {
	if (!o) return false;
	for (k in o) {
		return true;
	}
	return false;
}

function errorLoading(list) {
	if (list.status.text == "Timeout") {
		setStatus("statusmsg", "Connection timed out.  Check your password!");
	} else if (list.status.text == "HTTP status 400") {
		setStatus("statusmsg", "Check your site name and/or password!");
	} else if (list.status.text == "HTTP status 404") {
		setStatus("statusmsg", "Check your site name!");
	} else {
		setStatus("statusmsg", list.status.text);
	}

	hourglass.stop();
	$("#done")[0].object.setEnabled(true);
}

function fillOutClients()
{
	var cl = $("#Clients")[0];
	cl.options.length = 0;
	var sortedList = [];
	for (var i in myClients) {
		if (myClients[i] != null) {
			var id = myClients[i].id
			var o = new Option("", id);
			o.innerHTML = myClients[i].lastname+', '+myClients[i].firstname+' ('+id+')';
			cl.add(o);
		}
		
	}
}	

function loadClients(event)
{
	var username = $("#username")[0].value;
	var password = $("#password")[0].value;
	var whmcsurl = $("#whmcsurl")[0].value;
	
	if (!validate(username)) { setStatus("statusmsg", "Invalid username"); return false; }
	if (!validate(password)) { setStatus("statusmsg", "Invalid password"); return false; }
	if (!validate(whmcsurl)) { setStatus("statusmsg", "Invalid WHMCS URL"); return false; }

	widget.setPreferenceForKey(username, createInstancePreferenceKey("username"));
	widget.setPreferenceForKey(password, createInstancePreferenceKey("password"));
	widget.setPreferenceForKey(whmcsurl, createInstancePreferenceKey("whmcsurl"));

	var clients = [];


	fullUrl = whmcsurl + '/timetracker.php';
	data = 'username='+username+'&password='+MD5(password)+'&action=getclients';
	setStatus("statusmsg", "Loading Clients...", false, xmlTimeout);
	hourglass.start();
	$("#done")[0].object.setEnabled(false);

	var http = new XMLHttpRequest();
	http.onreadystatechange = function() {
		if (this.status == 200 && this.readyState == 4) {
			if (this.responseXML != null) {
				var xmlDoc = this.responseXML;
				if (xmlDoc.getElementsByTagName('status')[0].firstChild.nodeValue != 'success') {
					var err = xmlDoc.getElementsByTagName('data')[0].firstChild.nodeValue;
					setStatus("statusmsg", err);
					return false;
				} else {
					// Clear out any existing clients
					widget.setPreferenceForKey(null, createInstancePreferenceKey("myClients"));
					data = xmlDoc.getElementsByTagName('client');
					if (data.length) document.getElementById('Clients').options.length = 0;
					for (var i = 0; i < data.length; i++) {
						rec = data[i];
						cid = rec.getElementsByTagName('id')[0].firstChild.nodeValue;
						fname = rec.getElementsByTagName('fname')[0].firstChild.nodeValue;
						lname = rec.getElementsByTagName('lname')[0].firstChild.nodeValue;
						clients[i] = {"id": cid, "firstname": fname, "lastname": lname};
						setTimeout("showFront();",500);
						hourglass.stop();
						$("#done")[0].object.setEnabled(true);
					}
					widget.setPreferenceForKey(JSON.stringify(clients), createInstancePreferenceKey("myClients"));
					myClients = clients;
					fillOutClients();
				}
			}
				
		}
	}
	http.open("GET", fullUrl+"?"+data, false);
	http.send(null);	
}
		



function submitHours(event)
{
	var username = $("#username")[0].value;
	var password = $("#password")[0].value;
	var whmcsurl = $("#whmcsurl")[0].value;

	if (!validate(username) || !validate(password) || !validate(whmcsurl))
	{
		// Um, we need some auth info first!
		// We shouldn't be able to get here, but it doesn't hurt. :)
		showBack();
		return;
	}


    // Read time; we must log in (fractional) hours.
	// 3600 seconds per hour, 1000 milliseconds per second
	var loggedTime = clocker.getTime() / 3600000;

	// Read Client
	var clientId = $("#Clients")[0].value;

	// Read Rate
	var rate = $("#Rate")[0].value

	// Read notes
	var notes = $("#Notes")[0].value;
	notes = notes.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");

	// Notify user that we're posting
	$("#Clients")[0].disabled = true;
	$("#Rate")[0].disabled = true;
	$("#Notes")[0].disabled = true;
	$("#submithours")[0].object.setEnabled(false);

	function enableInputs() {
		$("#Clients")[0].disabled = false;
		$("#Rate")[0].disabled = false;
		$("#Notes")[0].disabled = false;
		$("#submithours")[0].object.setEnabled(true);
	};

	var entryCreateTimeout = setTimeout( function () {
			setStatus("submitStatus", "Timed out. :(");
			r.abort();
			enableInputs();
			entryCreateTimeout = null;
		}, xmlTimeout);

	// Build request
	var fullUrl = whmcsurl + '/timetracker.php';
	data = 'username='+username+'&password='+MD5(password)+'&action=submittime&cid='+clientId+'&notes='+notes+'&hours='+loggedTime+'&rate='+rate;
	// Notify the user when we're done posting
	var http = new XMLHttpRequest();
        http.onreadystatechange = function() {
                if (this.status == 200 && this.readyState == 4) {
			if (entryCreateTimeout) {
				clearTimeout(entryCreateTimeout);
				entryCreateTimeout = null;
			}
                        if (this.responseXML != null) {
                                var xmlDoc = this.responseXML;
                                if (xmlDoc.getElementsByTagName('status')[0].firstChild.nodeValue != 'success') {
                                        var err = xmlDoc.getElementsByTagName('data')[0].firstChild.nodeValue;
					enableInputs();
	                                setStatus("submitStatus", $("error",err));
                                        return false;
                                } else {
					enableInputs();
			                clocker.reset();
                        	        widget.setPreferenceForKey(clocker.getState(), createInstancePreferenceKey("clocker"));
                                	setStatus("submitStatus", "Hours submitted!");
	                                $("#Notes")[0].value = "";
        	                        widget.setPreferenceForKey(null, createInstancePreferenceKey("Notes"));
                                }
                        }

                }
        }

	// Don't count while we're submitting
	clocker.stopClock();
	widget.setPreferenceForKey(clocker.getState(), createInstancePreferenceKey("clocker"));

	// Fade in feedback text
	setStatus("submitStatus", "Submitting...", false, xmlTimeout);
	
	http.open("GET", fullUrl+"?"+data, false);
	http.send(null);
	
}




function headToWHMCS(event)
{
    widget.openURL("http://www.whmcs.com");
}


function headToMyWHMCS(event)
{
    widget.openURL("https://" + $("#whmcsurl")[0].value);
}


function loginKeypress(event)
{
	switch (event.keyCode) {
		case 3:
		case 13:
			//return loadProjects(event);
			return loadClients(event);
	}
	return true;
}


function enterToSubmitHours(event)
{
	switch (event.keyCode) {
		case 3: // Use enter to submit hours instead of performing a CR in the field
			return submitHours(event);
	}
	return true;
}


//
// Returns true if the supplied timestring is in the correct format.
function isValidTime(timestring)
{
	var ta = timestring.match(/^(\d*):(\d{0,2}):(\d{0,2})$/);
	if (ta == null) {
		return false;
	}
	if (parseInt(ta[2]) > 59 || parseInt(ta[3]) > 59)
		return false;
	return true;
}

//
// See what the result would be if we insert the users character into the string.
// Kind of silly, but it seems to be the most effective way to validate input!
function playKeypress(event,textbox)
{
	var curTime = textbox.value;
	var sstart = textbox.selectionStart;
	var send = textbox.selectionEnd;
	var c;
	if (event.charCode == 8)
	{	// Backspace
		c = "";
		if (sstart == send) { // NO selection
			if (sstart > 0) sstart--;
		}
	}
	else if (event.charCode == 63272)
	{	// Delete
		c = "";
		if (sstart == send) { // NO selection
			if (send < textbox.value.length-1) send++;
		}
	}
	else c = String.fromCharCode(event.charCode);

	return curTime.substr(0,sstart) + c + curTime.substr(send);
}

//
// Initiate editing of the amount of time logged in the clock.
function editClockedTime(event)
{
	var ut = $("#updatedTime")[0];

	clocker.stopClock();
	setElementVisibility("count",false);
	setElementVisibility("updatedTime",true);
	$("#submithours")[0].object.setEnabled(false);
	var curtime = $("#count")[0].innerHTML;
	ut.value = isValidTime(curtime) ? curtime : "0:00:00";
	ut.focus();
	ut.selectionStart = 0;
	ut.selectionEnd = ut.value.indexOf(":");
}

function validateClockedKey(event)
{
	if (event.ctrlKey || event.altKey) return true;
	switch (event.keyCode) {
		case 3:
		case 13: // Enter and Return
			event.preventDefault();
			$("#updatedTime")[0].blur();
			return false;
		case 27: // Esc
			event.preventDefault();
			$("#updatedTime")[0].value = $("#count")[0].innerHTML;
			$("#updatedTime")[0].blur();
			return false;
		case 63232: // Arrow keys
		case 63233:
		case 63234:
		case 63235:
			return true;
		case 8:     // Backspace -- We need to validate these keypresses, too
		case 63272: // Delete
		case 48:
		case 49:
		case 50:
		case 51:
		case 52:
		case 53:
		case 54:
		case 55:
		case 56:
		case 57:
		case 58:
			return validateUpdatedTime(event);
		case 9:
			tabToNextField();
			// Intentional fall-through
		default:
			event.preventDefault();
			return false;
	}
}

function updateClockedTime(event,throwaway)
{
	function map(f,a) { for (var x in a) { a[x] = f(a[x]); } return a; }
	function getInt(x) { var y = parseInt(x,10); return isNaN(y) ? 0 : y; }
    // Insert Code Here
	setElementVisibility("count",true);
	setElementVisibility("updatedTime",false);
	$("#submithours")[0].object.setEnabled(true);

	if (throwaway) return;

	// Update clocked time with what we have...
	var ts = $("#updatedTime")[0].value;
	var ta = map(getInt, ts.split(":"));
	// Apparently it's possible to get bad data past the input validator
	// (namely, you can select-all and delete the whole time display).  If
	// something Really Silly like that happens, just set things to 0.
	if (!ta[0]) { ta[0] = 0; }
	if (!ta[1]) { ta[1] = 0; }
	if (!ta[2]) { ta[2] = 0; }
	clocker.stopClock(); // Just to make sure
	clocker.millisecondsClocked = 1000 * (ta[2] + 60 * (ta[1] + 60 * ta[0]));	
	widget.setPreferenceForKey(clocker.getState(), createInstancePreferenceKey("clocker"));
}

function tabToNextField()
{
	var ut = $("#updatedTime")[0];
	var startOfNextNumber = ut.value.indexOf(":",ut.selectionStart)+1;
	if (startOfNextNumber < 0) return;
	var endOfNextNumber = ut.value.indexOf(":",startOfNextNumber);
	ut.selectionStart = startOfNextNumber;
	ut.selectionEnd = endOfNextNumber > -1 ? endOfNextNumber : ut.value.length;
}

function validateUpdatedTime(event)
{
	var newTime = playKeypress(event,$("#updatedTime")[0]);
	var ut = $("#updatedTime")[0];

	if (isValidTime(newTime)) {
		return true;
	} else if ((ut.selectionStart == ut.selectionEnd) &&
				(ut.value.substr(ut.selectionStart,1) == ":") &&
				(event.keyCode == 58) ) {
		tabToNextField();
	}

	event.preventDefault();
	return false;
}


function addTimeStampToNotes(event)
{
	var n = $("#Notes")[0];
	var notes = n.value;
	var d = new Date();
	var h = d.getHours();
	var m = d.getMinutes();
	var ampm;
	// Noon is PM
	if (h == 12) {
		ampm = "PM";
	// Midnight is 12AM
	} else if (h == 0) {
		ampm = "AM";
		h = 12;
	// Things over 12 are in the afternoon.
	} else if (h > 12) {
		h %= 12;
		ampm = "PM";
	// Anything else is in the morning
	} else {
		ampm = "AM";
	}
	var ts = h + ":" + formatTwoDigits(m) + ampm;
	$("#Notes")[0].value = notes.substr(0,n.selectionStart) + ts + notes.substr(n.selectionEnd);
	n.selectionStart = n.selectionEnd = n.selectionStart + ts.length;
}
