// Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
// Copyright (C) 2014 Cédric GROSS	<c.gross@kreiz-it.fr>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/

//
// \file       htdocs/core/js/lib_rare.js
// \brief      File that include javascript functions not frequently used (included if option use_javascript activated)
//

// in [-]HH:MM format...
// won't yet work with non-even tzs
function fetchTimezone() {
    var localclock = new Date(),
    // returns negative offset from GMT in minutes
        tzRaw = localclock.getTimezoneOffset(),
        tzHour = Math.floor(Math.abs(tzRaw) / 60),
        tzMin = Math.abs(tzRaw) % 60;
    return ((tzRaw >= 0) ? "-" : "") + ((tzHour < 10) ? "0" : "") + tzHour + ":" + ((tzMin < 10) ? "0" : "") + tzMin;
}

function AddLineDLUO(index) {
	var nme = 'dluo_0_'+index;
	$row=$("tr[name='"+nme+"']").clone(true);
	$row.find("input[name^='qty']").val('');
	var trs = $("tr[name^='dluo_'][name$='_"+index+"']");
	var newrow=$row.html().replace(/_0_/g,"_"+(trs.length)+"_");
	$row.html(newrow);
	//clear value
	$row.find("input[name^='qty']").val('');
	//change name of row
	$row.attr('name','dluo_'+trs.length+'_'+index);
	$("tr[name^='dluo_'][name$='_"+index+"']:last").after($row);
}
