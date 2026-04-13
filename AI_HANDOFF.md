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
- Finish Aurora-powered ticket reply tools in the CRM and push them live to DreamHost once SSH key auth is active.

## Recent Completed Work
- Restored the live homepage after the wrong root `index.php` had been uploaded.
- Built and deployed verification admin, ID card, and certificate tooling in the verify workspace.
- Updated the public verification page copy, layout, supporting visuals, and bank-name styling.
- Added CRM/verify admin flows for signup approvals and email confirmations.
- Patched Perfex CRM admin sidebar branding, profile text styling, and top-bar theming.
- Added local CRM support for Aurora/Base44 ticket AI:
  - reply suggestion routed through a capability-aware AI service
  - follow-up message generation endpoint/button
  - dictation mic UI with browser speech plus server transcription fallback
  - generic Base44 Super Agent provider scaffold and transcription service config sample
- Added shared direct-publish helper:
  - `G:\GithubRepos\GithubUtilities\publish_ssh_files.ps1`
  - shared SSH publish guidance in `G:\GithubRepos\GithubUtilities\AGENTS.md`
- Added repo-local DreamHost wrapper for the current Aurora CRM upload set:
  - `G:\GithubRepos\uscapitalprivatebank-site\publish_dreamhost_crm_aurora.ps1`

## In Progress
- Perfex CRM sidebar width and profile presentation are being tuned live.
- WhatsApp CRM setup is in progress and still needs Meta-side configuration.
- DreamHost direct publish setup is being converted to SSH-key-based deployment from the local repo workspace.

## Pending Requests
- Continue refining the Perfex CRM sidebar width if needed.
- Finish WhatsApp connection setup after the Meta WhatsApp Business account is created.
- Continue performance optimization review for the main site when the user returns to it.
- Deploy the new CRM ticket actions live:
  - `Revise`
  - `Follow-up Message`
  - `Aurora Mic`

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
- DreamHost known SSH host key fingerprint observed in WinSCP:
  - `ssh-ed25519 255 QjwRRIb1/hmhzp+EMq3BJkRYdDbsHMSKxiFepNXpNto`
- Local deploy key created on this PC:
  - private: `C:\Users\uscap\.ssh\codex_deploy_ed25519`
  - public: `C:\Users\uscap\.ssh\codex_deploy_ed25519.pub`
- Current blocker:
  - DreamHost still returns `Permission denied (publickey,password)` for the new key, so the public key must be installed on the server or hosting panel before direct publish will work.
- Keep passwords out of this file unless the user explicitly asks for that.

## Deployment Notes
- Uploads have been done with PuTTY tools from Windows:
  - `C:\Program Files\PuTTY\pscp.exe`
  - `C:\Program Files\PuTTY\plink.exe`
- OpenSSH tools also exist on this PC:
  - `C:\WINDOWS\System32\OpenSSH\ssh.exe`
  - `C:\WINDOWS\System32\OpenSSH\scp.exe`
  - `C:\WINDOWS\System32\OpenSSH\ssh-keygen.exe`
- Remote PHP lint path used previously:
  - `/usr/local/bin/php-8.3`
- Shared upload helper command shape:
  - `powershell -ExecutionPolicy Bypass -File G:\GithubRepos\GithubUtilities\publish_ssh_files.ps1 -RepoPath "G:\GithubRepos\uscapitalprivatebank-site" -RemoteUser "dh_9a4ezr" -RemoteHost "iad1-shared-e1-24.dreamhost.com" -RemoteRoot "/home/dh_9a4ezr/uscapitalprivatebank.com" -RemotePhpBin "/usr/local/bin/php-8.3" -Files "crm\\application\\views\\admin\\tickets\\partials\\ticket-tabpanel-add-reply.php"`
- Repo-local Aurora CRM publish wrapper:
  - `powershell -ExecutionPolicy Bypass -File G:\GithubRepos\uscapitalprivatebank-site\publish_dreamhost_crm_aurora.ps1`

## Key Files
- Root site entry: `G:\GithubRepos\uscapitalprivatebank-site\index.php`
- Public verification page: `G:\GithubRepos\uscapitalprivatebank-site\verification\index.html`
- Verification CSS: `G:\GithubRepos\uscapitalprivatebank-site\verification\verification.css`
- CRM sidebar view: `G:\GithubRepos\uscapitalprivatebank-site\crm\application\views\admin\includes\aside.php`
- CRM header view: `G:\GithubRepos\uscapitalprivatebank-site\crm\application\views\admin\includes\header.php`
- Perfex base CSS: `G:\GithubRepos\uscapitalprivatebank-site\crm\assets\css\style.css`
- Perfex minified CSS: `G:\GithubRepos\uscapitalprivatebank-site\crm\assets\css\style.min.css`
- CRM ticket reply UI:
  - `G:\GithubRepos\uscapitalprivatebank-site\crm\application\views\admin\tickets\partials\ticket-tabpanel-add-reply.php`
- CRM ticket AI JS:
  - `G:\GithubRepos\uscapitalprivatebank-site\crm\assets\js\tickets.js`
- CRM AI endpoints:
  - `G:\GithubRepos\uscapitalprivatebank-site\crm\application\controllers\admin\Ai_tickets.php`
  - `G:\GithubRepos\uscapitalprivatebank-site\crm\application\controllers\admin\Ai.php`
- Repo-local DreamHost publish wrapper:
  - `G:\GithubRepos\uscapitalprivatebank-site\publish_dreamhost_crm_aurora.ps1`

## Testing / Verification
- Verify public site returns HTTP 200 after any root-site changes.
- Hard refresh the CRM after CSS/sidebar changes because cached styles can hide live updates.
- Confirm verify admin routes still require authentication after any verify changes.

## Known Issues
- Perfex admin CSS may be influenced by multiple layers, including cached/minified CSS and inline overrides.
- WhatsApp CRM connection is not complete because no WhatsApp Business account is yet attached in Meta.
- The new Aurora CRM changes exist locally but are not yet live because DreamHost SSH key auth is not active yet.
- Server transcription will only work after `APP_AI_TRANSCRIPTION_*` values are configured on the CRM host.

## Next Best Actions
1. Install `C:\Users\uscap\.ssh\codex_deploy_ed25519.pub` into DreamHost SSH authorized keys or add an equivalent approved deploy key.
2. Upload the changed CRM Aurora files and run remote PHP lint with `/usr/local/bin/php-8.3`.
3. Hard refresh the CRM ticket page and verify `Revise`, `Follow-up Message`, and `Aurora Mic` appear and work.

## Notes For Future Sessions
- If working anywhere under `G:\GithubRepos`, read `G:\GithubRepos\GithubUtilities\AGENTS.md` first.
- For this repo, update this handoff file whenever a major deployment or decision is made.
