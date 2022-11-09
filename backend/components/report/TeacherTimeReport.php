<?php

namespace backend\components\report;

use backend\models\TeacherSubjectLink;
use common\components\helpers\MoneyHelper;
use DateTimeImmutable;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\Style\Table;
use Yii;

class TeacherTimeReport
{
    public static function create(TeacherSubjectLink $teacherSubject, DateTimeInterface $reportDate, int $totalHours, ?PhpWord $document = null): PhpWord
    {
        if (null === $document) {
            $document = new PhpWord();
            $section = $document->addSection();
        } else {
            $section = $document->getSection(0);
            $section->addPageBreak();
        }

        $fontStandard = new Font();
        $fontStandard->setName('Calibri');
        $fontStandard->setSize(11);
        
        $fontBold = clone $fontStandard;
        $fontBold->setBold(true);
        
        $fontUnderlined = clone $fontStandard;
        $fontUnderlined->setUnderline(Font::UNDERLINE_SINGLE);
        
        $pStyleCenter = new Paragraph();
        $pStyleCenter->setAlignment(Alignment::HORIZONTAL_CENTER);

        $pStyleJustify = new Paragraph();
        $pStyleJustify->setAlignment('both');

        $titleRun = $section->addTextRun($pStyleCenter);
        $titleRun->addText('АКТ оказания услуг', $fontBold);
        $titleRun->addTextBreak();
        $titleRun->addText("по договору № {$teacherSubject->teacher->contractNumber} от " . Yii::$app->formatter->asDate($teacherSubject->teacher->contractDate ?? new DateTimeImmutable(), 'long'), $fontBold);

        $section->addTextBreak();
        
        $date = DateTimeImmutable::createFromInterface($reportDate);
        $date = $date->modify('last day of this month');

        $table = $section->addTable((new Table())->setWidth(100 * 50)->setUnit(TblWidth::PERCENT)->setLayout(Table::LAYOUT_AUTO));
        $row = $table->addRow();
        $cell1 = $row->addCell(50 * 50);
        $cell1->addText('г. Ташкент');
        $cell2 = $row->addCell(50 * 50);
        $cell2->addText(Yii::$app->formatter->asDate($date, 'long'), $fontStandard, ['alignment' => 'right']);

        $textrun = $section->addTextRun($pStyleJustify);
        $bodyText1 = $textrun->addText('ООО «Exclusive Education»');
        $bodyText2 = $textrun->addText(', именуемое в дальнейшем ');
        $bodyText3 = $textrun->addText('«Заказчик»');
        $bodyText4 = $textrun->addText(', в лице директора Климова А.С., действующего на основании Устава, с одной стороны, и Гражданин (ка) Республики Узбекистан ');
        $bodyText5 = $textrun->addText($teacherSubject->teacher->name);
        $bodyText6 = $textrun->addText(', именуемый (ая) в дальнейшем ');
        $bodyText7 = $textrun->addText('«Исполнитель»');
        $bodyText8 = $textrun->addText(', с другой стороны, составили настоящий акт о нижеследующем:');
        $bodyText1->setFontStyle($fontBold);
        $bodyText2->setFontStyle($fontStandard);
        $bodyText3->setFontStyle($fontBold);
        $bodyText4->setFontStyle($fontStandard);
        $bodyText5->setFontStyle($fontBold);
        $bodyText6->setFontStyle($fontStandard);
        $bodyText7->setFontStyle($fontBold);
        $bodyText8->setFontStyle($fontStandard);

        $textrun = $section->addTextRun($pStyleJustify);
        $textrun->addText('Исполнителем выполнены, а Заказчиком приняты услуги по обучению групп(ы) слушателей учебного курса «');
        $textrun->addText($teacherSubject->subject->name, $fontUnderlined);
        $textrun->addText('» по договору №');
        $textrun->addText($teacherSubject->teacher->contractNumber, $fontBold);
        $textrun->addText(' от ');
        $textrun->addText(Yii::$app->formatter->asDate($teacherSubject->teacher->contractDate ?? new DateTimeImmutable(), 'long'));
        
        $section->addText(
            'Услуги оказаны Исполнителем в полном объеме и в срок. Заказчик претензий по объему, качеству и срокам оказания услуг претензий не имеет. Исполнитель претензий к Заказчику не имеет.',
            $fontStandard,
            $pStyleJustify
        );

        $textrun = $section->addTextRun($pStyleJustify);
        $textrun->addText('Количество академических часов составляет ', $fontBold);
        $textrun->addText($totalHours, $fontBold);
        
        $textrun = $section->addTextRun($pStyleJustify);
        $textrun->addText('Стоимость оказанных услуг составляет ', $fontBold);
        $textrun->addText(number_format($totalHours * 30000, 0, '.', ' '), $fontBold);
        $textrun->addText(' (', $fontBold);
        $textrun->addText(MoneyHelper::numberToStringRus($totalHours * 30000, true), $fontBold);
        $textrun->addText(') сум без НДС, поскольку Исполнитель не является плательщиком НДС.', $fontBold);
        
        $section->addTextBreak();

        $table = $section->addTable((new Table())->setWidth(100 * 50)->setUnit(TblWidth::PERCENT)->setLayout(Table::LAYOUT_AUTO));
        $row = $table->addRow();
        $cell1 = $row->addCell(50 * 50);
        $textRun = $cell1->addTextRun($pStyleCenter);
        $textRun->addText('ИСПОЛНИТЕЛЬ', $fontBold);
        $textRun->addTextBreak();
        $textRun->addText('УСЛУГУ ОКАЗАЛ', $fontBold);
        $textRun->addTextBreak(2);
        $textRun->addText('________________________');
        $cell2 = $row->addCell(50 * 50);
        $textRun = $cell2->addTextRun($pStyleCenter);
        $textRun->addText('ЗАКАЗЧИК', $fontBold);
        $textRun->addTextBreak();
        $textRun->addText('ООО «EXCLUSIVE EDUCATION»', $fontBold);
        $textRun->addTextBreak();
        $textRun->addText('УСЛУГУ ПРИНЯЛ:', $fontBold);
        $textRun->addTextBreak(2);
        $textRun->addText('Директор ________________ Климов А.С.', $fontBold);
        
        return $document;
    }
}
