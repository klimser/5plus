<?php

namespace backend\components;

use Yii;
use yii\base\Component;

class DefaultValuesComponent extends Component
{
    public static function getDatePickerSettings()
    {
        return [
            'clientOptions' => [
                'autoclose' => true,
                'format' => 'dd.mm.yyyy',
                'language' => 'ru',
                'weekStart' => 1,
            ]
        ];
    }
    public static function getTinyMceSettings()
    {
        return [
            'options' => ['rows' => 6],
            'language' => 'ru',
            'clientOptions' => [
                'plugins' => [
                    'advlist autolink lists link charmap preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table contextmenu paste',
                    'image imagetools',
                    'textcolor responsivefilemanager'
                ],
                'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre',
                'style_formats_autohide' => true,
                'style_formats' => [
                    [
                        'title' => 'Картинка слева вверху',
                        'selector' => 'img',
                        'attributes' => ['height' => ''],
                        'styles' => [
                            'float' => 'left',
                            'margin' => '0 10px 10px 0',
                            'max-width' => '100%',
                        ],
                    ],
                    [
                        'title' => 'Картинка в середине',
                        'selector' => 'img',
                        'attributes' => ['height' => ''],
                        'styles' => [
                            'margin' => '0 auto',
                            'max-width' => '100%',
                        ],
                    ],
                    [
                        'title' => 'Картинка любая',
                        'selector' => 'img',
                        'attributes' => ['height' => ''],
                        'styles' => ['max-width' => '100%'],
                    ],
                ],
                'toolbar' => 'undo redo | styleselect | bold italic | subscript superscript | fontsizeselect | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | responsivefilemanager link image',
                'imagetools_toolbar' => 'rotateleft rotateright | flipv fliph | editimage imageoptions',
                'external_filemanager_path' => Yii::$app->getHomeUrl() . 'filemanager/filemanager/',
                'filemanager_title' => 'Responsive Filemanager',
                'external_plugins' => [
                    'filemanager' => Yii::$app->getHomeUrl() . 'filemanager/filemanager/plugin.min.js',
                    'responsivefilemanager' => Yii::$app->getHomeUrl() . 'filemanager/responsivefilemanager/plugin.min.js',
                ],
                'relative_urls' => false,
            ]
        ];
    }
}
