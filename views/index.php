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
    var hot = new Handsontable(container, {
        data: data,
        rowHeaders: true,
        colHeaders: true
    });
});
</script>
