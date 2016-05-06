/**
 * Callback after a cell in handsontable has changed
 *
 * @param {object} change
 * @param {string} action
 * @param {object} data
 * @param {string} saveLink - Link to send POST data
 */
function afterChange(change, action, data, saveLink)
{
    if (change === null)
    {
        return;
    }

    $('#mass-action-saving').removeClass('hide');

    var change = change[0];  // Only one change
    var rowNumber = change[0];
    var columnName = change[1];
    var oldValue = change[2];
    var newValue = change[3];
    var row = data.data[rowNumber];

    if (row === undefined)
    {
        throw "Internal error: found no row with number " + rowNumber;
    }

    $.ajax({
        method: 'POST',
        url: saveLink,
        data: {
            row: row,
            change: change,
            surveyId: LS.plugin.massAction.surveyId
        }
    }).done(function(response) {
        $('#mass-action-saving').addClass('hide');
        var data = JSON.parse(response);

        if (data.result == 'success')
        {
            $('#mass-action-saving-done').removeClass('hide');
            $('#mass-action-saving-done').show();
            clearTimeout(LS.plugin.massAction.t);
            LS.plugin.massAction.t = setTimeout(function() {
                $('#mass-action-saving-done').fadeOut(500);
            }, 2000);
        }
        else if(data.result == 'error')
        {
            $('#mass-action-error-message').html(data.message);
            $('#mass-action-saving-error').removeClass('hide');
            $('#mass-action-saving-error').show();
            clearTimeout(LS.plugin.massAction.t);
            LS.plugin.massAction.t = setTimeout(function() {
                $('#mass-action-saving-error').fadeOut(500);
            }, 2000);
        }

    });
}

/**
 * Load questions into handsontable
 */
function loadQuestions() {
    $.ajax({
        method: 'GET',
        url: LS.plugin.massAction.getQuestionsLink,
    }).done(function(data) {

        var data = JSON.parse(data);

        var hot = new Handsontable(LS.plugin.massAction.container, {
            data: data.data,
            rowHeaders: true,
            colHeaders: data.colHeaders,
            columns: data.columns,
            afterChange: function(change, action) {
                afterChange(change, action, data, LS.plugin.massAction.saveQuestionChangeLink);
            }
        });

    });
}

/**
 * Load question groups into handsontable
 */
function loadQuestionGroups()
{
    $.ajax({
        method: 'GET',
        url: LS.plugin.massAction.getQuestionGroupsLink,
    }).done(function(data) {

        var data = JSON.parse(data);

        var hot = new Handsontable(LS.plugin.massAction.container, {
            data: data.data,
            rowHeaders: true,
            colHeaders: data.colHeaders,
            columns: data.columns,
            afterChange: function(change, action) {
                afterChange(change, action, data, LS.plugin.massAction.saveQuestionGroupChangeLink);
            }
        });
    });
}
