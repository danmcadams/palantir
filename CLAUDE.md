# claude_markdown_server

A local web server for reviewing planning documents. Running at `http://192.168.50.196:8181`.

## Dropping Files

All markdown files go in the `docs/` directory relative to this project:

```
/home/work/projects/claude_markdown_server/docs/
```

Files appear in the sidebar immediately — no server restart needed. The sidebar is nestable: subdirectories become collapsible sections.

## Organizing with Subdirectories

Each project gets its own top-level subfolder under `docs/`. The sidebar renders them as collapsible sections. Nesting works to arbitrary depth.

```
docs/
├── project-alpha/
│   ├── overview.md
│   └── tracker.md
└── project-beta/
    ├── overview.md
    └── services/
        └── api.md
```

When adding a new planning effort, create a new top-level folder under `docs/`. Nest further with subdirectories as needed.

## File Naming

- Use lowercase, hyphen-separated names: `user-auth-flow.md`, `data-model.md`
- The sidebar strips the `.md` extension and uses the filename as the label
- Directory names become section headers in the sidebar (also hyphen-separated is fine)

## Linking Between Documents

Use `/?file=` links to link from one doc to another:

```markdown
See the [tracker](/?file=my-project/tracker.md) for current status.
```

| Path | Link |
|------|------|
| `overview.md` | `/?file=overview.md` |
| `my-project/tracker.md` | `/?file=my-project/tracker.md` |
| `my-project/services/api.md` | `/?file=my-project/services/api.md` |

## Adding Images

Drop image files anywhere under `docs/` and reference them with a `/docs/` prefix:

```markdown
![alt text](/docs/path/to/image.png)
![diagram](/docs/my-project/architecture.svg)
```

**Supported formats:** PNG, JPG, GIF, WebP, SVG, ICO

## Source of Truth Rule

To keep docs from going stale: **one doc owns each piece of information, everyone else links to it.** Don't copy status or facts across files — just link.

## Managing the Server

```bash
cd /home/work/projects/claude_markdown_server

# Start
docker-compose up -d

# Stop
docker-compose down

# Logs
docker-compose logs -f
```

> **Note:** This machine currently uses `docker-compose` (v1). Docker Compose v2 (`docker compose`) is available and migration is planned as part of ops standardization — but hasn't happened yet, so use v1 syntax for now.
