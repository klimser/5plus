let Translit = {
    space: '-',
    allowedCharsRegExp: /[a-z0-9]/,
    maxUrlLength: 40,
    symbolTable: new Map([
        ['а', 'a'], ['б', 'b'], ['в', 'v'], ['г', 'g'], ['д', 'd'], ['е', 'e'], ['ё', 'e'], ['ж', 'zh'],
        ['з', 'z'], ['и', 'i'], ['й', 'j'], ['к', 'k'], ['л', 'l'], ['м', 'm'], ['н', 'n'],
        ['о', 'o'], ['п', 'p'], ['р', 'r'], ['с', 's'], ['т', 't'], ['у', 'u'], ['ф', 'f'], ['х', 'h'],
        ['ц', 'c'], ['ч', 'ch'], ['ш', 'sh'], ['щ', 'sh'], ['ъ', ''], ['ы', 'y'], ['ь', ''], ['э', 'e'], ['ю', 'yu'], ['я', 'ya']
    ]),
    url: function(str) {
        let result = "",
            wasSpace = true,
            nextChar = "";
        str = str.toLowerCase();
        for (let i = 0; i < str.length; i++) {
            if (this.symbolTable.has(str[i])) nextChar = this.symbolTable.get(str[i]);
            else if (this.allowedCharsRegExp.test(str[i])) nextChar = str[i];
            else nextChar = this.space;
            if (nextChar !== this.space || !wasSpace) {
                result += nextChar;
                wasSpace = (nextChar === this.space);
            }
            if (result.length >= this.maxUrlLength) break;
        }
        if (result.length && result[result.length - 1] === this.space) result = result.substr(0, result.length - 1);
        return result;
    }
};