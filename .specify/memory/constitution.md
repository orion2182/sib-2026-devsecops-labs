# DevSecOps and Supply Chain Labs 2026 Constitution

## Core Principles

### I. Two different release decisions

DEV-1 MUST focus on source/dependency/configuration findings, an API release gate, and normal-function regression. DEV-2 MUST focus on source-to-artifact integrity, provenance, and tamper rejection. Their repositories, domain stories, fixtures, and decisive evidence MUST differ. Shared runner, packaging, and report formats MAY be reused.

### II. Reproducible evidence before green checks

Every case MUST pin source revision, dependency versions, tool versions, fixture IDs, and artifact digest. The gate MUST disclose which input and policy produced pass/fail. A green pipeline alone MUST NOT count as proof. Students MUST be able to show baseline failure, a justified fix/policy, a later pass, and that legitimate behavior still works. SBOM and provenance MUST refer to the exact artifact tested, not merely to a nearby build.

### III. Safe synthetic supply chain

All tokens, packages, identities, and domains MUST be synthetic or local-only. No case may require public registry publication, live credentials, external service changes, executing an untrusted malicious package, or importing unreviewed dependencies during student work. A tampered artifact is inert data. Secrets shown in scans are deliberately invalid strings.

### IV. Contextual triage and explicit exceptions

Scanner output MUST include enough context for students to distinguish release-blocking issues from lower-priority findings and false positives. Broad suppression that hides the intended flaw MUST fail review. Any override MUST record owner, rationale, expiry, compensating control, and affected artifact; it does not silently convert a failing policy into a pass.

### V. Portable, separate lab delivery

The required workflow SHOULD run locally on representative 8 GB laptops using pinned tools and modest fixtures. This is a release hypothesis subject to pilot; a mentor-hosted runner is required if local operation fails. Each of two groups MUST receive only its assigned repo, student card, and allowed fixture. Instructor golden artifacts, holdout inputs/answers, and reference solutions MUST remain outside student packages.

## Learning and Calendar Constraints

The fixed SIB schedule is **20 November–23 December 2026**. Student cards MUST preserve Q1–Q12 and the G0–G5 learning gates in the research document. DEV-1 and DEV-2 both cover secure development and supply chain, but assessment must follow their different evidence. NIST SSDF supplies secure-development practices; SLSA provenance and SPDX/CycloneDX SBOM formats are used only where the case actually requires them. No mandatory VAPT exploitation or production CI deployment is added.

## Delivery Gates

Before implementation, each spec MUST define an independently demonstrable journey, the deliberate failing condition, valid behavior, exclusions, and testable outcomes. Before release, a reviewer MUST verify pinned/offline operation, expected fail/pass, functional regression, manifest/digest consistency, holdout secrecy, no real credentials or public publication, package separation, resource measurements, and answerability of Q1–Q12. Blind pilots MUST calibrate case effort and gate thresholds before student distribution.

## Governance

This constitution governs the shared runner and both case features. Changes require a written reason and impact review for case specs, pipeline policy, student cards, golden artifacts, and scoring. Use semantic versioning: MAJOR for incompatible principle changes, MINOR for expanded requirements, PATCH for clarification. The copied research document is planning source; this constitution and specs are the active build contract.

**Version**: 1.0.0 | **Ratified**: 2026-10-26 | **Last Amended**: 2026-10-26
