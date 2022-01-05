var gulp = require('gulp');
var htmlbars = require('gulp-htmlbars-compiler');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var clean = require('gulp-clean');
var flatten = require('gulp-flatten');
// Hardcoded way to the ember-template-compilator as the versions needs to match and there is no node module in the repository
var compiler = require('./frontend/js/externals/ember-template-compiler-2.0.0');


// ====== Configuration =======
var paths = {
	scriptsFrontend: [
		'frontend/js/externals/jquery-2.1.4.js',
		'frontend/js/externals/ember-2.0.0.js',
		'frontend/js/externals/ember-shortcuts.js',
		'frontend/js/utils.js',
		'frontend/js/app.js',
		'frontend/js/routes.js',
		'frontend/js/views.js',
		'frontend/js/controllers.js',
		'frontend/js/components.js',
		'frontend/js/netteForms.js',
		'frontend/js/overscroll.js'
	],
	scriptsBackend: [
		'frontend/js/externals/jquery-2.1.4.js',
		'frontend/js/nette.ajax.js',
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
gulp.task('clean', gulp.series(function () {
	return gulp.src(destination, {read: false})
		.pipe(clean());
}));


// ====== Stylesheet preparation =======
gulp.task('styles-backend', gulp.series(function() {
	return gulp.src(paths.stylesBackend, { "base" : "./frontend/css/" })
		.pipe(gulp.dest(destination));
}));
gulp.task('styles-frontend', gulp.series(function() {
	return gulp.src(paths.stylesFrontend)
		.pipe(concat('screen.css'))
		.pipe(gulp.dest(destination));
}));
gulp.task('styles', gulp.series('styles-backend', 'styles-frontend', (done) => {done()}));

// ====== Javascript =======
gulp.task('scripts-backend', gulp.series(function() {
	return gulp.src(paths.scriptsBackend, { "base" : "./frontend/js/" })
		.pipe(flatten({dirname: ''}))
		.pipe(gulp.dest(destination));
}));
gulp.task('scripts-frontend', gulp.series(function() {
	return gulp.src(paths.scriptsFrontend.concat(paths.locales))
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest(destination));
}));
gulp.task('scripts-frontend-mini', gulp.series(function() {
	return gulp.src(paths.scriptsFrontend.concat(paths.locales))
		.pipe(concat('scripts.js'))
		.pipe(uglify())
		.pipe(gulp.dest(destination));
}));
gulp.task('scripts-full', gulp.series('scripts-backend', 'scripts-frontend', (done) => {done()}));
gulp.task('scripts-mini', gulp.series('scripts-backend', 'scripts-frontend-mini', (done) => { done() })); // For now backend scripts are not minified

// ====== Handlebars templates =======
gulp.task('templates', gulp.series(function() {
	return gulp.src(paths.templates)
		.pipe(htmlbars({
				compiler: compiler // Required
				//pathHandler: function(filePath, separator) { } // Optional way how to change naming of templates
		}))
		.pipe(concat('templates.js'))
		.pipe(gulp.dest(destination));
}));
// ====== Other actions =======
gulp.task('watch', function() {
	gulp.watch(paths.stylesFrontend, gulp.series('styles-frontend', (done) => { done() }));
	gulp.watch(paths.stylesBackend, gulp.series('styles-backend', (done) => { done() }));

	gulp.watch(paths.scriptsFrontend, gulp.series('scripts-frontend', (done) => { done() }));
	gulp.watch(paths.scriptsBackend, gulp.series('scripts-backend', (done) => { done() }));

	gulp.watch(paths.locales, gulp.series('scripts-frontend', (done) => { done() }));

	gulp.watch(paths.templates, gulp.series('templates', (done) => { done() }));
});

gulp.task('default', gulp.series('styles', 'scripts-full', 'templates', (done) => { done(); }));
gulp.task('default-mini', gulp.series('styles', 'scripts-mini', 'templates', (done) => { done() }));

gulp.task('development', gulp.series('default', 'watch', (done) => { done() }));
gulp.task('production', gulp.series('default-mini', (done) => { done() }));
