<?php
namespace common\resources\documents;

use setasign\Fpdi\Tcpdf\Fpdi;

class GiftCard
{
    /** @var null|Fpdi|\TCPDF */
    protected $_doc = null;

    /**
     * @param \common\models\GiftCard $giftCard
     * @throws \Exception
     */
    public function __construct(\common\models\GiftCard $giftCard)
    {
        $this->_doc = new Fpdi();
        $this->_doc->setPrintHeader(false);
        $this->_doc->setPrintFooter(false);
        $this->_doc->AddPage('L', 'A5');
        $this->_doc->Image(\Yii::getAlias('@common') . '/resources/images/logo.png', 10, 10, 48, 54);
        $this->_doc->write1DBarcode($giftCard->code, 'C128', 68, 12, 80, 22, '',
            ['stretch' => true, 'bgcolor' => [255, 255, 255], 'hpadding' => 2, 'vpadding' => 4]);

        $fontRegular = \TCPDF_FONTS::addTTFfont(\Yii::getAlias('@common') . '/resources/fonts/calibri.ttf');
        $fontBold = \TCPDF_FONTS::addTTFfont(\Yii::getAlias('@common') . '/resources/fonts/calibrib.ttf');

        $this->_doc->SetFont($fontBold, '', 18);
        $this->_doc->SetXY(68, 6);
        $this->_doc->Write(6, 'Квитанция об оплате');

        $fontSize = 12;
        $this->_doc->SetFont($fontBold, '', $fontSize);
        $this->_doc->SetXY(68, 34);
        $this->_doc->Write(6, 'Предмет');
        $this->_doc->SetXY(68, 40);
        $this->_doc->Write(6, 'Студент');
        $this->_doc->SetXY(68, 46);
        $this->_doc->Write(6, 'Телефон');
        $this->_doc->SetXY(68, 52);
        $this->_doc->Write(6, 'Сумма');
        $this->_doc->SetXY(68, 58);
        $this->_doc->Write(6, 'Дата оплаты');
        $this->_doc->SetFont($fontRegular, '', $fontSize);
        $this->_doc->SetXY(100, 34);
        $this->_doc->Write(6, $giftCard->name);
        $this->_doc->SetXY(100, 40);
        $this->_doc->Write(6, $giftCard->customer_name);
        $this->_doc->SetXY(100, 46);
        $this->_doc->Write(6, $giftCard->phoneFull);
        $this->_doc->SetXY(100, 52);
        $this->_doc->Write(6, $giftCard->amount);
        $this->_doc->SetXY(100, 58);
        $this->_doc->Write(6, $giftCard->createDate->format('d.m.Y'));
    }

    public function save(): string
    {
        return $this->_doc->Output('flyer.pdf', 'S');
    }
}