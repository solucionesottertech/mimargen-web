# Skill Registry

Generated: 2026-05-28
Scope: user-level (`~/.config/opencode/skills/`)
Project skills: none found

## Registry Contract

This index maps available skills for orchestration and delegation.
`SKILL.md` is the source of truth — this is an index, not a summary.
Sub-agents receive exact paths and read the full skill source of duty.

## Indexed Skills

| # | Name | Trigger / Description | Path |
|---|------|----------------------|------|
| 1 | branch-pr | Create Gentle AI pull requests with issue-first checks. Trigger: creating, opening, or preparing PRs for review. | `~/.config/opencode/skills/branch-pr/SKILL.md` |
| 2 | chained-pr | Trigger: PRs over 400 lines, stacked PRs, review slices. Split oversized changes into chained PRs that protect review focus. | `~/.config/opencode/skills/chained-pr/SKILL.md` |
| 3 | cognitive-doc-design | Design docs that reduce cognitive load. Trigger: writing guides, READMEs, RFCs, onboarding, architecture, or review-facing docs. | `~/.config/opencode/skills/cognitive-doc-design/SKILL.md` |
| 4 | comment-writer | Write warm, direct collaboration comments. Trigger: PR feedback, issue replies, reviews, Slack messages, or GitHub comments. | `~/.config/opencode/skills/comment-writer/SKILL.md` |
| 5 | go-testing | Trigger: Go tests, go test coverage, Bubbletea teatest, golden files. Apply focused Go testing patterns. | `~/.config/opencode/skills/go-testing/SKILL.md` |
| 6 | issue-creation | Create Gentle AI issues with issue-first checks. Trigger: creating GitHub issues, bug reports, or feature requests. | `~/.config/opencode/skills/issue-creation/SKILL.md` |
| 7 | judgment-day | Trigger: judgment day, dual review, adversarial review, juzgar. Run blind dual review, fix confirmed issues, then re-judge. | `~/.config/opencode/skills/judgment-day/SKILL.md` |
| 8 | skill-creator | Trigger: new skills, agent instructions, documenting AI usage patterns. Create LLM-first skills with valid frontmatter. | `~/.config/opencode/skills/skill-creator/SKILL.md` |
| 9 | skill-improver | Trigger: improve skills, audit skills, refactor skills, skill quality, skillopt. Audit and upgrade existing LLM-first skills with bounded edits and validation gates. | `~/.config/opencode/skills/skill-improver/SKILL.md` |
| 10 | work-unit-commits | Plan commits as reviewable work units. Trigger: implementation, commit splitting, chained PRs, or keeping tests and docs with code. | `~/.config/opencode/skills/work-unit-commits/SKILL.md` |

## Excluded Skills

The following skill groups are excluded from the registry per policy:

| Group | Reason |
|-------|--------|
| `sdd-*` (8 skills) | Pipeline skills, indexed by SDD orchestrator explicitly |
| `_shared` | Support package, not a standalone skill |
| `skill-registry` | Self-referential, excluded to avoid infinite regress |
| `customize-opencode` | Built-in skill, no `SKILL.md` file |

## Convention Files

None found in project root.

## Notes

- 10 skills indexed from 1 user-level directory (`~/.config/opencode/skills/`).
- Second user-level directory `~/.claude/skills/` scanned — all skills are duplicates, skipped.
- No project-level skills found.
