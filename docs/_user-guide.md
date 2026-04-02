# User Guide

A quick reference for working with this planning server.

---

## Dropping Files

Put any `.md`, `.txt`, or `.pdf` file under `docs/` and it shows up in the sidebar immediately — no restart needed.

- `.md` files are rendered as formatted documents
- `.txt` files are displayed as plain text
- `.pdf` files are embedded with the browser's native PDF viewer

```
docs/
├── user-guide.md       ← this file
├── my-project/
│   ├── overview.md
│   └── tracker.md
└── another-project/
    └── notes.md
```

Subdirectories become collapsible sections in the sidebar.

---

## File Naming

- Lowercase, hyphen-separated: `my-doc.md`, `q2-tracker.md`
- The sidebar uses the filename (without `.md`) as the label
- Directory names follow the same convention

---

## Hiding Files from the Sidebar

Prefix any file or folder name with `_` to hide it from the sidebar. The file is still accessible via direct URL — it just won't appear in the nav.

```
docs/
├── _internal-notes.md      ← hidden from sidebar
├── _drafts/                ← entire folder hidden
│   └── wip.md
└── my-project/
    └── overview.md
```

This works for both files and directories.

---

## Linking Between Documents

Use `/?file=` links to link from one doc to another:

```markdown
See the [tracker](/?file=my-project/tracker.md) for current status.
```

A few more examples:

| Path | Link |
|------|------|
| `overview.md` | `/?file=overview.md` |
| `my-project/tracker.md` | `/?file=my-project/tracker.md` |
| `my-project/services/api.md` | `/?file=my-project/services/api.md` |

---

## Code Blocks

Wrap code in triple backticks. Add a language name after the opening fence for syntax highlighting:

````markdown
```python
def greet(name):
    return f"Hello, {name}!"
```
````

````markdown
```sql
SELECT id, name FROM users WHERE active = true;
```
````

Supported languages include `python`, `sql`, `bash`, `javascript`, `typescript`, `json`, `yaml`, `markdown`, and many others.

For inline code within a sentence, use single backticks: `` `some_variable` ``.

A copy button appears on hover for each code block.

---

## Adding Images

Drop image files anywhere under `docs/` and reference them with a `/docs/` prefix:

```markdown
![alt text](/docs/path/to/image.png)
![diagram](/docs/my-project/architecture.svg)
```

**Supported formats:** PNG, JPG, GIF, WebP, SVG, ICO

For example, here's an SVG sitting at `docs/img/example.svg`:

![A little rocket flying past a purple planet](/docs/img/example.svg)

---

## Organizing Multiple Projects

Each project gets its own top-level folder:

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

The sidebar renders each folder as a collapsible section. Nesting works to arbitrary depth.

---

## Exporting a Folder

Each folder in the sidebar has a small download icon. Click it to download the entire folder as a `.zip` file.

---

## Source of Truth Rule

To keep docs from going stale: **one doc owns each piece of information, everyone else links to it.** Don't copy status or facts across files — just link.

---

## Themes

Click the gear icon at the bottom of the sidebar to open the theme picker. Available themes: **Light**, **Dark**, **Hacker**, **Warm**, **Nord**. Your preference is saved automatically.

---

## Sidebar

Click the `‹` button at the top of the sidebar to collapse it and give the document more room. Click again to expand. State is saved automatically.

---

## Managing the Server

```bash
cd /home/work/projects/claude_markdown_server

docker-compose up -d    # start
docker-compose down     # stop
docker-compose logs -f  # tail logs
```

The server runs at `http://192.168.50.196:8181`.
