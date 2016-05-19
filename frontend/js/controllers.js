App.ApplicationController = Ember.Controller.extend({
	contactModal: false,
	contactContent: '',

	currentUser: function() {
		return App.AuthManager.get('data');
	}.property('App.AuthManager.data'),

	isAnonymous: function() {
		return App.AuthManager.isAnonymous();
	}.property('App.AuthManager.data'),

	isLogged: function () {
		return App.AuthManager.isLogged();
	}.property('App.AuthManager.data'),

	/* For actions see ApplicationRoute */
});

App.ResultController = Ember.Controller.extend({
	facebookLoginLink: App.External.FACEBOOK_LOGIN_URL,
	googleLoginLink: App.External.GOOGLE_LOGIN_URL,

	questions: null,
	successRate: function() {
		var questions = this.get('questions');
		if (questions != null && questions.hasOwnProperty('count') && questions.hasOwnProperty('success_rate') && questions.success_rate > 0) {
			return Math.round((questions.success_rate / questions.count)*100);
		}
		return 0;
	}.property('this.questions'),
	strongBarWidth: function () {
		var questions = this.get('questions');
		if (questions != null && questions.hasOwnProperty('statistics') && questions.statistics.hasOwnProperty('strong') && questions.statistics.hasOwnProperty('available')) {
			return Math.round((questions.statistics.strong / questions.statistics.available)*100);
		}
		return 0;
	}.property('this.questions'),

	weakBarWidth: function () {
		var questions = this.get('questions');
		if (questions != null && questions.hasOwnProperty('statistics') && questions.statistics.hasOwnProperty('answered') && questions.statistics.hasOwnProperty('strong') && questions.statistics.hasOwnProperty('available')) {
			return Math.round(((questions.statistics.answered - questions.statistics.strong) / questions.statistics.available)*100);
		}
		return 0;
	}.property('this.questions'),

	isAnonymous: function () {
		return App.AuthManager.isAnonymous();
	}.property('App.AuthManager.data'),
	actions: {
		closeWrongImage: function (item) {
			$('#quiz-item-overlay-' + item).each(function (index, element) {
				element.innerHTML = '';
			});
		},
		showWrongImage: function (item, image) {
			$('#quiz-item-overlay-' + item).each(function (index, element) {
				if (element.innerHTML == image) {
					element.innerHTML = null;
				} else {
					element.innerHTML = image;
				}
			});
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
		if (!self.get('isProcessing')) {
			return;
		}
		self.set('isProcessing', false);
		self.set('errors', [App.Translator.translate('processing_timeout')]);
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
		if (!self.get('isProcessing')) {
			return;
		}
		self.set('isProcessing', false);
		self.set('errors', [App.Translator.translate('processing_timeout')]);
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
	incorrectOption: null, // User triggered incorrect option to be shown side by side with the original picture
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
	failureDetected: false,

	reportMessage: function() {
		var message = App.Translator.translate('quiz.report_description') + ': ';
		var question = this.get('question');
		if (typeof question === 'undefined') {
			return '';
		}
		if (this.get('isChooseRepresentationQuestion')) {
			message += '' + question.questionText + ' ' + App.Translator.translate('quiz.report_representations') + ' [';
			question.options.forEach(function(el) {
				message += el.id_representation + ' '
			});
			message += ']';
		} else if (this.get('isChooseNameQuestion')) {
			message += '' + question.id_representation + ' ' + App.Translator.translate('quiz.report_organisms') + ' [';
			question.options.forEach(function(el) {
				message += el.id_organism + ' '
			});
			message += ']\n\n';
		}
		message += '\n' + App.Translator.translate('quiz.report_comment') + ':\n';
		return message;
	}.property('this.question'),

	shortcutTriggered: function (event, controller) {
		if (event.keyCode == 27) { // Escape
			controller.send('close');
		} else if (event.keyCode == 97 || event.keyCode == 49 || event.keyCode == 187) { // Num 1 (and +)
			controller.send('answerByKeyboard', 1);
		} else  if (event.keyCode == 98 || event.keyCode == 50) { // Num 2
			controller.send('answerByKeyboard', 2);
		} else  if (event.keyCode == 99 || event.keyCode == 51) { // Num 3
			controller.send('answerByKeyboard', 3);
		} else  if (event.keyCode == 100 || event.keyCode == 52) { // Num 4
			controller.send('answerByKeyboard', 4);
		} else if (event.keyCode == 13) { // Enter
			if (controller.isAnswered()) {
				controller.send('next');
			}
		}
	},

	loadNextQuestion: function() {
		this.set('isProcessing', true);
		var self = this;
		var request = App.Play.getQuestions(this.id_concept, this.questionMaxCount - this.questionCurrent + 1); // +1 as the questionCurrent is already incremented
		request.then(function(data) {
			self.set('isProcessing', false);
			if (data.hasOwnProperty('deployment') || data.hasOwnProperty('error') || (data.hasOwnProperty('questions') && data.questions.length == 0)) {

				// Commented code is for fast quit, which is not so user friendly.
				//self.transitionToRoute('concepts', {queryParams: {invalid: true, interruption: null}});
				//return;

				// More UX way, use data from previous query
				data = self.get('model');
				data.count -= 1;
				data.questions.splice(0,1); // Remove first item and shift all question forward
			}
			self.set('model', null);
			self.set('model', data);
			App.Timetracking.start('question');
		});
		request.fail(function() {
			self.transitionToRoute('offline');
		});
	},
	isLastQuestion: function () {
		return (this.questionCurrent == this.questionMaxCount);
	},
	isAnswered: function() {
		return (this.answered);
	},
	isQuestionFinished: function() { // The same as isAnswered just binded to the variable for usage in template
		return (this.answered);
	}.property('this.answered'),
	markAnswered: function() {
		this.set('answered', true);
	},
	reset: function() {
		this.set('incorrectOption', null);
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
		var oldClasses = $('#a-' + selectedValue).attr('class');
		var toAppend = (answerCorrect) ? 'correct' : 'wrong';
		$('#a-' + selectedValue).attr('class', oldClasses + ' ' + toAppend);
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
				if (option.correct) {
					answers[i].id_representation = model.questions[0].id_representation;
				}
			}
		}
		var output = {};
		$.extend(output, App.Utils.getClientInfo());
		output.conceptId = this.id_concept;
		output.round = this.roundIdentification;
		output.questionType = type;
		output.answers = answers;
		output.time = questionTime;
		output.seqNum = this.questionCurrent;

		App.Play.answerQuestion(output, function() {
			self.set('failureDetected', true);
		});
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
		answerByKeyboard: function(index) {
			index = index - 1; // Array offset start from 0
			var options = this.model.questions[0].options;
			if (!(index in options)) {
				return;
			}
			var selectedOption = options[index];
			var value;
			if (selectedOption.hasOwnProperty('id_organism')) {
				value = selectedOption.id_organism;
			} else {
				value = selectedOption.id_representation;
			}
			this.send('answer', value);
		},
		next: function (selectedValue) {
			// If this was last question we proceed to 'result' screen
			if (this.isLastQuestion()) {
				this.transitionToRoute('result', this.get('id_concept'));
				return;
			}
			if (typeof selectedValue !== 'undefined') {
				// If user clicked on wrong answer do nothing
				var answerCorrect = this.evaluateAnswerCorrectness(selectedValue);
				if (!answerCorrect) {
					return;
				}
			}
			// Otherwise load and prepare for the next question
			this.reset();
			this.set('questionCurrent', this.get('questionCurrent') + 1);
			this.loadNextQuestion();
			return;
		},
		close: function () {
			if (this.get('questionCurrent') == 1 && !this.get('answered')) {
				this.transitionToRoute('concepts', {queryParams: {interruption: 1, invalid: "null"}});
			} else {
				this.transitionToRoute('result', this.get('id_concept'));
			}
			this.reset();

		},
		showHint: function (index) {
			var options = this.model.questions[0].options;
			if (!(index in options)) {
				return;
			}
			var selectedOption = options[index];
			var previousInccorectOption = this.get('incorrectOption');
			if (previousInccorectOption === selectedOption) {
				selectedOption = null;
			}
			this.set('incorrectOption', selectedOption);
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