(function () {
    const KEY = 'sidebar-open';
    const saved = JSON.parse(localStorage.getItem(KEY) || '[]');

    document.querySelectorAll('details[data-path]').forEach(function (el) {
        if (saved.includes(el.dataset.path)) el.open = true;

        el.addEventListener('toggle', function () {
            const current = JSON.parse(localStorage.getItem(KEY) || '[]');
            const path = el.dataset.path;
            const idx = current.indexOf(path);
            if (el.open && idx === -1) current.push(path);
            if (!el.open && idx !== -1) current.splice(idx, 1);
            localStorage.setItem(KEY, JSON.stringify(current));
        });
    });

    // Re-enable transitions after two frames (ensures browser has painted first)
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            document.documentElement.classList.remove('notransition');
        });
    });
})();

(function () {
    var THEMES = ['light', 'dark', 'hacker', 'warm', 'nord'];

    function setTheme(name) {
        if (!THEMES.includes(name)) return;
        document.documentElement.setAttribute('data-theme', name);
        localStorage.setItem('theme', name);
        updateActiveCard();
        updateHomeView(name);
    }

    var manifestoFetched = false;
    function updateHomeView(theme) {
        var hackerEl  = document.getElementById('hacker-home');
        var defaultEl = document.getElementById('default-home');
        if (!hackerEl) return;
        if (theme === 'hacker') {
            defaultEl.style.display = 'none';
            hackerEl.style.display  = '';
            if (!manifestoFetched) {
                manifestoFetched = true;
                fetch('/manifesto.txt')
                    .then(function(r) { return r.text(); })
                    .then(function(t) { document.getElementById('manifesto-text').textContent = t; })
                    .catch(function() { document.getElementById('manifesto-text').textContent = '// connection refused'; });
            }
        } else {
            hackerEl.style.display  = 'none';
            defaultEl.style.display = '';
        }
    }

    updateHomeView(localStorage.getItem('theme') || 'light');

    function updateActiveCard() {
        var current = localStorage.getItem('theme') || 'light';
        document.querySelectorAll('.theme-card').forEach(function (card) {
            card.classList.toggle('is-active', card.dataset.theme === current);
        });
    }

    document.querySelectorAll('.theme-card').forEach(function (card) {
        card.addEventListener('click', function () { setTheme(card.dataset.theme); });
    });

    updateActiveCard();
})();

(function () {
    var btn    = document.getElementById('settings-btn');
    var panel  = document.getElementById('settings-panel');
    var close  = document.getElementById('settings-close');
    var isOpen = false;

    function openPanel() {
        isOpen = true;
        panel.getBoundingClientRect(); // force reflow
        panel.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
    }

    function closePanel() {
        isOpen = false;
        panel.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        isOpen ? closePanel() : openPanel();
    });

    close.addEventListener('click', closePanel);

    document.addEventListener('click', function (e) {
        if (isOpen && !panel.contains(e.target)) closePanel();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) closePanel();
    });
})();

(function () {
    var btn = document.getElementById('sidebar-toggle');
    var html = document.documentElement;
    function isCollapsed() { return html.classList.contains('sidebar-collapsed'); }
    function updateBtn() {
        btn.textContent = isCollapsed() ? '→' : '_';
        btn.title = isCollapsed() ? 'Expand sidebar' : 'Collapse sidebar';
        btn.setAttribute('aria-label', isCollapsed() ? 'Expand sidebar' : 'Collapse sidebar');
    }
    updateBtn();
    btn.addEventListener('click', function () {
        html.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', isCollapsed() ? '1' : '0');
        updateBtn();
    });
})();

