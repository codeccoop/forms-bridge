for (const form of document.querySelectorAll(".wpcf7 > form")) {
	let _wpcft, _schema;
	Object.defineProperty(form, "wpcf7", {
		get: () => _wpcft,
		set: (val) => {
			_wpcft = val;
			Object.defineProperty(_wpcft, "schema", {
				get: () => _schema,
				set: (val) => {
					_schema = val;
				},
			});
		},
	});
}

document.addEventListener("DOMContentLoaded", () => {
	function conditionalValidator(a) {
		const validator = window.swv.validators[this.condition];
		if (validator) {
			validator.call(this, a);
		}
	}

	window.swv.validators.conditional = conditionalValidator;
	window.swv.validators.conditionalfile = conditionalValidator;
});
