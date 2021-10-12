<?php

require_once DOL_DOCUMENT_ROOT.'/expedition/card.php';

$container_number = GETPOST('tracking_number', 'alpha');

// searates tracking
if (!empty($object->tracking_number)) {
	print '<tr><td>';
	print '<div class="tracking-filter">';
	print '<form class="search-form" method="GET" action="tracking.html">';
	print '<label class="filter-label"><input type="text" name="container" required placeholder="Shipment, B/L, Container no." value="'.$object->tracking_number.'"></label>';
	print '<input id="sealine-code" name="sealine" type="hidden" value="AUTO">';
	print '<div class="sealines-input-block">';
	print '<div id="sealines-title" class="sealines-input-title">Auto Detect</div>';
	print '<div id="search-block" class="sealines-search-block">';
	print '<input type="text" id="search-lines" autocomplete="off" placeholder="Search">';
	print '<ul id="sealine-list"></ul>';
	print '</div></div>';
	print '<style>.sealines-input-block{display:inline-block;vertical-align:middle;position:relative;z-index:10}.sealines-input-title{cursor:text;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:300px}.sealines-search-block{top:0;left:0;position:absolute;display:none;width:100%}#search-lines{width:100%;box-sizing:border-box}#sealine-list{max-height:195px;overflow-y:auto;list-style:none;padding:0;margin:0}.sealine-li{cursor:pointer;padding:10px;user-select:none}.sealine-li:hover{background-color:#f2f6fb}</style>';
	print '<script type="text/javascript" language="javascript">(()=>{const e=document.getElementById("sealine-list"),t=document.getElementById("search-lines"),a=document.getElementById("sealines-title"),n=document.getElementById("search-block"),s=document.getElementById("sealine-code"),d=document.createElement("div");d.style.cssText="position:fixed;height:100%;width:100%;top:0;left:0;z-index:-1;",d.addEventListener("click",()=>{n.style.display="none"}),n.appendChild(d),a.addEventListener("click",()=>{n.style.display="block",t.value="";for(let e=0;e<c.length;e++)c[e].DOM.style.display="block";t.focus()}),e.addEventListener("click",e=>{e.currentTarget!==e.target&&(a.textContent=e.target.dataset.name,s.value=e.target.dataset.sealine,n.style.display="none")}),t.addEventListener("keydown",e=>{13==e.keyCode&&e.preventDefault()}),t.addEventListener("keyup",e=>{const t=e.currentTarget.value.toLowerCase();for(let e=0;e<c.length;e++){let a=c[e];~a.name.toLowerCase().indexOf(t)?a.DOM.style.display="block":a.DOM.style.display="none"}});const l=document.createElement("li");l.classList.add("sealine-li"),l.dataset.sealine="AUTO",l.appendChild(document.createTextNode("Auto Detect")),e.appendChild(l);const c=[{name:"Auto Detect",scac_codes:"AUTO",DOM:l}],i=new XMLHttpRequest;i.onload=(()=>{if(200===i.status){const t=JSON.parse(i.response);if("OK"===t.message&&"success"===t.status)for(let a=0;a<t.data.length;a++){let n=t.data[a];if(n.active&&!n.maintenance&&n.active_types.ct){let t=document.createElement("li");t.classList.add("sealine-li"),t.dataset.sealine=n.scac_codes[0],t.dataset.name=n.name,t.appendChild(document.createTextNode(n.name)),e.appendChild(t),c.push({name:n.name,scac_codes:n.scac_codes[0],DOM:t})}}}}),i.open("GET","https://tracking.searates.com/info/sealines"),i.send()})();</script>';
	print '<button id="tracking" class="btn" type="submit">Search</button>';
	print '</form></div>';
	print '</td></tr>\n';
}
