import { useBlockProps, InnerBlocks, InspectorControls } from "@wordpress/block-editor";
import { TextControl, PanelBody, PanelRow, ToggleControl, DropdownMenu } from "@wordpress/components";
import "./editor.scss";

export default function Edit({ attributes, setAttributes }) {
	const { __ } = wp.i18n;
	const {
		name,
		type,
		placeholder,
		defaultValue,
	} = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={__("Form control settings", "wpct-form")}>
					<PanelRow>
						<DropdownMenu
							label={__("Type", "wpct-form")}
							value={type}
							onChange={(value) => setAttributes({ type: value })}
							required
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("name", "wpct-form")}
							value={name}
							onChange={(value) => setAttributes({ name: value })}
							required
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("placeholder", "wpct-form")}
							value={placeholder}
							onChange={(value) => setAttributes({ placeholder: value })}
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={__("defaultValue", "wpct-form")}
							checked={defaultValue}
							onChange={(value) => setAttributes({ defaultValue: value })}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<InnerBlocks template={TEMPLATE} />
			</div>
		</>
	);
}

const TEMPLATE = [
	[
		"core/group",
		{
			className: "wpct-form-control",
			lock: {
				remove: true,
				move: true,
			},
		},
		[
			["core/paragraph", { className: "wpct-form-control-label" }, ["label"]],
			["core/html", { className: "wpct-form-control-input", lock: { remove: true, move: true } }, ["<input type='text' />"]],
			["core/paragraph", { className: "wpct-form-control-description" }, ["description"]],
		],
	],
];
