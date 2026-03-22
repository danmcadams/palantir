# Markdown Planning Server

A local web server for reading and navigating markdown planning documents. Drop `.md` files in `docs/` and they appear instantly in a nestable sidebar. No restart needed.

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

## Notes

- The server is bound to `0.0.0.0:8181` — accessible from other machines on the network, not just localhost
- `docs/` contents are excluded from version control (`.gitignore` inside `docs/`); the folder itself is tracked so it exists after a fresh clone
- Built on PHP 8.4 with [Parsedown](https://parsedown.org/) for rendering
