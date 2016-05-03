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

    public function init()
    {
        $this->subscribe('beforeToolsMenuRender');
        $this->subscribe('newDirectRequest');
        $this->subscribe('afterQuickMenuLoad');
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

        $data = array();
        $data['surveyId'] = $surveyId;
        $data['getQuestionsLink'] = $getQuestionsLink;
        $data['saveQuestionChangeLink'] = $saveQuestionChangeLink;

        Yii::setPathOfAlias('massAction', dirname(__FILE__));
        $content = Yii::app()->controller->render('massAction.views.index', $data, true);

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/bower_components');
        App()->clientScript->registerCssFile("$assetsUrl/handsontable/dist/handsontable.full.css");
        App()->clientScript->registerScriptFile("$assetsUrl/handsontable/dist/handsontable.full.js", CClientScript::POS_END); 

        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/css');
        App()->clientScript->registerCssFile("$assetsUrl/mass-action.css");

        return $content;
    }

    public function afterQuickMenuLoad()
    {
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

        $buttons = array(
            new QuickMenuButton(array(
                'href' => $href,
                'tooltip' => gT('Mass action'),
                'iconClass' => 'fa fa-table navbar-brand',
                'neededPermission' => array('surveycontent', 'update')
            ))
        );

        $event->append('quickMenuItems', $buttons);
    }

    /**
     * @param LSHttpRequest $request
     */
    public function saveQuestionChange(LSHttpRequest $request)
    {
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

            $question->$changedFieldName = $newValue;
            $question->save();

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
     * @param LSHttpRequest $request
     */
    public function getQuestions(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');
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
        // Header
        $colHeaders = array(
            gT('QID'),
            gT('Code'),
            gT('Question'),
            gT('Help'),
            gT('Mandatory'),
            gT('Relevance equation'),
            gT('Validation')
        );

        // handsontable needs this information for
        // readonly option
        $columns = array(
            // TODO: hidden?
            array(
                'data' => 'qid',
                'readOnly' => true
            ),
            array(
                'data' => 'title',
            ),
            array(
                'data' => 'question',
            ),
            array(
                'data' => 'help',
            ),
            array(
                'data' => 'mandatory',
            ),
            array(
                'data' => 'relevance',
            ),
            array(
                'data' => 'preg',
            )
        );

        $data = array();

        foreach ($questions as $question)
        {
            $questionArr = array();
            foreach ($columns as $column)
            {
                $field = $column['data'];
                $questionArr[$field] = $question->$field;
            }
            $data[] = $questionArr;
        }

        return json_encode(array(
            'colHeaders' => $colHeaders,
            'columns' => $columns,
            'data' => $data
        ));

    }

    public function newDirectRequest()
    {
        $event = $this->event;
        if ($event->get('target') == "MassAction")
        {
            // you can get other params from the request object
            $request = $event->get('request');

            //get the function name to call and use the method call_user_func
            $functionToCall = $event->get('function'); 
            //$content = call_user_func(array($this,$functionToCall), $surveyId);
            //set the content on the event
            //$event->setContent($this, $content);
            echo $this->$functionToCall($request);
        }
    }

}
