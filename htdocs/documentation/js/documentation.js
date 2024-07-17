// MENU
let navmenu = document.querySelector('.doc-sidebar nav ul');
let navmenulinks = navmenu.querySelectorAll('a.link-withsubmenu');
if(navmenulinks.length > 0){	
	navmenulinks.forEach(function (menulink){
		menulink.addEventListener('click', function (e){
			e.preventDefault();
			if(menulink.parentNode.classList.contains('active')){
				menulink.parentNode.classList.remove('active');
			} else {
				menulink.parentNode.classList.add('active');
			}
		});
    });
}

// VIEW SCROLL
window.onscroll = function() {showscroll()};
function showscroll() {
	var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
	var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
	var scrolled = (winScroll / height) * 100;
	document.getElementById("documentation-scroll").style.width = scrolled + "%";
}