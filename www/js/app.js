App = Ember.Application.create({});

App.NatureQuizzer = {};
App.NatureQuizzer.User = {};
App.NatureQuizzer.User.LOGIN_URL = '/api/user-login';
App.NatureQuizzer.User.LOGOUT_URL = '/api/user-logout';
App.NatureQuizzer.User.PROFILE_URL = '/api/user-profile';
App.NatureQuizzer.User.REGISTER_URL = '/api/user-register';
App.NatureQuizzer.Concept = {};
App.NatureQuizzer.Concept.ALL_URL = '/api/concepts';
App.NatureQuizzer.Concept.URL = '/api/concept?conceptId=__CONCEPT__';

App.NatureQuizzer.Concept.getAll = function() {
	return $.getJSON(App.NatureQuizzer.Concept.ALL_URL).then(function (data) {
		return data.groups;
	});
};

App.NatureQuizzer.Concept.get = function(conceptId) {
	var url = App.NatureQuizzer.Concept.URL;
	url = url.replace('__CONCEPT__', conceptId);
	return $.getJSON(url).then(function (data) {
		return data;
	});
};


App.NatureQuizzer.Play = {};
App.NatureQuizzer.Play.DEFAULT_COUNT = 10;
App.NatureQuizzer.Play.URL = '/api/questions/?conceptId=__CONCEPT__&count=__COUNT__';
App.NatureQuizzer.Play.ANSWER_URL = '/api/answer/';

