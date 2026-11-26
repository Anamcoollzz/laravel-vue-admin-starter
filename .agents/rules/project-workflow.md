---
trigger: always_on
---

# Project Workflow Rules

These rules take precedence over any default framework or Boost guidelines.

## No Testing

- Do NOT write, create, update, or run any tests whatsoever (including Pest, PHPUnit, Vitest, Playwright, or any `artisan test` / `pest` commands) unless the user explicitly asks for testing in that prompt.
- This rule explicitly overrides any test enforcement guidelines in `AGENTS.md`.

## Controller Method Comments

- Every method in a controller (`app/Http/Controllers/**`) MUST have a PHPDoc block directly above it that describes what the method does, written in **English**.
- The description should state the purpose clearly (e.g., `Display a paginated list of users with optional search filter.`, `Store a newly created role and sync its permissions.`), not just repeat the method name.
- Mention notable side effects or guards when relevant (e.g., prevents deleting own account, creates missing permissions on import).
- Apply this when creating new controller methods and when modifying existing ones that lack a comment.

## Auto-Stage Changes

- Every time you finish a task or complete a user prompt that creates, modifies, or deletes files, automatically stage all changes by running `git add -A` before giving your final response.

## Commit Message Suggestion

- Every time you finish a task or complete a user prompt, always include a suggested Git commit message in **English** at the end of your response.
- Format the commit message cleanly (e.g., using Conventional Commits such as `feat: ...`, `fix: ...`, `chore: ...`, `docs: ...`).
- Always wrap the commit message inside a fenced code block (` ```text `) containing only the commit message so the user can copy it directly with one click without manually selecting text.
