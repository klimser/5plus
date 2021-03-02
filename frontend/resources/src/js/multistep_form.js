let MultiStepForm = {
    currentStep: 1,
    addSubmitHandler: function(element) {
        $(element).on('keydown', event => {
            if (event.which === 13 && !MultiStepForm.validate()) {
                event.preventDefault();
            }
        });
    },
    init: function(form) {
        form.querySelectorAll('input, select').forEach(elem => { MultiStepForm.addSubmitHandler(elem); });
    },
    jumpTo: function(stepNum, form = document) {
        return MultiStepForm.jump(form.querySelector('.step-tab[data-step-order="' + stepNum + '"]'));
    },
    jump: function(targetLink) {
        if (!targetLink) {
            return false;
        }

        let stepNum = parseInt($(targetLink).data('stepOrder'));
        if (this.validateSteps(stepNum)) {
            this.currentStep = stepNum;
            $(targetLink).tab("show");
        }
        return false;
    },
    validateSteps: function(tillStepNum) {
        if (tillStepNum <= 0) {
            return false;
        }
        for (let i = 1; i < tillStepNum; i++) {
            let link = $('.step-tab[data-step-order=' + i + ']');
            if (!this.isStepValid($(link).attr('href'))) {
                $(link).removeClass('step-success').addClass('step-invalid');
                this.jump(link);
                return false;
            }
            $(link).removeClass('step-invalid').addClass('step-success');
        }
        return true;
    },
    isStepValid: function(container) {
        let basicValid = true;
        $(container).find("input, select, textarea").each((index, elem) => {
            if (!elem.reportValidity()) {
                basicValid = false;
            }
        });
        if (!basicValid) {
            return false;
        }

        let additionalValid = true;
        $(container).find("[data-multistep-validate]").each((index, block) => {
            $(block).data('multistepValidate').split(' ').forEach(validator => {
                switch (validator) {
                    case 'checkbox-list-required':
                        if ($(block).find("input[type=checkbox]:checked").length === 0) {
                            $(block).addClass('border-danger');
                            additionalValid = false;
                        } else {
                            $(block).removeClass('border-danger');
                        }
                        break;
                    case 'item-list-required':
                        let selector = $(block).data('multistepItemListSelector');
                        if (selector && $(block).find(selector).length === 0) {
                            additionalValid = false;
                        }
                        break;
                    default:
                        if (!Main.executeFunctionByName(validator, window, block)) {
                            $(block).addClass('border-danger');
                            additionalValid = false;
                        } else {
                            $(block).removeClass('border-danger');
                        }
                }
            });
        });
        return additionalValid;
    },
    validate: function() {
        let nextStep = $('.step-tab[data-step-order=' + (this.currentStep + 1) + ']');
        if (nextStep.length > 0) {
            return this.jump(nextStep);
        } else {
            return this.validateSteps(this.currentStep + 1);
        }
    },
};
