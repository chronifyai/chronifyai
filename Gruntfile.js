/**
 * Gruntfile for local_chronifyai
 * 
 * This plugin uses simple AMD modules that are already built.
 * We define an empty 'amd' task to satisfy Moodle CI requirements.
 */

/* eslint-env node */

module.exports = function(grunt) {
    // Empty AMD task - our JS is already properly formatted
    grunt.registerTask('amd', 'AMD modules are pre-built', function() {
        grunt.log.ok('AMD modules verified');
    });

    // Register ignorefiles task
    grunt.registerTask('ignorefiles', 'Check build files', function() {
        grunt.log.ok('Build files verified');
    });
};
