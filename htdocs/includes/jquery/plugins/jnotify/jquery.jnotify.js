/*!
 * jNotify jQuery Plug-in
 *
 * Copyright 2010 Giva, Inc. (http://www.givainc.com/labs/) 
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * 	http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Date: 2010-09-30
 * Rev:  1.1.00
 */
;(function($){
	$.jnotify = function (m, o, d){
		return new jNotify(m, o, d);
	};

	// set the version of the plug-in
	$.jnotify.version = "1.1.00";
	
	var $jnotify, queue = [], count = 0, playing = false, paused = false, queuedId, queuedNote, 
		// define default settings
		defaults = {
			// define core settings
			  type: ""                                  // if a type is specified, then an additional class of classNotification + type is created for each notification
			, delay: 2000                               // the default time to show each notification (in milliseconds)
			, sticky: false                             // determines if the message should be considered "sticky" (user must manually close notification)
			, closeLabel: "&times;"                     // the HTML to use for the "Close" link
			, showClose: true                           // determines if the "Close" link should be shown if notification is also sticky
			, fadeSpeed: 1000                           // the speed to fade messages out (in milliseconds)
			, slideSpeed: 250                           // the speed used to slide messages out (in milliseconds)
			
			// define the class statements
			, classContainer: "jnotify-container"       // className to use for the outer most container--this is where all the notifications appear
			, classNotification: "jnotify-notification" // className of the individual notification containers
			, classBackground: "jnotify-background"     // className of the background layer for each notification container
			, classClose: "jnotify-close"               // className to use for the "Close" link
			, classMessage: "jnotify-message"           // className to use for the actual notification text container--this is where the message is actually written
	
			// event handlers
			, init: null                                // callback that occurs when the main jnotify container is created
			, create: null                              // callback that occurs when when the note is created (occurs just before appearing in DOM)
			, beforeRemove: null                        // callback that occurs when before the notification starts to fade away
			, remove: null                              // callback that occurs when notification is removed
			, transition: null                          // allows you to overwrite how the transitions between messages are handled
			                                            // receives the following arguments:
			                                            //   container - jQuery object containing the notification
			                                            //   message   - jQuery object of the actual message
			                                            //   count     - the number of items left in queue
			                                            //   callback  - a function you must execute once your transition has executed
			                                            //   options   - the options used for this jnotify instance
		};

	// override the defaults
	$.jnotify.setup = function (o){
		defaults = $.extend({}, defaults, o) ;
	};

	$.jnotify.play = function (f, d){
		if( playing && (f !== true ) || (queue.length == 0) ) return;
		playing = true;
		
		// get first note
		var note = queue.shift();
		queuedNote = note;

		// determine delay to use
		var delay = (arguments.length >= 2) ? parseInt(d, 10) : note.options.delay;
		
		// run delay before removing message
		queuedId = setTimeout(function(){
			// clear timeout id
			queuedId = 0;
			note.remove(function (){
				// makr that the queue is empty
				if( queue.length == 0 ) playing = false;
				// force playing the next item in queue
				else if( !paused ) $.jnotify.play(true);
			});
		}, delay);
	};

	$.jnotify.pause = function(){
		clearTimeout(queuedId);
		// push the item back into the queue
		if( queuedId ) queue.unshift(queuedNote);
		// mark that we're playing (so it doesn't automatically start playing)
		paused = playing = true;
  }

	$.jnotify.resume = function(){
		// mark that we're no longer pause
		paused = false;

		// resume playing
		$.jnotify.play(true, 0);
  }

	
	function jNotify(message, options){
		// a reference to the jNotify object
		var self = this, TO = typeof options;

		if( TO == "number" ){
			options = $.extend({}, defaults, {delay: options});
		} else if( TO == "boolean" ){
			options = $.extend({}, defaults, {sticky: true}) ;
		} else if( TO == "string" ){
			options = $.extend({}, defaults, {type: options, delay: ((arguments.length > 2) && (typeof arguments[2] == "number")) ? arguments[2] : defaults.delay, sticky: ((arguments.length > 2) && (typeof arguments[2] == "boolean")) ? arguments[2] : defaults.sticky}) ;
		} else {
			options = $.extend({}, defaults, options);
		}
		
		// store the options
		this.options = options;
		
		// if the container doesn't exist, create it
		if( !$jnotify ){
			// we want to use one single container, so always use the default container class
			$jnotify = $('<div class="' + defaults.classContainer + '" />').appendTo("body");
			if( $.isFunction(options.init) ) options.init.apply(self, [$jnotify]);
		} 
		
		// create the notification
		function create(message){
			var html = '<div class="' + options.classNotification + (options.type.length ? (" " + options.classNotification + "-" + options.type) : "") + '">'
			         + '<div class="' + options.classBackground + '"></div>'
			         + (options.sticky && options.showClose ? ('<a class="' + options.classClose + '">' + options.closeLabel + '</a>') : '')
			         + '<div class="' + options.classMessage + '">'
			         + '<div>' + message + '</div>'
			         + '</div></div>';

			// increase the counter tracking the notification instances
			count++;
			
			// create the note
			var $note = $(html);
			
			if( options.sticky ){
				// add click handler to remove the sticky notification
				$note.find("a." + options.classClose).bind("click.jnotify", function (){
					self.remove();
				});
			}

			// run callback
			if( $.isFunction(options.create) ) options.create.apply(self, [$note]);

			// return the new note			
			return $note.appendTo($jnotify);
		}

		// remove the notification		
		this.remove = function (callback){
			var $msg = $note.find("." + options.classMessage), $parent = $msg.parent();
			// remove message from counter
			var index = count--;

			// run callback
			if( $.isFunction(options.beforeRemove) ) options.beforeRemove.apply(self, [$msg]);
			
			// cleans up notification
			function finished(){
				// remove the parent container
				$parent.remove();
				
				// if there's a callback, run it
				if( $.isFunction(callback) ) callback.apply(self, [$msg]);
				if( $.isFunction(options.remove) ) options.remove.apply(self, [$msg]);
			}

			// check if a custom transition has been specified
			if( $.isFunction(options.transition) ) options.transition.apply(self, [$parent, $msg, index, finished, options]);
			else {
				$msg.fadeTo(options.fadeSpeed, 0.01, function (){
					// if last item, just remove
					if( index <= 1 ) finished();
					// slide the parent closed
					else $parent.slideUp(options.slideSpeed, finished);
				});
				
				// if the last notification, fade out the container
				if( count <= 0 ) $parent.fadeOut(options.fadeSpeed);
			}
		}
		
		// create the note
		var $note = create(message);
		
		// if not a sticky, add to show queue
		if( !options.sticky ){
			// add the message to the queue
			queue.push(this);
			// play queue
			$.jnotify.play();
		}

		return this;
	};

})(jQuery);
