var gulp = require('gulp'),
	bower = require('gulp-bower'),
	compass = require('gulp-compass');

gulp.task('default', function() {});

/**
 * Executes compass to generate the CSS files
 */
function the_compass( mode ) {
	if ( mode )
		var file = './config-' + mode + '.rb';
	else
		var file = './config.rb';
	
	return gulp.src('./assets/scss/*.scss')
		.pipe(compass({
		    config_file: file,
		    css: 'assets/css',
		    sass: 'assets/scss'
		}));	
}
gulp.task('compass', function() {
  return the_compass();
});

gulp.task( 'watch', function() {
	console.log("Processing the file");
	the_compass();
	gulp.watch('./assets/scss/*.scss', ['compass']);
		
});


gulp.task('install', function() {
  bower({ cmd: 'update'});
  the_compass();
});

gulp.task('release', function() {
	the_compass('release');
});