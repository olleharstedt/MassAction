// Namespace
var LS = LS || {};
LS.plugin = LS.plugin || {};
LS.plugin.massAction = LS.plugin.massAction || {};

LS.plugin.massAction.init = function()
{
    $('#handsontable').html('');
    $('#handsontable').html('');
    $('#mass-action-search-field').val('');
    LS.plugin.massAction.latestSearch = null;
}

/**
 * Callback after a cell in handsontable has changed
 *
 * @param {object} change
 * @param {string} action
 * @param {object} data
 * @param {string} saveLink - Link to send POST data
 */
LS.plugin.massAction.afterChange = function(change, action, data, saveLink)
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

    var csrfToken = $('input[name="YII_CSRF_TOKEN"]').val();

    $.ajax({
        method: 'POST',
        url: saveLink,
        data: {
            row: row,
            change: change,
            surveyId: LS.plugin.massAction.surveyId,
            YII_CSRF_TOKEN: csrfToken
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
LS.plugin.massAction.loadQuestions = function()
{

    LS.plugin.massAction.init();

    $.ajax({
        method: 'GET',
        url: LS.plugin.massAction.getQuestionsLink,
    }).done(function(data) {

        var data = JSON.parse(data);

        var height = $('.side-body').height();
        var width = $('.side-body').width();

        var hot = new Handsontable(LS.plugin.massAction.container, {
            width: width - 100,
            height: height - 100,
            data: data.data,
            rowHeaders: true,
            colHeaders: data.colHeaders,
            colWidths: data.colWidths,
            columns: data.columns,
            manualColumnResize: true,
            search: true,
            afterChange: function(change, action) {
                LS.plugin.massAction.afterChange(change, action, data, LS.plugin.massAction.saveQuestionChangeLink);
            }
        });

        var latestSearch = null;

        // Search
        var searchField = document.getElementById('mass-action-search-field');
        Handsontable.Dom.addEvent(searchField, 'keyup', function (event) {
            var queryResult = hot.search.query(this.value);
            latestSearch = queryResult;
            hot.render();
        });

        // Replace
        $('#mass-action-replace-button').on('click', function() {

            // Abort if there's no latest search
            if (latestSearch === null)
            {
                return;
            }

            var replaceString = $('#mass-action-replace-field').val();

            // Abort if there's nothing to replace with
            if (replaceString == '')
            {
                return;
            }

            var searchString = $('#mass-action-search-field').val();

            if (searchString == '')
            {
                return;
            }

            $(latestSearch).each(function(i, cell) {
                var regexp = new RegExp(searchString, 'g');
                var cellData  = hot.getDataAtCell(cell.row, cell.col);
                var newCellData = cellData.replace(regexp, replaceString);
                hot.setDataAtCell(cell.row, cell.col, newCellData);
            });

        });
    });
}

/**
 * Load question groups into handsontable
 */
LS.plugin.massAction.loadQuestionGroups = function()
{

    LS.plugin.massAction.init();

    $.ajax({
        method: 'GET',
        url: LS.plugin.massAction.getQuestionGroupsLink,
    }).done(function(data) {

        var data = JSON.parse(data);

        var height = $('.side-body').height();
        var width = $('.side-body').width();

        var hot = new Handsontable(LS.plugin.massAction.container, {
            width: width - 100,
            height: height - 100,
            data: data.data,
            rowHeaders: true,
            colHeaders: data.colHeaders,
            colWidths: data.colWidths,
            columns: data.columns,
            manualColumnResize: true,
            search: true,
            afterChange: function(change, action) {
                LS.plugin.massAction.afterChange(change, action, data, LS.plugin.massAction.saveQuestionGroupChangeLink);
            }
        });

        var latestSearch = null;

        // Search
        var searchField = document.getElementById('mass-action-search-field');
        Handsontable.Dom.addEvent(searchField, 'keyup', function (event) {
            var queryResult = hot.search.query(this.value);
            latestSearch = queryResult;
            hot.render();
        });

        // Replace
        $('#mass-action-replace-button').on('click', function() {

            // Abort if there's no latest search
            if (latestSearch === null)
            {
                return;
            }

            var replaceString = $('#mass-action-replace-field').val();

            // Abort if there's nothing to replace with
            if (replaceString == '')
            {
                return;
            }

            var searchString = $('#mass-action-search-field').val();

            if (searchString == '')
            {
                return;
            }

            $(latestSearch).each(function(i, cell) {
                var regexp = new RegExp(searchString, 'g');
                var cellData  = hot.getDataAtCell(cell.row, cell.col);
                var newCellData = cellData.replace(regexp, replaceString);
                hot.setDataAtCell(cell.row, cell.col, newCellData);
            });

        });
    });
}
