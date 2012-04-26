/*
 * jQuery UI Multiselect
 *
 * Authors:
 *  Michael Aufreiter (quasipartikel.at)
 *  Yanick Rochon (yanick.rochon[at]gmail[dot]com)
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://www.quasipartikel.at/multiselect/
 *
 *
 * Depends:
 *	ui.core.js
 *	ui.sortable.js
 *
 * Optional:
 * localization (http://plugins.jquery.com/project/localisation)
 * scrollTo (http://plugins.jquery.com/project/ScrollTo)
 *
 * Todo:
 *  Make batch actions faster
 *  Implement dynamic insertion through remote calls
 */


(function($) {

$.widget("ui.multiselect", {
  options: {
		sortable: true,
		dragToAdd: true,
		searchable: true,
		doubleClickable: true,
		animated: 'fast',
		show: 'slideDown',
		hide: 'slideUp',
		dividerLocation: 0.6,
		selectedContainerOnLeft: true,
		width: null,
		height: null,
		nodeComparator: function(node1,node2) {
			var text1 = node1.text(),
			    text2 = node2.text();
			return text1 == text2 ? 0 : (text1 < text2 ? -1 : 1);
		},
		includeRemoveAll: true,
		includeAddAll: true,
		pressEnterKeyToAddAll: false
	},
	_create: function() {
		this.element.hide();
		this.id = this.element.attr("id");
		this.container = $('<div class="ui-multiselect ui-helper-clearfix ui-widget"></div>').insertAfter(this.element);
		this.count = 0; // number of currently selected options
		this.selectedContainer = $('<div class="selected"></div>');
		if (this.options.selectedContainerOnLeft) {
			this.selectedContainer.appendTo(this.container);
			this.availableContainer = $('<div class="available"></div>').appendTo(this.container);
			this.availableContainer.addClass('right-column');
		}
		else
		{
			this.availableContainer = $('<div class="available"></div>').appendTo(this.container);
			this.selectedContainer.appendTo(this.container);
			this.selectedContainer.addClass('right-column');
		}
		this.selectedActions = $('<div class="actions ui-widget-header ui-helper-clearfix"><span class="count">0 '+$.ui.multiselect.locale.itemsCount+'</span>'+(this.options.includeRemoveAll?'<a href="#" class="remove-all">'+$.ui.multiselect.locale.removeAll+'</a>':'<span class="remove-all">&nbsp;</span>')+'</div>').appendTo(this.selectedContainer);
		this.availableActions = $('<div class="actions ui-widget-header ui-helper-clearfix"><input type="text" class="search empty ui-widget-content ui-corner-all"/>'+(this.options.includeAddAll?'<a href="#" class="add-all">'+$.ui.multiselect.locale.addAll+'</a>':'<span class="add-all">&nbsp;</span>')+'</div>').appendTo(this.availableContainer);
		this.selectedList = $('<ul class="selected connected-list"><li class="ui-helper-hidden-accessible"></li></ul>').bind('selectstart', function(){return false;}).appendTo(this.selectedContainer);
		this.availableList = $('<ul class="available connected-list"><li class="ui-helper-hidden-accessible"></li></ul>').bind('selectstart', function(){return false;}).appendTo(this.availableContainer);

		var that = this;

		var width = this.options.width;
		if (!width) {
			width = this.element.width();
		}
		var height = this.options.height;
		if (!height) {
			height = this.element.height();
		}

		// set dimensions
		this.container.width(width-2);
		if (this.options.selectedContainerOnLeft) {
			this.selectedContainer.width(Math.floor(width*this.options.dividerLocation)-1);
			this.availableContainer.width(Math.floor(width*(1-this.options.dividerLocation))-2);
		}
		else
		{
			this.selectedContainer.width(Math.floor(width*this.options.dividerLocation)-2);
			this.availableContainer.width(Math.floor(width*(1-this.options.dividerLocation))-1);
		}

		// fix list height to match <option> depending on their individual header's heights
		this.selectedList.height(Math.max(height-this.selectedActions.height(),1));
		this.availableList.height(Math.max(height-this.availableActions.height(),1));

		if ( !this.options.animated ) {
			this.options.show = 'show';
			this.options.hide = 'hide';
		}

		// init lists
		this._populateLists(this.element.find('option'));

		// make selection sortable
		if (this.options.sortable) {
			this.selectedList.sortable({
				placeholder: 'ui-state-highlight',
				axis: 'y',
				update: function(event, ui) {
					// apply the new sort order to the original selectbox
					that.selectedList.find('li').each(function() {
						if ($(this).data('optionLink'))
							$(this).data('optionLink').remove().appendTo(that.element);
					});
				},
				beforeStop: function (event, ui) {
					// This lets us recognize which item was just added to
					// the list in receive, per the workaround for not being
					// able to reference the new element.
					ui.item.addClass('dropped');
				},
				receive: function(event, ui) {
					ui.item.data('optionLink').attr('selected', true);
					// increment count
					that.count += 1;
					that._updateCount();
					// workaround, because there's no way to reference
					// the new element, see http://dev.jqueryui.com/ticket/4303
					that.selectedList.children('.dropped').each(function() {
						$(this).removeClass('dropped');
						$(this).data('optionLink', ui.item.data('optionLink'));
						$(this).data('idx', ui.item.data('idx'));
						that._applyItemState($(this), true);
					});

					// workaround according to http://dev.jqueryui.com/ticket/4088
					setTimeout(function() { ui.item.remove(); }, 1);
				},
				stop: function (event, ui) { that.element.change(); }
			});
		}

		// set up livesearch
		if (this.options.searchable) {
			this._registerSearchEvents(this.availableContainer.find('input.search'));
		} else {
			$('.search').hide();
		}

		// batch actions
		this.container.find(".remove-all").click(function() {
			that._populateLists(that.element.find('option').removeAttr('selected'));
			that.element.trigger('change');
			return false;
		});

		this.container.find(".add-all").click(function() {
			var options = that.element.find('option').not(":selected");
			if (that.availableList.children('li:hidden').length > 1) {
				that.availableList.children('li').each(function(i) {
					if ($(this).is(":visible")) $(options[i-1]).attr('selected', 'selected');
				});
			} else {
				options.attr('selected', 'selected');
			}
			that._populateLists(that.element.find('option'));
			that.element.trigger('change');
			if (that.options.pressEnterKeyToAddAll) {
                    //clear input after add all
                    $('input.search').val("");
                }
			
			return false;
		});
	},
	destroy: function() {
		this.element.show();
		this.container.remove();

		$.Widget.prototype.destroy.apply(this, arguments);
	},
	addOption: function(option) {
		// Append the option
		option = $(option);
		var select = this.element;
		select.append(option);

		var item = this._getOptionNode(option).appendTo(option.attr('selected') ? this.selectedList : this.availableList).show();

		if (option.attr('selected')) {
			this.count += 1;
		}
		this._applyItemState(item, option.attr('selected'));
		item.data('idx', this.count);

		// update count
		this._updateCount();
		this._filter.apply(this.availableContainer.find('input.search'), [this.availableList]);
	},
    // Redisplay the lists of selected/available options.
    // Call this after you've selected/unselected some options programmatically.
    // GRIPE This is O(n) where n is the length of the list - seems like
    // there must be a smarter way of doing this, but I have not been able
    // to come up with one. I see no way to detect programmatic setting of
    // the option's selected property, and without that, it seems like we
    // can't have a general-case listener that does its thing every time an
    // option is selected.
    refresh: function() {
		// Redisplay our lists.
		this._populateLists(this.element.find('option'));
    },
	_populateLists: function(options) {
		this.selectedList.children('.ui-element').remove();
		this.availableList.children('.ui-element').remove();
		this.count = 0;

		var that = this;
		var groups = $(this.element).find("optgroup").map(function(i) {
			return that._getOptionGroup($(this));
		});
		groups.appendTo(this.selectedList.add(this.availableList));
		
		var items = $(options.map(function(i) {
		  var item = that._getOptionNode(this).appendTo(that._getOptionList(this)).show();

			if (this.selected) that.count += 1;
			that._applyItemState(item, this.selected);
			item.data('idx', i);
			return item[0];
    }));

		// update count
		this._updateCount();
		that._filter.apply(this.availableContainer.find('input.search'), [that.availableList]);
  },
	_getOptionList: function(option) {
		var selected = option.selected;
		option = $(option);
		var $list = selected ? this.selectedList : this.availableList;
		var $group = option.closest("optgroup");
		if ($group.length === 0) {
			return $list;
		} else {
			var $groupList = $list.find("ul[title='" + $group.attr("label") + "']");
			if ($groupList.length === 0) {
				$groupList = $("<ul class='ui-state-default ui-element available' title='" + $group.attr("label") + "'>" + $group.attr("label") + "</ul>").appendTo($list);
			}
			$groupList.show();
			return $groupList;
		}
	},
	_getOptionGroup : function(optgroup) {
		var groupNode = $("<ul class='ui-state-default ui-element available' title='" + optgroup.attr("label") + "'>" + optgroup.attr("label") + "</ul>").hide();
		return groupNode[0];
	},
	_updateCount: function() {
		this.selectedContainer.find('span.count').text(this.count+" "+$.ui.multiselect.locale.itemsCount);
	},
	_getOptionNode: function(option) {
		option = $(option);
		var node = $('<li class="ui-state-default ui-element" title="'+option.text()+'"><span class="ui-icon"/>'+option.text()+'<a href="#" class="action"><span class="ui-corner-all ui-icon"/></a></li>').hide();
		node.data('optionLink', option);
		return node;
	},
	// clones an item with associated data
	// didn't find a smarter away around this
	_cloneWithData: function(clonee) {
		var clone = clonee.clone(false,false);
		clone.data('optionLink', clonee.data('optionLink'));
		clone.data('idx', clonee.data('idx'));
		return clone;
	},
	_setSelected: function(item, selected) {
		var temp = item.data('optionLink').attr('selected', selected);
		var parent = temp.parent();
		temp.detach().appendTo(parent);
		this.element.trigger('change');

		if (selected) {
			var selectedItem = this._cloneWithData(item);
			item[this.options.hide](this.options.animated, function() { 
				if (item.siblings().length === 0) {
					item.closest("ul[title]").hide();
				}
				$(this).remove(); 
			});
			// get group to add it to...
			var $list = this._getOptionList(selectedItem.data("optionLink")[0]);
			selectedItem.appendTo($list).hide()[this.options.show](this.options.animated);

			this._applyItemState(selectedItem, true);
			return selectedItem;
		} else {

			// look for successor based on initial option index
			var items = this.availableList.find('li'), comparator = this.options.nodeComparator;
			var succ = null, i = item.data('idx'), direction = comparator(item, $(items[i]));

			// TODO: test needed for dynamic list populating
			if ( direction ) {
				while (i>=0 && i<items.length) {
					direction > 0 ? i++ : i--;
					if ( direction != comparator(item, $(items[i])) ) {
						// going up, go back one item down, otherwise leave as is
						succ = items[direction > 0 ? i : i+1];
						var group1 = item.closest("ul[title]"),
							group2 = $(succ).closest("ul[title]");
						if (group1.length !== 0 && group2.length !== 0) {
							if (group1.attr("title") !== group2.attr("title")) {
								succ = null;
							}
						}
						break;
					}
				}
			} else {
				succ = items[i];
			}

			var availableItem = this._cloneWithData(item);
			var $list = this._getOptionList(availableItem.data("optionLink")[0]);
			succ ? availableItem.insertBefore($(succ)) : availableItem.appendTo($list);
			item[this.options.hide](this.options.animated, function() { 
				if (item.siblings().length === 0) {
					item.closest("ul[title]").hide();
				}
				$(this).remove(); 
			});
			availableItem.hide()[this.options.show](this.options.animated);

			this._applyItemState(availableItem, false);
			return availableItem;
		}
	},
	_applyItemState: function(item, selected) {
		if (selected) {
			if (this.options.sortable)
				item.children('span').addClass('ui-icon-arrowthick-2-n-s').removeClass('ui-helper-hidden').addClass('ui-icon');
			else
				item.children('span').removeClass('ui-icon-arrowthick-2-n-s').addClass('ui-helper-hidden').removeClass('ui-icon');
			item.find('a.action span').addClass('ui-icon-minus').removeClass('ui-icon-plus');
			this._registerRemoveEvents(item.find('a.action'));

		} else {
			item.children('span').removeClass('ui-icon-arrowthick-2-n-s').addClass('ui-helper-hidden').removeClass('ui-icon');
			item.find('a.action span').addClass('ui-icon-plus').removeClass('ui-icon-minus');
			this._registerAddEvents(item.find('a.action'));
		}

		this._registerDoubleClickEvents(item);
		this._registerHoverEvents(item);
	},
	// taken from John Resig's liveUpdate script
	_filter: function(list) {
		var input = $(this);
		var rows = list.find('li'),
			cache = rows.map(function(){

				return $(this).text().toLowerCase();
			});

		var term = $.trim(input.val().toLowerCase()), scores = [];

		if (!term) {
			rows.show();
		} else {
			rows.hide();

			cache.each(function(i) {
				if (this.indexOf(term)>-1) { scores.push(i); }
			});

			$.each(scores, function() {
				$(rows[this]).show();
			});
		}
	},
	_registerDoubleClickEvents: function(elements) {
		if (!this.options.doubleClickable) return;
		elements.dblclick(function() {
			elements.find('a.action').click();
		});
	},
	_registerHoverEvents: function(elements) {
		elements.removeClass('ui-state-hover');
		elements.mouseover(function() {
			$(this).addClass('ui-state-hover');
		});
		elements.mouseout(function() {
			$(this).removeClass('ui-state-hover');
		});
	},
	_registerAddEvents: function(elements) {
		var that = this;
		elements.click(function() {
			var item = that._setSelected($(this).parent(), true);
			that.count += 1;
			that._updateCount();

			// Prevent extra clicks from triggering bogus add events, if a user
			// tries clicking during the removal process.
			$(this).unbind('click');

			return false;
		});

		// make draggable
		if (this.options.sortable && this.options.dragToAdd) {
  		elements.each(function() {
  			$(this).parent().draggable({
  	      connectToSortable: that.selectedList,
  				helper: function() {
  					var selectedItem = that._cloneWithData($(this)).width($(this).width() - 50);
  					selectedItem.width($(this).width());
  					return selectedItem;
  				},
  				appendTo: that.container,
  				containment: that.container,
  				revert: 'invalid'
  	    });
  		});
		}
	},
	_registerRemoveEvents: function(elements) {
		var that = this;
		elements.click(function() {
			that._setSelected($(this).parent(), false);
			that.count -= 1;
			that._updateCount();

			// Prevent extra clicks from triggering bogus remove events, if a
			// user tries clicking during the removal process.
			$(this).unbind('click');

			return false;
		});
 	},
	_registerSearchEvents: function(input) {
		var that = this;

		input.focus(function() {
			$(this).addClass('ui-state-active');
		})
		.blur(function() {
			$(this).removeClass('ui-state-active');
		})
		.keypress(function(e) {
			if (e.keyCode == 13) {
				if (that.options.pressEnterKeyToAddAll) {
		            //on Enter, if a filter is present add all, then clear the input
		            var str = $('input.search').val();
		            if (str !== undefined && str !== null && str !== "") {
		                $('a.add-all').click();
		                $('input.search').val("");
		            }
		        }
				return false;
			}
		})
		.keyup(function() {
			that._filter.apply(this, [that.availableList]);
		});
	}
});

$.extend($.ui.multiselect, {
	locale: {
		addAll:'Add all',
		removeAll:'Remove all',
		itemsCount:'items selected'
	}
});


})(jQuery);
