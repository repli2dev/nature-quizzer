var UserData = Ember.Object.extend({
	status: '',
	anonymous: null,
	id: null,
	name: ''
});

var AuthManager = Ember.Object.extend({

	init: function() {
		this.flushData();
		this.refreshProfile();
	},

	flushData: function () {
		this.set('data', null);
	},

	refreshProfile: function () {
		var self = this;
		var request = $.ajax({
			type: "POST",
			url: App.User.PROFILE_URL
		});
		request.then(function (response) {
			var data = UserData.create();
			data.setProperties(response);
			self.set('data', data);
		});

	},

	login: function (data, callback) {
		var self = this;
		// Login on backend
		var request = Ember.$.ajax({
			type: "POST",
			url: App.User.LOGIN_URL,
			data: data
		});
		request.then(function (response) {
			self.flushData();
			self.refreshProfile();
			if (typeof callback !== 'undefined') {
				callback(response);
			}
		});
	},

	register: function(data, callback) {
		var self = this;
		var request = $.ajax({
			type: "POST",
			url: App.User.REGISTER_URL,
			data: data
		});
		request.then(function (response) {
			self.flushData();
			self.refreshProfile();
			if (typeof callback !== 'undefined') {
				callback(response);
			}
		});
	},

	logout: function() {
		var self = this;
		// Logout on backend
		var request = $.ajax({
			type: "POST",
			url: App.User.LOGOUT_URL
		});
		request.then(function (response) {
			// Reset stored data
			self.flushData();
			self.refreshProfile();
		});

	},
	isLogged: function () { // Returns bool whether user is logged on the backend
		var data = this.get('data');
		return data !== null && data.status == 'success';
	},
	isAnonymous: function () { // Returns bool whether user is in anonymous role
		var data = this.get('data');
		if (data !== null && data.status == 'success' && data.anonymous == true) {
			return true;
		}
		return false;
	}
});

App = Ember.Application.create({
	LOG_TRANSITIONS: true
});

App.User = {};
App.User.LOGIN_URL = '/api/user-login';
App.User.LOGOUT_URL = '/api/user-logout';
App.User.PROFILE_URL = '/api/user-profile';
App.User.REGISTER_URL = '/api/user-register';
App.Concept = {};
App.Concept.ALL_URL = '/api/concepts';
App.Concept.URL = '/api/concept?conceptId=__CONCEPT__';

App.Concept.getAll = function() {
	return $.getJSON(App.Concept.ALL_URL).then(function (data) {
		return data.groups;
	});
};

App.Concept.get = function(conceptId) {
	var url = App.Concept.URL;
	url = url.replace('__CONCEPT__', conceptId);
	return $.getJSON(url).then(function (data) {
		return data;
	});
};


App.Play = {};
App.Play.DEFAULT_COUNT = 10;
App.Play.URL = '/api/questions/?conceptId=__CONCEPT__&count=__COUNT__';
App.Play.ANSWER_URL = '/api/answer/';

App.Play.getQuestions = function(conceptId, count) {
	if (typeof count === 'undefined') {
		count = App.Play.DEFAULT_COUNT;
	}
	var url = App.Play.URL;
	url = url.replace('__CONCEPT__', conceptId).replace('__COUNT__', count);
	return $.getJSON(url).then(function (data) {
		return data;
	});
};
App.Play.answerQuestion = function(data) {
	$.ajax({
		type: "POST",
		url: App.Play.ANSWER_URL,
		data: data
	});
};

App.Router.map(function () {
	this.resource('about');
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
	}
});

App.IndexRoute = Ember.Route.extend({
	model: App.Concept.getAll
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
		controller.set('roundIdentification', App.Utils.getRoundIdentification());
		controller.set('model', model);
		controller.set('questionCurrent', 1);
		controller.set('questionMaxCount', model.count);
		if (typeof model.concept === 'undefined') {
			controller.set('id_concept', 'mix');
		} else {
			controller.set('id_concept', model.concept.id_concept);
		}
	}
});

