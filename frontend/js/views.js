App.PlayView = Ember.Component.extend({
	// FIXME: missing method ?
	afterRenderEvent: function() {
		App.Timetracking.start('question');
	}
});