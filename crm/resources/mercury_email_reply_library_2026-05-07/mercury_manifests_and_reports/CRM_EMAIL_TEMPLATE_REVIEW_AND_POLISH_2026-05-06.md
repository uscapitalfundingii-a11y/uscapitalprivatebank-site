# CRM Email Template Review And Polish - 2026-05-06

This file follows the root workspace instructions in `D:\GithubRepos\AGENTS.md`.

Owner: Aurora  
Assigned to: Mercury Vale  
CRM partner: Morpheus

## Source Reviewed

CRM/Outlook export folder:

`D:\GithubRepos\.mercury\templates\outlook-reply-signatures-2026-05-06`

Manifest:

`D:\GithubRepos\.mercury\templates\outlook-reply-signatures-2026-05-06\manifest.json`

Current manifest count: 327 artifacts.

Breakdown:

- 198 CRM email templates
- 83 CRM SQL/predefined replies
- 23 Morpheus-import predefined replies
- 23 Sales AI predefined reply Markdown sources

## Initial Findings

The CRM template library is useful, but it should not be copied blindly into active email responses.

Known issues:

- Some older replies include broken character encoding.
- Some contain outdated or incorrect portal links.
- Some are too long for routine customer service.
- Some imply outcomes too strongly and should be replaced with safe routing language.
- Some are system event templates, not reusable client-response templates.
- Restricted templates involving wires, deposits, balances, account tiers, instruments, or legal/wealth language must be treated as escalation-only unless rewritten safely.

## Approved Polish Direction

Mercury should build email responses from:

1. Aurora's safe predefined replies.
2. Welcome Package KB articles.
3. CRM email templates only after polishing.
4. Client-specific context.
5. A professional Outlook signature.

## Immediate Safe Improvements

Replace older broad/long replies with the new safe replies:

- Old welcome package replies -> `Short-Welcome-And-CRM-Portal.md`
- Old profile/photo requirement replies -> `Profile-Photo-Upload-Issue.md`
- Old trading platform update replies -> `Trade-Platform-Registration-Invitation.md`
- Old broker/principal replies -> `Broker-Or-Intermediary-Routing.md`
- Old wire/deposit/balance replies -> `Restricted-Deposit-Wire-Or-Balance-Question.md`
- Old instrument/SWIFT/BTC replies -> `High-Value-Instrument-Or-Transaction-Intake.md`
- Old legal/wealth/advisory replies -> `Restricted-Legal-Financial-Advisory-Question.md`
- Old delayed/neglected-message replies -> `Delayed-Response-And-Resume.md`

## Mercury Work Queue

1. Inventory all Outlook reply-signature files currently visible in Outlook.
2. Separate them into:
   - routine-safe
   - useful after polish
   - restricted routing only
   - archive/do not use
3. Send Aurora the inventory with suggested actions.
4. Rewrite useful templates into short Mercury-ready responses.
5. Send approved candidates to Morpheus for CRM predefined reply insertion.
6. Log any template used in a real client email.

## CRM Insertion Target

Morpheus should add the new Aurora safe replies under CRM predefined replies / email response shortcuts, not as automated event templates unless the exact event use is confirmed.

Suggested prefix:

`USCPB Safe -`

Do not overwrite legacy CRM email templates until Aurora approves exact replacements and backups are confirmed.
