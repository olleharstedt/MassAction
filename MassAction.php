<?php

/**
 * @category Plugin
 * @package  MassAction
 * @author   Olle Haerstedt <blabla@bla.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     -
 */

use \LimeSurvey\Menu\MenuItem;

/**
 * This is a file
 *
 * PHP version 5
 *
 * @category Plugin
 * @package  MassAction
 * @author   Olle Haerstedt <blabla@bla.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     -
 */
class MassAction extends PluginBase
{
    /**
     * @var string
     */
    static protected $description = 'Edit many questions or question groups in one page';

    /**
     * @var string
     */
    static protected $name = 'Mass action';

    /**
     * @var string
     */
    protected $storage = 'DbStorage';

    /**
     * Which version of LS we're using (2.5 or 2.06lts)
     * @var string
     */
    protected $lsVersion = '2.5';  // Default to 2.5

    /**
     * @var array[]
     */
    protected $columns = [
        // TODO: hidden?
        array('data' => 'tid', 'readOnly' => true),
        array('data' => 'participant_id',),
        array('data' => 'firstname',),
        array('data' => 'lastname',),
        array('data' => 'email',),
        array('data' => 'emailstatus',),
        array('data' => 'token',),
        array('data' => 'language',),
        array('data' => 'blacklisted',),
        array('data' => 'sent',),
        array('data' => 'remindersent',),
        array('data' => 'remindercount',),
        array('data' => 'completed',),
        array('data' => 'usesleft',),
        array('data' => 'validfrom',),
        array('data' => 'validuntil',),
        array('data' => 'mpid',)
    ];

    /**
     * @var string[]
     */
    protected $colWidths = [
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
    ];

    /**
     * Init stuff
     *
     * @return void
     */
    public function init()
    {
        $this->subscribe('newDirectRequest');
        $this->subscribe('beforeDeactivate');
        $this->subscribe('beforeToolsMenuRender');
    }

    /**
     * Remove menu when deactivating.
     * @return void
     */
    public function beforeDeactivate()
    {
        $menu = SurveymenuEntries::model()->findByAttributes(['name' => 'massaction']);
        if (!empty($menu)) {
            $menu->delete();
        }
    }

    /**
     * Run before survey settings are shown
     *
     * @return void
     */
    public function beforeSurveySettings()
    {
        $this->event->set(
            "surveysettings.{$this->id}",
            array(
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
            )
        );
    }

