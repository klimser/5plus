var Webpage = {
    urlSelector: null,
    titleSelector: null,
    suggestUrl: function(elem) {
        if (this.urlSelector && $(elem).val().length === 0 && $(this.urlSelector).length) {
            $(elem).val(Translit.url($(this.urlSelector).val()));
        }
    },
    fillTitle: function(elem) {
        if (this.titleSelector && $(elem).val().length === 0 && $(this.titleSelector).length) {
            $(elem).val($(this.titleSelector).val());
        }
    }
};