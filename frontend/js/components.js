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

App.ContactFormComponent = Ember.Component.extend({
	isProcessing: false,
	timeout: 5000,
	errors: [],
	result: null,
	actions: {
		submit: function () {
			this.set('isProcessing', true);
			setTimeout(this.processingTimeout, this.get('timeout'), this);
			var data = {};
			data.email = this.get('email');
			data.text = this.get('text');

			var self = this;
			App.Feedback.send(data, function (response) {
				self.set('isProcessing', false);
				var output;
				if (response.status == 'success') {
					output = {
						errors: [],
						result: response.result
					};
					// Reset data
					self.set('text', '');
				} else {
					output = {
						errors: response.errors,
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