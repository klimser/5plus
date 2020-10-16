<?php

namespace backend\components\report;

use backend\models\Action;
use backend\models\Consultation;
use backend\models\WelcomeLesson;
use common\models\Payment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ManagerSalaryReport
{
    public static function create(\DateTimeImmutable $date, $month = true): Spreadsheet
    {
        if ($month) {
            $date = $date->modify('first day of this month midnight');
            $endDate = $date->modify('+1 month -1 second');
        } else {
            $date = $date->modify('midnight');
            $endDate = $date->modify('+1 day -1 second');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->setTitle('Обзор');
        
        $consultationSheet = $spreadsheet->createSheet();
        $consultationSheet->setTitle('Консультации');
        $welcomeLessonSheet = $spreadsheet->createSheet();
        $welcomeLessonSheet->setTitle('Пробные уроки');
        $groupsSheet = $spreadsheet->createSheet();
        $groupsSheet->setTitle('Группы');
        $incomeSheet = $spreadsheet->createSheet();
        $incomeSheet->setTitle('Деньги');

        $consultationSheet->setCellValue('A1', 'Админ');
        $consultationSheet->setCellValue('B1', 'Студент');
        $consultationSheet->setCellValue('C1', 'Предмет');
        $consultationSheet->setCellValue('D1', 'Дата');
        $header = $consultationSheet->getStyle('A1:D1');
        $header->getFont()->setBold(true);
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        $managerMap = [];
        
        /** @var Consultation[] $consultations */
        $consultations = Consultation::find()
            ->alias('c')
            ->joinWith('createdAdmin u')
            ->with(['subject', 'user'])
            ->andWhere(['between', 'c.created_at', $date->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->orderBy(['u.name' => SORT_ASC, 'c.created_at' => SORT_ASC])
            ->all();
        
        $row = 2;
        foreach ($consultations as $consultation) {
            if (!array_key_exists($consultation->createdAdmin->id, $managerMap)) {
                $managerMap[$consultation->createdAdmin->id] = [
                    'name' => $consultation->createdAdmin->name,
                    'consultation' => 0,
                    'welcome_lesson' => 0,
                    'group' => 0,
                    'money' => 0,
                ];
            }

            $consultationSheet->setCellValue("A$row", $consultation->createdAdmin->name);
            $consultationSheet->setCellValue("B$row", $consultation->user->name);
            $consultationSheet->setCellValue("C$row", $consultation->subject->name);
            $consultationSheet->setCellValue("D$row", Date::PHPToExcel($consultation->createDate));
            $managerMap[$consultation->createdAdmin->id]['consultation']++;
            $row++;
        }
        $consultationSheet->getStyle("D2:D$row")->getNumberFormat()->setFormatCode('dd mmmm yy');
        $consultationSheet->getColumnDimension('A')->setAutoSize(true);
        $consultationSheet->getColumnDimension('B')->setAutoSize(true);
        $consultationSheet->getColumnDimension('C')->setAutoSize(true);
        $consultationSheet->getColumnDimension('D')->setAutoSize(true);

        $welcomeLessonSheet->setCellValue('A1', 'Админ');
        $welcomeLessonSheet->setCellValue('B1', 'Студент');
        $welcomeLessonSheet->setCellValue('C1', 'Группа');
        $welcomeLessonSheet->setCellValue('D1', 'Дата');
        $header = $welcomeLessonSheet->getStyle('A1:D1');
        $header->getFont()->setBold(true);
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        /** @var WelcomeLesson[] $welcomeLessons */
        $welcomeLessons = WelcomeLesson::find()
            ->alias('wl')
            ->joinWith('createdAdmin u')
            ->with(['group', 'user'])
            ->andWhere(['between', 'wl.created_at', $date->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->orderBy(['u.name' => SORT_ASC, 'wl.created_at' => SORT_ASC])
            ->all();

        $row = 2;
        foreach ($welcomeLessons as $welcomeLesson) {
            if (!array_key_exists($welcomeLesson->createdAdmin->id, $managerMap)) {
                $managerMap[$welcomeLesson->createdAdmin->id] = [
                    'name' => $welcomeLesson->createdAdmin->name,
                    'consultation' => 0,
                    'welcome_lesson' => 0,
                    'group' => 0,
                    'money' => 0,
                ];
            }

            $welcomeLessonSheet->setCellValue("A$row", $welcomeLesson->createdAdmin->name);
            $welcomeLessonSheet->setCellValue("B$row", $welcomeLesson->user->name);
            $welcomeLessonSheet->setCellValue("C$row", $welcomeLesson->group->name);
            $welcomeLessonSheet->setCellValue("D$row", Date::PHPToExcel($welcomeLesson->createDate));
            $managerMap[$welcomeLesson->createdAdmin->id]['welcome_lesson']++;
            $row++;
        }
        $welcomeLessonSheet->getStyle("D2:D$row")->getNumberFormat()->setFormatCode('dd mmmm yy');
        $welcomeLessonSheet->getColumnDimension('A')->setAutoSize(true);
        $welcomeLessonSheet->getColumnDimension('B')->setAutoSize(true);
        $welcomeLessonSheet->getColumnDimension('C')->setAutoSize(true);
        $welcomeLessonSheet->getColumnDimension('D')->setAutoSize(true);

        $groupsSheet->setCellValue('A1', 'Админ');
        $groupsSheet->setCellValue('B1', 'Студент');
        $groupsSheet->setCellValue('C1', 'Группа');
        $groupsSheet->setCellValue('D1', 'Дата');
        $header = $groupsSheet->getStyle('A1:D1');
        $header->getFont()->setBold(true);
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        /** @var Action[] $actions */
        $actions = Action::find()
            ->alias('a')
            ->with(['group', 'user', 'admin'])
            ->andWhere(['a.type' => \common\components\Action::TYPE_GROUP_PUPIL_ADDED])
            ->andWhere(['between', 'a.created_at', $date->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->orderBy(['a.created_at' => SORT_ASC])
            ->all();

        $row = 2;
        $useSet = [];
        $groupData = [];
        foreach ($actions as $action) {
            $key = "{$action->group_id}|{$action->user_id}";
            if (array_key_exists($key, $useSet)) continue;
            $useSet[$key] = true;

            if (!array_key_exists($action->admin->id, $groupData)) {
                $groupData[$action->admin_id] = [
                    'name' => $action->admin->name,
                    'groups' => [],
                ];
            }
            $groupData[$action->admin_id]['groups'][] = $action;
        }
        
        foreach ($groupData as $adminId => $data) {
            
            if (!array_key_exists($adminId, $managerMap)) {
                $managerMap[$adminId] = [
                    'name' => $data['name'],
                    'consultation' => 0,
                    'welcome_lesson' => 0,
                    'group' => 0,
                    'money' => 0,
                ];
            }
            
            foreach ($data['groups'] as $action) {

                $groupsSheet->setCellValue("A$row", $data['name']);
                $groupsSheet->setCellValue("B$row", $action->user->name);
                $groupsSheet->setCellValue("C$row", $action->group->name);
                $groupsSheet->setCellValue("D$row", Date::PHPToExcel($action->createDate));
                $managerMap[$adminId]['group']++;
                $row++;
            }
        }
        $groupsSheet->getStyle("D2:D$row")->getNumberFormat()->setFormatCode('dd mmmm yy');
        $groupsSheet->getColumnDimension('A')->setAutoSize(true);
        $groupsSheet->getColumnDimension('B')->setAutoSize(true);
        $groupsSheet->getColumnDimension('C')->setAutoSize(true);
        $groupsSheet->getColumnDimension('D')->setAutoSize(true);

        $incomeSheet->setCellValue('A1', 'Админ');
        $incomeSheet->setCellValue('B1', 'Студент');
        $incomeSheet->setCellValue('C1', 'Группа');
        $incomeSheet->setCellValue('D1', 'Дата');
        $incomeSheet->setCellValue('E1', 'Сумма');
        $header = $incomeSheet->getStyle('A1:E1');
        $header->getFont()->setBold(true);
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->alias('p')
            ->joinWith('admin ad')
            ->with(['group', 'user'])
            ->andWhere(['between', 'p.created_at', $date->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->andWhere(['not', ['p.admin_id' => null]])
            ->andWhere(['>', 'p.amount', 0])
//            ->andWhere(['not', ['like', 'p.comment', 'Перевод оставшихся средств%', false]])
            ->orderBy(['ad.name' => SORT_ASC, 'p.created_at' => SORT_ASC])
            ->all();

        $row = 2;
        foreach ($payments as $payment) {
            if (!array_key_exists($payment->admin_id, $managerMap)) {
                $managerMap[$payment->admin_id] = [
                    'name' => $payment->admin->name,
                    'consultation' => 0,
                    'welcome_lesson' => 0,
                    'group' => 0,
                    'money' => 0,
                ];
            }

            $incomeSheet->setCellValue("A$row", $payment->admin->name);
            $incomeSheet->setCellValue("B$row", $payment->user->name);
            $incomeSheet->setCellValue("C$row", $payment->group->name);
            $incomeSheet->setCellValue("D$row", Date::PHPToExcel($payment->createDate));
            $incomeSheet->setCellValueExplicit("E$row", $payment->amount, DataType::TYPE_NUMERIC);
            $managerMap[$payment->admin_id]['money'] += $payment->amount;
            $row++;
        }
        $incomeSheet->getStyle("D2:D$row")->getNumberFormat()->setFormatCode('dd mmmm yy');
        $incomeSheet->getStyle("E2:E$row")->getNumberFormat()->setFormatCode('#,##0');
        $incomeSheet->getColumnDimension('A')->setAutoSize(true);
        $incomeSheet->getColumnDimension('B')->setAutoSize(true);
        $incomeSheet->getColumnDimension('C')->setAutoSize(true);
        $incomeSheet->getColumnDimension('D')->setAutoSize(true);
        $incomeSheet->getColumnDimension('E')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Админ');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Консультации');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Пробные уроки');
        $spreadsheet->getActiveSheet()->setCellValue('D1', 'Студенты');
        $spreadsheet->getActiveSheet()->setCellValue('E1', 'Деньги');
        $header = $spreadsheet->getActiveSheet()->getStyle('A1:E1');
        $header->getFont()->setBold(true);
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $row = 2;
        foreach ($managerMap as $data) {
            $spreadsheet->getActiveSheet()->setCellValue("A$row", $data['name']);
            $spreadsheet->getActiveSheet()->setCellValue("B$row", $data['consultation']);
            $spreadsheet->getActiveSheet()->setCellValue("C$row", $data['welcome_lesson']);
            $spreadsheet->getActiveSheet()->setCellValue("D$row", $data['group']);
            $spreadsheet->getActiveSheet()->setCellValueExplicit("E$row", $data['money'], DataType::TYPE_NUMERIC);
            $row++;
        }
        $spreadsheet->getActiveSheet()->getStyle("E2:E$row")->getNumberFormat()->setFormatCode('#,##0');
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

        return $spreadsheet;
    }
}
