<?php
/* @var $this \yii\web\View */
/* @var $content string */

$this->beginPage();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        table {
            width: 100%;
            max-width: 100%;
            margin: 0 0 3mm 0;
            padding: 0;
            border-collapse: collapse;
        }
        table.bordered th, table.bordered td {
            border: 1px solid black;
            margin: 0;
            padding: 2mm;
        }
        span.placeholder {
            border-bottom: 1px solid black;
            display: inline-block;
            padding: 0 3mm;
            /*margin-right: 2mm;*/
            /*font-size: 5mm;*/
        }
        tr {
            break-inside: avoid !important;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-uppercase {
            text-transform: uppercase;
        }
    </style>
    <title>Договор</title>
    <?php $this->head() ?>
</head>
<body onload="window.print();">
<?php $this->beginBody() ?>
    <?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
