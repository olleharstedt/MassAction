<div class="row">
    <div class="col-lg-12 content-right">
        <h3><?php eT("Mass action");?></h3>
        <div id="handsontable">
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var data = [
        ["", "Ford", "Volvo", "Toyota", "Honda"],
        ["2016", 10, 11, 12, 13],
        ["2017", 20, 11, 14, 13],
        ["2018", 30, 15, 12, 13]
    ];

    var container = document.getElementById('handsontable');
    var getQuestionsLink = '<?php echo $getQuestionsLink; ?>';
    var saveQuestionChangeLink = '<?php echo $saveQuestionChangeLink; ?>';
    var surveyId = '<?php echo $surveyId; ?>';

    $.ajax({
        method: 'GET',
        url: getQuestionsLink,
    }).done( function(data) {
        console.log("here");
        console.log(data);

        var data = JSON.parse(data);

        var hot = new Handsontable(container, {
            data: data.data,
            rowHeaders: true,
            colHeaders: data.colHeaders,
            columns: data.columns,
            afterChange: function(change, action) {

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
                    url: saveQuestionChangeLink,
                    data: {
                        row: row,
                        change: change,
                        surveyId: surveyId
                    }
                }).done(function(response) {
                    console.log("done saving");
                    console.log(response);
                });

                // action can be "edit" or "undo"

                //console.log('a', a);
                //console.log('b', b);
            }
        });

    });
});
</script>
