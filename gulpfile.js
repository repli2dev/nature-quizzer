var gulp = require('gulp');
var htmlbars = require('gulp-htmlbars-compiler');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var clean = require('gulp-clean');
// Hardcoded way to the ember-template-compilator as the versions needs to match and there is no node module in the repository
var compiler = require('./frontend/js/externals/ember-template-compiler-2.0.0');


// ====== Configuration =======
var paths = {
	scripts: [
		'frontend/js/externals/jquery-2.1.4.js',
		'frontend/js/externals/ember-2.0.0.js',
		'frontend/js/externals/ember-shortcuts.js',
		'frontend/js/utils.js',
		'frontend/js/app.js',
		'frontend/js/routes.js',
		'frontend/js/views.js',
		'frontend/js/controllers.js',
		'frontend/js/components.js',
		'frontend/js/netteForms.js'
	],
	locales: [
		'frontend/locales/**/*.js'
	],
	stylesFrontend: [
		'frontend/css/animations.css',
		'frontend/css/style.css',
		'frontend/css/forms.css',
		'frontend/css/responsive.css'
	],
	stylesBackend: [
		'frontend/css/admin/**'
	],
	templates: [
		'frontend/templates/**/*.hbs'
	]

};

var destination = 'www/src/';


// ====== General tasks =======
gulp.task('clean', function () {
	return gulp.src(destination, {read: false})
		.pipe(clean());
});


// ====== Stylesheet preparation =======
gulp.task('styles-backend', function() {
	return gulp.src(paths.stylesBackend, { "base" : "./frontend/css/" })
		.pipe(gulp.dest(destination));
});
gulp.task('styles-frontend', function() {
	return gulp.src(paths.stylesFrontend)
		.pipe(concat('screen.css'))
		.pipe(gulp.dest(destination));
});
gulp.task('styles', ['styles-backend', 'styles-frontend']);

// ====== Javascript =======
gulp.task('scripts', function() {
	return gulp.src(paths.scripts.concat(paths.locales))
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest(destination));
});
gulp.task('mini-scripts', function() {
	return gulp.src(paths.scripts.concat(paths.locales))
		.pipe(concat('scripts.js'))
		.pipe(uglify())
		.pipe(gulp.dest(destination));
});

// ====== Handlebars templates =======
gulp.task('templates', function() {
	return gulp.src(paths.templates)
		.pipe(htmlbars({
				compiler: compiler // Required
				//pathHandler: function(filePath, separator) { } // Optional way how to change naming of templates
		}))
		.pipe(concat('templates.js'))
		.pipe(gulp.dest(destination));
});
// ====== Other actions =======
gulp.task('watch', function() {
	gulp.watch(paths.stylesFrontend, ['styles-frontend']);
	gulp.watch(paths.stylesBackend, ['styles-backend']);

	gulp.watch(paths.scripts, ['scripts']);
	gulp.watch(paths.locales, ['scripts']);
	gulp.watch(paths.templates, ['templates']);
});

gulp.task('default', ['styles', 'scripts', 'templates']);
gulp.task('default-mini', ['styles', 'mini-scripts', 'templates']);

gulp.task('development', ['default', 'watch']);
gulp.task('production', ['default']);
