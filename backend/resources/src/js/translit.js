var Translit = {
    space: '-',
    allowedCharsRegExp: /[a-z0-9]/,
    maxUrlLength: 40,
    symbolTable: {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh',
        'з': 'z', 'и': 'i', 'й': 'j', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
        'о': 'o', 'п': 'p', 'р': 'r','с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h',
        'ц': 'c', 'ч': 'ch', 'ш': 'sh', 'щ': 'sh','ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
        ' ': this.space
    },
    url: function(str) {
        var result = "";
        str = str.toLowerCase();
        var wasSpace = true;
        var nextChar = "";
        for (var i = 0; i < str.length; i++) {
            if (this.symbolTable[str[i]] !== undefined) nextChar = this.symbolTable[str[i]];
            else if (this.allowedCharsRegExp.test(str[i])) nextChar = str[i];
            else nextChar = this.space;
            if (nextChar !== this.space || !wasSpace) {
                result += nextChar;
                wasSpace = (nextChar === this.space);
            }
        }
        if (result.length && result[result.length - 1] === this.space) result = result.substr(0, result.length - 1);
        return result;
    }
};