App.ApplicationController = Ember.Controller.extend({
	currentUser: function() {
		return App.AuthManager.get('data');
	}.property('App.AuthManager.data'),

	isAnonymous: function() {
		return App.AuthManager.isAnonymous();
	}.property('App.AuthManager.data'),

	isLogged: function () {
		return App.AuthManager.isLogged();
	}.property('App.AuthManager.data')
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

App.RegisterFormComponent = Ember.Component.extend({
	actions: {
		submit: function() {
			this.sendAction('submit', {
				name: this.get('name'),
				email: this.get('email'),
				password: this.get('password'),
				password2: this.get('password2')
			});
		}
	}
});

App.LoginFormComponent = Ember.Component.extend({
	actions: {
		submit: function() {
			this.sendAction('submit', {
				email: this.get('email'),
				password: this.get('password')
			});
		}
	}
});

App.UserLoginController = Ember.ObjectController.extend({
	isProcessing: false,
	logged: false,
	errors: [],
	result: null,
	actions: {
		login: function (formData) {
			this.set('isProcessing', true);
			var data = {};
			data.email = formData.email;
			data.password = formData.password;

			var self = this;
			App.AuthManager.login(data, function (response) {
				self.set('isProcessing', false);
				var output;
				if (response.status == 'fail') {
					output = {
						logged: false,
						errors: response.errors,
						result: null
					};
					if (response.hasOwnProperty('result')) {
						output.result = response.result;
					}
				} else {
					output = {
						logged: true,
						errors: [],
						result: null
					};
				}
				self.setProperties(output);
			});
		}
	}
});

App.UserRegisterController = Ember.ObjectController.extend({
	isProcessing: false,
	errors: [],
	logged: false,
	actions: {
		register: function (formData) {
			this.set('isProcessing', true);
			var data = {};
			data.name = formData.name;
			data.email = formData.email;
			data.password = formData.password;
			data.password2 = formData.password2;

			var self = this;
			App.AuthManager.register(data, function(response) {
				self.set('isProcessing', false);
				var output;
				if (response.status == 'fail') {
					output = {
						logged: false,
						errors: response.errors
					};
				} else {
					output = {
						logged: true,
						errors: []
					};
				}
				self.setProperties(output);
			});
		}
	}
});

App.PlayView = Ember.View.extend({
	afterRenderEvent: function() {
		App.Timetracking.start('question');
	}
});

App.PlayController = Ember.ObjectController.extend({
	id_concept: null,
	roundIdentification: null,

	questionMaxCount: 10,	// Total number of quiz questions
	questionCurrent: 1, // Starting from 1

	progressValue: function() { return this.questionCurrent - 1;}.property('this.questionCurrent'),
	progressMax: function() { return this.questionMaxCount;}.property('this.questionMaxCount'),

	answered: false,	// True means that answering of current question was finished (correct was selected)
	markedAnswers: [],	// Answers marked (selected) by the user

	isProcessing: false,

	loadNextQuestion: function() {
		this.set('isProcessing', true);
		var self = this;
		var request = App.Play.getQuestions(this.id_concept, this.questionMaxCount - this.questionCurrent);
		request.then(function(data) {
			self.set('isProcessing', false);
			self.set('model', data);
			App.Timetracking.start('question');
		});
	},
	isLastQuestion: function () {
		return (this.questionCurrent == this.questionMaxCount);
	},
	isAnswered: function() {
		return (this.answered);
	},
	markAnswered: function() {
		this.set('answered', true);
	},
	reset: function() {
		this.set('answered', false);
		this.set('markedAnswers', []);
	},
	evaluateAnswerCorrectness: function(selectedValue) {
		var self = this;
		var model = self.get('model');
		var type = model.questions[0].type;
		var options = model.questions[0].options;
		var state = false;
		for (var i = 0; i < options.length; i++) {
			var option = options[i];
			if ((type == 1 && option.id_representation == selectedValue) ||
				(type == 2 && option.id_organism == selectedValue)) {
				state = option.correct;
			}
		}
		return state;
	},
	highlightAnswer: function(selectedValue, answerCorrect) {
		var oldClasses = $('#' + selectedValue).attr('class');
		var toAppend = (answerCorrect) ? 'correct' : 'wrong';
		$('#' + selectedValue).attr('class', oldClasses + ' ' + toAppend);
	},

	sendQuestionInfo: function(questionTime) {
		var self = this;
		var model = self.get('model');
		var type = model.questions[0].type;
		var options = model.questions[0].options;
		var answers = {};
		for (var i = 0; i < options.length; i++) {
			var option = options[i];
			if (type == 1) {
				answers[i] = {
					id_representation: option.id_representation,
					correct: option.correct,
					answered: (typeof this.markedAnswers[option.id_representation] !== 'undefined')
				};
			} else if (type == 2) {
				answers[i] = {
					id_organism: option.id_organism,
					correct: option.correct,
					answered: (typeof this.markedAnswers[option.id_organism] !== 'undefined')
				};
			}
		}
		var output = {};
		$.extend(output, App.Utils.getClientInfo());
		output.round = this.roundIdentification;
		output.questionType = type;
		output.answers = answers;
		output.time = questionTime;
		output.seqNum = this.questionCurrent;

		App.Play.answerQuestion(output);
	},

	actions: {
		answer: function (selectedValue) {
			// Check if the answering of this question was already finished
			// YES -> delegate to 'next' action
			// NO -> let user choose different answer
			if (this.isAnswered()) {
				this.send('next', selectedValue);
				return;
			}
			// Check if this answer was already answered (and do nothing if so)
			if (typeof this.markedAnswers[selectedValue] !== 'undefined') {
				return;
			}
			// Mark answer as answered and evaluate its correctness
			this.markedAnswers[selectedValue] = true;

			// Obtain correctness and mark it
			var answerCorrect = this.evaluateAnswerCorrectness(selectedValue);
			this.highlightAnswer(selectedValue, answerCorrect);

			if (answerCorrect) {
				var questionTime = App.Timetracking.end('question');
				this.sendQuestionInfo(questionTime);
				this.markAnswered();
			}
		},
		next: function (selectedValue) {
			// If this was last question we proceed to 'result' screen
			if (this.isLastQuestion()) {
				this.transitionToRoute('result', this.get('id_concept'));
				return;
			}
			// If user clicked on wrong answer do nothing
			var answerCorrect = this.evaluateAnswerCorrectness(selectedValue);
			if (!answerCorrect) {
				return;
			}
			// Otherwise load and prepare for the next question
			this.reset();
			this.set('questionCurrent', this.get('questionCurrent') + 1);
			this.loadNextQuestion();
			return;
		}
	}
});

Handlebars.registerHelper('isChooseRepresentationQuestion', function(options) {
	if(this.type == 1) {
		return options.fn(this);
	}
});

Handlebars.registerHelper('isChooseNameQuestion', function(options) {
	if(this.type == 2) {
		return options.fn(this);
	}
});

// TODO: check if used
Ember.View.reopen({
	didInsertElement : function(){
		this._super();
		Ember.run.scheduleOnce('afterRender', this, this.afterRenderEvent);
	},
	afterRenderEvent : function(){
		// implement this hook in your own subclasses and run your jQuery logic there
	}
});

// TODO: check if used
function toogleMenu() {
	var el = $("#top-bar");
	if (el.hasClass('top-bar-opened')) {
		el.removeClass('top-bar-opened');
	} else {
		el.addClass('top-bar-opened');
	}
}

/*** Timetracking support ***/

App.Timetracking = {};
App.Timetracking.timers = {};
App.Timetracking.start = function(timer) {
	if (typeof performance !== 'undefined' && typeof performance.now !== 'undefined') {
		this.timers[timer] = performance.now();
	}
	return null;
};
App.Timetracking.end = function(timer) {
	if (typeof performance !== 'undefined' && typeof performance.now !== 'undefined') {
		return performance.now() - this.timers[timer];
	}
	return null;
};

/*** Utility functions ***/

App.Utils = {};
App.Utils.getClientInfo = function getClientInfo()  {
	return {
		screenWidth: screen.width,
		screenHeight: screen.height,
		viewportWidth: document.documentElement.clientWidth,
		viewportHeight: document.documentElement.clientHeight
	};
};

App.Utils.getRoundIdentification = function getRoundIdentification() {
	return navigator.userAgent + ' ' + (new Date()).getTime() + ' ' + Math.random();
};