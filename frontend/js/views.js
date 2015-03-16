App.PlayView = Ember.View.extend({
	afterRenderEvent: function() {
		App.Timetracking.start('question');
	}
});