<?php

namespace backend\controllers;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use common\components\ComponentContainer;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use common\models\Company;
use common\models\Contract;
use common\models\ContractSearch;
use common\models\Course;
use common\models\GroupParam;
use common\models\CourseStudent;
use common\models\User;
use common\components\Action;
use DateTimeImmutable;
use yii;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * ContractController implements contracts management.
 */
class ContractController extends AdminController
{
    protected $accessRule = 'contractManagement';

     /**
     * Print Contract.
     * @param int $id
     * @return mixed
     */
    public function actionPrint($id)
    {
        $contract = $this->findModel($id);
        $this->layout = 'print';
        return $this->render("/contract/print/" . ($contract->user->individual ? 'spec' : 'company'), ['contract' => $contract]);
    }

    /**
     * @param $id
     * @return yii\web\Response
     */
    public function actionBarcode($id)
    {
        $contract = $this->findModel($id);
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        return \Yii::$app->response->sendContentAsFile(
            $generator->getBarcode($contract->number, $generator::TYPE_CODE_128),
            "barcode.svg",
            ['mimeType' => 'image/svg+xml', 'inline' => true]
        );
    }

    public function actionQr()
    {
        $link = 'https://t.me/fiveplus_public_bot?start=account';
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'addQuietzone' => false,
            'imageBase64' => false,
        ]);
        $generator = new QRCode($options);

        return \Yii::$app->response->sendContentAsFile(
            $generator->render($link),
            "qrcode.svg",
            ['mimeType' => 'image/svg+xml', 'inline' => true]
        );
    }

    public function actionIndex()
    {
        $searchModel = new ContractSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $students */
        $students = User::find()->where(['role' => User::ROLE_STUDENT])->orderBy(['name' => SORT_ASC])->all();
        $studentMap = [null => 'Все'];
        foreach ($students as $student) {
            $studentMap[$student->id] = $student->name;
        }

        $courseMap = [null => 'Все'];
        foreach (CourseComponent::getAllSortedByActiveAndName() as $course) {
            $courseMap[$course->id] = $course->latestCourseConfig->name;
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'studentMap' => $studentMap,
            'courseMap' => $courseMap,
        ]);
    }

    /**
     * Create new contract
     * @return mixed
     */
    public function actionCreateAjax()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $formData = Yii::$app->request->post('new-contract', []);

        if (!isset($formData['userId'], $formData['courseId'], $formData['amount'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $student = User::findOne($formData['userId']);
        $course = Course::findOne($formData['courseId']);
        if (!$student || $student->role !== User::ROLE_STUDENT) {
            return self::getJsonErrorResult('Wrong student');
        }
        if (!$course || $course->active !== Course::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Wrong course');
        }
        if ($formData['amount'] <= 0) {
            return self::getJsonErrorResult('Wrong amount');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $contract = MoneyComponent::addStudentContract(Company::findOne(Company::COMPANY_EXCLUSIVE_ID), $student, $formData['amount'], $course);
            $transaction->commit();
            return self::getJsonOkResult(['userId' => $student->id, 'contractLink' => yii\helpers\Url::to(['contract/print', 'id' => $contract->id])]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            return self::getJsonErrorResult($ex->getMessage());
        }
    }

    public function actionReport(?int $type = null, ?string $from = null, ?string $to = null)
    {
        $this->checkAccess('root');

        $params = [];
        if ($type && $from && $to) {
            $dateFrom = new DateTimeImmutable($from);
            $dateTo = new DateTimeImmutable($to);
            /** @var Contract[] $contracts */
            $contracts = Contract::find()
                ->andWhere(['status' => Contract::STATUS_PAID])
                ->andWhere(['payment_type' => $type])
                ->andWhere(['>', 'paid_at', $dateFrom->modify('midnight')->format('Y-m-d H:i:s')])
                ->andWhere(['<', 'paid_at', $dateTo->modify('+ 1 day midnight')->format('Y-m-d H:i:s')])
                ->orderBy(['paid_at' => SORT_ASC])
                ->all();
            $totalAmount = 0;
            foreach ($contracts as $contract) {
                $totalAmount += $contract->amount;
            }
            
            $params = [
                'type' => $type,
                'from' => $dateFrom,
                'to' => $dateTo,
                'totalAmount' => $totalAmount,
                'contracts' => $contracts,
            ];
        }
        
        return $this->render('report', $params);
    }

    /**
     * Finds the Contract model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Contract the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Contract::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
