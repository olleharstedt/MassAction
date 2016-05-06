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

    var change = change[0];  // Only one change
    console.log('change', change);
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
        console.log("done saving");
        console.log(response);
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
        console.log("here");
        console.log(data);

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
        console.log(data);

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
