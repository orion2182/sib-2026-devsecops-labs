# Feature Specification: Shared Release Evaluation Harness

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: Two DevSecOps cases need consistent pinned execution, evidence capture, private holdout, and safe packaging.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Student reproduces baseline (Priority: P1)

A student opens the assigned repository, checks the pinned toolchain, runs a baseline gate, and records exact failures without real credentials or public services.

**Why this priority**: Q1–Q5 require an inspectable starting state.

**Independent Test**: In a clean local environment, run the documented gate twice and compare outputs, artifact digest, and failure reasons.

**Acceptance Scenarios**:

1. **Given** a fresh package, **when** baseline instructions run, **then** the intended gate fails for documented reasons and the normal function test can be evaluated separately.
2. **Given** a missing tool or incompatible architecture, **when** preflight runs, **then** it reports a clear blocker and directs the student to the equivalent mentor runner.

### User Story 2 - Student submits a safer release decision (Priority: P1)

A student changes source, dependency, configuration, or policy and shows why the gate now passes or remains blocked, with a functional regression result and evidence bound to the tested artifact.

**Why this priority**: Q6–Q10 require more than a green badge.

**Independent Test**: Run a known-bad and a known-good fixture with the same gate; verify reasoned fail/pass and artifact binding.

**Acceptance Scenarios**:

1. **Given** a corrected fixture, **when** checks run, **then** the gate result references source revision, tool/policy versions, artifact digest, and functional test.
2. **Given** a broad suppression or missing provenance/SBOM required by the case, **when** the gate runs, **then** it fails with a specific reason.

### User Story 3 - Instructor evaluates private holdout (Priority: P1)

An instructor applies a frozen submitted gate to an undisclosed inert fixture and checks the decision without shipping answers to the group.

**Why this priority**: Q11 and grading need independent verification.

**Independent Test**: Inspect both student packages, freeze submitted gate digest, run private holdout twice, and compare identical results with no private file leakage.

**Acceptance Scenarios**:

1. **Given** a student package, **when** its manifest is inspected, **then** it contains one case only and no golden artifact, private holdout, or instructor key.
2. **Given** a frozen gate, **when** scored twice on the same holdout, **then** decisions and reason codes match exactly.

### Edge Cases

- Cache makes an old result appear current; lockfile/source mismatch; tool version drift; checksum mismatch; missing runner; a deliberate override without owner or expiry.
- A scanner exits zero while producing findings, or exits nonzero for an infrastructure error; both must be distinguished from policy pass/fail.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Record source revision, dependency lockfile digest, tool/policy versions, fixture version, build ID, artifact digest, and result in a manifest.
- **FR-002**: Run separate stages for preflight, build, security checks, functional checks, artifact checks, and final policy decision; each must expose a reason code.
- **FR-003**: Support deterministic local/offline fixtures; no public registry publication or valid token is needed.
- **FR-004**: Verify SBOM and/or provenance against the exact tested artifact when required by a case spec.
- **FR-005**: Reject unapproved blanket suppressions and require owner, rationale, expiry, and compensating control for an explicit override.
- **FR-006**: Freeze student gate/rule by digest before mentor-only holdout, log input and output hashes, and prevent private expected results from entering student packages.
- **FR-007**: Produce separate DEV-1 and DEV-2 packages, with checksums and start/reset/troubleshoot instructions.
- **FR-008**: Give the mentor a way to verify normal function before and after remediation, independent of security scanner status.

### Key Entities

- **Build manifest**: source, lockfile, toolchain, fixture, artifact digest, policy version.
- **Gate result**: stage decisions, reason codes, evidence references, final release decision.
- **Policy exception**: owner, scope, reason, expiry, compensating control, approval state.
- **Student package**: assigned case files, card, checksums, instructions; no instructor-only material.
- **Holdout run**: frozen gate digest, fixture digest, private expected/actual decision, reviewer notes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Baseline and corrected runs produce the same result on repeated clean executions with pinned inputs.
- **SC-002**: Every pass/fail can be traced to exact source, lockfile, artifact, tool, and policy versions.
- **SC-003**: Both student packages contain exactly one assigned case and zero instructor-only files or valid credentials.
- **SC-004**: A known-bad fixture is rejected and a known-good fixture accepted for the documented reason, while normal function succeeds.
- **SC-005**: Pilots record actual memory and runtime on representative 8 GB devices and validate an equivalent mentor-hosted fallback when needed.

## Assumptions

- Build tools and scanners are pinned before case implementation; no particular CI vendor is mandatory.
- The release decision is educational and local, not a publication to a real registry.
