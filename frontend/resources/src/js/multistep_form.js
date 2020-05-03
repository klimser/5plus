let MultiStepForm = {
    currentStep: 1,
    addSubmitHandler: function(element) {
        $(element).on('keydown', function(event) {
            if (event.which === 13 && !MultiStepForm.validate()) {
                event.preventDefault();
            }
        });
    },
    init: function(form) {
        $(form).find('input, select').each(function() {
            MultiStepForm.addSubmitHandler(this);
        });
    },
    jump: function(targetLink) {
        let stepNum = parseInt($(targetLink).data('stepOrder'));
        for (let i = 1; i < stepNum; i++) {
            let link = $('.step-tab[data-step-order=' + i + ']');
            if (!this.validateStep($(link).attr('href'))) {
                $(link).removeClass('step-success').addClass('step-invalid');
                return false;
            }
            $(link).removeClass('step-invalid').addClass('step-success');
        }
        this.currentStep = stepNum;
        $(targetLink).tab("show");
        return false;
    },
    validateStep: function(container) {
        let basicValid = true, additionalValid = true;
        $(container).find("input, select, textarea").each(function() {
            if (!this.reportValidity()) {
                basicValid = false;
            }
        });
        if (!basicValid) {
            return false;
        }
        
        $(container).find("[data-multistep-validate]").each(function() {
            switch ($(this).data('multistepValidate')) {
                case 'checkbox-list-required':
                    if ($(this).find("input[type=checkbox]:checked").length === 0) {
                        $(this).addClass('border-danger');
                        additionalValid = false;
                    } else {
                        $(this).removeClass('border-danger');
                    }
                    break;
            }
        });
        return additionalValid;
    },
    validate: function() {
        if (this.validateStep($('.step-tab[data-step-order=' + this.currentStep + ']'))) {
            let nextStep = $('.step-tab[data-step-order=' + (this.currentStep + 1) + ']');
            if (nextStep.length > 0) {
                return this.jump(nextStep);
            } else {
                return true;
            }
        }
    },
    
};
