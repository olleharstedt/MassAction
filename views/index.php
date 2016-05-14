<div class="row">
    <div class="col-lg-12 content-right">
        <h3><?php eT("Mass action");?></h3>
        <p>
            <div class='btn btn-default' onclick='loadQuestions();'><?php echo gT("Load questions"); ?></div>
            <div class='btn btn-default' onclick='loadQuestionGroups();'><?php echo gT("Load question groups"); ?></div>
            <div id='mass-action-saving' class='hide' style='display: inline-block;'><span class='fa fa-spinner fa-spin'></span>&nbsp;<?php eT("Saving data..."); ?></div>
            <div id='mass-action-saving-done' class='hide' style='display: inline-block;'><span class='fa fa-check'></span>&nbsp;<?php eT("All changes saved"); ?></div>
            <div id='mass-action-saving-error' class='hide' style='display: inline-block;'><span class='fa fa-exclamation'></span>&nbsp;<?php eT("Could not save data:"); ?>&nbsp;<span id='mass-action-error-message'></span></div>
        </p>
        <div id="handsontable">
        </div>
        <?php echo CHtml::hiddenField('YII_CSRF_TOKEN',Yii::app()->request->csrfToken); ?>
    </div>
</div>

<script>

// Namespace
var LS = LS || {};
LS.plugin = LS.plugin || {};
LS.plugin.massAction = {};

LS.plugin.massAction.container = document.getElementById('handsontable');
LS.plugin.massAction.getQuestionsLink = '<?php echo $getQuestionsLink; ?>';
LS.plugin.massAction.getQuestionGroupsLink = '<?php echo $getQuestionGroupsLink; ?>';
LS.plugin.massAction.saveQuestionChangeLink = '<?php echo $saveQuestionChangeLink; ?>';
LS.plugin.massAction.saveQuestionGroupChangeLink = '<?php echo $saveQuestionGroupChangeLink; ?>';
LS.plugin.massAction.surveyId = '<?php echo $surveyId; ?>';

$(document).ready(function() {
    var data = [
        ["", "Ford", "Volvo", "Toyota", "Honda"],
        ["2016", 10, 11, 12, 13],
        ["2017", 20, 11, 14, 13],
        ["2018", 30, 15, 12, 13]
    ];
});
</script>
