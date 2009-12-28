var iWebkit;

if (!iWebkit) {
	
	iWebkit = window.onload = function () {
		
		iWebkit.checkboxHeight = "25";
		iWebkit.radioHeight = "25";
		iWebkit.autolistNumVisible = 10;
	
		function url() {
			var a = document.getElementsByTagName("a");
			for (var i = 0; i < a.length;i++) {
				if (a[i].className.match("noeffect")) {
				}
				else {
					a[i].onclick = function () {
						window.location = this.getAttribute("href");
						return false;
					};
				}
			}
		}
		
		function hideURLbar() {
			window.scrollTo(0, 0.9);
		}
		
	    iWebkit.popup = function () {
	        window.scrollTo(0, 9999);
	        var o_popup = document.getElementById(arguments[0]);
	        var o_frame = o_popup.getElementsByClassName('confirm_screen')[0];
	        o_frame.className = 'confirm_screenopen'; 
	        var b = document.getElementById("cover");
	        b.className = "cover";
			b.style.height = document.height + "px";
        };

        iWebkit.closepopup = function () {
	        var o_parent = arguments[0].toElement;
	        var b_found = false;
	        do {
		        o_parent = o_parent.parentNode;
		        if (o_parent.tagName.toLowerCase() == 'div' && o_parent.className.toLowerCase() == 'popup') {
			        b_found = true;
			        break;
   	         }
	        } while (o_parent.parentNode);
	
	        if (b_found === false) {
                return false;
	        }
	        
            var o_frameclose = o_parent.getElementsByClassName('confirm_screenopen')[0];
            o_frameclose.className = 'confirm_screenclose';    	
	        var b = document.getElementById("cover");
			b.className = "nocover";
		    b.style.height = 0;		
        };        

        function initAutoLists() {
	
	        var ul = document.getElementsByTagName('ul');
	        for (var i = 0; i < ul.length; i++) {
		        var list = ul[i];
	        	if (list.getAttribute('class').search(/(autolist)/) === -1) {
			        continue;
		        }		
		        var items = list.getElementsByTagName('li');
		        if (items.length <= iWebkit.autolistNumVisible) {
                    continue;
	        	}		
		        list.numitems = items.length;
	        	list.visibleitems = 0;
	        	var button = list.getElementsByClassName('autolisttext')[0];
		        button.onclick = function (event) {
		        	var list = this.parentNode;
		        	list.showItems(list.visibleitems + iWebkit.autolistNumVisible);
		        	return false;
	        	};
		        list.showItems = function (numItems) {
			        var items = this.getElementsByTagName('li');
			        var count = 0;
			        for (var i = 0; i < items.length; i++) {
				        items[i].className = items[i].className.replace(/hidden/g, '');
			        	if (i >= numItems) {
				        	items[i].className = items[i].className + ' hidden';
				        } 
				        else {
					        count += 1;
			        	}
			        }
        			this.visibleitems = count;
		        	button.className = button.className.replace(/hidden/g, '');
	        		if (count >= (items.length - 1)) {
	        			button.className = button.className + ' hidden';
	        		}
	        	};
	        	list.showItems(iWebkit.autolistNumVisible);
        	}
        }		
		
		iWebkit.init = function () {
			url();
			hideURLbar();
			initAutoLists();
			var inputs = document.getElementsByTagName("input"), span = [], textnode, option, active;
			for (var a = 0;a < inputs.length;a++) {
				if (inputs[a].type === "checkbox" || inputs[a].type === "radio") {
					span[a] = document.createElement("span");
					span[a].className = inputs[a].type;
					if (inputs[a].checked) {
						if (inputs[a].type === "checkbox") {
							var position = "0 -" + (iWebkit.checkboxHeight * 2) + "px";
							span[a].style.backgroundPosition = position;
						} else {
							position = "0 -" + (iWebkit.radioHeight * 2) + "px";
							span[a].style.backgroundPosition = position;
						}
					}
					inputs[a].parentNode.insertBefore(span[a], inputs[a]);
					inputs[a].onchange = iWebkit.clear;
					span[a].onmouseup = iWebkit.check;
					document.onmouseup = iWebkit.clear;
				}
			}
			inputs = document.getElementsByTagName("select");
			for (a = 0;a < inputs.length; a++) {
				if (inputs[a]) {
					option = inputs[a].getElementsByTagName("option");
					active = option[0].childNodes[0].nodeValue;
					textnode = document.createTextNode(active);
					for (var b = 0;b < option.length;b++) {
						if (option[b].selected) {
							textnode = document.createTextNode(option[b].childNodes[0].nodeValue);
						}
					}
					span[a] = document.createElement("span");
					span[a].className = "select";
					span[a].id = "select" + inputs[a].name;
					span[a].appendChild(textnode);
					inputs[a].parentNode.insertBefore(span[a], inputs[a]);
					inputs[a].onchange = iWebkit.choose;
				}
			}
		};
		
		iWebkit.pushed = function () {
			var element = this.nextSibling;
			if (element.checked && element.type === "checkbox") {
				this.style.backgroundPosition = "0 -" + iWebkit.checkboxHeight * 3 + "px";
			} else {
				if (element.checked && element.type === "radio") {
					this.style.backgroundPosition = "0 -" + iWebkit.radioHeight * 3 + "px";
				} else {
					if (!element.checked && element.type === "checkbox") {
						this.style.backgroundPosition = "0 -" + iWebkit.checkboxHeight + "px";
					} else {
						this.style.backgroundPosition = "0 -" + iWebkit.radioHeight + "px";
					}
				}
			}
		};
		
		iWebkit.check = function () {
			var element = this.nextSibling;
			if (element.checked && element.type === "checkbox") {
				this.style.backgroundPosition = "0 0";
				element.checked = false;
			} else {
				if (element.type === "checkbox") {
					this.style.backgroundPosition = "0 -" + iWebkit.checkboxHeight * 2 + "px";
				} else {
					this.style.backgroundPosition = "0 -" + iWebkit.radioHeight * 2 + "px";
					var group = this.nextSibling.name;
					var inputs = document.getElementsByTagName("input");
					for (var a = 0;a < inputs.length;a++) {
						if (inputs[a].name === group && inputs[a] !== this.nextSibling) {
							inputs[a].previousSibling.style.backgroundPosition = "0 0";
						}
					}
				}
				element.checked = true;
			}
		};
		
		iWebkit.clear = function () {
			var inputs = document.getElementsByTagName("input");
			for (var b = 0;b < inputs.length;b++) {
				if (inputs[b].type === "checkbox" && inputs[b].checked) {
					inputs[b].previousSibling.style.backgroundPosition = "0 -" + iWebkit.checkboxHeight * 2 + "px";
				} else {
					if (inputs[b].type === "checkbox") {
						inputs[b].previousSibling.style.backgroundPosition = "0 0";
					}
					else {
						if (inputs[b].type === "radio" && inputs[b].checked) {
							inputs[b].previousSibling.style.backgroundPosition = "0 -" + iWebkit.radioHeight * 2 + "px";
						} else {
							if (inputs[b].type === "radio") {
								inputs[b].previousSibling.style.backgroundPosition = "0 0";
							}
						}
					}
				}
			}
		};
		
		iWebkit.choose = function () {
			var option = this.getElementsByTagName("option");
			for (var d = 0;d < option.length; d++) {
				if (option[d].selected) {
					document.getElementById("select" + this.name).childNodes[0].nodeValue = option[d].childNodes[0].nodeValue;
				}
			}
		};
		
		iWebkit.init();
	};
}