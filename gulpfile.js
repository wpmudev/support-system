var gulp = require('gulp'),
	bower = require('gulp-bower'),
	del = require( 'del' ),
	uglify = require('gulp-uglify'),
	rename = require('gulp-rename'),
	compass = require('gulp-compass'),
	clean = require('gulp-clean');


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




function bower_update() {
	return bower({ cmd: 'update'});
}

function move_foundation_elements_to_assets() {
	gulp.src('bower_components/foundation/js/foundation.js')
		.pipe( gulp.dest( 'assets/js' ) );

	gulp.src('bower_components/foundation/js/foundation.min.js')
		.pipe( gulp.dest( 'assets/js' ) );

	gulp.src( 'bower_components/foundation-icons/svgs/fi-plus.svg' )
		.pipe( gulp.dest( 'assets/images' ) );

	gulp.src( 'bower_components/foundation-icons/svgs/fi-minus.svg' )
		.pipe( gulp.dest( 'assets/images' ) );
}


/**
 * Install
 */
gulp.task('install', function() {
	// Update dependencies with Bower
	console.log( "Updating Bower packages...");
	bower_update();
	console.log( "Moving Foundation files to assets...");
	move_foundation_elements_to_assets();
	console.log( "Uglifying JS...");
	support_uglify();
	console.log("Executing Compass...");
	the_compass();
});

/**
 * Release a new version
 */
gulp.task('release', function() {
	the_compass('release');
	support_uglify();
});