/*
   Copyright (c) 2007-9, iUI Project Members
   See LICENSE.txt for licensing terms
 */

// requires querySelectorAll, therefore iPhone OS 2.x or later
// or Safari 3.x or later

(function() {

var eventNames = ['blur', 'focus', 'load', 'unload', 'beforetransition', 'aftertransition' ];

// Using DOMContentLoaded so this loads before the onload in iui.js -- need a better method
// We need to register before iui's main onload handler so we can get the 'load' and 'focus' events
// for the default 'page' (view).
//
addEventListener("DOMContentLoaded", function(event)
{
	document.body.addEventListener('beforeinsert', logEvent, false);
	document.body.addEventListener('afterinsert', afterInsert, false);
// This will register event handlers on all initial nodes
// We'll also need to register handlers on inserted (via ajax) nodes
// To do that we'll need to use the beforeInsert event
	nodes = document.querySelectorAll("body > *:not(.toolbar)");
	for (var i = 0; i  < nodes.length  ; i++)
	{
		registerAllEvents(nodes[i]);
	}
}, false);

function registerAllEvents(node)
{
	for (var i = 0; i  < eventNames.length  ; i++)
	{
		console.log("addlistener: " + eventNames[i] + " on #" + node.id + " = " + node);
		node.addEventListener(eventNames[i], logEvent, false);
	}
}

function afterInsert(e)
{
	logEvent(e);
	registerAllEvents(e.insertedNode);	// Set event handlers on newly added node
}

function logEvent(e)
{
	console.log("logEvent type: " + e.type + "  target " + e.target.tagName + "#" + e.target.id);
	if (e.type == "beforetransition" || e.type == "aftertransition")
	{
		console.log("  out trans = " + e.out);
	}
	else if (e.type == "beforeinsert") {
		console.log("  fragment = " + e.fragment);
	}
	else if (e.type == "afterinsert") {
		console.log("  node = " + e.insertedNode);
	}
}


})();
