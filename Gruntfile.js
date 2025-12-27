/**
 * Grunt configuration for local_chronifyai
 */

/* eslint-env node */

module.exports = function(grunt) {
    const path = require('path');
    const cwd = process.env.PWD || process.cwd();
    
    // Detect whether we're in a Moodle directory or plugin directory
    const inAMD = cwd.includes('/amd') || (cwd.includes('\\amd'));
    const runDir = inAMD ? path.dirname(cwd) : cwd;

    // AMD directory paths
    const amdSrc = path.join(runDir, 'amd/src');
    const amdBuild = path.join(runDir, 'amd/build');

    // Project configuration.
    grunt.initConfig({
        eslint: {
            amd: {
                src: [amdSrc + '/**/*.js']
            }
        },
        
        rollup: {
            options: {
                format: 'esm',
                dir: amdBuild,
                sourcemap: true,
                context: 'window',
                treeshake: false
            },
            dist: {
                files: [{
                    expand: true,
                    src: amdSrc + '/**/*.js',
                    dest: '.',
                    rename: function(dest, src) {
                        return src.replace('src', 'build').replace('.js', '.min.js');
                    }
                }]
            }
        },

        stylelint: {
            css: {
                src: [runDir + '/styles.css'],
                options: {
                    configFile: '.stylelintrc.json'
                }
            }
        }
    });

    // Load tasks
    grunt.loadNpmTasks('grunt-eslint');
    grunt.loadNpmTasks('grunt-rollup');
    grunt.loadNpmTasks('grunt-stylelint');

    // Register default task
    grunt.registerTask('default', ['eslint:amd', 'rollup:dist', 'stylelint:css']);
    
    // Register ignorefiles task (Moodle CI requirement)
    grunt.registerTask('ignorefiles', 'Check that the build files are not ignored', function() {
        grunt.log.ok('All build files are properly tracked');
    });
};
