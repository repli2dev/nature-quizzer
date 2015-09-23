App.Router.map(function () {
	this.route('about');
	this.route('facebook-login-problem');
	this.route('google-login-problem');
	this.route('play', { path: '/play/:id_concept' });
	this.route('result', { path: '/result/:id_concept' });
	this.route('concepts');
	this.route('user', function () {
		this.route('login');
		this.route('logout');
		this.route('register');
	});

});

App.ApplicationRoute = Ember.Route.extend({
	init: function() {
		this._super();
		// Instantiate authentication manager to handle user login/logout management
		App.AuthManager = AuthManager.create();
		App.Feedback = Feedback.create();
		// Prepare translator, helpers and load language
		App.Translator = Translator.create();
		App.TranslateHelper = Ember.Helper.helper(function (params, hash) {
			return App.Translator.translate(params[0]);
		});
		App.Translator.change(App.Languages.default, App.Translations[App.Languages.default]);
	},
	actions: {
		openContactModal: function (value) {
			this.controller.set('contactModal', true);
			if (typeof value !== 'undefined') {
				this.controller.set('contactContent', value);
			}
			$("body").animate({
				scrollTop: 0
			}, 400);
		},
		closeContactModal: function () {
			this.controller.set('contactModal', false);
			this.controller.set('contactContent', '');
		},
		changeLanguage: function (language) {
			// TODO: finish switching languages and redrawing
		}
	}
});
App.IndexRoute = Ember.Route.extend({
	model: App.Concept.getQuick
});

App.ConceptsRoute = Ember.Route.extend({
	model: App.Concept.getAll,
	resetController: function (controller, isExiting, transition) {
		if (isExiting) {
			controller.set('invalid', null);
			controller.set('interruption', null);
		}
	}
});

App.ResultRoute = Ember.Route.extend({
	model: function(params) {
		return App.Concept.get(params.id_concept);
	}
});

App.PlayRoute = Ember.Route.extend({
	shortcutHandler: null,
	controller: null,
	model: function (params) {
		return App.Play.getQuestions(params.id_concept);
	},
	setupController: function (controller, model) {
		this.set('shortcutHandler', controller.shortcutTriggered);
		this.set('controller', controller);
		// Check if the request went OK and that there are some data at all
		if (typeof model === "undefined" || model === null || model.hasOwnProperty('error') || (model.hasOwnProperty('questions') && model.questions.length == 0)) {
			controller.transitionToRoute('concepts', {queryParams: {invalid: true, interruption: null}});
			return;
		}

		controller.set('roundIdentification', App.Utils.getRoundIdentification());
		controller.set('model', model);
		controller.set('failureDetected', false);
		controller.set('questionCurrent', 1);
		controller.set('answered', false);
		controller.set('questionMaxCount', model.count);
		if (typeof model.concept === 'undefined') {
			controller.set('id_concept', 'mix');
		} else {
			controller.set('id_concept', model.concept.id_concept);
		}
	},
	shortcuts: {
		'num1': 'handleShortcut',
		'num2': 'handleShortcut',
		'num3': 'handleShortcut',
		'num4': 'handleShortcut',
		'enter': 'handleShortcut',
		'escape': 'handleShortcut',
	},
	actions: {
		handleShortcut: function (event) {
			var handler = this.shortcutHandler;
			var controller = this.controller;
			if (handler && controller) {
				handler(event, controller);
			}
		}
	}
});

App.UserLoginRoute = Ember.Route.extend({
	setupController: function (controller, model) {
		// Check if the user is already logged in
		if (!App.AuthManager.isAnonymous()) {
			controller.transitionToRoute('index');
		} else {
			controller.setProperties({
				logged: false,
				errors: [],
				result: null
			});
		}
	}
});

App.UserRegisterRoute = Ember.Route.extend({
	setupController: function (controller, model) {
		// Check if the user is already logged in
		if (!App.AuthManager.isAnonymous()) {
			controller.transitionToRoute('index');
		} else {
			controller.setProperties({
				logged: false,
				errors: []
			});
		}
	}
});

App.UserLogoutRoute = Ember.Route.extend({
	setupController: function (controller, model) {
		// Check if the user is already logged in
		if (!App.AuthManager.isAnonymous()) {
			App.AuthManager.logout();
		} else {
			controller.transitionToRoute('index');
		}
	}
});