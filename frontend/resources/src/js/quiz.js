let QuizList = {
    data: new Map(),
    subject: null,
    quiz: null,
    setSubject: function(e) {
        this.subject = $(e).data("subject");
        this.quiz = null;
        // $("#selected-subject").text(this.data.get(this.subject).name);
        let quizList = '';
        this.data.get(this.subject).quizzes.forEach(function(value, key) {
            quizList += '<a href="#" data-quiz="' + key + '" class="list-group-item" onclick="return QuizList.setQuiz(this);">' + value.name  + '</a>';
        });
        $("#quiz-list").html(quizList);
        MultiStepForm.jump($("#step-2-tab"));
        return false;
    },
    setQuiz: function(e) {
        this.quiz = $(e).data("quiz");
        $('input[name="quiz_id"]').val(this.quiz);

        $("#selected-quiz").text(this.data.get(this.subject).quizzes.get(this.quiz).name);
        $("#question-count").text(this.data.get(this.subject).quizzes.get(this.quiz).questionCount);
        MultiStepForm.jump($("#step-3-tab"));
        return false;
    },
    jump: function(button) {
        $(".step-content").addClass("hidden");
        $("#" + $(button).data("href")).removeClass("hidden");
    },
    startAllowed: function(form) {
        let gToken = grecaptcha.getResponse();
        return gToken.length !== 0;
        
    }
};
let Quiz = {
    questionList: [],
    answerList: [],
    timeLeft: 0,
    loadAttempts: 0,
    currentQuestion: -1,
    timerId: 0,
    answerUrl: '',
    completeUrl: '',
    errorCount: 0,
    tickTimer: function() {
        if (this.timeLeft > 0) this.timeLeft--;
        else {window.clearInterval(this.timerId); $("#complete_button").click();}
        let minutes = Math.floor(this.timeLeft / 60);
        let seconds = this.timeLeft % 60;
        $("#time_left").text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds).removeClass("hidden");
        if (minutes <= 2) $("#time_left").addClass("bg-danger");
    },
    loadQuiz: function () {
        if (this.questionList.length === 0 || this.questionList.length !== this.answerList.length) {
            if (this.loadAttempts >= 5) $("#question_content").text("Произошла ошибка. Тест не удаётся загрузить. Мы очень сожалеем о случившемся и постараемся исправить ситуацию как можно скорее. Попробуйте обновить страницу или начать тест снова.");
            else {
                this.loadAttempts++;
                window.setTimeout(function(){Quiz.loadQuiz();}, 1000);
            }
        } else {
            let questionListHtml = '', currentQuestion = 0;
            this.questionList.forEach(function(item, key) {
                questionListHtml += '<a href="#" class="list-group-item';
                if (Quiz.answerList[key] >= 0) {
                    questionListHtml += ' list-group-item-success';
                    if (currentQuestion === key) currentQuestion++;
                }
                questionListHtml += '" data-question="' + key + '" onclick="Quiz.openQuestion(' + key + '); return false;">Вопрос №' + (key + 1) + '</a>';
            });
            $("#question_list").html(questionListHtml);
            this.openQuestion(currentQuestion);

            if (currentQuestion === this.questionList.length && !this.checkFinished()) this.openQuestion(this.questionList.length - 1);

            this.timerId = window.setInterval(function(){Quiz.tickTimer();}, 1000);
        }
    },
    openQuestion: function (questionNumber, forceReload) {
        if (questionNumber >= this.questionList.length) return false;
        if (questionNumber !== this.currentQuestion || forceReload) {
            this.currentQuestion = questionNumber;
            $("#question_content").html(this.questionList[questionNumber].question);
            let answersHtml = '';
            this.questionList[questionNumber].answers.forEach(function(answer, index) {
                answersHtml += '<div class="radio"><label><input type="radio" name="answer" value="' + index + '"'
                    + (Quiz.answerList[questionNumber] === index ? ' checked' : '') + '> '
                    + answer + '</label></div>';
            });
            $("#answer_list").html(answersHtml);
        }
        $("#question_content").removeClass("hidden");
        $("#answer_list").removeClass("hidden");
        $("#answer_button").removeClass("hidden");
        $("#question_list a").removeClass("active");
        $("#question_list").find("a:eq(" + questionNumber + ")").addClass("active");
    },
    saveAnswer: function () {
        if (this.currentQuestion >= 0 && $("#answer_list").find("input:checked").length) {
            this.answerList[this.currentQuestion] = parseInt($("#answer_list").find("input:checked").val());
            $("#answer_button").attr('disabled', true).text('Сохранение...');
            $.ajax({
                url: this.answerUrl,
                type: 'post',
                dataType: 'json',
                data: {answers: this.answerList},
                success: function(data) {
                    if (data.status === 'ok') {
                        $("#msg_place").text("").removeClass("alert").removeClass("alert-danger");
                        if (typeof(data.timeLeft) !== 'undefined') {
                            Quiz.timeLeft = data.timeLeft;
                        }
                        $("#question_list").find(".list-group-item:eq(" + Quiz.currentQuestion + ")").addClass("list-group-item-success");
                        Quiz.nextQuestion();
                    } else {
                        $("#msg_place").text(data.message).addClass("alert alert-danger");
                    }
                    $("#answer_button").removeAttr('disabled').text('Ответить');
                },
                error: function(jqXHRObject, textStatus, errorThrown) {
                    Quiz.errorCount++;
                    if (Quiz.errorCount >= 5) {
                        $("#msg_place").html("Не удалось сохранить результат. Проверьте подключание к интернету и повторите попытку позже<br>" + textStatus + ': ' + errorThrown);
                        $("#answer_button").removeAttr('disabled').text('Ответить');
                    } else {
                        $("#msg_place").html("Не удалось сохранить результат. Пробуем снова. Попытка " + (Quiz.errorCount + 1) + "<br>" + textStatus + ': ' + errorThrown)
                            .addClass("alert alert-danger");
                        window.setTimeout(function(){Quiz.saveAnswer();}, 1000);
                    }
                }
            });
        }
    },
    nextQuestion: function() {
        if (!this.checkFinished()) {
            while (true) {
                this.currentQuestion++;
                if (this.currentQuestion === this.questionList.length) this.currentQuestion = 0;
                if (this.answerList[this.currentQuestion] < 0) {
                    this.openQuestion(this.currentQuestion, true);
                    break;
                }
            }
        }
    },
    checkFinished: function() {
        let isFinished = this.answerList.every(function(item) {return item >= 0;});
        if (isFinished) {
            $("#complete_button").removeClass("hidden");
            $("#question_content").addClass("hidden");
            $("#answer_list").addClass("hidden");
            $("#answer_button").addClass("hidden");
            return true;
        }
        return false;
    },
    complete: function() {
        $("#complete_button").attr('disabled', true).text('Сохранение...');
        $.ajax({
            url: this.completeUrl,
            type: 'post',
            dataType: 'json',
            data: {},
            success: function(data) {
                if (data.status === 'ok') {
                    $("#msg_place").text("").removeClass("alert").removeClass("alert-danger");
                    let blockHtml = '<div class="col-xs-12"><div class="alert alert-success">' + "\n"
                        + 'Тест завершён.<br>' + "\n"
                        + 'Ваш результат - <b>' + data.right_answers + ' правильных из ' + data.total_answers + '</b>.<br>' + "\n"
                        + 'Приходите в <a href="/contacts">наш офис</a>, назовите ваше имя <b>' + data.student_name + '</b> и мы подберём вам подходящую группу по результатам теста.' + "\n"
                        + '</div></div>';
                    $("#quiz_area").html(blockHtml);
                } else {
                    $("#msg_place").text(data.message).addClass("alert alert-danger");
                }
                $("#complete_button").removeAttr('disabled').text('Завершить тест');
            },
            error: function(jqXHRObject, textStatus, errorThrown) {
                Quiz.errorCount++;
                if (Quiz.errorCount >= 5) {
                    $("#msg_place").html("Не удалось завершить тест. Проверьте подключение к интернету и повторите попытку позже<br>" + textStatus + ': ' + errorThrown);
                    $("#complete_button").removeAttr('disabled').text('Завершить тест');
                } else {
                    $("#msg_place").html("Не удалось завершить тест. Пробуем снова. Попытка " + (Quiz.errorCount + 1) + "<br>" + textStatus + ': ' + errorThrown)
                        .addClass("alert alert-danger");
                    window.setTimeout(function(){Quiz.complete();}, 1000);
                }
            }
        });
    }
};
