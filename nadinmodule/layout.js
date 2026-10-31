(function () {
    'use strict';
    var nav = document.querySelector('[data-sidebar]');
    var toggle = document.getElementById('nadin-sidebar-toggle');
    if (!nav) { if (toggle) toggle.hidden = true; return; }
    var inlineAlphabet = nav.classList.contains('nadin-library-alphabet');
    if (inlineAlphabet && toggle) { toggle.hidden = true; toggle.style.display = 'none'; }
    var mobile = window.matchMedia('(max-width:700px)');
    var backdrop = document.createElement('div');
    backdrop.className = 'nadin-sidebar-backdrop';
    document.body.appendChild(backdrop);
    function closeList(restoreFocus) {
        document.body.classList.remove('nadin-sidebar-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open list');
        if (restoreFocus) toggle.focus();
    }
    toggle.addEventListener('click', function () {
        var open = !document.body.classList.contains('nadin-sidebar-open');
        document.body.classList.toggle('nadin-sidebar-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close list' : 'Open list');
        if (open) { var link = nav.querySelector('[aria-current], a'); if (link) link.focus({preventScroll:true}); }
    });
    backdrop.addEventListener('click', function () { closeList(true); });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (document.body.classList.contains('nadin-sidebar-open')) closeList(true);
        document.querySelectorAll('.nadin-mobile-actions details[open]').forEach(function (menu) { menu.open = false; menu.querySelector('summary').focus(); });
    });
    nav.addEventListener('click', function (event) { if (event.target.closest('a') && mobile.matches) closeList(false); });
    var gigSearch=document.getElementById('nadin-gig-search');
    if(gigSearch)gigSearch.addEventListener('input',function(){var q=gigSearch.value.toLocaleLowerCase();nav.querySelectorAll('a').forEach(function(a){a.hidden=!a.textContent.toLocaleLowerCase().includes(q);});});
    var key = 'nadin-sidebar:' + location.pathname + ':' + nav.getAttribute('data-sidebar');
    try { nav.scrollTop = Number(sessionStorage.getItem(key)) || 0; } catch (ignore) {}
    function fitSidebar() {
        if (inlineAlphabet) { nav.style.maxHeight = ''; return; }
        if (mobile.matches) nav.style.maxHeight = '';
        else {
            closeList(false);
            nav.style.maxHeight = Math.max(96, window.innerHeight - nav.getBoundingClientRect().top - 8) + 'px';
        }
    }
    fitSidebar();
    window.addEventListener('resize', fitSidebar);
    var current = nav.querySelector('[aria-current="page"]');
    if (!mobile.matches && current && (current.offsetTop < nav.scrollTop || current.offsetTop + current.offsetHeight > nav.scrollTop + nav.clientHeight)) nav.scrollTop = current.offsetTop - nav.clientHeight / 2;
    nav.addEventListener('scroll', function () { try { sessionStorage.setItem(key, String(nav.scrollTop)); } catch (ignore) {} }, {passive:true});
    function markAnchor() {
        var links = nav.querySelectorAll('a[href^="#"]');
        for (var i = 0; i < links.length; i++) {
            if (links[i].hash === location.hash) links[i].setAttribute('aria-current', 'location');
            else links[i].removeAttribute('aria-current');
        }
    }
    window.addEventListener('hashchange', markAnchor); markAnchor();
    // Keep the original links and handlers; only their container changes on phones.
    var rowMenus = [];
    document.querySelectorAll('.nadin-tune-row').forEach(function (row) {
        var title = row.querySelector('.nadin-tune-cell h5');
        if (!title) return;
        title.title = title.textContent;
        var cell = document.createElement('td'); cell.className = 'nadin-mobile-actions';
        var menu = document.createElement('details');
        var summary = document.createElement('summary'); summary.textContent = 'Open';
        summary.setAttribute('aria-label', 'Open ' + title.textContent);
        var panel = document.createElement('div'); panel.className = 'nadin-action-menu';
        var heading = document.createElement('strong'); heading.textContent = title.textContent.replace(/_/g, ' '); panel.appendChild(heading);
        menu.appendChild(summary); menu.appendChild(panel); cell.appendChild(menu);
        var groups = [];
        row.querySelectorAll('td').forEach(function (source) {
            if (source.classList.contains('nadin-tune-cell') || source.classList.contains('nadin-number')) return;
            source.classList.add('nadin-desktop-actions');
            var group = document.createElement('div'); panel.appendChild(group);
            groups.push({source:source, target:group});
        });
        row.appendChild(cell);
        menu.addEventListener('toggle', function () {
            if (!menu.open) return;
            rowMenus.forEach(function (other) { if (other.menu !== menu) other.menu.open = false; });
            var anchor = summary.getBoundingClientRect();
            var box = panel.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.right = 'auto';
            panel.style.left = Math.max(8, Math.min(anchor.right - box.width, window.innerWidth - box.width - 8)) + 'px';
            panel.style.top = (anchor.bottom + box.height + 8 <= window.innerHeight ? anchor.bottom + 2 : Math.max(8, anchor.top - box.height - 2)) + 'px';
            panel.style.maxHeight = Math.max(96, window.innerHeight - 16) + 'px';
            panel.style.overflowY = 'auto';
        });
        panel.addEventListener('click', function (event) { if (event.target.closest('a, [onclick]')) menu.open = false; });
        rowMenus.push({menu:menu, groups:groups});
    });
    function arrangeRows() {
        rowMenus.forEach(function (entry) {
            entry.menu.open = false;
            entry.groups.forEach(function (group) {
                var from = mobile.matches ? group.source : group.target;
                var to = mobile.matches ? group.target : group.source;
                while (from.firstChild) to.appendChild(from.firstChild);
            });
        });
        document.querySelectorAll('.nadin-downloads').forEach(function (downloads) { downloads.open = !mobile.matches; });
    }
    arrangeRows();
    window.addEventListener('resize', function () { rowMenus.forEach(function (entry) { entry.menu.open = false; }); });
    mobile.addEventListener('change', arrangeRows);
    document.addEventListener('click', function (event) {
        rowMenus.forEach(function (entry) { if (!entry.menu.contains(event.target)) entry.menu.open = false; });
    });
    if (mobile.matches) {
        var info = document.getElementById('infoarea');
        if (info) info.style.display = 'none';
        var downloads = document.querySelector('.nadin-downloads');
        if (downloads) downloads.open = false;
    }
}());

(function(){
 'use strict';
 var search=document.getElementById('nadin-tune-search');if(!search)return;
 var fold=function(s){return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/_/g,' ');};
 function filter(){var q=fold(search.value),count=0;document.querySelectorAll('.nadin-library-tune').forEach(function(group){var title=group.querySelector('h5');var show=title&&fold(title.textContent).includes(q);group.hidden=!show;if(show)count++;});document.querySelectorAll('.nadin-library-letter').forEach(function(letter){var value=letter.dataset.libraryLetter;letter.hidden=!Array.from(document.querySelectorAll('.nadin-library-tune')).some(function(t){return !t.hidden&&t.dataset.libraryLetter===value;});});document.getElementById('nadin-search-count').textContent=q?count+' tunes':'';}
 search.addEventListener('input',filter);document.getElementById('library-sort').addEventListener('change',filter);
}());
