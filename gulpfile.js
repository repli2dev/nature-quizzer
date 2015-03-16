var gulp = require('gulp');
var htmlbars = require('gulp-htmlbars-compiler');
var wrap = require('gulp-wrap');
var declare = require('gulp-declare');
var concat = require('gulp-concat');
var clean = require('gulp-clean');
// Hardcoded way to the ember-template-compilator as the versions needs to match
var compiler = require('./node_modules/gulp-htmlbars/bower_components/ember/ember-template-compiler');


// ====== Configuration =======
var paths = {
	scripts: [
		'frontend/js/externals/jquery-1.11.1.js',
		'frontend/js/externals/ember-1.10.0.js',
		'frontend/js/utils.js',
		'frontend/js/app.js',
		'frontend/js/routes.js',
		'frontend/js/views.js',
		'frontend/js/controllers.js',
		'frontend/js/components.js',
		'frontend/js/netteForms.js'
	],
	stylesFrontend: [
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
	return gulp.src(paths.scripts)
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest(destination));
});

// ====== Handlebars templates =======
gulp.task('templates', function(){
	gulp.src(paths.templates)
		.pipe(handlebars())
		.pipe(wrap('Handlebars.template(<%= contents %>)'))
		.pipe(declare({
			namespace: 'App.templates',
			noRedeclare: true
		}))
		.pipe(concat('templates.js'))
		.pipe(gulp.dest(destination));
});

/*gulp.task('templates', function(){
	gulp.src(paths.templates)
		.pipe(handlebars({
			handlebars: require('ember-handlebars')
		}))
		.pipe(wrap('Ember.Handlebars.template(<%= contents %>)'))
		.pipe(declare({
			namespace: 'Ember.TEMPLATES',
			amd: false,
			noRedeclare: true
		}))
		.pipe(concat('templates.js'))
		.pipe(gulp.dest(destination));
});*/
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
	gulp.watch(paths.templates, ['templates']);
});

gulp.task('default', ['styles', 'scripts', 'templates']);
