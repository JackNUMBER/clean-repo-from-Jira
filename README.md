# Clean your repo according to Jira
This script checks the resolution of each issue in Jira and delete the corresponding Git branch.
It uses the Jira REST API with cUrl.

Branches need to be named like this: **ISSUE-ID_short-description**

Launch it in your terminal with: `php clean_repo_from_jira.php`
