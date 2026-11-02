// MENU
let navmenu = document.querySelector('.doc-sidebar nav ul');
let navmenulinks = navmenu.querySelectorAll('a.link-withsubmenu');
if(navmenulinks.length > 0) {
    navmenulinks.forEach(
        function (menulink) {
            menulink.addEventListener(
                'click', function (e) {
                    e.preventDefault();
                    if(menulink.parentNode.classList.contains('active')) {
                        menulink.parentNode.classList.remove('active');
                    } else {
                        menulink.parentNode.classList.add('active');
                    }
                }
            );
        }
    );
}

// VIEW SCROLL
window.onscroll = function () {
    showscroll()
};
function showscroll()
{
    var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    var scrolled = (winScroll / height) * 100;
    document.getElementById("documentation-scroll").style.width = scrolled + "%";
}

// STICKY SUMMARY (TABLE OF CONTENTS) WITH ACTIVE SECTION TRACKING
// On large screens, the summary of the page is shown in a sticky column on the right (see documentation.css)
(function () {
    let docWrapper = document.querySelector('.doc-wrapper');
    let contentWrapper = docWrapper ? docWrapper.querySelector('.doc-content-wrapper') : null;
    let summaryWrapper = contentWrapper ? contentWrapper.querySelector('.summary-wrapper.summary-toc') : null;
    if (!summaryWrapper) {
        return;
    }

    // Summary with links to sections of the current page (summary-toc class set in PHP)
    let items = [];
    summaryWrapper.querySelectorAll('a[href^="#"]').forEach(function (link) {
        let target = document.getElementById(decodeURIComponent(link.getAttribute('href').substring(1)));
        if (target) {
            items.push({href: link.getAttribute('href'), target: target});
        }
    });
    if (items.length === 0) {
        return;
    }

    let toc = document.createElement('aside');
    toc.className = 'doc-toc';
    let tocHeader = document.createElement('div');
    tocHeader.className = 'doc-toc-header';
    let tocTitle = document.createElement('span');
    tocTitle.className = 'doc-toc-title';
    tocTitle.textContent = summaryWrapper.dataset.tocTitle || '';
    tocHeader.appendChild(tocTitle);
    let backTop = document.createElement('a');
    backTop.className = 'doc-toc-backtop';
    backTop.href = '#';
    backTop.innerHTML = '<span class="fas fa-arrow-up"></span> ';
    backTop.appendChild(document.createTextNode(summaryWrapper.dataset.tocBacktop || ''));
    backTop.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        window.scrollTo({top: 0, behavior: 'smooth'});
        history.replaceState(null, '', window.location.pathname + window.location.search);
    });
    tocHeader.appendChild(backTop);
    toc.appendChild(tocHeader);
    toc.appendChild(summaryWrapper.firstElementChild.cloneNode(true));
    contentWrapper.after(toc);

    items.forEach(function (item) {
        item.link = toc.querySelector('a[href="' + CSS.escape(item.href) + '"]');
    });

    let activeItem = null;
    function setActive(item)
    {
        if (item === activeItem) {
            return;
        }
        if (activeItem && activeItem.link) {
            activeItem.link.classList.remove('active');
        }
        activeItem = item;
        if (!item || !item.link) {
            return;
        }
        item.link.classList.add('active');

        // Keep the active link visible when the summary is higher than the screen (without scrolling the page)
        if (toc.scrollHeight > toc.clientHeight) {
            // Position of the link inside the scrollable summary (offsetTop is not reliable: the sticky summary is the offsetParent)
            let linkTop = item.link.getBoundingClientRect().top - toc.getBoundingClientRect().top + toc.scrollTop;
            if (linkTop < toc.scrollTop + tocHeader.offsetHeight) {
                toc.scrollTop = linkTop - tocHeader.offsetHeight - 8; // The sticky header covers the top of the summary
            } else if (linkTop + item.link.offsetHeight > toc.scrollTop + toc.clientHeight) {
                toc.scrollTop = linkTop + item.link.offsetHeight - toc.clientHeight + 8;
            }
        }
    }

    // Active section = last section whose title has passed the top quarter of the screen
    let lockedByClick = false;
    let lastClickId = 0;
    function updateActive()
    {
        if (lockedByClick) {
            return;
        }
        let doc = document.documentElement;
        if (window.scrollY + window.innerHeight >= doc.scrollHeight - 2) {
            setActive(items[items.length - 1]); // Bottom reached: short last sections can never reach the threshold
            return;
        }
        let threshold = window.innerHeight * 0.25;
        let current = items[0];
        items.forEach(function (item) {
            if (item.target.getBoundingClientRect().top <= threshold) {
                current = item;
            }
        });
        setActive(current);
    }

    let ticking = false;
    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(function () {
                ticking = false;
                updateActive();
            });
        }
    }, {passive: true});
    window.addEventListener('resize', updateActive);

    // A click activates the section at once, tracking resumes when the smooth scroll is over
    toc.addEventListener('click', function (e) {
        let link = e.target.closest('a[href^="#"]');
        let item = link ? items.find(function (i) { return i.link === link; }) : null;
        if (!item) {
            return;
        }
        e.preventDefault();
        setActive(item);
        let clickId = ++lastClickId;
        lockedByClick = true;
        let unlock = function () {
            window.removeEventListener('scrollend', unlock);
            if (clickId === lastClickId) {
                lockedByClick = false;
            }
        };
        window.addEventListener('scrollend', unlock);
        setTimeout(unlock, 1000);
        item.target.scrollIntoView({behavior: 'smooth', block: 'start'});
        history.replaceState(null, '', item.href);
    });

    updateActive();
})();
