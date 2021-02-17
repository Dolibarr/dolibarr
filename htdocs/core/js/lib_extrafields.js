function manageLinkedExtrafields(data){

	/**
	 * Make select options visible or invisible depending on what option is selected in the parent select
	 *
	 * @param {string} child_list  Code of the child extrafield (starts with "options_")
	 * @param {string} parent_list Code of the parent extrafield (starts with "options_")
	 */
	function showOptions(child_list, parent_list)
	{
		let child = $("select#" + child_list);
		let parent = $("select#" + parent_list);
		let val = 0;
		if (data["action"] === "create"){
			val = parent.val();
		} else if (data["action"] === "edit_extras"){
			let infos = parent_list.split("_");
			val = $("#"+data["table_element"]+"_extras_"+infos[1]+"_"+ data["object_id"]).attr("data-value");
		}
		let parentVal = parent_list + ":" + val;
		let childOptionsWithAParent = child.find("option[parent]");
		let childOptionsWithSelectedParent = child.find("option[parent='"+parentVal+"']");
		if(typeof val == "string"){
			if(val != "") {
				childOptionsWithAParent.hide();
				childOptionsWithSelectedParent.show();
			} else {
				child.find("option").show();
			}
		}
	}
	/**
	 * Make multiselect options visible or invisible depending on what option is selected in the parent select
	 *
	 * @param {string} child_list  Code of the child extrafield (starts with "options_")
	 * @param {string} parent_list Code of the parent extrafield (starts with "options_")
	 */
	function showOptionsOnMultiselect(child_list, parent_list){
		let val = 0;
		if (data["action"] === "create"){
			val = $("select[name=\""+parent_list+"\"]").val();
		} else if (data["action"] === "edit_extras"){
			let infos = parent_list.split("_");
			val = $("#"+data["table_element"]+"_extras_"+infos[1]+"_"+ data["object_id"]).attr("data-value");
		}
		let parentVal = parent_list + ":" + val;
		let child = $("select#" + child_list);
		if(typeof val == "string"){
			if(val !== "") {
				if($("#"+child_list).hasClass("multiselect")){
					let optionsByParent = multiSelectOptionsByParent[child_list];
					child.empty().select2({data: optionsByParent[parentVal]});
				}
			}
		}
	}
	/**
	 * Create event listeners on selects that depend on each other to hide them when they depend on
	 * a parent that has no option selected, or to change their option list when the parents selection changes
	 */
	function setListDependencies() {
		jQuery("select option[parent]").parent().each(function() {
			let child_list = $(this).attr("id");
			let child = $("#" + child_list);
			let parent_list = $(this).find("option[parent]:first").attr("parent").split(":")[0];
			let parent = $("#" + parent_list);
			let hasEmptyVal = function ($select) {
				let val = $select.val();
				return val === "0" || (Array.isArray(val) && val.length === 0);
			};
			// Hide child lists
			if (hasEmptyVal(child) && hasEmptyVal(parent)){
				if (child.hasClass("multiselect")) {
					child.next(".select2").hide();
				}
				child.hide();
				// Show parent lists
			} else if (parent.val() !== "0"){
				// parent.show()
				if (child.hasClass("multiselect")) {
					child.next(".select2").show();
				} else {
					child.show();
				}
			}
			// show the child list if the parent list value is selected
			parent.click(function() {
				if ($(this).val() !== "0"){
					child.show();
					//show children multiselects if the parent list value is selected
					if(child.hasClass("multiselect")){
						child.next(".select2").show();
					}
				}
			});
			if (data["action"] === "edit_extras"){
				showOptions(child_list, parent_list);
				showOptionsOnMultiselect(child_list, parent_list)
			}
			parent.change(function() {
				showOptions(child_list, parent_list);
				showOptionsOnMultiselect(child_list, parent_list)
				child.val(0).trigger("change");
				// Hide child lists if the parent value is set to 0
				if (hasEmptyVal(parent)){
					// Hide children multiselects
					if(child.hasClass("multiselect")){
						child.next(".select2").hide();
					}
					child.hide();
				}
			});
		});
	}
	// create an object holding all multiselect options sorted by parent and by multiselect
	let multiSelectOptionsByParent = {};
	$("select.multiselect").each(function (n, select) {
		if (!select.id) return;
		let optionsByParent = {};
		multiSelectOptionsByParent[select.id] = optionsByParent;
		$(select).find("option").each(function (n, opt) {
			let $opt = $(opt);
			let parent = $opt.attr("parent") || "";
			if (optionsByParent[parent] === undefined) optionsByParent[parent] = [];
			optionsByParent[parent].push({
				id: $opt.val(),
				text: $opt.text(),
			});
		});
	});

	setListDependencies();
}
