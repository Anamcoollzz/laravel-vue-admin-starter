---
trigger: always_on
---

# Project Workflow Rules

These rules take precedence over any default framework or Boost guidelines.

## No Testing

- Do NOT write, create, update, or run any tests whatsoever (including Pest, PHPUnit, Vitest, Playwright, or any `artisan test` / `pest` commands) unless the user explicitly asks for testing in that prompt.
- This rule explicitly overrides any test enforcement guidelines in `AGENTS.md`.

## Commit Message Suggestion

- Every time you finish a task or complete a user prompt, always include a suggested Git commit message in **English** at the end of your response.
- Format the commit message cleanly (e.g., using Conventional Commits such as `feat: ...`, `fix: ...`, `chore: ...`, `docs: ...`).
- Always wrap the commit message inside a fenced code block (` ```text `) containing only the commit message so the user can copy it directly with one click without manually selecting text.
