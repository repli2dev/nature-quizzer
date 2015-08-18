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
	errors: [],
	result: null,
	actions: {
		submit: function () {
			this.set('isProcessing', true);
			var data = {};
			data.email = this.get('email');
			data.text = this.get('text');

			var self = this;
			App.Feedback.send(data, function (response) {
				self.set('isProcessing', false);
				var output;
				if (response.status == 'fail') {
					output = {
						errors: response.errors,
						result: null
					};
				} else {
					output = {
						errors: [],
						result: response.result
					};
					// Reset data
					self.set('text', '');
					self.set('email', '');
				}
				self.setProperties(output);
			});
		}
	}
});