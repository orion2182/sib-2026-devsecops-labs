# DevSecOps Labs 2026 — Spec Kit workspace

Instructor-only builder workspace for **two distinct DevSecOps and software supply-chain cases**. Spec Kit 1.2.3 with Codex skills is installed locally. **The two case repositories, pipelines, fixtures, and reference outcomes have not been implemented yet.**

| Path | Role |
| --- | --- |
| `.specify/memory/constitution.md` | Shared build and release rules |
| `specs/001-release-evaluation-harness/spec.md` | Reproducible runner, packaging, evidence, holdout |
| `specs/002-campusapi-release-gate/spec.md` | DEV-1 API release gate case |
| `specs/003-buildgate-provenance/spec.md` | DEV-2 artifact provenance case |
| `docs/reference/Riset-Lab-DevSecOps-Supply-Chain-2026.md` | Frozen research/planning source |
| `shared/`, `cases/` | Reserved implementation locations |

## Continue with Spec Kit

Open Codex from this directory. These are **Codex skills, not shell commands**. For the active feature:

1. `$speckit-plan` — choose the runner, toolchain, and artifact design.
2. `$speckit-checklist` — review specification completeness.
3. `$speckit-tasks` — write dependency-ordered build tasks.
4. `$speckit-analyze` — check cross-document consistency.
5. `$speckit-implement` — build the feature.
6. `$speckit-converge` — close remaining gaps.

The first active feature is the shared runner. For another feature, set `.specify/feature.json` to `{"feature_directory":"specs/002-campusapi-release-gate"}` or `{"feature_directory":"specs/003-buildgate-provenance"}`. This file is local and ignored by Git. Recommended order: runner, DEV-1, DEV-2, blind pilot. Do not rerun `$speckit-specify` for the existing specs unless scope changes deliberately.

## Release gate

Each case must include pinned source/lockfile, baseline failure, expected legitimate function, synthetic scanner output where relevant, a reproducible before/after gate, instructor-only holdout, student card Q1–Q12, and private reference outcomes. Verify the gate rejects deliberate faults and accepts a known-good build, while no student package contains secrets, answer keys, or live registry access.
