var path = require('path');

module.exports = function(grunt) {
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-assets-versioning');
    grunt.loadNpmTasks('grunt-babel');

    require('load-grunt-config')(grunt, {
        configPath: path.join(process.cwd(), 'grunt'),
        jitGrunt: true
    });

    // YAML cannot call require(), so the Sass implementation is injected here.
    ['dev_back', 'prod_back', 'dev_front', 'prod_front'].forEach(function (target) {
        grunt.config.set('sass.' + target + '.options.implementation', require('sass'));
    });
};
