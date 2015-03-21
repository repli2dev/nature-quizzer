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
	actions: {
		submit: function() {
			var self = this;
			this.sendAction('submit', {
				text: this.get('text'),
				email: this.get('email')
			}, function () {
				self.set('text', '');
				self.set('email', '');
			});
		}
	}
});