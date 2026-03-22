# claude_markdown_server

A local web server for reviewing planning documents. Running at `http://192.168.50.196:8181`.

## Dropping Files

All markdown files go in the `docs/` directory relative to this project:

```
/home/work/projects/claude_markdown_server/docs/
```

Files appear in the sidebar immediately — no server restart needed. The sidebar is nestable: subdirectories become collapsible sections.

## Organizing with Subdirectories

Group related files in subdirectories. The sidebar renders them as collapsible sections with the directory name as a header. Example structure:

```
docs/
├── overview.md
├── auth/
│   ├── requirements.md
│   └── approach.md
└── data-model/
    ├── schema.md
    └── migrations.md
```

## File Naming

- Use lowercase, hyphen-separated names: `user-auth-flow.md`, `data-model.md`
- The sidebar strips the `.md` extension and uses the filename as the label
- Directory names become section headers in the sidebar (also hyphen-separated is fine)

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

> **Note:** This machine uses `docker-compose` (v1), not `docker compose` (v2).
