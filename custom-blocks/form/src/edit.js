import { useBlockProps, InnerBlocks, InspectorControls } from "@wordpress/block-editor";
import { TextControl, PanelBody, PanelRow, ToggleControl } from "@wordpress/components";
import * as components from "@wordpress/components";
import "./editor.scss";

console.log(components);
export default function Edit({ attributes, setAttributes }) {
	const { __ } = wp.i18n;
	const {
		title,
		subtitle,
		backendURL,
		sendMail,
	} = attributes;
	const blockProps = useBlockProps();

	const formTitle = TEMPLATE[0][2][0];
	const formSubtitle = TEMPLATE[0][2][1];
	formTitle.push(title);
	formSubtitle.push(subtitle);
	return (
		<>
			<InspectorControls>
				<PanelBody title={__("Form settings", "wpct-form")}>
					<PanelRow>
						<TextControl
							label={__("Title", "wpct-form")}
							value={title}
							onChange={(value) => setAttributes({ title: value })}
							required
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("Subtitle", "wpct-form")}
							value={subtitle}
							onChange={(value) => setAttributes({ subtitle: value })}
							required
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("Backend URL", "wpct-form")}
							value={backendURL}
							onChange={(value) => setAttributes({ backednURL: value })}
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody>
					<PanelRow>
						<ToggleControl
							label={__("Send mail", "wpct-form")}
							checked={sendMail}
							onChange={() => setAttributes({ sendMail: !sendMail })}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps} >
				<InnerBlocks template={TEMPLATE} />
			</div>
		</>
	);
}

const TEMPLATE = [
	[
		"core/group",
		{
			className: "wpct-form",
			lock: {
				remove: true,
				move: true,
			},
		},
		[
			["core/heading", { className: "wpct-form-title", level: 2, lock: { remove: true, move: true } }],
			["core/heading", { className: "wpct-form-subtitle", level: 3, lock: { remove: true, move: true } }],
			["core/group", { className: "wpct-form-content", lock: { remove: true, move: true } }],
		],
	],
];
