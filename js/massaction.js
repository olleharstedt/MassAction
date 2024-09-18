// Namespace
var LS = LS || {};
LS.plugin = LS.plugin || {};
LS.plugin.massAction = LS.plugin.massAction || {};

$(document).ready(function() {

    // Make search case sensitive
    Handsontable.Search.global.setDefaultQueryMethod(function(query, value) {
        if (typeof query == 'undefined' || query == null || !query.toLowerCase || query.length === 0)
        {
            return false;
        }

        if (value === null || value === undefined)
        {
            return false;
        }

        return value.toString().indexOf(query) !== -1;
    });

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

        $('#mass-action-saving').removeClass('d-none');

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
            $('#mass-action-saving').addClass('d-none');
            var data = JSON.parse(response);

            if (data.result == 'success')
            {
                $('#mass-action-saving-done').removeClass('d-none');
                $('#mass-action-saving-done').show();
                clearTimeout(LS.plugin.massAction.t);
                LS.plugin.massAction.t = setTimeout(function() {
                    $('#mass-action-saving-done').fadeOut(500);
                }, 2000);
            }
            else if(data.result == 'error')
            {
                $('#mass-action-error-message').html(data.message);
                $('#mass-action-saving-error').removeClass('d-none');
                $('#mass-action-saving-error').show();
                clearTimeout(LS.plugin.massAction.t);
                LS.plugin.massAction.t = setTimeout(function() {
                    $('#mass-action-saving-error').fadeOut(500);
                }, 2000);
            }

        });
    }

    /**
     * Helper function to load questions, groups, ...
     *
     * @param {object} link
     * @return void
     */
    LS.plugin.massAction.load = function(links)
    {
        var getLink = links.getLink;
        var saveLink = links.saveLink;

        LS.plugin.massAction.init();

        $.ajax({
            method: 'GET',
            url: getLink
        }).done(function(data) {

            var data = JSON.parse(data);

            if (data.result == 'error')
            {
                $('#mass-action-error-message').html(data.message);
                $('#mass-action-saving-error').removeClass('d-none');
                $('#mass-action-saving-error').show();
                clearTimeout(LS.plugin.massAction.t);
                LS.plugin.massAction.t = setTimeout(function() {
                    $('#mass-action-saving-error').fadeOut(500);
                }, 2000);
                return;
            }

            // At least 1'000 px high.
            var height = $('.side-body').height();
            height = height < 1000 ? 1000 : height;

            var width = $('.side-body').width();

            var cont = document.getElementById('handsontable');
            var hot = new Handsontable(cont, {
                width: width - 100,
                height: height - 200,
                data: data.data,
                rowHeaders: true,
                colHeaders: data.colHeaders,
                colWidths: data.colWidths,
                columns: data.columns,
                manualColumnResize: true,
                search: true,
                afterChange: function(change, action) {
                    LS.plugin.massAction.afterChange(change, action, data, saveLink);
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

                    /**
                     * Copied from here: http://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
                     */
                    function escapeRegExp(str) {
                          return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
                    }

                    var regexp = new RegExp(escapeRegExp(searchString), 'g');
                    var cellData  = hot.getDataAtCell(cell.row, cell.col);
                    if (cellData.replace) {
                        var newCellData = cellData.replace(regexp, replaceString);
                        hot.setDataAtCell(cell.row, cell.col, newCellData);
                    }
                    else {
                        // Readonly field?
                    }
                });

            });
        });
    }

    /**
     * Load localized question texts into handsontable
     */
    LS.plugin.massAction.loadQuestionTexts = function()
    {
        var links = {
            getLink: LS.plugin.massAction.getQuestionTextsLink,
            saveLink: LS.plugin.massAction.saveQuestionChangeLink
        };
        LS.plugin.massAction.load(links);
    }

    /**
     * Load non-localized question attributes into handsontable
     */
    LS.plugin.massAction.loadQuestionAttributes = function()
    {
        var links = {
            getLink: LS.plugin.massAction.getQuestionAttributesLink,
            saveLink: LS.plugin.massAction.saveQuestionChangeLink
        };
        LS.plugin.massAction.load(links);
    }

    /**
     * Load question groups into handsontable
     */
    LS.plugin.massAction.loadQuestionGroups = function()
    {
        var links = {
            getLink: LS.plugin.massAction.getQuestionGroupsLink,
            saveLink: LS.plugin.massAction.saveQuestionGroupChangeLink
        };
        LS.plugin.massAction.load(links);
    }

    /**
     * Load tokens into handsontable
     */
    LS.plugin.massAction.loadTokens = function()
    {
        var links = {
            getLink: LS.plugin.massAction.getTokensLink,
            saveLink: LS.plugin.massAction.saveTokenChangeLink
        };
        LS.plugin.massAction.load(links);
    }
});
