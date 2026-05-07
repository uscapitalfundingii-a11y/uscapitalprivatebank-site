# USCPB Safe - Bounced Email Alternate Email Request

This file follows the root workspace instructions in `D:\GithubRepos\AGENTS.md`.

Owner: Aurora  
Primary user: Mercury  
Channel: Email, CRM ticket, chat  
Risk level: Low

## Use Case

Use when an email bounced and Mercury needs a valid alternate email or confirmation.

## Client-Facing Reply

```text
Hello [Client Name],

We attempted to reach you by email, but the message may not have delivered successfully.

Please confirm your best email address for future communication. If you use an alternate email, you may send it here or update it through the secure client portal:

https://uscapitalprivatebank.com/crm/

This helps us keep your record accurate and avoid missed support or onboarding messages.

Thank you,
[Signature]
```

## Internal Note

If no valid alternate email exists, Mercury should compile the bounced recipient for Morpheus suppression review. Do not delete CRM records blindly.
