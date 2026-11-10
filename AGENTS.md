# DevSecOps Labs 2026 — contributor instructions

- Read `.specify/memory/constitution.md` and the active `specs/*/spec.md` before implementation.
- This is a public builder workspace for two separate simulated source repositories, not a student handout. Keep golden artifacts, hidden gate fixtures, expected outcomes, secret seeds, and instructor keys outside this public repository and student packages.
- DEV-1 concerns a small API release pipeline, dependency/configuration triage, SBOM, and normal-function checks. DEV-2 concerns source-to-artifact digest and provenance verification. Do not make one case a renamed copy of the other.
- All credentials, domains, artifacts, packages, and identities are synthetic. Never introduce valid tokens, public registry publication, untrusted package execution, or access to campus production systems.
- Pin dependencies and tool versions. Prefer offline/local fixtures and reproducible builds. A scanner report is evidence to triage, not an answer key.
- Every gate must have inspectable pass and fail reasons, a safe override workflow, and an independent functional check. Compare source, lockfile, SBOM, provenance, and artifact digest where applicable.
- Keep the mentor-only holdout and golden artifact outside student packages; do not encode expected answers in public tests or README files.
- Pilot both cases on representative 8 GB laptops, then provide a mentor-hosted runner fallback if resource targets fail.
- The first active feature is `specs/001-release-evaluation-harness`; switch `.specify/feature.json` for the other existing specs.

## OpenCode handoff

- The repository contains specifications, not runnable case repositories or pipelines. Build the shared runner first, then DEV-1 CampusAPI and DEV-2 BuildGate. Preserve the 20 November–23 December 2026 calendar and Q1–Q12.
- OpenCode loads this file from the repository root. Use `python3 scripts/select_feature.py --list` and select a feature before interactive `/speckit.plan`, `/speckit.tasks`, and `/speckit.implement` commands.
- A case is complete only when pinned inputs, baseline fail, justified pass, normal-function check, exact artifact binding, holdout separation, and a representative-device pilot pass.
