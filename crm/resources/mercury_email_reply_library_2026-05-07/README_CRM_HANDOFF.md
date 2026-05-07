# Mercury Email Reply Library CRM Handoff - 2026-05-07

This file follows the root workspace instructions in `D:\GithubRepos\AGENTS.md`.

Owner: Mercury Vale  
CRM partner: Morpheus  
Purpose: staged CRM handoff package for Outlook signatures, predefined replies, and safe reply templates.

## What Is Here

- `outlook_signatures_all`: all Outlook desktop signature/snippet files currently installed for Mercury/USCPB.
- `aurora_safe_signatures_119_136`: clean Outlook signature files for the 18 Aurora-safe live CRM predefined replies.
- `predefined_reply_markdown`: source Markdown predefined replies from the Sales AI knowledgebase.
- `mercury_manifests_and_reports`: Mercury/Aurora standards, manifest, inventory, and Morpheus import result files.
- `aurora_safe_predefined_replies_119_136_import_ready.json`: structured import/reference file for the 18 Aurora-safe replies.
- `aurora_safe_predefined_replies_119_136_import_ready.csv`: spreadsheet-friendly import/reference file for the 18 Aurora-safe replies.
- `CRM_HANDOFF_SUMMARY_2026-05-07.json`: generated counts and paths.

## Live CRM Status

Aurora already imported the 18 Aurora-safe predefined replies into the live CRM predefined replies table as IDs `119`-`136`.

The rest of the legacy CRM/Outlook signature material is staged here as source material. Do not bulk-import old CRM templates into live CRM without Aurora/Morpheus review because some are system-event templates, restricted-routing-only, or archive/do-not-use.

## Outlook Status

The Outlook desktop signature folder is:

`C:\Users\uscap\AppData\Roaming\Microsoft\Signatures`

The 18 Aurora-safe replies are installed as Outlook signatures with names beginning:

`USCPB Safe CRM <id> - ...`

Mercury's older generated reply snippets are installed with names beginning:

`Mercury ...`

The clean identity signatures are installed with names beginning:

`USCPB - ...`

## Use Rule

For client emails, use:

1. The current Mercury reply pack first.
2. Live CRM predefined replies `119`-`136` when they fit.
3. Older Outlook/CRM snippets only after review and polish.
4. Restricted snippets only as safe portal/escalation language.

Do not use snippets to promise approvals, funding, returns, deposits, wires, balances, KYC outcomes, legal outcomes, investment advice, underwriting, SWIFT/POF/RWA/BCL results, or high-value commitments.
