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