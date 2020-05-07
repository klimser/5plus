var path = require('path');

module.exports = function(grunt) {
    grunt.loadNpmTasks('grunt-contrib-sass');
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
};
