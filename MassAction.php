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

        $menuItem = new ExtraMenuItem(array(
            'label' => gT('Mass action'),
            'iconClass' => 'fa fa-table',
            'href' => $href
        ));

        $event->append('menuItems', array($menuItem));
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        Yii::setPathOfAlias('massAction', dirname(__FILE__));
        $content = Yii::app()->controller->render('massAction.views.index', array(), true);
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
}
