<?php

$files = [
<% _.forEach(files, function(file) { %>
    "<%= file.originalPath %>" => "<%= file.versionedPath %>",
<% }); %>
];

foreach ($files as $file) {
    if (substr($file, -4) == '.css') {
        $this->registerCssFile(Yii::$app->homeUrl . $file);
    } elseif (substr($file, -3) == '.js') {
        $this->registerJsFile(Yii::$app->homeUrl . $file, ['position' => \yii\web\View::POS_END]);
    }
}

$this->registerJs('yii.init();');