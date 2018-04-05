# TFRResponseLogger LimeSurvey plugin
LimeSurvey plugin by toolsforresearch.com, using the events beforeSurveyPage and beforeLoadResponse

**Purpose**<br />
This plugin for LimeSurvey enables survey administrators and/or database administrators to log the actions of the respondents to selected surveys. After enabling the plugin for a survey, it logs the following:
1. When a link to start or resume a survey was opened. Event: beforeSurveyPage in application/controllers/survey/index.php.
2. If the survey already has answers for the requested token: how many response records exist for the token and what the answers in every response ID were. The plugin uses the event beforeLoadResponse (in application/controllers/survey/index.php) for logging the existing answers. LimeSurvey triggers this event once per session: at the initial loading of the survey. On subsequent actions the answers are retrieved from the \$\_SESSION variable.
3. After any click on next/previous/submit the plugins logs (a) the array of loaded answers once again, this time extracted from the \$\_SESSION variable and (b) the response to the questions on the page that the respondent has just left. The response is a dump of the \$\_POST variable except the YII_CSRF_TOKEN and except \$\_POST[fieldnames], which is a huge array that cluttered the logging too much. Event: beforeSurveyPage in application/controllers/survey/index.php.

**Storage options**<br />
The plugin offers 2 types of storage for the logs:
1. A MySQL or PostgreSQL table, called response_log with the appropriate database prefix. This table is created on the fly the first time it is needed. Database administrators can inspect this table, using webbased tools like phpMyAdmin or phpPgAdmin. Warning: the reponse_log table is deleted on deactivating the plugin. Do not deactivate without being sure you have saved the logs or do not need them anymore.
2. A fake survey called 'Response Log'. Default Survey ID for the Response Log is 999999, but this can be adjusted globally or even per logged survey. The github repository toolsforresearch/TFRResponseLogger contains a Survey structure file (limesurvey_survey_999999.lss) and an empty Survey archive file (survey_archive_999999.lsa). The archive file is preferable, because it eliminates the risk that one forgets to activate a Response Log survey.<br />
This type of storage is useful for survey administrators that have no access to the database tables and/or in environments where tools like phpMyAdmin or phpPgAdmin are not allowed. Logging in Response Log survey(s) has the additional benefit that access rights can be configured using LimeSurvey's settings. Warning: the plugin does not check if a Response Log survey with the chosen logging survey ID exists and has 'questions' with the needed question codes. Be sure to create and activate a logging survey before activating it.

Both types of storage can be mixed. For instance, it is possible to enable table-logging for all surveys plus logging in separate Response Log surveys for just a few surveys at the same time.

**Installation**<br />
Installation of the plugin is like any other LimeSurvey plugin. Upload (or git clone) the plugin to a directory TFRResponseLogger in the plugin directory (directly under LimeSurvey's root directory). The plugin should be recognized automatically. If you activate the plugin it does not do anything yet. Beware: if you deactivate the plugin a response_log table is deleted if it exists.

**Global configuration**<br />
The plugin has the following global configuration options:
1. Use load response logger by default. Default is 'No'.
2. Store logs in response_log table by default. Default is 'No'. Change this and the previous option to 'Yes' if you want to enable logging for all surveys in a database table.
3. Store logs in Response Log 'survey' by default. Default is 'No'. We do not advise you to change this to 'Yes', because an awful lot will be logged and LimeSurvey's user interface is not very good in displaying large text fields with a dumped array as content.
4. Survey ID of the Response Log 'survey'. Default is 999999, but you can change the logging survey ID globally if 999999 is already taken. If you plan to use the type of logging, create and activate a Response Log survey with the configured ID right now. Use the lsa or lss in the toolsforresearch/TFRResponseLogger github repo to do that.
5. Force load of single response by default. We disabled changing this option to 'Yes', because it overrides some of LimeSurvey's settings.

All global configuration settings can be overridden on the survey level.

**Configuration on the survey level**<br />
In Survey properties > General settings & texts > Plugins you can adjust the plugin's behaviour per survey. Anything you specify here will override the global settings for the plugin. Special notes for some options:
1. Changing the ID of the Response Log 'survey' might be useful if you want to separate the logs for respondent actions for various surveys. Possible reasons to do that are (a) do not mix respondent actions of more than one survey in a single log or (b) use different access rights for surveys that have restricted access as well. However, changing has a risk: be sure a Response Log survey with the chaged survey ID exists and is activated, before you change and save this option on a survey level.
2. Force load of single response. Do not enable this unless you are absolutely sure and have tested it thoroughly. The effect of enabling: if the plugin finds only one response set for a token it forces LimeSurvey to use that response set, overriding any other LS settings that say otherwise. Example: if a participant has more than 1 'Uses left', LimeSurvey would normally create a new response record when the participant opens the 'newtest' link for the second time. Enabling this option for a survey would prevent a user from answering a survey for the second or third time in a row, despite the fact that 'Uses left' suggests otherwise. Do not use this in production! Testing only. Credits were they are due: we used a snippet from Sam Mousa's ResponsePicker Plugin to implement this option. See https://github.com/WorldHealthOrganization/ls-responsepicker
