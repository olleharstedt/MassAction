<div class="row">
    <div class="col-lg-12 content-right">

        <h3><?php eT("Mass action");?></h3>

        <!-- Load buttons -->
        <div class='btn btn-default' onclick='LS.plugin.massAction.loadQuestions();'><?php echo gT("Load questions"); ?></div>
        <div class='btn btn-default' onclick='LS.plugin.massAction.loadQuestionGroups();'><?php echo gT("Load question groups"); ?></div>
        <div class='btn btn-default' onclick='LS.plugin.massAction.loadTokens();'><?php echo gT("Load tokens"); ?></div>

        <!-- Save spinner -->
        <div id='mass-action-saving' class='hide' style='display: inline-block;'><span class='fa fa-spinner fa-spin'></span>&nbsp;<?php eT("Saving data..."); ?></div>
        <div id='mass-action-saving-done' class='hide' style='display: inline-block;'><span class='fa fa-check'></span>&nbsp;<?php eT("All changes saved"); ?></div>
        <div id='mass-action-saving-error' class='hide' style='display: inline-block;'><span class='fa fa-exclamation'></span>&nbsp;<span id='mass-action-error-message'></span></div>

        <p></p> <!-- Some margin -->

        <input id='mass-action-search-field' name='mass-action-search-field' class='form-control width-20' value='' type='text' placeholder="<?php eT("Search"); ?>" />
        <input id='mass-action-replace-field' name='mass-action-replace-field' class='form-control width-20' value='' type='text' placeholder="<?php eT("Replace"); ?>" />
        <button id='mass-action-replace-button' name='mass-action-replace-button' class='btn btn-default'><?php eT("Replace all"); ?></button>

        <p></p> <!-- Some margin -->

        <div id="handsontable">
        </div>

        <?php echo CHtml::hiddenField('YII_CSRF_TOKEN',Yii::app()->request->csrfToken); ?>

    </div>
</div>

<script>

// Namespace
var LS = LS || {};
LS.plugin = LS.plugin || {};
LS.plugin.massAction = LS.plugin.massAction || {};

LS.plugin.massAction.container = document.getElementById('handsontable');
LS.plugin.massAction.getQuestionsLink = '<?php echo $getQuestionsLink; ?>';
LS.plugin.massAction.getQuestionGroupsLink = '<?php echo $getQuestionGroupsLink; ?>';
LS.plugin.massAction.getTokensLink = '<?php echo $getTokensLink; ?>';
LS.plugin.massAction.saveQuestionChangeLink = '<?php echo $saveQuestionChangeLink; ?>';
LS.plugin.massAction.saveQuestionGroupChangeLink = '<?php echo $saveQuestionGroupChangeLink; ?>';
LS.plugin.massAction.saveTokenChangeLink = '<?php echo $saveTokenChangeLink; ?>';
LS.plugin.massAction.surveyId = '<?php echo $surveyId; ?>';

</script>
