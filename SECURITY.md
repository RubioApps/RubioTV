# Security Policy

## Supported Versions

RubioTV is released as a beta version 1.0.0 and it works for PHP8.x.
This will not work for previous versions of PHP

| Version | Supported          |
| ------- | ------------------ |
| 8.x     | :white_check_mark: |
| < 8.x   | :x:                |

## Reporting a Vulnerability

RubioTV uses a shell execution command to launch an external NodeJS application.
To do so, RubioTV needs the sudoers file to be modified to add a single execution of a temporary file (named .unlock) which contains a simple bash scripts.
Usually, this file is created on-the-fly and removed just after it is used. The code is designed to make this file to be removed asap. 

If you find any issue with this kind of interactions or other, please [Report a bug](https://github.com/RubioApps/RubioTV/blob/main/.github/ISSUE_TEMPLATE/bug_report.md)

Thank you.
