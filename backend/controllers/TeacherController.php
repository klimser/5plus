<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\models\Module;
use common\models\Subject;
use common\models\Teacher;
use common\models\Webpage;
use yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TeacherController implements the CRUD actions for Teacher model.
 */
class TeacherController extends AdminController
{
    use Active;

    protected $accessRule = 'manageTeachers';

    /**
     * Lists all Teacher models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Teacher::find()->with('subjects')->orderBy(['active' => SORT_DESC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50,],
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC],
                'attributes' => ['name'],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Teacher model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->processTeacherData(new Teacher());
    }

    /**
     * Updates an existing Teacher model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processTeacherData($this->findModel($id));
    }

    /**
     * @param Teacher $teacher
     * @return string|yii\web\Response
     * @throws \Exception
     */
    public function processTeacherData(Teacher $teacher)
    {
        if (\Yii::$app->request->isPost) {
            $isNew = $teacher->isNewRecord;
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                /*     Сохраняем учителя      */
                if (!$teacher->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    if ($teacher->isAttributeChanged('birthday')) {
                        if (empty($teacher->birthday)) $teacher->birthday= null;
                        else {
                            $birthdayDate = date_create_from_format('d.m', $teacher->birthday);
                            $teacher->birthday = $birthdayDate->format('Y-m-d');
                        }
                    }
                    $teacher->photoFile = yii\web\UploadedFile::getInstance($teacher, 'photoFile');
                    if (!$teacher->save()) {
                        $teacher->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        /*     Сохраняем картинку      */
                        if ($teacher->photoFile && (!$teacher->upload() || !$teacher->save(true, ['photo']))) {
                            \Yii::$app->session->addFlash('error', 'Unable to upload image');
                            $transaction->rollBack();
                        } else {
                            /*     Сохраняем страничку      */
                            if (!$teacher->webpage_id) {
                                $webpage = new Webpage();
                                $webpage->module_id = Module::getModuleIdByControllerAndAction('teacher', 'view');
                                $webpage->record_id = $teacher->id;
                            } else {
                                $webpage = $teacher->webpage;
                            }
                            if (!$webpage->load(Yii::$app->request->post())) {
                                \Yii::$app->session->addFlash('error', 'Form data not found');
                                $transaction->rollBack();
                            } elseif (!$webpage->save()) {
                                $webpage->moveErrorsToFlash();
                                $transaction->rollBack();
                            } else {
                                if (!$teacher->webpage_id) $teacher->link('webpage', $webpage);
                                $this->saveTeacherSubjects($teacher);
                                $transaction->commit();
                                Yii::$app->session->addFlash('success', $isNew ? 'Учитель добавлен' : 'Учитель обновлён');
                                return $this->redirect(['update', 'id' => $teacher->id]);
                            }
                        }
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('update', [
            'teacher' => $teacher,
            'module' => Module::getModuleByControllerAndAction('teacher', 'view'),
            'subjects' => Subject::find()->orderBy('name')->all(),
        ]);
    }

    /**
     * @param Teacher $teacher
     */
    private function saveTeacherSubjects(Teacher $teacher)
    {
        $subjectSet = [];
        foreach (Yii::$app->request->post('subject', []) as $subjectId) $subjectSet[$subjectId] = true;
        if ($teacher->subjects) {
            $subjects = $teacher->subjects;
            foreach ($subjects as $subject) {
                if (!isset($subjectSet[$subject->id])) $teacher->unlink('subjects', $subject, true);
                else unset($subjectSet[$subject->id]);
            }
        }
        foreach ($subjectSet as $subjectId => $devNull) {
            /** @var Subject $subject */
            $subject = Subject::findOne($subjectId);
            if ($subject) $teacher->link('subjects', $subject);
        }
    }

    public function actionPage()
    {
        $prefix = 'teacher_';
        $webpage = null;
        $moduleId = Module::getModuleIdByControllerAndAction('teacher', 'index');
        $webpage = Webpage::find()->where(['module_id' => $moduleId])->one();
        if (!$webpage) {
            $webpage = new Webpage();
            $webpage->module_id = $moduleId;
        }

        if (Yii::$app->request->isPost) {
            if (!$webpage->load(Yii::$app->request->post())) {
                \Yii::$app->session->addFlash('error', 'Form data not found');
            } elseif (!$webpage->save()) {
                $webpage->moveErrorsToFlash();
            } else {
                $sortOrder = Yii::$app->request->post('sorted-list');
                if ($sortOrder) {
                    $data = explode(',', $sortOrder);
                    for ($i = 1; $i <= count($data); $i++) {
                        $teacherId = str_replace($prefix, '', $data[$i - 1]);
                        $teacher = $this->findModel($teacherId);
                        $teacher->page_order = $i;
                        $teacher->save(true, ['page_order']);
                    }
                }
                Yii::$app->session->addFlash('success', 'Изменения сохранены');
                return $this->redirect(['page']);
            }
        }

        return $this->render('page', [
            'webpage' => $webpage,
            'teachers' => Teacher::find()->where(['page_visibility' => Teacher::STATUS_ACTIVE])->orderBy('page_order')->all(),
            'prefix' => $prefix,
        ]);
    }

    /**
     * @param int|null $subject
     * @return Response
     */
    public function actionListJson($subject = null) {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $query = Teacher::find()->andWhere(['active' => Teacher::STATUS_ACTIVE]);
            if ($subject) {
                $jsonData['subjectId'] = $subject;
                $jsonData['teachers'] = $query
                    ->innerJoinWith('teacherSubjects')
                    ->andWhere(['subject_id' => $subject])
                    ->orderBy('{{%teacher}}.name')
                    ->select('{{%teacher}}.id')
                    ->column();
            } else {
                $jsonData = $query->select(['id', 'name'])->asArray()->all();
            }
        }
        return $this->asJson($jsonData);
    }

    /**
     * Finds the Teacher model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Teacher the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Teacher::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
