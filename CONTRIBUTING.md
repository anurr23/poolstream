# Contributing to PoolStream

This document defines the Git workflow, commit convention, and review checklist used by this project.

## Branching model

`main` is the deployable branch and must always stay green. Work happens on short-lived branches created from the latest `main`:

| Branch | Use |
| --- | --- |
| `feat/<scope>` | New user-facing behavior, e.g. `feat/reports-export` |
| `fix/<scope>` | Bug fixes, e.g. `fix/checkout-total` |
| `chore/<scope>` | Tooling, dependencies, refactors without behavior change |
| `docs/<scope>` | Documentation only |
| `test/<scope>` | Test coverage only |

Workflow:

```bash
git switch main
git pull --ff-only origin main
git switch -c feat/reports-export

# work, commit in small logical units
git add <files>
git commit -m "feat(reports): add CSV export for revenue report"

git fetch origin
git rebase origin/main
git push -u origin feat/reports-export
```

Open a pull request into `main`. Keep it focused: one feature or one fix, ideally under ~400 changed lines. Rebase instead of merging `main` into the branch. After approval and a green check, squash-merge and delete the branch.

The repository is currently maintained by a single author, so early history contains direct commits to `main`. New changes follow the branch-and-pull-request flow above so the workflow stays reviewable when collaborators join.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```text
<type>(<optional scope>): <short imperative summary>

<optional body: why the change is needed, trade-offs, migration notes>
```

Rules:

- Summary in imperative mood, lowercase, no trailing period, max ~72 characters.
- One logical change per commit; do not mix formatting, refactoring, and behavior changes.
- Reference an issue in the footer when one exists: `Refs: #12`.
- Use `!` or a `BREAKING CHANGE:` footer for incompatible changes.

Allowed types:

| Type | Meaning |
| --- | --- |
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation |
| `style` | Formatting only, no behavior change |
| `refactor` | Code restructuring, no behavior change |
| `test` | Tests |
| `chore` | Build, tooling, dependencies |
| `perf` | Performance improvement |

Examples from this repository:

```text
feat(ui): add theme-matched SVG logo
fix: resolve ReportController ended_at vs end_time bug and add date validation
docs: update AGENTS.md with completed task notes and future plans/roadmap
```

## Before opening a pull request

- [ ] `composer test` passes.
- [ ] `npm run build` succeeds.
- [ ] `./vendor/bin/pint --test` reports no style issues (run `./vendor/bin/pint` to fix).
- [ ] New behavior has at least one test, or the PR explains why a test is not practical.
- [ ] UI changes include a screenshot or short recording in the PR description.
- [ ] No `.env`, credentials, `vendor/`, `node_modules/`, or generated caches are staged.
- [ ] `.env.example` is updated when a new environment variable is required.
- [ ] Database changes include a migration and, when relevant, a factory/seeder update.
- [ ] The PR description states what changed, why, and how it was verified.

## Repository hygiene

- Never commit secrets. `.env` is ignored; keep `.env.example` free of real credentials.
- `vendor/` and `node_modules/` are installed from lock files, not committed.
- Commit `composer.lock` and `package-lock.json` so installs are reproducible.
- Line endings are normalized to LF by `.gitattributes`; indentation is 4 spaces for PHP/JS/Vue and 2 spaces for YAML (`.editorconfig`).
- Generated files (`public/build`, `bootstrap/cache/*.php`) are build artifacts; rebuild them instead of editing them by hand.

## Releases

Use annotated SemVer tags on `main`:

```bash
git tag -a v1.2.0 -m "PoolStream v1.2.0"
git push origin v1.2.0
```

Tag only commits that passed tests and contain a working production build.
