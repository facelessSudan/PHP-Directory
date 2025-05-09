# Resume AI Agent - PHP + n8n
This project is a lightweight automation tool to help recruiters automatically process, evaluate and response to job applications without manual screening. 
It intergrates PHP form with an n8n workflow using a webhook.

## Features
- Simple resume upload form (PDF only)
- Sends candidates data and resume to n8n for automation.
- Automate resume scoring and candidate response via email.
- Saves applicant data to Google sheets.
- Notifies recruiter of Matches or Mismatches.

---

## Folder Structure
resume-ai-agent/
|---backend/
    |---public/
        |---index.php           # Resume upload form   
        |---upload.php          # Handles form subission and triggers webhook
        |---src/
        |---.env                # n8n Webhook URL & config
        |---composer.json       # project dependencies

## Setup instructions
Clone and install Dependancies.
```bash
composer install

## n8n Workflow Overview
1. Receive data from PHP webhook
2. Decode base64 resume and parse it
3. Match it against a ajob description
4. Score it using AI or rules.
5. Email the candidate based on the score
Notify the recruter (HR Dept)via Email.

## Licence
MIT-Feel free to use, fork or improve