App.NatureQuizzer.Play.getQuestions = function(conceptId, count) {
	if (typeof count === 'undefined') {
		count = App.NatureQuizzer.Play.DEFAULT_COUNT;
	}
	var url = App.NatureQuizzer.Play.URL;
	url = url.replace('__CONCEPT__', conceptId).replace('__COUNT__', count);
	return $.getJSON(url).then(function (data) {
		return data;
	});
};
App.NatureQuizzer.Play.answerQuestion = function(data) {
	$.ajax({
		type: "POST",
		url: App.NatureQuizzer.Play.ANSWER_URL,
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

App.IndexRoute = Ember.Route.extend({
	model: App.NatureQuizzer.Concept.getAll
});

App.ResultRoute = Ember.Route.extend({
	model: function(params) {
		return App.NatureQuizzer.Concept.get(params.id_concept);
	}
});

App.PlayRoute = Ember.Route.extend({
	model: function (params) {
		return App.NatureQuizzer.Play.getQuestions(params.id_concept);
	},
	setupController: function (controller, model) {
		controller.set('roundIdentification', App.NatureQuizzer.Utils.getRoundIdentification());
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

// TODO: refactor out
var currentUserData = null;
function refreshCurrentUserData () {
	if (currentUserData !== null) {
		return;
	}
	var responseData;
	var request = $.ajax({
		type: "POST",
		async: false,
		url: App.NatureQuizzer.User.PROFILE_URL
	});
	request.then(function (response) {
		responseData = (response);
	});
	currentUserData = responseData;
}

App.UserinfoController = Ember.ObjectController.extend({
	logged: function () {
		refreshCurrentUserData();
		return currentUserData !== null && currentUserData.status == 'success' && currentUserData.anonymous == false;
	}.property(),
	notLogged: function () {
		refreshCurrentUserData();
		console.log(currentUserData);
		return currentUserData === null || currentUserData.status == 'fail' || currentUserData.anonymous == true;
	}.property(),
	name: function () {
		refreshCurrentUserData();
		if (currentUserData !== null) {
			return currentUserData.name;
		}
		return null;
	}.property(),

	refresh: function() {
		alert('aaa');
	}
});

App.UserLogoutRoute = Ember.Route.extend({
	setupController: function (controller, model) {
		var responseData;
		var request = $.ajax({
			type: "POST",
			async: false,
			url: App.NatureQuizzer.User.LOGOUT_URL
		});
		request.then(function (response) {
			responseData = (response);
		});
		currentUserData = null;
		this.transitionTo('index');
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
	needs: ['userinfo'],
	errors: [],
	hasErrors: false,
	notLogged: true,
	logged: false,
	actions: {
		login: function (formData) {
			data = {};
			data.email = formData.email;
			data.password = formData.password;
			var responseData;
			var request = $.ajax({
				type: "POST",
				async: false,
				url: App.NatureQuizzer.User.LOGIN_URL,
				data: data
			});
			request.then(function (response) {
					responseData = (response);
			});
			if (responseData.status == 'fail') {
				this.set('hasErrors', true);
				this.set('errors', responseData.errors);
				this.set('notLogged', true);
				this.set('logged', false);
			} else {
				this.set('logged', true);
				this.set('notLogged', false);
				this.set('errors', []);
				this.set('hasErrors', false);
			}
			currentUserData = null;
		}
	}
});

App.UserRegisterController = Ember.ObjectController.extend({
	errors: [],
	hasErrors: false,
	notLogged: true,
	logged: false,
	actions: {
		register: function (formData) {
			data = {};
			data.name = formData.name;
			data.email = formData.email;
			data.password = formData.password;
			data.password2 = formData.password2;
			var responseData;
			var request = $.ajax({
				type: "POST",
				async: false,
				url: App.NatureQuizzer.User.REGISTER_URL,
				data: data
			});
			request.then(function (response) {
				responseData = (response);
			});
			if (responseData.status == 'fail') {
				this.set('hasErrors', true);
				this.set('errors', responseData.errors);
				this.set('notLogged', true);
				this.set('logged', false);
			} else {
				this.set('logged', true);
				this.set('notLogged', false);
				this.set('errors', []);
				this.set('hasErrors', false);
			}
			currentUserData = null;
		}
	}
});

App.PlayView = Ember.View.extend({
	afterRenderEvent: function() {
		App.NatureQuizzer.Timetracking.start('question');
	}
});

App.PlayController = Ember.ObjectController.extend({
	id_concept: null,
	questionCurrent: 1,
	questionMaxCount: 10,
	answeredAnswers: [],
	answeringCompleted: false,
	roundIdentification: null,
	loadNextQuestion: function() {
		var self = this;
		var request = App.NatureQuizzer.Play.getQuestions(this.id_concept, this.questionMaxCount - this.questionCurrent);
		request.then(function(data) {
			self.set('model', data);
			App.NatureQuizzer.Timetracking.start('question');
		});
	},
	isLastQuestion: function () {
		return (this.questionCurrent == this.questionMaxCount);
	},
	isAnsweringQuestionFinished: function() {
		return (this.answeringCompleted);
	},
	finishAnsweringQuestion: function() {
		this.answeringCompleted = true;
	},
	resetAnsweringFinishedFlag: function() {
		this.answeringCompleted = false;
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
	markAnswer: function(selectedValue, answerCorrect) {
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
					answered: (typeof this.answeredAnswers[option.id_representation] !== 'undefined')
				};
			} else if (type == 2) {
				answers[i] = {
					id_organism: option.id_organism,
					correct: option.correct,
					answered: (typeof this.answeredAnswers[option.id_organism] !== 'undefined')
				};
			}
		}
		var output = {};
		$.extend(output, App.NatureQuizzer.Utils.getClientInfo());
		output.round = this.roundIdentification;
		output.questionType = type;
		output.answers = answers;
		output.time = questionTime;
		output.seqNum = this.questionCurrent;

		App.NatureQuizzer.Play.answerQuestion(output);
	},

	actions: {
		answer: function (selectedValue) {
			// Check if answering of this question was finished
			// YES -> reload to get new question / show finish page
			// NO -> let user choose different answer
			if (this.isAnsweringQuestionFinished()) {
				if (this.isLastQuestion()) {
					this.transitionToRoute('result', this.get('id_concept'));
					return;
				}
				this.resetAnsweringFinishedFlag();
				this.set('questionCurrent', this.get('questionCurrent') + 1);
				this.loadNextQuestion();
				return;
			}
			// Check if this answer was already answered (and do nothing if so)
			if (typeof this.answeredAnswers[selectedValue] != 'undefined') {
				return;
			}
			// Mark answer as answered and evaluate its correctness
			this.answeredAnswers[selectedValue] = true;

			// Obtain correctness and mark it
			var answerCorrect = this.evaluateAnswerCorrectness(selectedValue);
			this.markAnswer(selectedValue, answerCorrect);

			if (answerCorrect) {
				var questionTime = App.NatureQuizzer.Timetracking.end('question');
				this.sendQuestionInfo(questionTime);
				this.finishAnsweringQuestion();
			}
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

Ember.View.reopen({
	didInsertElement : function(){
		this._super();
		Ember.run.scheduleOnce('afterRender', this, this.afterRenderEvent);
	},
	afterRenderEvent : function(){
		// implement this hook in your own subclasses and run your jQuery logic there
	}
});

function toogleMenu() {
	var el = $("#top-bar");
	if (el.hasClass('top-bar-opened')) {
		el.removeClass('top-bar-opened');
	} else {
		el.addClass('top-bar-opened');
	}
}

App.NatureQuizzer.Utils = {};
App.NatureQuizzer.Timetracking = {};
App.NatureQuizzer.Timetracking.timers = {};
App.NatureQuizzer.Timetracking.start = function(timer) {
	if (typeof performance !== 'undefined' && typeof performance.now !== 'undefined') {
		this.timers[timer] = performance.now();
	}
	return null;
};
App.NatureQuizzer.Timetracking.end = function(timer) {
	if (typeof performance !== 'undefined' && typeof performance.now !== 'undefined') {
		return performance.now() - this.timers[timer];
	}
	return null;
};

App.NatureQuizzer.Utils.getClientInfo = function getClientInfo()  {
	return {
		screenWidth: screen.width,
		screenHeight: screen.height,
		viewportWidth: document.documentElement.clientWidth,
		viewportHeight: document.documentElement.clientHeight
	};
};

App.NatureQuizzer.Utils.getRoundIdentification = function getRoundIdentification() {
	return navigator.userAgent + ' ' + (new Date()).getTime() + ' ' + Math.random();
};