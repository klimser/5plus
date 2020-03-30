<?php
namespace common\resources\documents;

use backend\models\WelcomeLesson;
use common\components\helpers\Calendar;
use setasign\Fpdi\Tcpdf\Fpdi;
use Yii;

class WelcomeLessons
{
    /** @var null|Fpdi|\TCPDF */
    protected $_doc = null;

    /**
     * @param string $text
     * @param int $maxWidth
     * @return string[]
     */
    private function splitText(string $text, int $maxWidth, string $fontName = 'calibri', string $fontStyle = '', int $fontSize = 12): array
    {
        $stringList = [];
        $words = explode(' ', $text);
        $index = 0;
        while (($word = array_shift($words)) !== null) {
            $newString = trim(($stringList[$index] ?? '') . ' ' . $word);
            if ($this->_doc->GetStringWidth($newString, $fontName, $fontStyle, $fontSize) > $maxWidth || empty($stringList[$index])) {
                $index++;
                $newString = $word;
            }
            $stringList[$index] = $newString;
        }
        
        return $stringList;
    }

    /**
     * @param WelcomeLesson[] $welcomeLessons
     */
    public function __construct(array $welcomeLessons)
    {
        $this->_doc = new Fpdi();
        $this->_doc->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->_doc->setPrintHeader(false);
        $this->_doc->setHeaderMargin(0);
        $this->_doc->setPrintFooter(false);
        $this->_doc->setFooterMargin(0);
        $this->_doc->SetMargins(0, 0, 0, false);
        $x2 = $y3 = 0;
        foreach (array_values($welcomeLessons) as $key => $welcomeLesson) {
            if ($key % 6 === 0) {
                $this->_doc->AddPage('P', 'A4');
                $x2 = floor($this->_doc->getPageWidth() / 2);
                $y3 = floor($this->_doc->getPageHeight() / 3);
                
                $this->_doc->Line($x2, 0, $x2, $this->_doc->getPageHeight(), []);
                $this->_doc->Line(0, $y3, $this->_doc->getPageWidth(), $y3, []);
                $this->_doc->Line(0, 2 * $y3, $this->_doc->getPageWidth(), 2 * $y3, []);
            }
            
            $xLeft = $x2 * ($key % 2);
            $yTop = $y3 * floor(($key % 6) / 2);
            
            $this->_doc->Image(\Yii::getAlias('@common') . '/resources/images/logo.png',  $xLeft+ 5, $yTop + 5, 30, 33);
            
            $fontRegular = \TCPDF_FONTS::addTTFfont(\Yii::getAlias('@common') . '/resources/fonts/calibri.ttf');
            $fontBold = \TCPDF_FONTS::addTTFfont(\Yii::getAlias('@common') . '/resources/fonts/calibrib.ttf');
            
            $this->_doc->SetFont($fontBold, '', 18);
            $this->_doc->SetXY($xLeft + 45, $yTop + 6);
            $this->_doc->Write(6, 'МЫ ВАС ЖДЁМ');

            $fontSize = 12;
            $this->_doc->SetFont($fontBold, '', $fontSize);
            $this->_doc->SetXY($xLeft + 45, $yTop + 20);
            $this->_doc->Write(6, Yii::$app->formatter->asDatetime($welcomeLesson->lessonDateTime, 'php:j F Y G:i'));

            $this->_doc->SetFont($fontRegular, '', $fontSize);
            $subjectStrings = $this->splitText($welcomeLesson->subject->name, 50);
            foreach ($subjectStrings as $i => $subjectString) {
                $this->_doc->SetXY($xLeft + 45, 6 * $i + $yTop + 25);
                $this->_doc->Write(6, $subjectString);
            }
            
            $teacherStrings = $this->splitText('Преподаватель - ' . $welcomeLesson->teacher->name, 90);
            foreach ($teacherStrings as $i => $teacherString) {
                $this->_doc->SetXY($xLeft + 5, 6 * $i + $yTop + 35);
                $this->_doc->Write(6, $teacherString);
            }
            
            if ($welcomeLesson->group_id) {
                $y = 6 * count($teacherStrings) + $yTop + 37;
                $groupStrings = $this->splitText('Группа - ' . $welcomeLesson->group->name, 90);
                foreach ($groupStrings as $i => $groupString) {
                    $this->_doc->SetXY($xLeft + 5, $y + 6 * $i);
                    $this->_doc->Write(6, $groupString);
                }
                $y += 6 * count($groupStrings) + 6;
                $this->_doc->SetXY($xLeft + 5, $y);
                foreach ($welcomeLesson->group->scheduleData as $day => $time) {
                    if ($time) {
                        $this->_doc->Write(6, Calendar::$weekDaysShort[($day + 1) % 7] . ' - ' . $time . ' | ');
                    }
                }
            }
        }
    }

    public function save(): string
    {
        return $this->_doc->Output('notice.pdf', 'S');
    }
}
