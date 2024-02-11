function ConditionalControl(el, meta) {
	this.el = el;
	this.type = el.getAttribute("type");
	this.fieldName = el.getAttribute("name");
	this.conditional =
		meta && meta.classList.contains("wpcf7-form-control-conditional");
	this.conditions = {};

	if (this.conditional) {
		this.conditions = meta.dataset.conditions
			.split("&")
			.reduce((acum, chunk) => {
				const [field, val] = chunk.split("=");
				acum[field] = val;
				return acum;
			}, {});
	}

	Object.defineProperty(this, "value", {
		get() {
			return this.el.value;
		},
		set(value) {
			this.el.value = value;
		},
	});

	if (this.el.value !== void 0) {
		Object.defineProperty(this, "defaultValue", {
			writable: false,
			configurable: false,
			enumerable: false,
			value: this.el.value,
		});
	}

	Object.defineProperty(this, "visible", {
		get() {
			return this.conditionalWrap.classList.contains("visible");
		},
	});

	this.prepareDom(meta);
}

ConditionalControl.prototype.prepareDom = function (meta) {
	this.controlWrap = this.el.parentElement;
	this.conditionalWrap = this.controlWrap.parentElement.parentElement;

	if (this.conditional) {
		this.conditionalWrap.classList.add(
			"wpcf7-form-control-conditional-wrap"
		);
	}

	if (meta) meta.parentElement.removeChild(meta);
};

ConditionalControl.prototype.validateConditions = function (state) {
	if (!this.conditional) return true;
	return Object.keys(this.conditions).reduce((acum, field) => {
		const value = Array.isArray(state[field])
			? state[field].join(",")
			: state[field];
		return acum && value == this.conditions[field];
	}, true);
};

ConditionalControl.prototype.updateVisibility = function (
	state,
	initial = false
) {
	// Check visibility based on state
	const isVisible = this.validateConditions(state);
	const hasChanged = this.visible !== isVisible;

	// Update visibility state
	if (isVisible) {
		this.conditionalWrap.classList.add("visible");
		if (!this.controlWrap.contains(this.el)) {
			this.controlWrap.appendChild(this.el);
			this.controlWrap.setAttribute("data-name", this.fieldName);
		}
	} else {
		this.conditionalWrap.classList.remove("visible");
		if (this.controlWrap.contains(this.el)) {
			this.controlWrap.removeChild(this.el);
			this.controlWrap.removeAttribute("data-name");
		}
	}

	if (!hasChanged) return;

	// Emit visibility change
	this.el.dispatchEvent(
		new CustomEvent("show", {
			detail: {
				value: this.visible,
				state: state,
			},
		})
	);
};

ConditionalControl.prototype.on = function (event, callback) {
	this.el.addEventListener(event, callback);
};

ConditionalControl.prototype.off = function (event, callback) {
	this.el.removeEventListener(event, callback);
};

export default ConditionalControl;
