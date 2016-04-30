<?php

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

        $menuItem = new ExtraMenuItem(array(
            'label' => gT('Mass action'),
            'iconClass' => 'fa fa-table',
            'href' => $href
        ));

        $event->set('menuItems', array($menuItem));
    }

    public function actionIndex()
    {
        return "asd";
    }
}
