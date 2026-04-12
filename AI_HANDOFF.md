# AI Handoff

This file is the persistent repo note for continuing work on another PC or in another Codex session.

Template source:
- `G:\GithubRepos\GithubUtilities\codex_repo_handoff_template.md`

## Project
- Name: U.S. Capital Private Bank site
- Primary path: `G:\GithubRepos\uscapitalprivatebank-site`
- Live site / app: `https://www.uscapitalprivatebank.com/`
- Deployment target: DreamHost

## Current Priorities
- Keep public site, CRM, and verify workspace stable while continuing CRM customization.
- Use repo-based notes so sessions can resume across PCs.

## Recent Completed Work
- Restored the live homepage after the wrong root `index.php` had been uploaded.
- Built and deployed verification admin, ID card, and certificate tooling in the verify workspace.
- Updated the public verification page copy, layout, supporting visuals, and bank-name styling.
- Added CRM/verify admin flows for signup approvals and email confirmations.
- Patched Perfex CRM admin sidebar branding, profile text styling, and top-bar theming.

## In Progress
- Perfex CRM sidebar width and profile presentation are being tuned live.
- WhatsApp CRM setup is in progress and still needs Meta-side configuration.

## Pending Requests
- Continue refining the Perfex CRM sidebar width if needed.
- Finish WhatsApp connection setup after the Meta WhatsApp Business account is created.
- Continue performance optimization review for the main site when the user returns to it.

## Important URLs
- Public site: `https://www.uscapitalprivatebank.com/`
- Verification page: `https://www.uscapitalprivatebank.com/verification/`
- Verify workspace: `https://www.uscapitalprivatebank.com/crm/verify/`
- Verify admin ID cards: `https://www.uscapitalprivatebank.com/crm/verify/admin/idcards.php`
- Verify admin certificates: `https://www.uscapitalprivatebank.com/crm/verify/admin/certificates.php`
- Perfex CRM: `https://www.uscapitalprivatebank.com/crm/admin/`
- Perfex help center: `https://help.perfexcrm.com/`

## Access Notes
- DreamHost host: `iad1-shared-e1-24.dreamhost.com`
- DreamHost user: `dh_9a4ezr`
- DreamHost web root: `/home/dh_9a4ezr/uscapitalprivatebank.com`
- Keep passwords out of this file unless the user explicitly asks for that.

## Deployment Notes
- Uploads have been done with PuTTY tools from Windows:
  - `C:\Program Files\PuTTY\pscp.exe`
  - `C:\Program Files\PuTTY\plink.exe`
- Remote PHP lint path used previously:
  - `/usr/local/bin/php-8.3`

## Key Files
- Root site entry: `G:\GithubRepos\uscapitalprivatebank-site\index.php`
- Public verification page: `G:\GithubRepos\uscapitalprivatebank-site\verification\index.html`
- Verification CSS: `G:\GithubRepos\uscapitalprivatebank-site\verification\verification.css`
- CRM sidebar view: `G:\GithubRepos\uscapitalprivatebank-site\crm\application\views\admin\includes\aside.php`
- CRM header view: `G:\GithubRepos\uscapitalprivatebank-site\crm\application\views\admin\includes\header.php`
- Perfex base CSS: `G:\GithubRepos\uscapitalprivatebank-site\crm\assets\css\style.css`
- Perfex minified CSS: `G:\GithubRepos\uscapitalprivatebank-site\crm\assets\css\style.min.css`

## Testing / Verification
- Verify public site returns HTTP 200 after any root-site changes.
- Hard refresh the CRM after CSS/sidebar changes because cached styles can hide live updates.
- Confirm verify admin routes still require authentication after any verify changes.

## Known Issues
- Perfex admin CSS may be influenced by multiple layers, including cached/minified CSS and inline overrides.
- WhatsApp CRM connection is not complete because no WhatsApp Business account is yet attached in Meta.

## Next Best Actions
1. Finish Meta WhatsApp Business account creation and webhook setup.
2. Re-check the live Perfex sidebar width after the latest inline/admin CSS changes.
3. Resume main-site performance optimization once CRM styling and WhatsApp setup are stable.

## Notes For Future Sessions
- If working anywhere under `G:\GithubRepos`, read `G:\GithubRepos\GithubUtilities\AGENTS.md` first.
- For this repo, update this handoff file whenever a major deployment or decision is made.
