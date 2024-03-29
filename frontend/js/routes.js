App.Router.reopen({
	location: 'auto',
	notifyGoogleAnalytics: function() {
	/*	return plausible('pageview', {props: {
			'page_title': this.get('url'),
			'page_location': this.get('url')
		}});*/
	}.on('didTransition')
});

// Do not forget to update HomepagePresenter too!
App.Router.map(function () {
	this.route('about');
	this.route('terms');
	this.route('offline');
	this.route('facebook-login-problem');
	this.route('google-login-problem');
	this.route('play', { path: '/play/:id_concept/:code_name' });
	this.route('view', { path: '/view/:id_concept/:code_name' });
	this.route('result', { path: '/result/:id_concept' });
	this.route('concepts');
	this.route('news');
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
		// Prepare Titler for composing titles
		App.Titler = Titler.create();
		App.TitleHelper = Ember.Helper.helper(function (params, hash) {
			App.Titler.change(App.Translator.translate(params[0]));
		});
		App.Translator.change(App.Languages.default, App.Translations[App.Languages.default]);
		// Prepare increment helper
		App.IncrementHelper = Ember.Helper.helper(function (params) {
			return params[0] + 1
		});
	},
	actions: {
		openContactModal: function (value) {
			this.controller.shortcuts.disable();
			this.controller.set('contactModal', true);
			if (typeof value !== 'undefined') {
				this.controller.set('contactContent', value);
			}
			$("html").animate({
				scrollTop: 0
			}, 400);
		},
		closeContactModal: function () {
			this.controller.set('contactModal', false);
			this.controller.set('contactContent', '');
			this.controller.shortcuts.enable();
		},
		changeLanguage: function (language) {
			// TODO: finish switching languages and redrawing
		}
	}
});
App.IndexRoute = Ember.Route.extend({
	model: App.Concept.getQuick,
	actions: {
		error: function () {
			this.transitionTo('offline');
		}
	}
});

App.ConceptsRoute = Ember.Route.extend({
	model: App.Concept.getAll,
	resetController: function (controller, isExiting, transition) {
		if (isExiting) {
			controller.set('invalid', null);
			controller.set('interruption', null);
		}
	},
	actions: {
		error: function () {
			this.transitionTo('offline');
		}
	}
});

App.ResultRoute = Ember.Route.extend({
	model: function(params) {
		return App.Concept.get(params.id_concept);
	},
	setupController: function(controller, model) {
		controller.set('model', model);
		var request = App.RoundSummary.getSummary();
		request.then(function(data) {
			controller.set('questions', data);
		});
		request.fail(function() {
			self.transitionToRoute('offline');
		});
	},
	actions: {
		error: function () {
			this.transitionTo('offline');
		}
	}
});

App.ViewRoute = Ember.Route.extend({
	model: function (params) {
		return App.Concept.getDetail(params.id_concept);
	}
});

App.PlayRoute = Ember.Route.extend({
	shortcutHandler: null,
	controller: null,
	model: function (params) {
		return App.Play.getQuestions(params.id_concept);
	},
	setupController: function (controller, model) {
		if (typeof(Storage) === "undefined" || localStorage.getItem('terms') !== "true") {
			var state = confirm(App.Translator.translate('terms_confirm'));
			if (!state) {
				controller.transitionToRoute('concepts', {queryParams: {invalid: null, interruption: null}});
				return;
			}
			if (typeof(Storage) !== "undefined") {
				localStorage.setItem('terms', true);
			}
		}
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
		App.Timetracking.start('question');
	},
	shortcuts: {
		'plus': 'handleShortcut', // (Chrome) + (= 1) on czech keyboard
		'num1': 'handleShortcut',
		'num1t': 'handleShortcut',
		'shift+num1t': 'handleShortcut',
		'num2': 'handleShortcut',
		'num2t': 'handleShortcut',
		'shift+num2t': 'handleShortcut',
		'num3': 'handleShortcut',
		'num3t': 'handleShortcut',
		'shift+num3t': 'handleShortcut',
		'num4': 'handleShortcut',
		'num4t': 'handleShortcut',
		'shift+num4t': 'handleShortcut',
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
		},
		error: function () {
			this.transitionTo('offline');
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

App.AboutRoute = Ember.Route.extend(App.ResetScroll, {
	activate: function() {
		this._super.apply(this, arguments);
	}
});

App.TermsRoute = Ember.Route.extend(App.ResetScroll, {
	activate: function() {
		this._super.apply(this, arguments);
	}
});

App.NewsRoute = Ember.Route.extend(App.ResetScroll, {
	activate: function() {
		this._super.apply(this, arguments);
	}
});
