import ConditionalControl from "./ConditionalControl.js";

function ConditionalHidden(el, meta) {
	ConditionalControl.call(this, el, meta);
}

ConditionalHidden.prototype = Object.create(ConditionalControl.prototype);

ConditionalHidden.prototype.prepareDom = function (meta) {
	if (this.conditional) {
		this.conditionalWrap = this.controlWrap = this.el.parentElement;
		this.conditionalWrap.classList.add(
			"wpcf7-form-control-conditional-wrap",
			"visible"
		);
		meta.parentElement.removeChild(meta);
	}
};

export default ConditionalHidden;
