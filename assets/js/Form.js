import ConditionalControl from "./ConditionalControl.js";
import ConditionalCheckbox from "./ConditionalCheckbox.js";
import ConditionalRadio from "./ConditionalRadio.js";
import ConditionalHidden from "./ConditionalHidden.js";

function Form(el) {
	this.el = el;
	this.state = {};
	this.conditions = {};

	// Instantiante controls
	this.controls = Array.from(this.el.querySelectorAll(".wpcf7-form-control"))
		.map((el) => Form.getControl(el))
		.filter((control) => control);

	for (const control of this.controls) {
		// Store control conditions to the global conditions registry
		Object.keys(control.conditions).forEach(
			(field) => (this.conditions[field] = true)
		);

		control.on("show", ({ detail }) => {
			if (detail.value) {
				// Subscrive visible values
				this.setStateField(control);
			} else {
				// Unsubscrive hidden values
				delete detail.state[control.fieldName];
			}
		});

		// Bind control to state
		this.setStateField(control);

		// Propagate visibility updates on control change acros form
		control.on("change", () => this.updateVisibility(control));
	}

	this.el.addEventListener("wpcf7reset", (ev) => this.reset(ev));

	// Initial visibility update
	this.updateVisibility();
}

Form.getControl = function (el) {
	let type = el.getAttribute("type");
	if (!type) {
		if (el.classList.contains("wpcf7-checkbox")) {
			type = "checkbox";
		} else if (el.classList.contains("wpcf7-radio")) {
			type = "radio";
		} else if (el.classList.contains("wpcf7-acceptance")) {
			type = "acceptance";
		}
	}

	let meta = el.parentElement.nextElementSibling;
	switch (type) {
		case "submit":
		case "acceptance":
			return null;
		case "hidden":
			meta = el.nextElementSibling;
			return new ConditionalHidden(el, meta);
		case "checkbox":
			return new ConditionalCheckbox(el, meta);
		case "radio":
			return new ConditionalRadio(el, meta);
		default:
			return new ConditionalControl(el, meta);
	}
};

Form.prototype.setStateField = function (control) {
	Object.defineProperty(this.state, control.fieldName, {
		enumerable: true,
		configurable: true,
		get() {
			return control.value;
		},
		set(value) {
			if (control.value != value) {
				control.value = value;
			}
		},
	});
};

Form.prototype.updateVisibility = function (control) {
	if (control && !this.conditions[control.fieldName]) return;

	for (const control of this.controls) {
		if (!control.conditional) continue;
		control.updateVisibility(this.state);
	}
};

Form.prototype.getState = function () {
	return this.controls.reduce((acum, control) => {
		const exists = Object.prototype.hasOwnProperty.call(
			this.state,
			control.fieldName
		);

		if (control.visible && exists) {
			acum[control.fieldName] = control.value;
		}

		return acum;
	}, {});
};

Form.prototype.reset = function ({ detail }) {
	this.controls.forEach(
		(control) => (control.value = control.defaultValue || "")
	);
	setTimeout(() => {
		this.updateVisibility();
		window.scrollTo({
			top: Math.max(0, this.el.offsetTop - 20),
			behavior: "smooth",
		});
	}, 500);
};

export default Form;
