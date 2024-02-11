import ConditionalControl from "./ConditionalControl.js";

function ConditionalRadio(el, meta) {
	ConditionalControl.call(this, el, meta);
	this.options = Array.from(el.querySelectorAll("input[type='radio']"));
	for (const option of this.options) {
		option.addEventListener("change", (ev) => {
			setTimeout(() => {
				el.value = this.getValue();
				el.dispatchEvent(new Event("change"));
			}, 0);
		});
	}

	this.fieldName = this.controlWrap.dataset.name;
	this.type = "radio";
	this.el.value = this.getValue();
	Object.defineProperty(this, "defaultValue", {
		writable: false,
		configurable: false,
		enumerable: false,
		value: this.el.value,
	});
}

ConditionalRadio.prototype = Object.create(ConditionalControl.prototype);

ConditionalRadio.prototype.getValue = function () {
	return this.options
		.filter((opt) => opt.checked)
		.map((opt) => opt.value)
		.pop();
};

export default ConditionalRadio;
