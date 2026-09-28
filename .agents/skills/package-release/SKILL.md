---
name: package-release
description: "Use this skill when preparing Laravel package releases: CHANGELOG.md updates, generated release notes, GitHub release workflows, version checks, tags, release validation, or release automation changes. Never publish autonomously."
license: MIT
metadata:
  author: laravel
---

# Package Release

## Primary Goal

Prepare a safe package release checklist and implementation without tagging, pushing, or publishing unless the user explicitly approves that action.

## Workflow

1. Review `CHANGELOG.md`, generated release notes config, open diff, and pending package changes.
2. Move the `Unreleased` entries in `CHANGELOG.md` under a new version heading with today's date, in the PR that precedes the release. `main` is protected, so nothing can commit the changelog after tagging.
3. Validate the release state with `composer test` before recommending a release.
4. Confirm whether version metadata needs to change; many Laravel packages rely on Git tags rather than a hardcoded package version.
5. After the PR is merged, create the GitHub release from `main` with generated notes (`gh release create vX.Y.Z --target main --generate-notes`); the `.github/release.yml` categories come from PR labels.
6. Do not tag, push, or publish without explicit user approval.

## References

- `CHANGELOG.md`
- `.github/release.yml`
- `.github/workflows/tests.yml`
- `composer.json`

## Examples

- Prepare a release by moving the changelog entries under the version heading in the release PR, confirming generated release notes categories, running `composer test`, and drafting the release command for user approval.
- Update release notes grouping in `.github/release.yml` when a new label convention is added.

## Anti-Patterns

- Creating tags, pushing branches, or publishing releases without explicit approval.
- Skipping `composer test` before a release recommendation.
- Treating generated release notes as a replacement for meaningful `CHANGELOG.md` entries.
- Changing release workflows without checking the supported matrix in `.github/workflows/tests.yml`.
