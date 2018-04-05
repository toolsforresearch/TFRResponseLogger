# TFRResponseLogger
LimeSurvey plugin, using events beforeSurveyPage and beforeLoadResponse

**Purpose**<br />
This plugin for LimeSurvey enables survey administrators and/or database administrators to log the actions of the respondents to slected surveys. After enabling the plugin for a survey, it logs the following:
1. When a link to start or resume a survey was opened. Event: beforeSurveyPage in application/controllers/survey/index.php.
2. If the survey already has answers for the requested token: how many response records exist for the token and what the answers in every response ID were. The plugin uses the event beforeLoadResponse (in application/controllers/survey/index.php) for logging the existing answers. LimeSurvey triggers this event once per session: at the initial loading of the survey. On subsequent actions the answers are retrieved from the \$\_SESSION variable.
3. After any click on next/previous/submit the plugins logs (a) the array of loaded answers once again, this time extracted from the \$\_SESSION variable and (b) the response to the questions on the page that the respondent has just left. The response is a dump of the \$\_POST variable except the YII_CSRF_TOKEN and except \$\_POST[fieldnames], which is a huge array that cluttered the logging too much. Event: beforeSurveyPage in application/controllers/survey/index.php.
