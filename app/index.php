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
        if (in_array($f->getExtension(), ['md', 'txt', 'pdf'])) {
            $rel   = str_replace('\\', '/', substr($f->getPathname(), strlen(DOCS_DIR) + 1));
            $parts = explode('/', $rel);
            $hidden = false;
            foreach ($parts as $part) {
                if (str_starts_with($part, '_')) { $hidden = true; break; }
            }
            if (!$hidden) $files[] = $rel;
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
        $path       = $prefix . '/' . $node['name'];
        $open       = dir_contains_active($node['children'], $activeFile) ? ' open' : '';
        $exportHref = '/export?folder=' . urlencode(ltrim($path, '/'));
        echo '<li class="dir">';
        echo '<details' . $open . ' data-path="' . htmlspecialchars($path, ENT_QUOTES) . '">';
        echo '<summary>';
        echo '<span class="dir-name">' . htmlspecialchars($node['name']) . '</span>';
        echo '<a class="dir-export" href="' . $exportHref . '" title="Download as zip" onclick="event.stopPropagation()">';
        echo '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
        echo '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>';
        echo '<polyline points="7 10 12 15 17 10"/>';
        echo '<line x1="12" y1="15" x2="12" y2="3"/>';
        echo '</svg>';
        echo '</a>';
        echo '</summary>';
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
$title   = 'Palantir';
$error   = false;

if ($requestedFile !== null) {
    $fullPath = realpath(DOCS_DIR . '/' . $requestedFile);
    $ext = $fullPath ? pathinfo($fullPath, PATHINFO_EXTENSION) : '';
    if ($fullPath === false
        || strpos($fullPath, DOCS_DIR) !== 0
        || !in_array($ext, ['md', 'txt', 'pdf'])
        || !is_file($fullPath)) {
        $error = true;
    } elseif ($ext === 'pdf') {
        $content = '<embed src="/docs/' . htmlspecialchars($requestedFile) . '" type="application/pdf" class="pdf-viewer">';
        $title   = htmlspecialchars(basename($fullPath, '.pdf'));
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
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/style.css">
    <script>
        document.documentElement.classList.add('notransition');
        (function () {
            var t = localStorage.getItem('theme');
            if (!t) { t = 'light'; localStorage.setItem('theme', 'light'); }
            document.documentElement.setAttribute('data-theme', t);
            if (localStorage.getItem('sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
</head>
<body>
    <aside id="sidebar">
        <div class="sidebar-header">
            <a href="/" class="sidebar-title">Palantir</a>
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
        <div class="sidebar-footer">
            <button id="settings-btn" class="settings-btn" aria-label="Settings" title="Settings" aria-expanded="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </button>
        </div>
    </aside>

    <div id="settings-panel" class="settings-panel">
        <div class="settings-header">
            <span class="settings-title">Theme</span>
            <button id="settings-close" class="settings-close" aria-label="Close settings">✕</button>
        </div>
        <a href="/?file=_user-guide.md" class="settings-guide-link">User Guide</a>
        <div class="theme-grid">
            <button class="theme-card" data-theme="light" aria-label="Light theme">
                <div class="theme-card-preview">
                    <div class="tc-sidebar" style="background:#f3f0f7"></div>
                    <div class="tc-content" style="background:#fff">
                        <div class="tc-bar" style="background:#7c3aed"></div>
                        <div class="tc-line" style="background:#222"></div>
                        <div class="tc-line-accent" style="background:#7c3aed"></div>
                    </div>
                </div>
                <span class="theme-card-label">Light</span>
            </button>
            <button class="theme-card" data-theme="dark" aria-label="Dark theme">
                <div class="theme-card-preview">
                    <div class="tc-sidebar" style="background:#1e1e2e"></div>
                    <div class="tc-content" style="background:#1e1e2e">
                        <div class="tc-bar" style="background:#cba6f7"></div>
                        <div class="tc-line" style="background:#cdd6f4"></div>
                        <div class="tc-line-accent" style="background:#cba6f7"></div>
                    </div>
                </div>
                <span class="theme-card-label">Dark</span>
            </button>
            <button class="theme-card" data-theme="hacker" aria-label="Hacker theme">
                <div class="theme-card-preview">
                    <div class="tc-sidebar" style="background:#050f05"></div>
                    <div class="tc-content" style="background:#0a0a0a">
                        <div class="tc-bar" style="background:#39ff14"></div>
                        <div class="tc-line" style="background:#00ff41"></div>
                        <div class="tc-line-accent" style="background:#39ff14"></div>
                    </div>
                </div>
                <span class="theme-card-label">Hacker</span>
            </button>
            <button class="theme-card" data-theme="warm" aria-label="Warm theme">
                <div class="theme-card-preview">
                    <div class="tc-sidebar" style="background:#2c1a0e"></div>
                    <div class="tc-content" style="background:#fdf6e3">
                        <div class="tc-bar" style="background:#c0622a"></div>
                        <div class="tc-line" style="background:#3d2b1f"></div>
                        <div class="tc-line-accent" style="background:#c0622a"></div>
                    </div>
                </div>
                <span class="theme-card-label">Warm</span>
            </button>
            <button class="theme-card" data-theme="nord" aria-label="Nord theme">
                <div class="theme-card-preview">
                    <div class="tc-sidebar" style="background:#242933"></div>
                    <div class="tc-content" style="background:#2e3440">
                        <div class="tc-bar" style="background:#88c0d0"></div>
                        <div class="tc-line" style="background:#eceff4"></div>
                        <div class="tc-line-accent" style="background:#88c0d0"></div>
                    </div>
                </div>
                <span class="theme-card-label">Nord</span>
            </button>
        </div>
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
                <div class="home" id="home">
                    <div id="hacker-home" class="hacker-home" style="display:none">
                        <div class="hacker-title">HACK<br>THE<br>PLANET</div>
                        <pre id="manifesto-text" class="manifesto-text"></pre>
                    </div>
                    <div id="default-home">
                    <div class="home-logo">Palantir</div>
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
                    <a href="/?file=_user-guide.md" class="home-guide-link">Using with Claude →</a>
                    </div><!-- /default-home -->
                </div>
            <?php endif; ?>
        </main>
        <?php if ($requestedFile !== null && !$error): ?>
        <div id="doc-bar">
            <span id="doc-bar-crumb"></span>
            <?php $nativeTypes = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']; ?>
            <?php $docBarHref = in_array(strtolower($ext), $nativeTypes) ? '/docs/' . htmlspecialchars($requestedFile) : '/raw?file=' . urlencode($requestedFile); ?>
            <a id="doc-bar-type" href="<?= $docBarHref ?>" target="_blank" rel="noopener"><?= $title ?>.<?= htmlspecialchars(strtolower($ext)) ?></a>
        </div>
        <?php endif; ?>
    </div>
    <script src="/app.js"></script>
</body>
</html>
