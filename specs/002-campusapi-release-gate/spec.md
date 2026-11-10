# Feature Specification: DEV-1 CampusAPI Release Gate

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: A small synthetic API whose release must be assessed through dependency, configuration, secret-scan, SBOM, and functional evidence.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Triages a failing release (Priority: P1)

The student examines the baseline source, lockfile, Dockerfile/configuration, SCA/SAST/secret-scan output, and policy. They decide which findings block release and which need context.

**Why this priority**: Q3–Q5 test risk judgment, not scanner execution alone.

**Independent Test**: A blind reviewer can reproduce the initial gate failure and distinguish at least one relevant finding from one lower-priority or false-positive finding.

**Acceptance Scenarios**:

1. **Given** the initial fixture, **when** the gate runs, **then** it fails on documented release-blocking condition(s), with source and evidence links.
2. **Given** the dummy secret string, **when** the secret scanner alerts, **then** the student can treat it as an intentionally invalid fixture while still explaining the policy that would apply to a valid credential.

### User Story 2 - Repairs while preserving API behavior (Priority: P1)

The student changes the minimum necessary source/dependency/configuration or gate policy, produces an SBOM for the tested build, and demonstrates a secure pass with normal API behavior.

**Why this priority**: Q6–Q10 require a justified before/after comparison.

**Independent Test**: Compare baseline and corrected build, run normal API tests, and cross-check source lockfile, SBOM, and artifact digest.

**Acceptance Scenarios**:

1. **Given** the corrected build, **when** the gate runs, **then** the chosen blocker is resolved without suppressing whole scanner categories.
2. **Given** the released artifact, **when** the SBOM is checked, **then** its package names/versions agree with the pinned lockfile and tested artifact manifest.
3. **Given** tightened checks, **when** normal API requests run, **then** expected responses still pass.

### Edge Cases

- Advisory without an applicable vulnerable path; transitive dependency version differs from lockfile; secret-scan false positive; stale cached image/SBOM; an API test passes against the wrong build.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Provide a small API with at least one normal, testable user flow; source, pinned lockfile, Dockerfile/config, and local build instructions.
- **FR-002**: Provide scanner outputs and a deterministic baseline gate failure, including enough finding context to support triage.
- **FR-003**: Include an invalid dummy secret string only; no valid credential or external endpoint.
- **FR-004**: Generate or validate an SBOM in SPDX or CycloneDX form for the exact build under review.
- **FR-005**: Provide functional tests against the exact artifact before and after the proposed fix.
- **FR-006**: Provide an instructor-only known-good build, holdout condition, expected gate outcomes, and answer rationale.
- **FR-007**: Provide a student card covering Q1–Q12, particularly Q8 lockfile/SBOM, Q9 dummy secret/image findings, and Q10 normal API function.

### Key Entities

- **API build**: source revision, lockfile, image/artefact digest, functional result.
- **Finding**: scanner, affected component/path, applicability, severity, disposition.
- **SBOM**: packages, versions, format/version, relationship to tested artifact.
- **Release decision**: gate result, rationale, exception if any.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A blind reviewer reproduces the baseline failure and corrected pass on pinned local inputs.
- **SC-002**: Every blocker and accepted finding has a traceable reason; broad suppression fails review.
- **SC-003**: Normal API tests pass against the corrected artifact, and SBOM/lockfile/artifact references agree.
- **SC-004**: The student package contains no valid secret, private holdout, golden build, or DEV-2 source.

## Assumptions

- The package is educational and remains local; it is never published to a registry.
- Exact scanner/tool selection follows a compatibility pilot, preserving the learning contract.
