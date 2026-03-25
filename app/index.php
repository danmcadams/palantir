<?php
require_once __DIR__ . '/Parsedown.php';

define('DOCS_DIR', '/var/www/docs');

// --- Collect all .md files as relative paths ---
function get_all_files(): array {
    $files = [];
    if (!is_dir(DOCS_DIR)) return $files;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(DOCS_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if (in_array($f->getExtension(), ['md', 'txt'])) {
            $files[] = str_replace('\\', '/', substr($f->getPathname(), strlen(DOCS_DIR) + 1));
        }
    }
    sort($files);
    return $files;
}

// --- Build nested tree from flat paths ---
// Returns array of nodes: ['type' => 'file'|'dir', 'name' => ..., 'path' => ..., 'children' => [...]]
function build_tree(array $files): array {
    $root = [];
    foreach ($files as $relPath) {
        $parts = explode('/', $relPath);
        $node  = &$root;
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $node[$part] = ['type' => 'file', 'name' => $part, 'path' => $relPath];
            } else {
                if (!isset($node[$part])) {
                    $node[$part] = ['type' => 'dir', 'name' => $part, 'children' => []];
                }
                $node = &$node[$part]['children'];
            }
        }
        unset($node);
    }
    return $root;
}

// --- Recursively render sidebar tree ---
function render_tree(array $tree, string $activeFile, string $prefix = ''): void {
    // Dirs first, then files
    $dirs  = array_filter($tree, fn($n) => $n['type'] === 'dir');
    $files = array_filter($tree, fn($n) => $n['type'] === 'file');

    foreach ($dirs as $node) {
        $path = $prefix . '/' . $node['name'];
        $open = dir_contains_active($node['children'], $activeFile) ? ' open' : '';
        echo '<li class="dir">';
        echo '<details' . $open . ' data-path="' . htmlspecialchars($path, ENT_QUOTES) . '">';
        echo '<summary>' . htmlspecialchars($node['name']) . '</summary>';
        echo '<ul>';
        render_tree($node['children'], $activeFile, $path);
        echo '</ul>';
        echo '</details>';
        echo '</li>';
    }

    foreach ($files as $node) {
        $isActive = $node['path'] === $activeFile;
        $active   = $isActive ? ' class="active"' : '';
        $label    = htmlspecialchars(preg_replace('/\.(md|txt)$/', '', $node['name']));
        $fileext  = htmlspecialchars('.' . pathinfo($node['name'], PATHINFO_EXTENSION));
        $href     = '/?file=' . urlencode($node['path']);
        $dataext  = $isActive ? ' data-ext="' . $fileext . '"' : '';
        echo '<li' . $active . $dataext . '><a href="' . $href . '">' . $label . '</a></li>';
    }
}

// Check if any descendant matches the active file
function dir_contains_active(array $children, string $activeFile): bool {
    foreach ($children as $node) {
        if ($node['type'] === 'file' && $node['path'] === $activeFile) return true;
        if ($node['type'] === 'dir' && dir_contains_active($node['children'], $activeFile)) return true;
    }
    return false;
}

// --- Main ---
$allFiles      = get_all_files();
$tree          = build_tree($allFiles);
$requestedFile = $_GET['file'] ?? null;

// Validate requested file
$content = '';
$title   = 'DGIST';
$error   = false;

