var gulp = require('gulp'),
	bower = require('gulp-bower'),
	del = require( 'del' ),
	uglify = require('gulp-uglify'),
	rename = require('gulp-rename'),
	compass = require('gulp-compass');

gulp.task('default', function() {});

/**
 * Executes compass to generate the CSS files
 */
function the_compass( mode ) {
	var file;
	if ( mode ) {
		file = './config-' + mode + '.rb';
	}
	else {
		file = './config.rb';
	}

	del(['assets/css/incsub-support.css']);

	return gulp.src('./assets/scss/*.scss')
		.pipe(compass({
		    config_file: file,
		    css: 'assets/css',
		    sass: 'assets/scss'
		}));
}

function support_uglify() {
	gulp.src('assets/js/support-system.js')
    	.pipe( uglify() )
    	.pipe(rename({suffix: '.min'}))
    	.pipe( gulp.dest('assets/js') );

}


gulp.task( 'watch', function() {
	console.log("Processing the file");
	the_compass('release');
	gulp.watch('./assets/scss/*.scss', ['compass']);
		
});


/**
 * Install
 */
gulp.task('install', function() {
	// Update dependencies with Bower
	return bower({ cmd: 'update'});
});

/**
 * Init the plugin. Execute right after installation
 */
gulp.task( 'init', function() {
	// Get Foundation Javascript
	gulp.src('bower_components/foundation/js/foundation.js')
		.pipe( gulp.dest( 'assets/js' ) );

	gulp.src('bower_components/foundation/js/foundation.min.js')
		.pipe( gulp.dest( 'assets/js' ) );

	gulp.src( 'bower_components/foundation-icons/svgs/fi-plus.svg' )
		.pipe( gulp.dest( 'assets/images' ) );

	gulp.src( 'bower_components/foundation-icons/svgs/fi-minus.svg' )
		.pipe( gulp.dest( 'assets/images' ) );

	support_uglify();

	the_compass();
});


/**
 * Release a new version
 */
gulp.task('release', function() {
	the_compass('release');
	support_uglify();
});