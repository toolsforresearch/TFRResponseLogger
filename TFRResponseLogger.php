<?php
	class TFRResponseLogger extends \ls\pluginmanager\PluginBase
	{

		static protected $description = 'Log $_POST, loaded answers and $oResponses, used in application/controllers/survey/index.php function action()';
		static protected $name = 'TFRResponseLogger';

		protected $storage = 'DbStorage';

		public function init()
		{
			$this->subscribe('beforeSurveyPage');
			$this->subscribe('beforeLoadResponse');
			
			// Provides survey specific settings.
			$this->subscribe('beforeSurveySettings');

			// Saves survey specific settings.
			$this->subscribe('newSurveySettings');
			
			// Clean up on deactivate
			$this->subscribe('beforeDeactivate');
		}
		
		public function beforeDeactivate()
		{
			$sDBPrefix = Yii::app()->db->tablePrefix;
			$sql = "DROP TABLE IF EXISTS {$sDBPrefix}response_log";
			Yii::app()->db->createCommand($sql)->execute();
		}

		protected $settings = array(
			'enabled' => array(
				'type' => 'select',
				'options' => array(
					0 => 'No',
					1 => 'Yes'
				),
				'default' => 0,
				'label' => 'Use load response logger by default?',
				'help' => 'Overwritable in the Survey settings',
			),
			'logtable' => array(
				'type' => 'select',
				'options' => array(
					0 => 'No',
					1 => 'Yes'
				),
				'default' => 0,
				'label' => 'Store logs in response_log table by default?',
				'help' => 'Overwritable in the Survey settings',
			),
			'logsurvey' => array(
				'type' => 'select',
				'options' => array(
					0 => 'No',
					1 => 'Yes'
				),
				'default' => 0,
				'label' => 'Store logs in Response Log \'survey\' by default?',
				'help' => 'Overwritable in the Survey settings',
			),
			'logsurveyid' => array(
				'type' => 'int',
				'default' => 999999,
				'label' => 'Survey ID of the Response Log \'survey\'',
				'help' => 'Make sure a Survey with this ID is activated.<br />Use survey_archive_999999.lsa to add it.',
			),
			'forceloadsingle' => array(
				'type' => 'select',
				'options' => array(
					0 => 'No',
				),
				'default' => 0,
				'label' => 'Do not force load of single response by default',
				'help' => 'Only configurable in the Survey settings',
			),
		);

		public function beforeSurveySettings()
		{
			$event = $this->event;
			$settings = array(
				'name' => get_class($this),
				'settings' => array(
					'enabled' => array(
						'type' => 'boolean',
						'label' => 'Use plugin for this survey',
						'current' => $this->get('enabled', 'Survey', $event->get('survey'), $this->get('enabled'))
					),
					'logtable' => array(
						'type' => 'boolean',
						'label' => 'Store logs in response_log table',
						'current' => $this->get('logtable', 'Survey', $event->get('survey'), $this->get('logtable'))
					),
					'logsurvey' => array(
						'type' => 'boolean',
						'label' => 'Store logs in Response Log \'survey\'',
						'current' => $this->get('logsurvey', 'Survey', $event->get('survey'), $this->get('logsurvey'))
					),
					'logsurveyid' => array(
						'type' => 'int',
						'label' => 'Survey ID of the Response Log \'survey\'',
						'help' => 'Make sure a Survey with this ID is activated. Use the lsa to add it.',
						'current' => $this->get('logsurveyid', 'Survey', $event->get('survey'), $this->get('logsurveyid'))
					),
					'forceloadsingle' => array(
						'type' => 'boolean',
						'options' => array(
							0 => 'No',
							1 => 'Yes'
						),
						'label' => 'Force load of single response',
						'help' => 'Do not do this, unless you are absolutely sure (overrides usesleft and other LS settings)',
						'current' => $this->get('forceloadsingle', 'Survey', $event->get('survey'), $this->get('forceloadsingle'))
					)
				)
			);
			$event->set("surveysettings.{$this->id}", $settings);
		}

		/**
		 * Save the settings
		 */
		public function newSurveySettings()
		{
			$event = $this->event;
			foreach ($event->get('settings') as $name => $value)
			{
				/* In order use survey setting, if not set, use global, if not set use default */
				$default=$event->get($name,null,null,isset($this->settings[$name]['default'])?$this->settings[$name]['default']:NULL);
				$this->set($name, $value, 'Survey', $event->get('survey'),$default);
			}
		}

		public function beforeSurveyPage()
		{
			$surveyid = $this->event->get('surveyId');
			if ($this->get('enabled', 'Survey', $surveyid) == false) {
				return;
			}
			$date = date("Y-m-d H:i:s", time());
			$remote_addr = $_SERVER["REMOTE_ADDR"];
			// borrow code at https://github.com/LimeSurvey/LimeSurvey/blob/master/application/models/Token.php#L224
			$token = isset($_REQUEST["token"]) ? preg_replace('/[^0-9a-zA-Z_~]/', '', $_REQUEST["token"]) : 
				(isset($_SESSION['survey_'.$surveyid]['token']) ? preg_replace('/[^0-9a-zA-Z_~]/', '', $_SESSION['survey_'.$surveyid]['token']) : NULL);
			$response_count = NULL;
			$responseid = NULL;
			$answerlist = "";
			if (isset($_SESSION['survey_'.$surveyid]['token'])) {
				$printarr = $_SESSION['survey_'.$surveyid];
				$thisstep = $_SESSION['survey_'.$surveyid]['step'];
				$group = $_SESSION['survey_'.$surveyid]['grouplist'][($thisstep-1)];
				$gid = $group['gid'];
				unset($printarr['fieldmap']);
				// echo "<pre>\r\n\r\n\r\n\r\n\r\n\r\n\r\nprintarr beforeSurveyPage step $thisstep gid $gid = ".print_r($printarr,true)."</pre>\n";
				foreach($printarr as $key => $value) {
					//if (stristr($key,'X'.$gid.'X')) {
						$xqid = explode('X',$key);
						if (count($xqid) == 3) {
							$answerlist .= "[{$key}] = {$value} \r\n";
						}
					//}
				}
			}
			$loadedanswers = isset($_SESSION['survey_'.$surveyid]['token']) && $answerlist!="" ? "beforeSurveyPage loaded answers:\r\n".$answerlist : "";
			$responsearr = $_POST;
			unset($responsearr['YII_CSRF_TOKEN']);
			unset($responsearr['fieldnames']);
			$responsedump = isset($_POST["token"]) ? "beforeSurveyPage post-log: ".print_r($responsearr,true) : "beforeSurveyPage no \$_POST[\"token\"], only \$_GET";
			if (isset($_REQUEST["token"])) {
				$this->saveLoadResponse($date, $remote_addr, $surveyid, $token, $response_count, $responseid, $loadedanswers, $responsedump);
			}
		}
		
		public function beforeLoadResponse()
		{
			$surveyid = $this->event->get('surveyId');
			if ($this->get('enabled', 'Survey', $surveyid) == false) {
				return;
			}
			// load $oResponses
			$responses = $this->event->get('responses');
			$response_count = count($responses);
			foreach ($responses as $response) {
				$date = date("Y-m-d H:i:s", time());
				$remote_addr = $_SERVER["REMOTE_ADDR"];
				$token = $response->token;
				$responseid = $response->id;
				$single_response = $response;
				$loadedanswers = "beforeLoadResponse:\r\n".print_r($response,true);
				$responsedump = "";
				//echo "<pre>\n\n\n\ndate={$date}\nremote_addr = {$remote_addr}\nsurveyid = {$surveyid}\ntoken = {$token}\nresponse count = {$response_count}\nloadedanswers = {$loadedanswers}\nresponseid = {$responseid}\nresponse = {$responsedump}</pre>\n";
				$this->saveLoadResponse($date, $remote_addr, $surveyid, $token, $response_count, $responseid, $loadedanswers, $responsedump);
			}
			if ($this->get('forceloadsingle', 'Survey', $surveyid) == true) {
				if ($response_count == 1) {
					// make sure a single response is returned to $event->get('response') in application/controllers/survey/index.php
					$this->event->set('response', $single_response);
					//echo "<pre>single_response = ".print_r($single_response,true)."</pre>\n";
				}
			}
			// echo "<br /><br /><br /><pre>_SESSION = ".print_r($_SESSION,true)."</pre>\n";
		}

		private function saveLoadResponse($date, $remote_addr, $surveyid, $token, $response_count, $responseid, $loadedanswers, $response) {
			$sDBPrefix = Yii::app()->db->tablePrefix;
			$parameters = array(
				'date' => $date,
				'remote_addr' => $remote_addr,
				'surveyid' => $surveyid,
				'token' => $token,
				'response_count' => $response_count,
				'responseid' => $responseid,
				'loadedanswers' => $loadedanswers,
				'response' => $response
			);
			if ($this->get('logtable', 'Survey', $surveyid) == true) {
				$sDBPrefix = Yii::app()->db->tablePrefix;
				$mysql = "CREATE TABLE IF NOT EXISTS {$sDBPrefix}response_log (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`date` datetime NOT NULL,
					`remote_addr` varchar(39) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
					`surveyid` int(11) NOT NULL,
					`token` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
					`response_count` int(11) DEFAULT NULL,
					`responseid` int(11) DEFAULT NULL,
					`loadedanswers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
					`response` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
					PRIMARY KEY (id)
				) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
				$pgsql = "CREATE TABLE IF NOT EXISTS {$sDBPrefix}response_log (
					id SERIAL,
					date timestamp without time zone NOT NULL,
					remote_addr character varying(39) NOT NULL,
					surveyid integer NOT NULL,
					token character varying(35),
					response_count integer,
					responseid integer,
					loadedanswers text,
					response text
				)";
				$constring = Yii::app()->db->connectionString;
				if (stristr($constring, "mysql:") !== false) $sql = $mysql;
				else $sql = $pgsql;
				Yii::app()->db->createCommand($sql)->execute();
				//CREATE ENTRY INTO "{$sDBPrefix}response_log"
				$sql = "insert into {$sDBPrefix}response_log
					(date, remote_addr, surveyid, token, response_count, responseid, loadedanswers, response)
				values
					(:date, :remote_addr, :surveyid, :token, :response_count, :responseid, :loadedanswers, :response)
				";
				//echo "<br /><br /><br /><pre>".$sql."\nparameters = ".print_r($parameters,true)."</pre>\n"; die();
				Yii::app()->db->createCommand($sql)->execute($parameters);
			}
			if ($this->get('logsurvey', 'Survey', $surveyid) == true) {
				$sid = $this->get('logsurveyid');
				$sql = "SELECT title, gid, qid FROM {{questions}} WHERE sid={$sid} and parent_qid=0 ORDER BY qid";
				$qidarr = Yii::app()->db->createCommand($sql)->queryAll();
				$fields = array();
				foreach($qidarr as $question) {
					if ($question['title'] == 'remoteaddr') $fields['remote_addr'] = $sid.'X'.$question['gid'].'X'.$question['qid'];
					else if ($question['title'] == 'responsecount') $fields['response_count'] = $sid.'X'.$question['gid'].'X'.$question['qid'];
					else $fields[$question['title']] = $sid.'X'.$question['gid'].'X'.$question['qid'];
				}

				// INSERT record into Response Log survey
				$sql = "insert into {$sDBPrefix}survey_{$sid}
					(\"startlanguage\", \"{$fields['date']}\", \"{$fields['remote_addr']}\", \"{$fields['surveyid']}\", \"{$fields['token']}\", \"{$fields['response_count']}\", \"{$fields['responseid']}\", \"{$fields['loadedanswers']}\", \"{$fields['response']}\")
				values
					('', :date, :remote_addr, :surveyid, :token, :response_count, :responseid, :loadedanswers, :response)
				";
				// die("logsurveyid = $sid\n<pre>".$sql."\n".print_r($parameters,true)."\n".print_r($qidarr,true)."</pre>\n");
				Yii::app()->db->createCommand($sql)->execute($parameters);
			}
		}
	}
?>
