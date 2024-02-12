import Form from "./Form.js";

document.addEventListener("DOMContentLoaded", function () {
	for (const form of document.querySelectorAll(".wpcf7-form")) {
		new Form(form);
	}
});
