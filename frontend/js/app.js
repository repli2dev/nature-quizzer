App = Ember.Application.create({
	LOG_TRANSITIONS: true
});

App.External = {};
App.External.FACEBOOK_LOGIN_URL = '/external/fb';
App.External.GOOGLE_LOGIN_URL = '/external/google';

App.User = {};
App.User.LOGIN_URL = '/api/user-login';
App.User.LOGOUT_URL = '/api/user-logout';
App.User.PROFILE_URL = '/api/user-profile';
App.User.REGISTER_URL = '/api/user-register';
App.Contact = {};
App.Contact.FEEDBACK_URL = '/api/feedback';
App.Concept = {};
App.Concept.ALL_URL = '/api/concepts';
App.Concept.QUICK_URL = '/api/quick';
App.Concept.URL = '/api/concept?conceptId=__CONCEPT__';

App.Concept.getAll = function() {
	return $.getJSON(App.Concept.ALL_URL).then(function (data) {
		return data.groups;
	});
};

App.Concept.getQuick = function() {
	return $.getJSON(App.Concept.QUICK_URL).then(function (data) {
		return data;
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
App.Play.answerQuestion = function(data, failureCallback) {
	$.ajax({
		type: "POST",
		url: App.Play.ANSWER_URL,
		data: data
	}).fail(failureCallback);
};

App.Menu = {};
App.Menu.toogle = function () {
	var el = $("#top-bar");
	if (el.hasClass('top-bar-opened')) {
		el.removeClass('top-bar-opened');
	} else {
		el.addClass('top-bar-opened');
	}
};

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

/*** Language support ***/

App.Languages = {};
App.Languages.available = {
	'cs': {
		'name':'Czech',
		'local_name': 'Čeština',
	}
};
App.Languages.default = 'cs';

/*** Common helpers ***/

/** Adapted from: http://stackoverflow.com/questions/2061325/javascript-object-key-value-coding-dynamically-setting-a-nested-value */
function getNestedData(key, obj) {
	if (!obj) obj = data; //outside (non-recursive) call, use "data" as our base object
	var ka = key.split(/\./); //split the key by the dots
	if (ka.length < 2) {
		return obj[ka[0]]; //only one part (no dots) in key, just set value
	} else {
		if (!obj[ka[0]]) obj[ka[0]] = {}; //create our "new" base obj if it doesn't exist
		obj = obj[ka.shift()]; //remove the new "base" obj from string array, and hold actual object for recursive call
		return getNestedData(ka.join("."), obj); //join the remaining parts back up with dots, and recursively set data on our new "base" obj
	}
}