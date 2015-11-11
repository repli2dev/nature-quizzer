var UserData = Ember.Object.extend({
	status: '',
	anonymous: null,
	id: null,
	name: ''
});

var Feedback = Ember.Object.extend({
	send: function (data, callback) {
		var self = this;
		var request = Ember.$.ajax({
			type: "POST",
			url: App.Contact.FEEDBACK_URL,
			data: data
		});
		request.then(function (response) {
			if (typeof callback !== 'undefined') {
				callback(response);
			}
		});
	}
});

var Translator = Ember.Object.extend({
	data: {},
	langauge: null,

	translate: function (key) {
		var result = getNestedData(key, this.data);
		if (typeof result !== 'undefined') {
			return result;
		} else {
			console.warn('Missing translation: ' + key);
			return key;
		}
	},
	change: function(language, data) {
		this.set('language', language);
		this.set('data', data);
	}
});

var Titler = Ember.Object.extend({
	common: null,
	last: null,

	change: function(value) {
		if (this.get('common') === null) {
			this.set('common', value);
		} else {
			this.set('last', value);
		}
		var newTitle;
		if (this.get('common') && this.get('last')) {
			newTitle = this.get('last') + ' | ' + this.get('common');
		} else if (this.get('last')) {
			newTitle = this.get('last');
		} else {
			newTitle = this.get('common');
		}
		document.title = newTitle;
	}
});

var AuthManager = Ember.Object.extend({

	init: function () {
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