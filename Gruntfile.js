module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            options: {
                compress: true
            },
            main: {
                files: {
                    'static/css/main.css': [
                        'assets/less/main.less',
                    ]
                }
            }
        },
        uglify: {
            main: {
                files: {
                    'app/Model/Bookmarklet/template.js': [
                        'assets/bookmarklet-dev/source.js',
                    ],
                    'static/js/main.js': [
                        'assets/js/main.js',
                    ]
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    grunt.loadNpmTasks('grunt-contrib-less');

    // Default task(s).
    grunt.registerTask('default', ['less', 'uglify']);

};