if ($requestedFile !== null) {
    $fullPath = realpath(DOCS_DIR . '/' . $requestedFile);
    $ext = $fullPath ? pathinfo($fullPath, PATHINFO_EXTENSION) : '';
    if ($fullPath === false
        || strpos($fullPath, DOCS_DIR) !== 0
        || !in_array($ext, ['md', 'txt'])
        || !is_file($fullPath)) {
        $error = true;
    } elseif ($ext === 'txt') {
        $content = '<pre class="plaintext">' . htmlspecialchars(file_get_contents($fullPath)) . '</pre>';
        $title   = htmlspecialchars(basename($fullPath, '.txt'));
    } else {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false);
        $content = $parsedown->text(file_get_contents($fullPath));
        $title   = htmlspecialchars(basename($fullPath, '.md'));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/style.css">
    <script>
        document.documentElement.classList.add('notransition');
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            if (localStorage.getItem('sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
</head>
<body>
    <aside id="sidebar">
        <div class="sidebar-header">
            <a href="/" class="sidebar-title">DGIST</a>
            <button id="sidebar-toggle" class="sidebar-toggle" title="Collapse sidebar" aria-label="Collapse sidebar">‹</button>
        </div>
        <nav>
            <?php if (empty($allFiles)): ?>
                <p class="sidebar-empty">No files yet.</p>
            <?php else: ?>
                <ul class="tree">
                    <?php render_tree($tree, $requestedFile ?? ''); ?>
                </ul>
            <?php endif; ?>
        </nav>
    </aside>

    <div class="theme-switch-wrap" id="theme-switch-fixed">
        <span class="theme-icon">☀</span>
        <button id="theme-toggle" role="switch" class="theme-switch" aria-checked="false" aria-label="Toggle dark mode">
            <span class="switch-thumb"></span>
        </button>
        <span class="theme-icon">☾</span>
    </div>
    <div id="main">
        <main id="content">
            <?php if ($error): ?>
                <p class="empty">File not found.</p>
            <?php elseif ($requestedFile !== null): ?>
                <article data-ext="<?= htmlspecialchars(strtolower($ext)) ?>">
                    <?= $content ?>
                </article>
            <?php else: ?>
                <div class="home">
                    <div class="home-logo">DGIST</div>
                    <p class="home-tagline">your documents, organized and readable.</p>
                    <div class="home-hints">
                        <div class="home-hint">
                            <span class="hint-key">←</span>
                            <span>pick a file from the sidebar</span>
                        </div>
                        <div class="home-hint">
                            <span class="hint-key">drop</span>
                            <span>any <code>.md</code> or <code>.txt</code> into <code>docs/</code> to add it</span>
                        </div>
                        <div class="home-hint">
                            <span class="hint-key">nest</span>
                            <span>subdirectories become collapsible sections</span>
                        </div>
                        <div class="home-hint">
                            <span class="hint-key">img</span>
                            <span>reference images as <code>/docs/path/to/image.png</code></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        <?php if ($requestedFile !== null && !$error): ?>
        <div id="doc-bar">
            <span id="doc-bar-crumb"></span>
            <span id="doc-bar-type"><?= $title ?>.<?= htmlspecialchars(strtolower($ext)) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <script>
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
        var btn = document.getElementById('theme-toggle');
        function isDark() { return document.documentElement.getAttribute('data-theme') === 'dark'; }
        function updateSwitch() { btn.setAttribute('aria-checked', isDark() ? 'true' : 'false'); }
        updateSwitch();
        btn.addEventListener('click', function () {
            if (isDark()) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateSwitch();
        });
    })();

    (function () {
        var btn = document.getElementById('sidebar-toggle');
        var html = document.documentElement;
        function isCollapsed() { return html.classList.contains('sidebar-collapsed'); }
        function updateBtn() {
            btn.textContent = isCollapsed() ? '›' : '‹';
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
        var bar   = document.getElementById('doc-bar');
        var crumb = document.getElementById('doc-bar-crumb');
        if (!bar) return;

        var content  = document.getElementById('content');
        var article  = document.querySelector('article');
        var headings = Array.from(article.querySelectorAll('h1,h2,h3,h4,h5,h6'));

        // Inject IDs and hover anchor links
        headings.forEach(function (h) {
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

        // Update crumb with the last heading scrolled past (skip h1)
        content.addEventListener('scroll', function () {
            var scrollTop = content.scrollTop + 8;
            var current = null;
            headings.forEach(function (h) {
                if (h.offsetTop <= scrollTop) current = h;
            });
            crumb.textContent = (current && current.tagName !== 'H1')
                ? current.querySelector('.heading-anchor').textContent
                : '';
        });
    })();
    </script>
</body>
</html>
