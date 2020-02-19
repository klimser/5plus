<?php

namespace backend\controllers;

use common\models\Group;
use common\models\Module;
use common\models\SubjectCategory;
use common\models\Teacher;
use common\models\Webpage;
use yii;
use common\models\Subject;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * AjaxInfoController returns infos via ajax-requests
 */
class AjaxInfoController extends AdminController
{
    public function beforeAction($action)
    {
        if (!Yii::$app->request->isAjax) {
            return false;
        }
        return parent::beforeAction($action);
    }

    private function getFilter(): array
    {
        return Yii::$app->request->get('filter');
    }

    private function getOrder($defaultOrder = null): array
    {
        $order = Yii::$app->request->get('order', $defaultOrder);
        if ($order) {
            $field = preg_replace('#^-#', '', $order);
            $direction = mb_substr($order, 0, 1) === '-' ? SORT_DESC : SORT_ASC;
            return [$field => $direction];
        }
        return [];
    }

    public function actionSubjects()
    {
        $where = [];
        $params = [];
        $whitelist = ['name', 'active'];
        foreach ($this->getFilter() as $key => $value) {
            if (in_array($key, $whitelist)) {
                $where[] = Group::tableName() . "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        $query = Subject::find();
        foreach ($where as $condition) {
            $query->andWhere($condition);
        }
        /** @var Subject[] $subjects */
        $subjects = $query->addParams($params)
            ->orderBy($this->getOrder('name'))
            ->with('subjectCategory')
            ->all();
        $resultArray = [];
        foreach ($subjects as $subject) {
            $resultArray[] = [
                'id' => $subject->id,
                'name' => $subject->name,
                'categoryId' => $subject->category_id,
                'category' => $subject->subjectCategory->name,
            ];
        }

        return $this->asJson($resultArray);
    }
    
    public function actionGroups()
    {
        $where = [];
        $params = [];
        $whitelist = ['name', 'active', 'subject_id', 'teacher_id'];
        foreach ($this->getFilter() as $key => $value) {
            if (in_array($key, $whitelist)) {
                $where[] = Group::tableName() . "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        $query = Group::find();
        foreach ($where as $condition) {
            $query->andWhere($condition);
        }
        /** @var Group[] $groups */
        $groups = $query->addParams($params)
            ->orderBy($this->getOrder('name'))
            ->with('subject', 'teacher')
            ->all();
        $resultArray = [];
        foreach ($groups as $group) {
            $resultArray[] = [
                'id' => $group->id,
                'name' => $group->name,
                'subjectId' => $group->subject_id,
                'subject' => $group->subject->name,
                'teacherId' => $group->teacher_id,
                'teacher' => $group->teacher->name,
                'price' => $group->priceMonth,
                'price3' => $group->price3Month,
            ];
        }

        return $this->asJson($resultArray);
    }

    public function actionTeachers()
    {
        $where = [];
        $params = [];
        $whitelist = ['name', 'active', 'subject_id'];
        foreach ($this->getFilter() as $key => $value) {
            if (in_array($key, $whitelist)) {
                $where[] = Teacher::tableName() . "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        $query = Teacher::find()
            ->innerJoinWith('teacherSubjects');
        foreach ($where as $condition) {
            $query->andWhere($condition);
        }
        /** @var Teacher[] $teachers */
        $teachers = $query->addParams($params)
            ->orderBy($this->getOrder(Teacher::tableName() . '.name'))
            ->with('teacherSubjects')
            ->all();
        $resultArray = [];
        foreach ($teachers as $teacher) {
            $resultArray[] = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'subjectIds' => yii\helpers\ArrayHelper::getColumn($teacher->teacherSubjects, 'id'),
            ];
        }

        return $this->asJson($resultArray);
    }
    /**
     * Lists all Subject models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Subject::find()->orderBy(['category_id' => SORT_ASC, 'name' => SORT_ASC]),
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
     * Creates a new Subject model.
     * If creation is successful, the browser will be redirected to the 'page' page.
     * @return mixed
     * @throws \Exception
     */
    public function actionCreate()
    {
        return $this->processSubjectData(new Subject());
    }

    /**
     * Updates an existing Subject model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function actionUpdate($id)
    {
        return $this->processSubjectData($this->findModel($id));
    }

    /**
     * @param Subject $subject
     * @return string|yii\web\Response
     * @throws \Exception
     */
    public function processSubjectData(Subject $subject)
    {
        if (Yii::$app->request->isPost) {
            $isNew = $subject->isNewRecord;
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                /*     Сохраняем курс      */
                if (!$subject->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
                else {
                    $subject->imageFile = yii\web\UploadedFile::getInstance($subject, 'imageFile');
                    if (!$subject->save()) {
                        $subject->moveErrorsToFlash();
                        $transaction->rollBack();
                    } else {
                        /*     Сохраняем картинку      */
                        if (($isNew && !$subject->imageFile)
                            || ($subject->imageFile
                                && (!$subject->upload() || !$subject->save(true, ['image'])))) {
                            \Yii::$app->session->addFlash('error', 'Unable to upload image');
                            $transaction->rollBack();
                        } else {
                            /*     Сохраняем страничку      */
                            if (!$subject->webpage_id) {
                                $webpage = new Webpage();
                                $webpage->module_id = Module::getModuleIdByControllerAndAction('subject', 'view');
                                $webpage->record_id = $subject->id;
                            } else {
                                $webpage = $subject->webpage;
                            }
                            if (!$webpage->load(Yii::$app->request->post())) {
                                \Yii::$app->session->addFlash('error', 'Form data not found');
                                $transaction->rollBack();
                            } elseif (!$webpage->save()) {
                                $webpage->moveErrorsToFlash();
                                $transaction->rollBack();
                            } else {
                                if (!$subject->webpage_id) $subject->link('webpage', $webpage);
                                $transaction->commit();
                                Yii::$app->session->addFlash('success', $isNew ? 'Курс добавлен' : 'Курс обновлён');
                                return $this->redirect(['update', 'id' => $subject->id]);
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
            'subject' => $subject,
            'subjectCategories' => SubjectCategory::find()->all(),
            'module' => Module::getModuleByControllerAndAction('subject', 'view'),
        ]);
    }

    /**
     * Deletes an existing Subject model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionDelete($id)
    {
        $subject = $this->findModel($id);
        $transaction = Subject::getDb()->beginTransaction();
        try {
            if (!$subject->delete()) {
                $subject->moveErrorsToFlash();
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $this->redirect(['index']);
    }

    /**
     * Finds the Subject model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Subject the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Subject::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested subject does not exist.');
        }
    }
}
