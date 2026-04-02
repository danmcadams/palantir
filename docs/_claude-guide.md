# Using with Claude

Paste the following prompt into Claude (adjust the path if Palantir is cloned somewhere other than `~/projects/palantir`):

```
I use a local planning server called Palantir at ~/projects/palantir (http://localhost:8181).

Rules for all planning work:
- All planning docs go in ~/projects/palantir/docs/ — organized by project subfolder
- File names: lowercase, hyphen-separated (e.g. user-auth.md)
- Cross-link between docs using /?file=project/doc.md syntax
- Source-of-truth rule: one doc owns each fact; others link, never copy
- The docs/ folder is gitignored — content lives only locally
- Before writing any planning doc, read ~/projects/palantir/CLAUDE.md and README.md

Do not use any other planning folder.
```
