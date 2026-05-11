# DreamHost GitHub Deploy

Future agents: read `D:\GithubRepos\AGENTS.md` before editing this production-adjacent deploy path.

This repository can deploy changed files to DreamHost automatically when `main` is pushed to GitHub.

## Required GitHub Settings

Add this repository secret:

- `DREAMHOST_SSH_KEY`: private SSH key authorized for the DreamHost user.

Optional repository variables:

- `DREAMHOST_HOST`: defaults to `iad1-shared-e1-24.dreamhost.com`
- `DREAMHOST_USER`: defaults to `dh_9a4ezr`
- `DREAMHOST_PORT`: defaults to `22`
- `DREAMHOST_PATH`: defaults to `/home/dh_9a4ezr/uscapitalprivatebank.com`

## Behavior

- Normal GitHub Desktop push to `main` uploads changed files over SSH.
- Normal push does not delete remote files.
- Manual workflow runs can upload all tracked files.
- Manual workflow runs can remove files deleted from Git only when `delete_removed_files=true`.
- The workflow excludes Git internals, workflow files, local diagnostics, logs, backups, temp folders, dependency folders, databases, archives, PDFs, and large media.

## Safety

Do not commit real SSH keys, DreamHost passwords, database credentials, CRM secrets, OAuth secrets, or private client records. Store deploy secrets only in GitHub Secrets or the approved server secret store.
