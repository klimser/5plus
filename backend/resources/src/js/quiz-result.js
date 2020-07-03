var QuizResult = {
    questionList: [],
    answerList: [],
    currentQuestion: -1,
    loadQuiz: function () {
        if (this.questionList.length === 0 || this.questionList.length !== this.answerList.length) {
            if (this.loadAttempts >= 5) $("#question_content").text("Произошла ошибка. Тест не удаётся загрузить. Мы очень сожалеем о случившемся и постараемся исправить ситуацию как можно скорее. Попробуйте обновить страницу или начать тест снова.");
            else {
                this.loadAttempts++;
                window.setTimeout(function(){QuizResult.loadQuiz();}, 1000);
            }
        } else {
            var questionListHtml = '';
            for (var i = 0; i < this.questionList.length; i++) {
                questionListHtml += '<a href="#" class="list-group-item';
                var additionalClass = '';
                if (this.answerList[i] >= 0) {
                    additionalClass = ' list-group-item-danger';
                    for (var j = 0; j < this.questionList[i].answers.length; j++) {
                        if (this.questionList[i].answers[j][1] === 1 && this.answerList[i] === j) {additionalClass = ' list-group-item-success'; break;}
                    }
                }
                questionListHtml += additionalClass + '" data-question="' + i + '" onclick="QuizResult.openQuestion(' + i + '); return false;">' +
                    'Вопрос №' + (i + 1) + '</a>';
            }
            $("#question_list").html(questionListHtml);
            this.openQuestion(0);
        }
    },
    openQuestion: function (questionNumber) {
        if (questionNumber >= this.questionList.length) return false;
        if (questionNumber !== this.currentQuestion) {
            this.currentQuestion = questionNumber;
            $("#question_content").html(this.questionList[questionNumber].question);
            var answersHtml = '';
            for (var i = 0; i < this.questionList[questionNumber].answers.length; i++) {
                answersHtml += '<li class="list-group-item' +
                    (this.questionList[questionNumber].answers[i][1] === 1 ? ' list-group-item-success' :
                        (this.answerList[questionNumber] === i ? ' list-group-item-danger' : ''))
                    + '">' + this.questionList[questionNumber].answers[i][0] + '</li>';
            }
            $("#answer_list").html(answersHtml);
        }
        $("#answer_list").collapse("show");
        $("#question_list a").removeClass("active");
        $("#question_list").find("a:eq(" + questionNumber + ")").addClass("active");
    }
};
