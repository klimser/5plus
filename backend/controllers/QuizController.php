<?php

namespace backend\controllers;

use common\models\Module;
use common\models\Question;
use common\models\Quiz;
use common\models\Subject;
use common\models\Webpage;
use yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * MenuController implements the CRUD actions for Quiz model.
 */
class QuizController extends AdminController
{
    protected $accessRule = 'manageQuiz';

    /**
     * Lists all Quiz models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Quiz::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Quiz model.
     * If creation is successful, the browser will be redirected to the 'update' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $quiz = new Quiz();

        if (Yii::$app->request->isPost) {
            if ($quiz->load(Yii::$app->request->post()) && $quiz->save()) {
                Yii::$app->session->setFlash('success', 'Тест успешно добавлен');
                return $this->redirect(['update', 'id' => $quiz->id]);
            } else $quiz->moveErrorsToFlash();
        }
        return $this->render('create', [
            'quiz' => $quiz,
            'subjects' => $this->getSubjectsForSelect(),
        ]);
    }

    /**
     * Updates an existing Quiz model.
     * If update is successful, the browser will be redirected to the 'update' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $quiz = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            if ($quiz->load(Yii::$app->request->post()) && $quiz->save()) {
                Yii::$app->session->setFlash('success', 'Тест обновлён');
                return $this->redirect(['update', 'id' => $quiz->id]);
            } else $quiz->moveErrorsToFlash();
        }
        $question = new Question();
        $question->quiz_id = $quiz->id;
        return $this->render('update', [
            'newQuestion' => $question,
            'quiz' => $quiz,
            'subjects' => $this->getSubjectsForSelect(),
        ]);
    }

    /**
     * Deletes an existing Quiz model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $transaction = Quiz::getDb()->beginTransaction();
        if ($this->findModel($id)->delete()) {
            $transaction->commit();
        } else $transaction->rollBack();

        return $this->redirect(['index']);
    }

    /**
     * @return string
     */
    public function actionPage()
    {
        $prefix = 'quiz_';
        $webpage = null;
        $moduleId = Module::getModuleIdByControllerAndAction('quiz', 'list');
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
                        $quizId = str_replace($prefix, '', $data[$i - 1]);
                        $quiz = $this->findModel($quizId);
                        $quiz->page_order = $i;
                        $quiz->save(true, ['page_order']);
                    }
                }
                Yii::$app->session->addFlash('success', 'Изменения сохранены');
                return $this->redirect(['page']);
            }
        }

        return $this->render('page', [
            'webpage' => $webpage,
            'quizes' => Quiz::find()->with('subject')->orderBy('page_order')->all(),
            'prefix' => $prefix,
        ]);
    }

    public function actionAddQuestion()
    {
        $question = new Question();
        $transaction = Question::getDb()->beginTransaction();
        try {
            if ($question->load(Yii::$app->request->post())) {
                $maxOrder = Question::find()->where(['quiz_id' => $question->quiz_id, 'parent_id' => null])->max('sort_order');
                $question->sort_order = $maxOrder + 1;
                if ($question->save() && $this->saveAnswers($question)) {
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Вопрос успешно добавлен');
                    return $this->redirect(['update', 'id' => $question->quiz_id]);
                } else $transaction->rollBack();
            }
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        $question->moveErrorsToFlash();
        return $this->render('update', [
            'newQuestion' => $question,
            'quiz' => $question->quiz,
            'subjects' => $this->getSubjectsForSelect(),
        ]);
    }

    /**
     * @param int $questionId
     * @return string|Response
     * @throws \Exception
     * @throws yii\db\Exception
     */
    public function actionUpdateQuestion($questionId) {
        $question = Question::findOne($questionId);
        if ($question != null && Yii::$app->request->isPost) {
            $transaction = Question::getDb()->beginTransaction();
            try {
                if ($question->load(Yii::$app->request->post()) && $question->save() && $this->saveAnswers($question)) {
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Вопрос обновлён');
                    return $this->redirect(['update', 'id' => $question->quiz_id]);
                } else $transaction->rollBack();
                $question->moveErrorsToFlash();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return $this->render('update', [
            'editQuestion' => $question,
            'quiz' => $question->quiz,
            'subjects' => $this->getSubjectsForSelect(),
        ]);
    }

    /**
     * @param Question $question
     * @return bool
     */
    private function saveAnswers($question)
    {
        $rightAnswerText = Yii::$app->request->post('rightAnswer');
        $wrongAnswers = Yii::$app->request->post('wrongAnswers');
        if (!$rightAnswerText) Yii::$app->session->setFlash('error', 'Укажите правильный вариант');
        elseif (!$wrongAnswers) Yii::$app->session->setFlash('error', 'Укажите неправильные варианты');
        else {
            if ($question->rightAnswer) $rightAnswer = $question->rightAnswer;
            else $rightAnswer = new Question();
            $rightAnswer->content = $rightAnswerText;
            $rightAnswer->is_right = 1;
            $rightAnswer->quiz_id = $question->quiz_id;
            $rightAnswer->parent_id = $question->id;
            if ($rightAnswer->save()) {
                if ($question->wrongAnswers) {
                    foreach ($question->wrongAnswers as $oldAnswer) $oldAnswer->delete();
                }
                foreach ($wrongAnswers as $answer) {
                    $wrongAnswer = new Question();
                    $wrongAnswer->content = $answer;
                    $wrongAnswer->is_right = 0;
                    $wrongAnswer->quiz_id = $question->quiz_id;
                    $wrongAnswer->parent_id = $question->id;
                    if (!$wrongAnswer->save()) {
                        $wrongAnswer->moveErrorsToFlash();
                        return false;
                    }
                }
                return true;
            } else $rightAnswer->moveErrorsToFlash();
        }
        return false;
    }

    /**
     * @param int $questionId
     * @return string|Response
     */
    public function actionGetEditQuestionForm($questionId) {
        if (Yii::$app->request->isAjax) {
            $question = Question::findOne($questionId);
            if ($question != null) {
                \yii\base\Event::on(\yii\web\View::class, \yii\web\View::EVENT_AFTER_RENDER, function ($e) {
                    $response = [
                        'content' => $e->output,
                    ];
                    $first = true;
                    foreach ($e->sender->js[\yii\web\View::POS_READY] as $key => $script) {
                        if ($first) $response['script'] = $script;
                        $first = false;
                    }
                    $e->output = json_encode($response);
                });
                return $this->renderPartial('question_form', [
                    'question' => $question,
                    'config' => [
                        'action' => yii\helpers\Url::to(['update-question', 'questionId' => $question->id]),
                        'enableClientValidation' => false,
                    ],
                ]);
            }
        }
        return '';
    }
    
    public function actionDeleteQuestion() {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $questionId = Yii::$app->request->post('question_id');
            if ($questionId) {
                /** @var Question $question */
                $question = Question::findOne($questionId);
                if ($question) {
                    $transaction = Question::getDb()->beginTransaction();
                    if (!$question->delete()) {
                        $transaction->rollBack();
                        $jsonData = self::getJsonErrorResult($question->getErrorsAsString());
                    } else {
                        $transaction->commit();
                        $jsonData = self::getJsonOkResult();
                    }
                } else $jsonData = self::getJsonErrorResult('Question is not found');
            } else $jsonData = self::getJsonErrorResult('Wrong request');
        }
        return $this->asJson($jsonData);
    }

    /**
     * @param int $quizId
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionQuestionReorder($quizId) {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            if ($quizId) {
                $quiz = $this->findModel($quizId);
                if ($quiz) {
                    $questionsMap = Question::getListAsMap($quiz->questions);
                    $orderData = Yii::$app->request->post('ordered_data');
                    $errors = '';
                    foreach ($orderData as $order => $element) {
                        if (isset($element['id']) && $element['id'] && isset($questionsMap[$element['id']])) {
                            /** @var Question $question */
                            $question = $questionsMap[$element['id']];
                            $question->sort_order = $order;
                            if (!$question->save(true, ['sort_order'])) $errors .= $question->getErrorsAsString() . ' ';
                        }
                    }
                    $jsonData = $errors ? self::getJsonErrorResult(trim($errors)) : self::getJsonOkResult();
                }
            }
        }
        return $this->asJson($jsonData);
    }

    /**
     * Finds the Quiz model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Quiz the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Quiz::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    private function getSubjectsForSelect()
    {
        return Subject::find()->where(['active' => Subject::STATUS_ACTIVE])->orderBy(['category_id' => SORT_ASC, 'name' => SORT_ASC])->with('subjectCategory')->all();
    }
}
