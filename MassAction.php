<?php

use \ls\menu\MenuItem;

/**
 * Mass action
 *
 * @since 2016-04-29
 * @author Olle Härstedt
 */
class MassAction extends \ls\pluginmanager\PluginBase
{
    static protected $description = 'Edit many questions or question groups in one page';
    static protected $name = 'Mass action';

    protected $storage = 'DbStorage';

    /**
     * Which version of LS we're using (2.5 or 2.06lts)
     * @var string
     */
    protected $lsVersion = '2.5';  // Default to 2.5

    public function init()
    {
        $config = require(Yii::app()->basePath . '/config/version.php');
        $this->lsVersion = $config['versionnumber'];

                // Old version schema, 2.06lts
        if ($this->lsVersion == '2.06lts'
                // New version schema, like 2.6.x-lts
                || (strpos($this->lsVersion, 'lts') !== false
                    && strpos($this->lsVersion, '2.6') !== false
            ))
        {
            $this->subscribe('newDirectRequest');
            $this->subscribe('afterSurveyMenuLoad');
        }
        else if (floatval($this->lsVersion) >= 2.50)
        {
            $this->subscribe('beforeToolsMenuRender');
            $this->subscribe('newDirectRequest');
            $this->subscribe('afterQuickMenuLoad');
        }
        else
        {
            $this->subscribe('newDirectRequest');
            $this->subscribe('afterSurveyMenuLoad');
            $this->subscribe('beforeActivate');  // To show a warning message when activate
            $this->subscribe('beforeSurveySettings');  // We are unsure afterSurveyMenuLoad event , then add a link to the plugin settings
        }

    }
    public function beforeActivate()
    {
        App()->setFlashMessage(gT("Warning : this plugin was not tested with this LimeSurvey version."),"error");
    }
    public function beforeSurveySettings()
    {
        $this->event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'linkMassAction'=>array(
                    'type'=>'link',
                    'link'=>$this->api->createUrl(
                        'plugins/direct',
                        array(
                            'plugin' => get_class($this),
                            'surveyId' => $this->event->get('survey'),
                            'function' => 'actionIndex'
                        )
                    ),
                    'label'=>'Do some mass action on this survey',
                ),
            ),
        ));
    }
    public function beforeToolsMenuRender()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $href = Yii::app()->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'sidebody',
                'plugin' => 'MassAction',
                'method' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $menuItem = new MenuItem(array(
            'label' => gT('Mass action'),
            'iconClass' => 'fa fa-table',
            'href' => $href
        ));

        $event->append('menuItems', array($menuItem));
    }

    /**
     * @return string
     */
    public function actionIndex($surveyId)
    {

        // In 2.06, we get survey ID from URL param
        if ($surveyId instanceof LSHttpRequest)
        {
            $surveyId = Yii::app()->request->getParam('surveyId');
        }

        $getQuestionsLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getQuestions',
                'surveyId' => $surveyId
            )
        );

        $saveQuestionChangeLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'saveQuestionChange',
                'surveyId' => $surveyId
            )
        );

        $getQuestionGroupsLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getQuestionGroups',
                'surveyId' => $surveyId
            )
        );

        $saveQuestionGroupChangeLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'saveQuestionGroupChange',
                'surveyId' => $surveyId
            )
        );

        $getTokensLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getTokens',
                'surveyId' => $surveyId
            )
        );

        $saveTokenChangeLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'saveTokenChange',
                'surveyId' => $surveyId
            )
        );

        $data = array();
        $data['surveyId'] = $surveyId;
        $data['getQuestionsLink'] = $getQuestionsLink;
        $data['getQuestionGroupsLink'] = $getQuestionGroupsLink;
        $data['getTokensLink'] = $getTokensLink;
        $data['saveQuestionChangeLink'] = $saveQuestionChangeLink;
        $data['saveQuestionGroupChangeLink'] = $saveQuestionGroupChangeLink;
        $data['saveTokenChangeLink'] = $saveTokenChangeLink;

        // NB: Cannot use $this->renderPartial() because 2.06lts support
        Yii::setPathOfAlias('massAction', dirname(__FILE__));
        $content = Yii::app()->controller->renderPartial('massAction.views.index', $data, true);

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/bower_components');
        App()->clientScript->registerCssFile("$assetsUrl/handsontable/dist/handsontable.full.css");
        App()->clientScript->registerScriptFile("$assetsUrl/handsontable/dist/handsontable.full.js", CClientScript::POS_END);

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/css');
        App()->clientScript->registerCssFile("$assetsUrl/massaction.css");

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/js');
        App()->clientScript->registerScriptFile("$assetsUrl/massaction.js");

        // Include extra JavaScript for 2.06lts
        if (floatval($this->lsVersion) < 2.5)
        {
            App()->clientScript->registerScriptFile("$assetsUrl/massaction206.js");
        }

        return $content;
    }

    /**
     * @return void
     */
    public function afterQuickMenuLoad()
    {
        // Do nothing if QuickMenu plugin is not active
        $quickMenuExistsAndIsActive = $this->api->pluginExists('QuickMenu')
            && $this->api->pluginIsActive('QuickMenu');
        if (!$quickMenuExistsAndIsActive)
        {
            return;
        }

        $event = $this->getEvent();
        $settings = $this->getPluginSettings(true);

        $data = $event->get('aData');
        $activated = $data['activated'];
        $surveyId = $data['surveyid'];

        $href = Yii::app()->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'sidebody',
                'plugin' => 'MassAction',
                'method' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $button = new QuickMenuButton(array(
            'name' => 'massAction',
            'href' => $href,
            'tooltip' => gT('Mass action'),
            'iconClass' => 'fa fa-table navbar-brand',
            'neededPermission' => array('surveycontent', 'update')
        ));
        $db = Yii::app()->db;
        $userId = Yii::app()->user->getId();
        $orderings = QuickMenu::getOrder($userId);
        if (isset($orderings['massAction']))
        {
            $button->setOrder($orderings['massAction']);
        }

        $event->append('quickMenuItems', array($button));
    }

    /**
     * @param LSHttpRequest $request
     * @return string - Result as json
     */
    public function saveQuestionChange(LSHttpRequest $request)
    {
        // Check update permission
        $surveyId = $request->getParam('surveyId');
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update'))
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "You don't have access to update survey content"
            ));
        }

        try
        {
            $surveyId = $request->getParam('surveyId');
            $row = $request->getParam('row');
            $change = $request->getParam('change');
            $baselang = Survey::model()->findByPk($surveyId)->language;

            $question = Question::model()->findByPk(array(
                'qid' => $row['qid'],
                'language' => $baselang
            ));

            $changedFieldName = $change[1];
            $newValue = $change[3];

            $attributes = QuestionAttribute::model()->getQuestionAttributes($question->qid);

            // Question field
            if (isset($question->$changedFieldName))
            {
                $question->$changedFieldName = $newValue;
            }
            // Question attribute (lime_question_attributes table)
            else if (isset($attributes[$changedFieldName]))
            {
                $attribute = QuestionAttribute::model()->findByAttributes(array(
                    'qid' => $question->qid,
                    'attribute' => $changedFieldName
                ));

                if (empty($attribute))
                {
                    $attribute = new QuestionAttribute();
                    $attribute->qid = $question->qid;
                    $attribute->attribute = $changedFieldName;
                    $attribute->value = $newValue;
                    $attribute->save();
                }
                else
                {
                    $attribute->value = $newValue;
                    $attribute->update();
                }

                // Safe to end here, attribute is language agnostic
                return json_encode(array('result' => 'success'));
            }
            else
            {
                return json_encode(array(
                    'result' => 'error',
                    'message' => 'Neither attribute nor question field'
                ));
            }

            // Validate question (e.g. for unique code)
            if ($question->validate() !== true)
            {
                return json_encode(array(
                    'result' => 'error',
                    'message' => 'Could not validate question'
                ));
            }

            $question->save();
            $this->saveQuestionForAllLanguages($question, $changedFieldName, $newValue);

            // All well!
            return json_encode(array('result' => 'success'));
        }
        catch (Exception $ex)
        {
            // Any error is sent as JSON to client
            return json_encode(array(
                'result' => 'error',
                'message' => $ex->getMessage()
            ));
        }

        // This could not happen
        return json_encode(array(
            'result' => 'error',
            'message' => 'Impossibru!'
        ));
    }

    /**
     * Some question attributes are localized, some are not. Since the database
     * is not normalized with regard to this, we need to manually update
     * all language version of the question.
     *
     * @param object $question
     * @param string fieldName - The name of the database field to update
     * @param mixed value
     * @return void
     */
    protected function saveQuestionForAllLanguages($question, $fieldName, $value)
    {
        $localizedFields = array(
            'question',
            'help'
        );

        if (!in_array($fieldName, $localizedFields))
        {
            // Save in all languages
            Yii::app()->db->createCommand()->update(
                '{{questions}}',
                array("$fieldName" => $value),
                'qid = :qid',
                array(':qid' => $question->qid
            ));
        }
        else
        {
            // Localized field, don't save
        }
    }

    /**
     * Same as above.
     * @param object $questionGroup
     * @param string fieldName - The name of the database field to update
     * @param mixed value
     * @return void
     */
    protected function saveQuestionGroupForAllLanguages($questionGroup, $fieldName, $value)
    {
        $localizedFields = array(
            'group_name',
            'description'
        );

        if (!in_array($fieldName, $localizedFields))
        {
            // Save in all languages
            Yii::app()->db->createCommand()->update(
                '{{groups}}',
                array("$fieldName" => $value),
                'gid = :gid',
                array(':gid' => $questionGroup->gid
            ));
        }
        else
        {
            // Localized field, don't save
        }
    }

    /**
     * @param LSHttpRequest $request
     */
    public function getQuestions(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read'))
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "You don't have access to read survey content"
            ));
        }

        $baselang = Survey::model()->findByPk($surveyId)->language;
        $questions = Question::model()->findAllByAttributes(array(
            'sid' => $surveyId,
            'language' => $baselang
        ));

        return $this->questionsToJSON($questions);
    }

    /**
     * Turn questions into JSON
     *
     * @todo Use IToJSON interface for models instead?
     * @param array<Question>
     * @return string
     */
    protected function questionsToJSON(array $questions)
    {
        $data = array();
        $colWidths = array();
        $colHeaders = array();
        $columns = array();
        $questionColumns = $this->getQuestionColumns();

        foreach ($questionColumns as $column) {
            $colWidths[] = $column->width;
            $colHeaders[] = $column->header;
            $columns[] = array(
                'data' => $column->data,
                'readonly' => $column->readonly
            );
        }

        foreach ($questions as $question) {
            $attributes = QuestionAttribute::model()->getQuestionAttributes($question->qid);
            $questionArr = array();
            foreach ($questionColumns as $column) {
                $field = $column->data;
                if (isset($question->$field)) {
                    $questionArr[$field] = $question->$field;
                }
                else if (isset($attributes[$field])) {
                    $questionArr[$field] = $attributes[$field];
                }
            }
            $data[] = $questionArr;
        }

        return json_encode(array(
            'colHeaders' => $colHeaders,
            'colWidths' => $colWidths,
            'columns' => $columns,
            'data' => $data
        ));

    }

    /**
     * @param LSHttpRequest $request
     * @return json string
     */
    public function getQuestionGroups(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read'))
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "You don't have access to read survey content"
            ));
        }

        $baselang = Survey::model()->findByPk($surveyId)->language;
        $questionGroups = QuestionGroup::model()->findAllByAttributes(array(
            'sid' => $surveyId,
            'language' => $baselang
        ));

        return $this->questionGroupsToJSON($questionGroups);
    }

    /**
     * @return json string
     */
    protected function questionGroupsToJSON($questionGroups)
    {
        // Header
        $colHeaders = array(
            gT('GID'),
            gT('Title'),
            gT('Description'),
            gT('Randomization group'),
            gT('Relevance equation'),
        );

        // handsontable needs this information for
        // readonly option
        $columns = array(
            // TODO: hidden?
            array(
                'data' => 'gid',
                'readOnly' => true
            ),
            array(
                'data' => 'group_name',
            ),
            array(
                'data' => 'description',
            ),
            array(
                'data' => 'randomization_group',
            ),
            array(
                'data' => 'grelevance',
            )
        );

        // Limit width
        $colWidths = array(
            '100',
            '100',
            '300',
            '100',
            '100'
        );

        $data = array();

        foreach ($questionGroups as $questionGroup)
        {
            $groupArr = array();
            foreach ($columns as $column)
            {
                $field = $column['data'];
                $groupArr[$field] = $questionGroup->$field;
            }
            $data[] = $groupArr;
        }

        return json_encode(array(
            'colHeaders' => $colHeaders,
            'colWidths' => $colWidths,
            'columns' => $columns,
            'data' => $data
        ));

    }

    /**
     * Save change to question group field
     *
     * @param LSHttpRequest $request
     * @return string - Result as json
     * @todo Duplication of saveQuestionChange
     */
    public function saveQuestionGroupChange(LSHttpRequest $request)
    {
        // Check update permission
        $surveyId = $request->getParam('surveyId');
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update'))
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "You don't have access to update survey content"
            ));
        }

        try
        {
            $surveyId = $request->getParam('surveyId');
            $row = $request->getParam('row');
            $change = $request->getParam('change');
            $baselang = Survey::model()->findByPk($surveyId)->language;

            $questionGroup = QuestionGroup::model()->findByPk(array(
                'gid' => $row['gid'],
                'language' => $baselang
            ));

            $changedFieldName = $change[1];
            $newValue = $change[3];

            $questionGroup->$changedFieldName = $newValue;
            $questionGroup->save();
            $this->saveQuestionGroupForAllLanguages($questionGroup, $changedFieldName, $newValue);

            // All well!
            return json_encode(array('result' => 'success'));
        }
        catch (Exception $ex)
        {
            // Any error is sent as JSON to client
            return json_encode(array(
                'result' => 'error',
                'message' => $ex->getMessage()
            ));
        }
    }

    /**
     * Get tokens
     *
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function getTokens(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'token', 'read'))
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "You don't have access to read tokens"
            ));
        }

        // Check to see if a token table exists for this survey
        $tokenExists = tableExists('{{tokens_' . $surveyId . '}}');
        if (!$tokenExists)
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "Found no token table"
            ));
        }

        $tokens = TokenDynamic::model($surveyId)->findAll();

        return $this->tokensToJSON($tokens);
    }

    /**
     * Turn tokens from db into JSON for handsontable
     *
     * @param array $tokens
     * @return string JSON
     */
    protected function tokensToJSON($tokens)
    {
        // Header
        $colHeaders = array(
            gT('TID'),
            gT('Participant ID'),
            gT('First name'),
            gT('Last name'),
            gT('E-mail'),
            gT('E-mail status'),
            gT('Token'),
            gT('Language'),
            gT('Blacklisted'),
            gT('Sent'),
            gT('Reminder sent'),
            gT('Reminder count'),
            gT('Completed'),
            gT('Uses left'),
            gT('Valid from'),
            gT('Valid until'),
            gT('MPID')
            // TODO: Attributes here
        );

        // handsontable needs this information for
        // readonly option
        $columns = array(
            // TODO: hidden?
            array(
                'data' => 'tid',
                'readOnly' => true
            ),
            array(
                'data' => 'participant_id',
            ),
            array(
                'data' => 'firstname',
            ),
            array(
                'data' => 'lastname',
            ),
            array(
                'data' => 'email',
            ),
            array(
                'data' => 'emailstatus',
            ),
            array(
                'data' => 'token',
            ),
            array(
                'data' => 'language',
            ),
            array(
                'data' => 'blacklisted',
            ),
            array(
                'data' => 'sent',
            ),
            array(
                'data' => 'remindersent',
            ),
            array(
                'data' => 'remindercount',
            ),
            array(
                'data' => 'completed',
            ),
            array(
                'data' => 'usesleft',
            ),
            array(
                'data' => 'validfrom',
            ),
            array(
                'data' => 'validuntil',
            ),
            array(
                'data' => 'mpid',
            )
        );

        $data = array();

        foreach ($tokens as $token)
        {
            $tokenArr = array();
            foreach ($columns as $column)
            {
                $field = $column['data'];
                $tokenArr[$field] = $token->$field;
            }
            $data[] = $tokenArr;
        }

        // Limit width
        $colWidths = array(
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100',
            '100'
        );

        return json_encode(array(
            'colHeaders' => $colHeaders,
            'colWidths' => $colWidths,
            'columns' => $columns,
            'data' => $data
        ));

    }

    /**
     * Save token cell change
     */
    public function saveTokenChange(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check update permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'token', 'update'))
        {
            return json_encode(array(
                'result' => 'error',
                'message' => "You don't have access to update tokens"
            ));
        }

        try
        {
            $row = $request->getParam('row');
            $change = $request->getParam('change');

            $token = TokenDynamic::model($surveyId)->findByPk(array(
                'tid' => $row['tid'],
            ));

            $changedFieldName = $change[1];
            $newValue = $change[3];

            $token->$changedFieldName = $newValue;
            $token->save();

            // All well!
            return json_encode(array('result' => 'success'));
        }
        catch (Exception $ex)
        {
            // Any error is sent as JSON to client
            return json_encode(array(
                'result' => 'error',
                'message' => $ex->getMessage()
            ));
        }
    }

    /**
     * Specific for 2.06
     */
    public function afterSurveyMenuLoad()
    {
        $event = $this->event;
        $menu = $event->get('menu', array());
        $surveyId = $event->get('surveyId');
        $href = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $menu[] = array(
            //'href' => "plugins/direct/MassAction?function=actionIndex",
            'href' => $href,
            'alt' => gT('Mass action'),
            'image' => 'bounce.png'
        );

        $event->set('menu', $menu);
    }

    public function newDirectRequest()
    {
        if (empty($this->lsVersion))
        {
            throw new Exception("Internal error: this->lsVersion is not set");
        }

        $event = $this->event;
        if ($event->get('target') == "MassAction")
        {
            $request = $event->get('request');
            $functionToCall = $event->get('function');
            // TODO: Hardcode functions
            if (floatval($this->lsVersion) >= 2.5 || $functionToCall != "actionIndex")
            {
                echo $this->$functionToCall($request);
            }
            else
            {
                $content = $this->$functionToCall($request);
                $event->setContent($this, $content);
            }
        }
    }

    /**
     * @return array<Column>
     */
    private function getQuestionColumns()
    {
        return array(
            new Column(array(
                'data' => 'qid',
                'header' => gT('ID'),
                'readonly' => true
            )),
            new Column(array(
                'data' => 'gid',
                'header' => gT('Group'),
                'readonly' => true,
                'width' => 50
            )),
            new Column(array(
                'data' => 'type',
                'header' => gT('Type'),
                'readonly' => true,
                'width' => 50
            )),
            new Column(array(
                'header' => gT('Code'),
                'data' => 'title',
            )),
            new Column(array(
                'header' => gT('Question'),
                'data' => 'question',
                'width' => 300,
            )),
            new Column(array(
                'header' => gT('Help'),
                'data' => 'help',
                'width' => 300,
            )),
            new Column(array(
                'header' => gT('Mandatory'),
                'data'=> 'mandatory',
                'width' => 0
            )),
            new Column(array(
                'header' => gT('Other'),
                'data'=> 'other',
                'width' => 50
            )),
            new Column(array(
                'header' => gT('Relevance equation'),
                'data' => 'relevance',
            )),
            new Column(array(
                'header' => gT('Validation'),
                'data' => 'preg',
            )),
            new Column(array(
                'header' => gT('Randomization group name'),
                'data' => 'random_group',
                'width' => 200,
            )),
            new Column(array(
                'header' => gT('Public statistics'),
                'data' => 'public_statistics',
                'width' => 150,
            )),
            new Column(array(
                'header' => gT('Show graph'),
                'data' => 'statistics_showgraph',
                'width' => 50,
            )),
            new Column(array(
                'header' => gT('Graph type'),
                'data' => 'statistics_graphtype',
                'width' => 50,
            )),
            new Column(array(
                'header' => gT('Random order'),
                'data' => 'random_order',
            )),
            new Column(array(
                'header' => gT('Hide tip'),
                'data' => 'hide_tip',
            )),
            new Column(array(
                'header' => gT('Always hidden'),
                'data' => 'hidden'
            )),
            new Column(array(
                'header' => gT('Max answers'),
                'data' => 'max_answers'
            )),
            new Column(array(
                'header' => gT('Min answers'),
                'data' => 'min_answers'
            )),
            new Column(array(
                'header' => gT('Array filter'),
                'data' => 'array_filter'
            )),
            new Column(array(
                'header' => gT('Array filter excl.'),
                'data' => 'array_filter_exclude'
            )),
            new Column(array(
                'header' => gT('Question val. eq.'),
                'data' => 'em_validation_q'
            )),
        );
    }

}
