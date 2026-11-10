# Feature Specification: DEV-2 BuildGate Provenance

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: A synthetic library artifact whose source-to-build integrity and provenance must be verified before release.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Detects artifact mismatch (Priority: P1)

The student compares pinned source, lockfile, build manifest, package archive, and hashes to locate where a deliberate inert alteration breaks the expected chain.

**Why this priority**: Q8 centers on evidence of mismatch rather than generic vulnerability scanning.

**Independent Test**: Compare known-good and tampered archives against source/build digests; the mismatch is identified at a specific transition.

**Acceptance Scenarios**:

1. **Given** a tampered inert archive, **when** verified against the manifest, **then** the gate rejects it and identifies the first mismatched digest/reference.
2. **Given** a known-good archive, **when** verified with the same policy, **then** it passes without an ad hoc allowlist.

### User Story 2 - Defines an artifact-bound provenance gate (Priority: P1)

The student specifies necessary provenance fields and builds a gate that checks source revision, builder identity, build inputs, and artifact digest, then explains what those fields can and cannot prove.

**Why this priority**: Q9–Q10 require correct provenance semantics and a falsifiable gate.

**Independent Test**: Change each required field or digest in a copy of the attestation and confirm that validation rejects the mismatch.

**Acceptance Scenarios**:

1. **Given** a provenance statement for the wrong source commit, **when** the gate evaluates it, **then** release is blocked even if the archive's own checksum matches its file.
2. **Given** a matching statement and golden artifact, **when** evaluated, **then** the gate accepts them and records source/build/artifact evidence.

### Edge Cases

- A self-consistent hash for the wrong artifact, stale attestation, missing builder identity, unsigned/untrusted attestation, ambiguous source reference, and a rebuild with nondeterministic metadata.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Provide a small synthetic library source repo, pinned lockfile, inert package archives, build logs, hash manifest, and provenance examples.
- **FR-002**: Define the trust root and exact claim being checked; a hash comparison alone MUST NOT be described as proof of trusted origin.
- **FR-003**: Make the source, build input, builder identity, build invocation, and subject artifact digest inspectable as SLSA-compatible provenance fields where used.
- **FR-004**: Require the gate to reject tampered, stale, wrong-source, or wrong-subject fixtures and accept an instructor golden fixture.
- **FR-005**: Keep golden artifact, holdout tampering variants, private expected decisions, and answer rationale instructor-only.
- **FR-006**: Provide a student card covering Q1–Q12, particularly Q8 mismatch location, Q9 required provenance fields, and Q10 fail/pass evidence.
- **FR-007**: No step may require executing the tampered package or publishing any artifact to a public registry.

### Key Entities

- **Source revision**: immutable identifier and dependency lockfile digest.
- **Build invocation**: builder identity, input digest, toolchain, parameters, result digest.
- **Provenance**: statement type/version, subject digest, source/material references, signer or trust claim.
- **Artifact**: inert archive, digest, manifest, known-good/tampered status.
- **Gate decision**: exact comparison and trust checks with reason codes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A blind reviewer can answer Q8–Q10 from manifest/provenance/artifact evidence without executing the package.
- **SC-002**: The same gate rejects every documented tampered/wrong-source/stale holdout fixture and accepts the known-good artifact.
- **SC-003**: Every accepted provenance subject digest equals the evaluated artifact digest; source and builder claims match the configured trust policy.
- **SC-004**: The student package contains no golden artifact, private holdout, valid signing key, or DEV-1 source.

## Assumptions

- This is an educational simulation of provenance verification; trust roots and signatures, if used, are local test identities.
- Reproducible-build limits and any nondeterministic metadata are documented rather than hidden.
