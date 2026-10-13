/* Copyright (C) 2025 John BOTELLA
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/js/lib_foot_search_tool.js.php
 * \brief      File that include javascript functions (included if option use_javascript activated)
 *
 * this file is included in htdocs/core/js/lib_foot.js.php
 */

/**
 * JS CODE USED by form::getSearchFilterToolInput
 * This code allow quick filtering on user key press
 * */
$(function() {
	$("[data-search-tool-target]").on("keyup", function () {
		let search = $(this).val();
		let target = $(this).attr('data-search-tool-target');
		let targetCounter = $(this).attr('data-counter-target');
		let targetNoResultTarget = $(this).attr('data-no-item-target');
		let count = 0;

		if (target == undefined || target.length === 0) {
			return;
		}

		if (search.length > 0) {
			$(target).addClass('hidden-search-result');
			$(target).each(function () {
				if ($(this).text().toUpperCase().indexOf(search.toUpperCase()) != -1) {
					$(this).removeClass('hidden-search-result');
					count++;
				}
			});
		} else {
			$(target).removeClass('hidden-search-result');
			count = $(target).length;
		}

		if(targetCounter != undefined &&  $(targetCounter)){
			$(targetCounter).text(count);
		}

		if(targetNoResultTarget != undefined && $(targetNoResultTarget)){
			if (count == 0 && search.length !== 0) {
				$(targetNoResultTarget).removeClass("hidden-search-result");
			} else {
				$(targetNoResultTarget).addClass("hidden-search-result");
			}
		}
	});
});
