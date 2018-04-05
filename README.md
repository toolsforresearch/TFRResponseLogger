# TFRResponseLogger
LimeSurvey plugin, using events beforeSurveyPage and beforeLoadResponse

**Purpose**<br />
This plugin for LimeSurvey enables survey administrators and/or database administrators to log the actions of the respondents to selected surveys. After enabling the plugin for a survey, it logs the following:
1. When a link to start or resume a survey was opened. Event: beforeSurveyPage in application/controllers/survey/index.php.
2. If the survey already has answers for the requested token: how many response records exist for the token and what the answers in every response ID were. The plugin uses the event beforeLoadResponse (in application/controllers/survey/index.php) for logging the existing answers. LimeSurvey triggers this event once per session: at the initial loading of the survey. On subsequent actions the answers are retrieved from the \$\_SESSION variable.
3. After any click on next/previous/submit the plugins logs (a) the array of loaded answers once again, this time extracted from the \$\_SESSION variable and (b) the response to the questions on the page that the respondent has just left. The response is a dump of the \$\_POST variable except the YII_CSRF_TOKEN and except \$\_POST[fieldnames], which is a huge array that cluttered the logging too much. Event: beforeSurveyPage in application/controllers/survey/index.php.

**Storage options**<br />
The plugin offers 2 types of storage for the logs:
1. A MySQL or PostgreSQL table, called response_log with the appropriate database prefix. This table is created on the fly the first time it is needed. Database administrators can inspect this table, using webbased tools like phpMyAdmin or phpPgAdmin. Warning: the reponse_log table is deleted on deactivating the plugin. Do not deactivate without being sure you have saved the logs or do not need them anymore.
2. A fake survey called 'Response Log'. Default Survey ID for the Response Log is 999999, but this can be adjusted globally or even per logged survey. The github repository toolsforresearch/TFRResponseLogger contains a Survey structure file (limesurvey_survey_999999.lss) and an empty Survey archive file (survey_archive_999999.lsa). The archive file is preferable, because it eliminates the risk that one forgets to activate a Response Log survey.
This type of storage is useful for survey administrators that have no access to the database tables and/or in environments where tools like phpMyAdmin or phpPgAdmin are not allowed. Logging in Response Log survey(s) has the additional benefit that access rights can be configured using LimeSurvey's settings. Warning: the plugin does not check if a Response Log survey with the chosen logging survey ID exists and has 'questions' with the needed question codes. Be sure to create and activate a logging survey before activating it.

Both types of storage can be mixed. For instance, it is possible to enable table-logging for all surveys plus logging in separate Response Log surveys for just a few surveys.