(function () {
    var nodes = document.querySelectorAll('article pre code.language-mermaid');
    if (!nodes.length) return;

    var CONFIGS = {
        light:  { theme: 'default' },
        dark:   { theme: 'dark' },
        hacker: { theme: 'base', themeVariables: {
            background: '#0a0a0a', primaryColor: '#0d1a0d',
            primaryTextColor: '#00ff41', primaryBorderColor: '#003d0f',
            lineColor: '#00cc33', secondaryColor: '#050f05'
        }},
        warm: { theme: 'base', themeVariables: {
            background: '#fdf6e3', primaryColor: '#f5e6d0',
            primaryTextColor: '#3d2b1f', primaryBorderColor: '#dfc9a8',
            lineColor: '#c0622a'
        }},
        nord: { theme: 'base', themeVariables: {
            background: '#2e3440', primaryColor: '#3b4252',
            primaryTextColor: '#eceff4', primaryBorderColor: '#434c5e',
            lineColor: '#88c0d0'
        }}
    };

    function getConfig() {
        var t = document.documentElement.getAttribute('data-theme') || 'light';
        return CONFIGS[t] || CONFIGS.light;
    }

    // Synchronous DOM transform — runs before copy-btn IIFE
    nodes.forEach(function (code) {
        var pre = code.parentElement;
        if (!pre || pre.tagName !== 'PRE') return;
        var raw = code.textContent;
        var div = document.createElement('div');
        div.className = 'mermaid mermaid-diagram';
        div.setAttribute('data-diagram', raw);
        div.textContent = raw;
        pre.parentNode.replaceChild(div, pre);
    });

    function render(mermaid) {
        var cfg = getConfig();
        mermaid.initialize(Object.assign({ startOnLoad: false }, cfg));
        mermaid.run({ querySelector: '.mermaid-diagram' });
    }

    function onReady(mermaid) {
        render(mermaid);
        // Re-render on theme change
        new MutationObserver(function () {
            document.querySelectorAll('.mermaid-diagram').forEach(function (el) {
                el.removeAttribute('data-processed');
                el.textContent = el.getAttribute('data-diagram');
            });
            render(mermaid);
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }

    if (window.__mermaid) {
        onReady(window.__mermaid);
    } else {
        window.addEventListener('mermaid-ready', function () {
            onReady(window.__mermaid);
        }, { once: true });
    }
})();

(function () {
    document.querySelectorAll('article pre').forEach(function (pre) {
        var btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'copy';
        btn.addEventListener('click', function () {
            var code = pre.querySelector('code');
            var text = (code || pre).innerText;
            navigator.clipboard.writeText(text).then(function () {
                btn.textContent = 'copied!';
                setTimeout(function () { btn.textContent = 'copy'; }, 1500);
            });
        });
        pre.appendChild(btn);
    });
})();

(function () {
    var article = document.querySelector('article');
    if (!article) return;

    article.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach(function (h) {
        var slug = h.textContent.trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        h.id = slug;
        var a = document.createElement('a');
        a.className = 'heading-anchor';
        a.href = '#' + slug;
        a.innerHTML = h.innerHTML;
        h.innerHTML = '';
        h.appendChild(a);
    });
})();

(function () {
    var article  = document.querySelector('article');
    var tocPanel = document.getElementById('toc-panel');
    if (!article || !tocPanel) return;

    var headings = Array.from(article.querySelectorAll('h2, h3'));
    if (headings.length < 2) return;

    var content  = document.getElementById('content');
    var tocInner = tocPanel.querySelector('.toc-inner');

    var label = document.createElement('span');
    label.className = 'toc-label';
    label.textContent = 'On this page';
    tocInner.appendChild(label);

    var links = headings.map(function (h) {
        var a = document.createElement('a');
        a.className = 'toc-link toc-' + h.tagName.toLowerCase();
        a.href = '#' + h.id;
        a.textContent = h.textContent.trim();
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var top = h.getBoundingClientRect().top + content.scrollTop - content.getBoundingClientRect().top - 24;
            content.scrollTo({ top: top, behavior: 'smooth' });
        });
        tocInner.appendChild(a);
        return { heading: h, link: a };
    });

    tocPanel.classList.add('is-visible');

    content.addEventListener('scroll', function () {
        var contentTop = content.getBoundingClientRect().top;
        var current = null;
        headings.forEach(function (h) {
            if (h.getBoundingClientRect().top - contentTop <= 60) current = h;
        });
        links.forEach(function (item) {
            item.link.classList.toggle('is-active', item.heading === current);
        });
    });
})();
