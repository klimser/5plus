<?php

namespace common\components;

use Yii;
use yii\base\Component;

class DefaultValuesComponent extends Component
{
    public static function getPagerSettings(): array
    {
        return [
            'maxButtonCount' => 4,
            'options' => ['class' => 'pagination justify-content-center'],
            'linkContainerOptions' => ['class' => 'page-item'],
            'linkOptions' => ['class' => 'page-link'],
            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
            'nextPageLabel' => '<span class="fas fa-angle-right"></span>',
            'prevPageLabel' => '<span class="fas fa-angle-left"></span>',
            'lastPageLabel' => '<span class="fas fa-angle-double-right"></span>',
            'firstPageLabel' => '<span class="fas fa-angle-double-left"></span>',
            'nextPageCssClass' => 'arr',
            'prevPageCssClass' => 'arr',
            'lastPageCssClass' => 'arr',
            'firstPageCssClass' => 'arr',
            'disableCurrentPageButton' => true,
        ];
    }
    public static function getDatePickerSettings(): array
    {
        return [
            'dateFormat' => 'dd.MM.y',
            'clientOptions' => [
                'firstDay' => 1,
            ],
            'options' => [
                'class' => 'form-control',
                'pattern' => '\d{2}\.\d{2}\.\d{4}',
            ],
        ];
    }
    public static function getTinyMceSettings(): array
    {
        return [
            'options' => ['rows' => 16],
            'language' => 'ru',
            'clientOptions' => [
                'element_format' => 'html',
                'plugins' => [
                    'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview', 'anchor',
                    'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table',
                    'image',
                    'responsivefilemanager'
                ],
                'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre',
                'style_formats_autohide' => true,
                'formats' => [
                    'bold' => ['inline' => 'b'],
                    'italic' => ['inline' => 'i'],
                ],
                'style_formats' => [
                    [
                        'title' => 'Картинка слева вверху',
                        'selector' => 'img',
                        'attributes' => ['height' => ''],
                        'classes' => 'img-fluid float-left mr-2 mb-2',
                    ],
                    [
                        'title' => 'Картинка в середине',
                        'selector' => 'img',
                        'attributes' => ['height' => ''],
                        'classes' => 'img-fluid mx-auto',
                    ],
                ],
                'extended_valid_elements' => 'img[class=img-fluid|src|border=0|alt|title|hspace|vspace|align|onmouseover|onmouseout|name|style|width|height],b/strong[class|id|style],i/em[class|id|style]',
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
    
    public static function getPhoneInputTemplate(): string
    {
        return '<div class="input-group"><div class="input-group-prepend"><span class="input-group-text">+998</span></div>{input}</div>';
    }
}
