App.Router.map(function () {
	this.resource('about');
	this.resource('contact');
	this.resource('facebook-login-problem');
	this.resource('google-login-problem');
	this.resource('play', { path: '/play/:id_concept' });
	this.resource('result', { path: '/result/:id_concept' });
	this.resource('concepts');
	this.resource('user', function () {
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
	}
});
App.IndexRoute = Ember.Route.extend({
	model: App.Concept.getQuick
});

App.ContactRoute = Ember.Route.extend({
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
	model: function (params) {
		return App.Play.getQuestions(params.id_concept);
	},
	setupController: function (controller, model) {
		// Check that on initial route there are any data
		if (model.questions.length == 0) {
			controller.transitionToRoute('concepts', {queryParams: {invalid: true, interruption: null}});
			return;
		}
		controller.set('roundIdentification', App.Utils.getRoundIdentification());
		controller.set('model', model);
		controller.set('questionCurrent', 1);
		controller.set('answered', false);
		controller.set('questionMaxCount', model.count);
		if (typeof model.concept === 'undefined') {
			controller.set('id_concept', 'mix');
		} else {
			controller.set('id_concept', model.concept.id_concept);
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