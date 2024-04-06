for (const form of document.querySelectorAll(".wpcf7 > form")) {
	let _wpcft = form.wpcf7,
		_schema = form.wpcf7?.schema;
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

	form.querySelectorAll(".wpcf7-files").forEach((control) => {
		control.name = control.name + "[]";
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
