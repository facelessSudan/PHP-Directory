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
```resume-ai-agent/
|---composer.json                 # project dependencies
|---.env                          # n8n Webhook URL & config
|---setup.sh
|---public/
|   |---index.php           # Resume upload form
|   |--- .htaccess
|--- src/
|   |--- AIrecruitementAgent.php
|   |--- webhook_handler.php
|   |--- form_handler.php 
|   |--- views/
|   |    |--- application_form.php
|   |    |--- thank_you_note.php
|   |    |--- error.php
|   |--- Services/
|        |--- GoogleSheetsService.php
|        |--- EmailService.php
|        |--- DatabaseService.php
|--- config/
|--- logs/
|--- uploads/
|    |--- resumes/
|---tests
|    |--- tests_db.php
|---README.md
```

## Prerequisites
- PHP 8.x+
- Composer
- [n8](https://n8n.io) (self-hosted or cloud)
- Google account for Google Sheets API
- An AI scoring mechanism in your n8n flow
---

## Setup instructions
### Clone and install Dependancies.
```bash
composer install
```
## n8n Workflow Overview
1. Receive data from PHP webhook
2. Decode base64 resume and parse it
3. Match it against a ajob description
4. Score it using AI or rules.
5. Email the candidate based on the score
Notify the recruter (HR Dept)via Email.

## Resume AI Workflow Design
![Automated Recruitment Workflow Design png 1](https://github.com/user-attachments/assets/2d36dd8f-7c0b-4fa4-927e-71bf0898d117)

## Licence
MIT-Feel free to use, fork or improve