    /**
     * Main function
     *
     * @param int|LSHttpRequest $surveyId Survey id
     * @return string
     */
    public function actionIndex($surveyId)
    {

        // In 2.06, we get survey ID from URL param
        if ($surveyId instanceof LSHttpRequest) {
            $surveyId = Yii::app()->request->getParam('surveyId');
        }

        $getQuestionTextsLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getQuestionTexts',
                'surveyId' => $surveyId
            )
        );

        $getQuestionAttributesLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getQuestionAttributes',
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

        $getQuestionGroupTextsLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getQuestionGroupTexts',
                'surveyId' => $surveyId
            )
        );

        $getQuestionGroupAttributesLink = Yii::app()->createUrl(
            'plugins/direct',
            array(
                'plugin' => 'MassAction',
                'function' => 'getQuestionGroupAttributes',
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
        $data['getQuestionTextsLink'] = $getQuestionTextsLink;
        $data['getQuestionAttributesLink'] = $getQuestionAttributesLink;
        $data['getQuestionGroupTextsLink'] = $getQuestionGroupTextsLink;
        $data['getQuestionGroupAttributesLink'] = $getQuestionGroupAttributesLink;
        $data['getTokensLink'] = $getTokensLink;
        $data['saveQuestionChangeLink'] = $saveQuestionChangeLink;
        $data['saveQuestionGroupChangeLink'] = $saveQuestionGroupChangeLink;
        $data['saveTokenChangeLink'] = $saveTokenChangeLink;
        $data['YII_CSRF_TOKEN'] = CHtml::hiddenField('YII_CSRF_TOKEN', Yii::app()->request->csrfToken);

        // NB: Cannot use $this->renderPartial() because 2.06lts support
        Yii::setPathOfAlias('massAction', dirname(__FILE__));

        $content = $this->api->renderTwig(__DIR__ . '/views/index.twig', $data);

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/bower_components');
        App()->clientScript->registerCssFile("$assetsUrl/handsontable/dist/handsontable.full.css");
        App()->clientScript->registerScriptFile(
            "$assetsUrl/handsontable/dist/handsontable.full.js",
            CClientScript::POS_END
        );

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/css');
        App()->clientScript->registerCssFile("$assetsUrl/massaction.css");

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/js');
        App()->clientScript->registerScriptFile("$assetsUrl/massaction.js");

        // Include extra JavaScript for 2.06lts
        /*
        if ($this->lsVersion == '2.06lts'
                // New version schema, like 2.6.x-lts
                || (strpos($this->lsVersion, 'lts') !== false
                    && strpos($this->lsVersion, '2.6') !== false)
            ) {
            App()->clientScript->registerScriptFile("$assetsUrl/massaction206.js");
        }
         */

        return $content;
    }

    /**
     * @param LSHttpRequest $request
     * @return string - Result as json
     */
    public function saveQuestionChange(LSHttpRequest $request)
    {
        // Check update permission
        $surveyId = $request->getParam('surveyId');
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to update survey content"
                ]
            );
        }

        try {
            $surveyId = $request->getParam('surveyId');
            $lang = $request->getParam('lang');
            $survey = Survey::model()->findByPk($surveyId);
            $row = $request->getParam('row');
            $change = $request->getParam('change');

            $question = Question::model()->findByPk(['qid' => $row['qid']]);

            if (empty($question)) {
                throw new \Exception('Could not find a question with qid ' . json_encode($row['qid']));
            }

            $changedFieldName = $change[1];
            $column = $this->findQuestionColumnInfo(
                array_merge(
                    $this->getQuestionColumns(),
                    $this->getLocalizedQuestionColumns()
                ),
                $changedFieldName
            );
            $newValue = $change[3];

            $attributes = QuestionAttribute::model()->getQuestionAttributes($question->qid);

            // Question field
            if ($column->localized) {
                $table = $column->localized;
                $questionl10n = $question->$table[$lang];
                $questionl10n->$changedFieldName = $newValue;
                $questionl10n->save();
            } elseif (isset($question->$changedFieldName)) {
                $question->$changedFieldName = $newValue;
            } elseif (isset($attributes->$changedFieldName)) {
                // Question attribute (lime_question_attributes table)
                $attribute = QuestionAttribute::model()->findByAttributes(
                    [
                        'qid' => $question->qid,
                        'attribute' => $changedFieldName
                    ]
                );

                if (empty($attribute)) {
                    $attribute = new QuestionAttribute();
                    $attribute->qid = $question->qid;
                    $attribute->attribute = $changedFieldName;
                    $attribute->value = $newValue;
                    $attribute->save();
                } else {
                    $attribute->value = $newValue;
                    $attribute->update();
                }

                // Safe to end here, attribute is language agnostic
                return json_encode(array('result' => 'success'));
            } else {
                return json_encode(
                    [
                        'result' => 'error',
                        'message' => 'Neither attribute nor question field'
                    ]
                );
            }

            // Validate question (e.g. for unique code)
            if ($question->validate() !== true) {
                return json_encode(
                    [
                        'result' => 'error',
                        'message' => 'Could not validate question'
                    ]
                );
            }

            $question->save();

            // All well!
            return json_encode(array('result' => 'success'));
        } catch (Exception $ex) {
            // Any error is sent as JSON to client
            return json_encode(
                [
                    'result' => 'error',
                    'message' => $ex->getMessage()
                ]
            );
        }

        // This could not happen
        return json_encode(
            [
                'result' => 'error',
                'message' => 'Impossibru!'
            ]
        );
    }

    /**
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function getQuestionTexts(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to read survey content"
                ]
            );

        }

        $survey = Survey::model()->findByPk($surveyId);

        $questions = Question::model()->findAllByAttributes(['sid' => $surveyId]);
        $questionColumns = $this->getLocalizedQuestionColumns();

        return $this->localizedQuestionsToJSON($questions, $survey, $questionColumns);
    }

    public function getQuestionAttributes(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to read survey content"
                ]
            );

        }

        $survey = Survey::model()->findByPk($surveyId);

        $questions = Question::model()->findAllByAttributes(['sid' => $surveyId]);
        $questionColumns = $this->getQuestionColumns();

        return $this->questionsToJSON($questions, $survey, $questionColumns);
    }

    /**
     * Turn questions into JSON
     *
     * @todo Use IToJSON interface for models instead?
     * @param Question[] $questions
     * @return string
     */
    protected function questionsToJSON(array $questions, Survey $survey, array $questionColumns)
    {
        $data = array();
        $colWidths = array();
        $colHeaders = array();
        $columns = array();

        foreach ($questionColumns as $column) {
            $colWidths[] = $column->width;
            $colHeaders[] = $column->header;
            $columns[] = array(
                'data' => $column->data,
                'readOnly' => $column->readonly
            );
        }

        foreach ($questions as $question) {
            $attributes = QuestionAttribute::model()->getQuestionAttributes($question->qid);
            if (is_array($attributes)) {
                $questionArr = [];
                foreach ($questionColumns as $column) {
                    $field = $column->data;
                    if (isset($question->$field)) {
                        $questionArr[$field] = $question->$field;
                    } elseif (isset($attributes[$field])) {
                        $questionArr[$field] = $attributes[$field];
                    }
                }
                $data[] = $questionArr;
            } else {
                throw new Exception('Could not find question attributes.');
            }
        }

        return json_encode(
            [
                'colHeaders' => $colHeaders,
                'colWidths' => $colWidths,
                'columns' => $columns,
                'data' => $data
            ]
        );
    }

    protected function localizedQuestionsToJSON(array $questions, Survey $survey, array $questionColumns)
    {
        $data = [];
        $colWidths = [];
        $colHeaders = [];
        $columns = [];

        foreach ($questionColumns as $column) {
            $colWidths[] = $column->width;
            $colHeaders[] = $column->header;
            $columns[] = array(
                'data' => $column->data,
                'readOnly' => $column->readonly
            );
        }

        $langs = array_merge(
            $survey->additionalLanguages,
            [$survey->language],
        );

        foreach ($questions as $question) {
            foreach ($langs as $lang) {
                $questionArr = [];
                foreach ($questionColumns as $column) {
                    $field = $column->data;
                    if ($column->localized) {
                        $table = $column->localized;
                        $questionArr[$field] = $question->$table[$lang]->$field;
                    }
                    if (isset($question->$field)) {
                        $questionArr[$field] = $question->$field;
                    }
                }
                $data[] = $questionArr;
            }
        }

        return json_encode(
            [
                'colHeaders' => $colHeaders,
                'colWidths' => $colWidths,
                'columns' => $columns,
                'data' => $data
            ]
        );
    }

    /**
     *
     */
    public function getQuestionGroupTexts(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');
        $survey = Survey::model()->findByPk($surveyId);

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to read survey content"
                ]
            );
        }

        $questionGroups = QuestionGroup::model()->findAllByAttributes(['sid' => $surveyId]);

        return $this->localizedQuestionGroupsToJSON($questionGroups, $survey);
    }

    /**
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function getQuestionGroupAttributes(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');
        $survey = Survey::model()->findByPk($surveyId);

        // Check read permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to read survey content"
                ]
            );
        }

        $questionGroups = QuestionGroup::model()->findAllByAttributes(['sid' => $surveyId]);

        return $this->questionGroupsToJSON($questionGroups, $survey);
    }

    protected function localizedQuestionGroupsToJSON($questionGroups, Survey $survey)
    {
        /** @var Column[] */
        $groupColumns = $this->getLocalizedQuestionGroupColumns();

        $colWidths = [];
        $colHeaders = [];
        $columns = [];

        foreach ($groupColumns as $column) {
            $colWidths[] = $column->width;
            $colHeaders[] = $column->header;
            $columns[] = array(
                'data' => $column->data,
                'readOnly' => $column->readonly
            );
        }

        $langs = array_merge(
            $survey->additionalLanguages,
            [$survey->language],
        );
        $data = [];

        foreach ($questionGroups as $questionGroup) {
            foreach ($langs as $lang) {
                $groupArr = array();
                foreach ($groupColumns as $column) {
                    $field = $column->data;
                    if ($column->localized) {
                        $table = $column->localized;
                        $groupArr[$field] = $questionGroup->$table[$lang]->$field;
                    } else {
                        $groupArr[$field] = $questionGroup->$field;
                    }
                }
                $data[] = $groupArr;
            }
        }

        return json_encode(
            [
                'colHeaders' => $colHeaders,
                'colWidths' => $colWidths,
                'columns' => $columns,
                'data' => $data
            ]
        );
    }

    /**
     * @param QuestionGroup[] $questionGroups
     * @return string JSON
     */
    protected function questionGroupsToJSON($questionGroups, Survey $survey)
    {
        /** @var Column[] */
        $groupColumns = $this->getQuestionGroupColumns();

        $colWidths = [];
        $colHeaders = [];
        $columns = [];

        foreach ($groupColumns as $column) {
            $colWidths[] = $column->width;
            $colHeaders[] = $column->header;
            $columns[] = array(
                'data' => $column->data,
                'readOnly' => $column->readonly
            );
        }

        $baselang = $survey->language;
        $data = [];

        foreach ($questionGroups as $questionGroup) {
            $groupArr = array();
            foreach ($groupColumns as $column) {
                $field = $column->data;
                if ($column->localized) {
                    $table = $column->localized;
                    $groupArr[$field] = $questionGroup->$table[$baselang]->$field;
                } else {
                    $groupArr[$field] = $questionGroup->$field;
                }
            }
            $data[] = $groupArr;
        }

        return json_encode(
            [
                'colHeaders' => $colHeaders,
                'colWidths' => $colWidths,
                'columns' => $columns,
                'data' => $data
            ]
        );
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
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to update survey content"
                ]
            );
        }

        try {
            $surveyId = $request->getParam('surveyId');
            $survey = Survey::model()->findByPk($surveyId);
            $baselang = $survey->language;
            $row = $request->getParam('row');
            $change = $request->getParam('change');
            $lang = $request->getParam('lang');

            $questionGroup = QuestionGroup::model()->findByPk(['gid' => $row['gid']]);

            $changedFieldName = $change[1];
            $column = $this->findQuestionColumnInfo(
                array_merge(
                    $this->getQuestionGroupColumns(),
                    $this->getLocalizedQuestionGroupColumns(),
                ),
                $changedFieldName
            );
            $newValue = $change[3];

            if ($column->localized) {
                $table = $column->localized;
                $l10n = $questionGroup->$table[$lang];
                $l10n->$changedFieldName = $newValue;
                $l10n->save();
            } else {
                $questionGroup->$changedFieldName = $newValue;
            }
            $questionGroup->save();

            // All well!
            return json_encode(array('result' => 'success'));
        } catch (Exception $ex) {
            // Any error is sent as JSON to client
            return json_encode(
                [
                    'result' => 'error',
                    'message' => $ex->getMessage()
                ]
            );
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
        if (!Permission::model()->hasSurveyPermission($surveyId, 'token', 'read')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to read tokens"
                ]
            );
        }

        // Check to see if a token table exists for this survey
        $tokenExists = tableExists('{{tokens_' . $surveyId . '}}');
        if (!$tokenExists) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "Found no token table"
                ]
            );
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
        $data = array();

        foreach ($tokens as $token) {
            $tokenArr = array();
            foreach ($this->columns as $column) {
                $field = $column['data'];
                $tokenArr[$field] = $token->$field;
            }
            $data[] = $tokenArr;
        }

        return json_encode(
            [
                'colHeaders' => $this->getColHeaders(),
                'colWidths' => $this->colWidths,
                'columns' => $this->columns,
                'data' => $data
            ]
        );

    }

    /**
     * Save token cell change
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function saveTokenChange(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');

        // Check update permission
        if (!Permission::model()->hasSurveyPermission($surveyId, 'token', 'update')) {
            return json_encode(
                [
                    'result' => 'error',
                    'message' => "You don't have access to update tokens"
                ]
            );
        }

        try {
            $row = $request->getParam('row');
            $change = $request->getParam('change');

            $token = TokenDynamic::model($surveyId)->findByPk(
                ['tid' => $row['tid']]
            );

            $changedFieldName = $change[1];
            $newValue = $change[3];

            $token->$changedFieldName = $newValue;
            $token->save();

            // All well!
            return json_encode(array('result' => 'success'));
        } catch (Exception $ex) {
            // Any error is sent as JSON to client
            return json_encode(
                [
                    'result' => 'error',
                    'message' => $ex->getMessage()
                ]
            );
        }
    }

    /**
     * @return void
     */
    public function newDirectRequest()
    {
        if (empty($this->lsVersion)) {
            throw new Exception("Internal error: this->lsVersion is not set");
        }

        $event = $this->event;
        if ($event->get('target') == "MassAction") {
            $request = $event->get('request');
            $functionToCall = $event->get('function');

            // TODO: Hardcode functions
            if ($functionToCall != 'actionIndex') {
                echo $this->$functionToCall($request);
            } elseif ($this->lsVersion == '2.06lts'
                    // New version schema, like 2.6.x-lts
                    || (strpos($this->lsVersion, 'lts') !== false
                        && strpos($this->lsVersion, '2.6') !== false)
                ) {
                $content = $this->$functionToCall($request);
                $event->setContent($this, $content);
            } else {
                echo $this->$functionToCall($request);
            }
        }
    }

    /**
     * Run before tools menu is rendered
     *
     * @return void
     */
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

        $menuItem = new MenuItem(
            array(
                'label' => gT('Mass action'),
                'iconClass' => 'fa fa-table',
                'href' => $href
            )
        );

        $event->append('menuItems', array($menuItem));
    }

    private function getLocalizedQuestionColumns()
    {
        return [
            new Column(['data' => 'qid', 'header' => gT('ID'), 'readonly' => true]),
            new Column(['data' => 'gid', 'header' => gT('Group'), 'readonly' => true, 'width' => 50]),
            new Column(['data' => 'type', 'header' => gT('Type'), 'readonly' => true, 'width' => 50]),
            new Column(['header' => gT('Lang'), 'data' => 'language', 'readonly' => true, 'width' => 50, 'localized' => 'questionl10ns']),
            new Column(['header' => gT('Code'), 'data' => 'title']),
            new Column(['header' => gT('Question'), 'data' => 'question', 'width' => 300, 'localized' => 'questionl10ns']),
            new Column(['header' => gT('Help'), 'data' => 'help', 'width' => 300, 'localized' => 'questionl10ns']),
        ];
    }

    /**
     * @return Column[]
     */
    private function getQuestionColumns()
    {
        return [
            new Column(['data' => 'qid', 'header' => gT('ID'), 'readonly' => true]),
            new Column(['data' => 'gid', 'header' => gT('Group'), 'readonly' => true, 'width' => 50]),
            new Column(['data' => 'type', 'header' => gT('Type'), 'readonly' => true, 'width' => 50]),
            new Column(['header' => gT('Code'), 'data' => 'title']),
            new Column(['header' => gT('Mandatory'), 'data'=> 'mandatory', 'width' => 0]),
            new Column(['header' => gT('Other'), 'data'=> 'other', 'width' => 50]),
            new Column(['header' => gT('Relevance equation'), 'data' => 'relevance']),
            new Column(['header' => gT('Sort order'), 'data' => 'question_order']),
            new Column(['header' => gT('Validation'), 'data' => 'preg']),
            new Column(['header' => gT('Randomization group name'), 'data' => 'random_group', 'width' => 200]),
            new Column(['header' => gT('Public statistics'), 'data' => 'public_statistics', 'width' => 150]),
            new Column(['header' => gT('Show graph'), 'data' => 'statistics_showgraph', 'width' => 50]),
            new Column(['header' => gT('Graph type'), 'data' => 'statistics_graphtype', 'width' => 50]),
            new Column(['header' => gT('Random order'), 'data' => 'random_order']),
            new Column(['header' => gT('Hide tip'), 'data' => 'hide_tip']),
            new Column(['header' => gT('Always hidden'), 'data' => 'hidden']),
            new Column(['header' => gT('Max answers'), 'data' => 'max_answers']),
            new Column(['header' => gT('Min answers'), 'data' => 'min_answers']),
            new Column(['header' => gT('Array filter'), 'data' => 'array_filter']),
            new Column(['header' => gT('Array filter excl.'), 'data' => 'array_filter_exclude']),
            new Column(['header' => gT('Question val. eq.'), 'data' => 'em_validation_q']),
        ];
    }

    /**
     * @return Column[]
     */
    private function getLocalizedQuestionGroupColumns()
    {
        return [
            new Column(['data' => 'gid', 'header' => gT('GID'), 'readonly' => true, 'width' => 50]),
            new Column(['header' => gT('Lang'), 'data' => 'language', 'readonly' => true, 'width' => 50, 'localized' => 'questiongroupl10ns']),
            new Column(['header' => gT('Title'), 'data' => 'group_name', 'width' => 300, 'localized' => 'questiongroupl10ns']),
            new Column(['header' => gT('Description'), 'data' => 'description', 'width' => 300, 'localized' => 'questiongroupl10ns']),
        ];
    }

    /**
     * @return Column[]
     */
    private function getQuestionGroupColumns()
    {
        return [
            new Column(['data' => 'gid', 'header' => gT('GID'), 'readonly' => true, 'width' => 50]),
            new Column(['header' => gT('Relevance equation'), 'data' => 'grelevance']),
            new Column(['header' => gT('Randomization group'), 'data' => 'randomization_group']),
        ];
    }

    /**
     * @param string $changedFieldName
     * @return ?Column
     */
    private function findQuestionColumnInfo(array $columns, string $changedFieldName)
    {
        foreach ($columns as $column) {
            if ($changedFieldName == $column->data) {
                return $column;
            }
        }
        return null;
    }

    /**
     * @return mixed[] TODO: Why won't type string[] work?
     */
    protected function getColHeaders()
    {
        return [
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
        ];
    }
}
