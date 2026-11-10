# DevSecOps Labs 2026 — contributor instructions

- Read `.specify/memory/constitution.md` and the active `specs/*/spec.md` before implementation.
- This is an instructor-only builder workspace for two separate simulated source repositories. Student packages must omit golden artifacts, hidden gate fixtures, expected outcomes, and instructor keys.
- DEV-1 concerns a small API release pipeline, dependency/configuration triage, SBOM, and normal-function checks. DEV-2 concerns source-to-artifact digest and provenance verification. Do not make one case a renamed copy of the other.
- All credentials, domains, artifacts, packages, and identities are synthetic. Never introduce valid tokens, public registry publication, untrusted package execution, or access to campus production systems.
- Pin dependencies and tool versions. Prefer offline/local fixtures and reproducible builds. A scanner report is evidence to triage, not an answer key.
- Every gate must have inspectable pass and fail reasons, a safe override workflow, and an independent functional check. Compare source, lockfile, SBOM, provenance, and artifact digest where applicable.
- Keep the mentor-only holdout and golden artifact outside student packages; do not encode expected answers in public tests or README files.
- Pilot both cases on representative 8 GB laptops, then provide a mentor-hosted runner fallback if resource targets fail.
- The first active feature is `specs/001-release-evaluation-harness`; switch `.specify/feature.json` for the other existing specs.
