App.ApplicationController = Ember.Controller.extend({
	contactModal: false,

	currentUser: function() {
		return App.AuthManager.get('data');
	}.property('App.AuthManager.data'),

	isAnonymous: function() {
		return App.AuthManager.isAnonymous();
	}.property('App.AuthManager.data'),

	isLogged: function () {
		return App.AuthManager.isLogged();
	}.property('App.AuthManager.data'),

	actions: {
		openContactModal: function() {
			this.set('contactModal', true);
			$("body").animate({
				scrollTop: 0
			}, 400);
		},
		closeContactModal: function() {
			this.set('contactModal', false);
		}
	}
});

App.ConceptsController = Ember.Controller.extend({
	queryParams: ['invalid', 'interruption'],
	invalid: null,
	interruption: null
});

App.UserLoginController = Ember.Controller.extend({
	facebookLoginLink: App.External.FACEBOOK_LOGIN_URL,
	googleLoginLink: App.External.GOOGLE_LOGIN_URL,

	isProcessing: false,
	timeout: 5000,
	logged: false,
	errors: [],
	result: null,
	actions: {
		login: function (formData) {
			this.set('isProcessing', true);
			setTimeout(this.processingTimeout, this.get('timeout'), this);
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
	},
	processingTimeout: function(self) {
		self.set('isProcessing', false);
		self.set('errors', ['Processing failed. Please check your internet connection and try again.']);
		self.set('result', null);
	}
});

App.UserRegisterController = Ember.Controller.extend({
	facebookLoginLink: App.External.FACEBOOK_LOGIN_URL,
	googleLoginLink: App.External.GOOGLE_LOGIN_URL,

	isProcessing: false,
	timeout: 5000,
	errors: [],
	logged: false,
	actions: {
		register: function (formData) {
			this.set('isProcessing', true);
			setTimeout(this.processingTimeout, this.get('timeout'), this);
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
	},
	processingTimeout: function(self) {
		self.set('isProcessing', false);
		self.set('errors', ['Processing failed. Please check your internet connection and try again.']);
		self.set('result', null);
	}
});

App.PlayController = Ember.Controller.extend({
	id_concept: null,
	roundIdentification: null,

	questionMaxCount: App.Play.DEFAULT_COUNT,	// Total number of quiz questions
	questionCurrent: 1, // Starting from 1

	progressValue: function() { return this.questionCurrent - 1;}.property('this.questionCurrent'),
	progressMax: function() { return this.questionMaxCount;}.property('this.questionMaxCount'),
	question: function() { return this.get('model').questions[0]; }.property('this.model'), // Workaround for some weird #with macro problems
	isChooseRepresentationQuestion: function () {
		var model = this.get('model');
		if (model != null && model.hasOwnProperty('questions')) {
			return model.questions[0].type == 1;
		}
		return false;
	}.property('this.model'),
	isChooseNameQuestion: function () {
		var model = this.get('model');
		if (model != null && model.hasOwnProperty('questions')) {
			return model.questions[0].type == 2;
		}
		return false;
	}.property('this.model'),

	answered: false,	// True means that answering of current question was finished (correct was selected)
	markedAnswers: [],	// Answers marked (selected) by the user

	isProcessing: false,

	loadNextQuestion: function() {
		this.set('isProcessing', true);
		var self = this;
		var request = App.Play.getQuestions(this.id_concept, this.questionMaxCount - this.questionCurrent + 1); // +1 as the questionCurrent is already incremented
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

App.FacebookLoginProblemController = Ember.Controller.extend({
	queryParams: ['type'],
	type: null,
	isTheirFailure: function() { return this.type == 1; }.property("this.type"),
	isUnknownFailure: function() { return this.type == 2; }.property("this.type"),
	isRegistrationFailure: function() { return this.type == 3; }.property("this.type"),
	isUnavailability: function() { return this.type == 4; }.property("this.type")
});

App.GoogleLoginProblemController = Ember.Controller.extend({
	queryParams: ['type'],
	type: null,
	isTheirFailure: function() { return this.type == 1; }.property("this.type"),
	isUnknownFailure: function() { return this.type == 2; }.property("this.type"),
	isRegistrationFailure: function() { return this.type == 3; }.property("this.type"),
	isUnavailability: function() { return this.type == 4; }.property("this.type")
});