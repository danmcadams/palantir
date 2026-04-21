# Gibson

A local web server for reading and navigating markdown planning documents. Drop `.md` files in `docs/` and they appear instantly in a nestable sidebar. No restart needed.

## Using with Claude

Paste the following prompt into Claude (adjust the path if Gibson is cloned somewhere other than `~/projects/gibson`):

```
I use a local planning folder called Gibson at ~/projects/gibson.

Rules for all planning work:
- All planning docs go in ~/projects/gibson/docs/ — organized by project subfolder
- File names: lowercase, hyphen-separated (e.g. user-auth.md)
- Cross-link between docs using /?file=project/doc.md syntax
- Source-of-truth rule: one doc owns each fact; others link, never copy
- The docs/ folder is gitignored — content lives only locally
- Use ```mermaid``` fences for any diagram (flowchart, sequence, ERD, Gantt, etc.) — the server renders them natively; prefer diagrams over ASCII art or prose descriptions
- Before writing any planning doc, read ~/projects/gibson/CLAUDE.md and README.md

Do not use any other planning folder.
```

## Requirements

- Docker
- `docker-compose` (v1) or `docker compose` (v2)

## Quick Start

```bash
docker-compose up -d
```

Open `http://localhost:8181` in your browser.

## Adding Documents

Drop markdown files into the `docs/` folder:

```
docs/
└── my-project/
    ├── overview.md
    ├── services/
    │   └── api.md
    └── planning/
        └── roadmap.md
```

Subdirectories become collapsible sections in the sidebar. The sidebar updates immediately — no server restart required.

**File naming:** lowercase, hyphen-separated (`my-document.md`). The sidebar uses the filename as the label (`.md` extension stripped).

## Managing the Server

```bash
# Start
docker-compose up -d

# Stop
docker-compose down

# Logs
docker-compose logs -f
```

## Features

- **Table of contents** — documents with 2+ headings get an auto-generated TOC panel on the right, with active-heading tracking as you scroll
- **Mermaid diagrams** — fenced code blocks tagged `mermaid` render as diagrams; re-renders automatically on theme change
- **Themes** — four built-in themes (Light, Dark, Warm, Nord), selectable via the ⚙ gear in the bottom-left corner
- **Multiple file types** — `.md`, `.txt`, and `.pdf` files all appear in the sidebar
- **Images** — drop images anywhere under `docs/` and reference them as `/docs/path/to/image.png`
- **Cross-doc links** — link between documents with `[label](/?file=path/to/doc.md)`
- **Hidden files** — prefix any file or folder name with `_` to hide it from the sidebar (still accessible by direct URL)
- **Folder export** — hover a directory in the sidebar to reveal a download button that zips the folder

## Notes

- The server is bound to `0.0.0.0:8181` — accessible from other machines on the network, not just localhost
- `docs/` contents are excluded from version control (`.gitignore` inside `docs/`); the folder itself is tracked so it exists after a fresh clone
- Built on PHP 8.4 with [Parsedown](https://parsedown.org/) for rendering
