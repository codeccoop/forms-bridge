// vendor
import React from "react";
import {
  SelectControl,
  TextControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// vendor
import useFormFields from "../hooks/useFormFields";
import { useI18n } from "../providers/I18n";

const castOptions = [
  {
    value: "string",
    label: "String",
  },
  {
    value: "int",
    label: "Integer",
  },
  {
    value: "float",
    label: "Decimal",
  },
  {
    value: "boolean",
    label: "Boolean",
  },
  {
    value: "json",
    label: "JSON",
  },
  {
    value: "null",
    label: "Ignore",
  },
];

export default function PipesTable({ formId, pipes, setPipes }) {
  const __ = useI18n();
  const fields = useFormFields({ formId });
  const fromOptions = [
    { label: __("Submission ID", "wpct-erp-forms"), value: "submission_id" },
  ].concat(
    fields.map((field) => ({
      label: __(field.label, "wpct-erp-forms"),
      value: field.name,
    }))
  );

  const setPipe = (attr, index, value) => {
    const newPipes = pipes.map((pipe, i) => {
      if (index === i) {
        pipe[attr] = value;
        if (attr === "from" && !pipe.to) {
          pipe.to = value;
        }
      }
      return { ...pipe };
    });

    setPipes(newPipes);
  };

  const addPipe = () => {
    const newPipes = pipes.concat([{ from: "", to: "", cast: "string" }]);
    setPipes(newPipes);
  };

  const dropPipe = (index) => {
    const newPipes = pipes.slice(0, index).concat(pipes.slice(index + 2));
    setPipes(newPipes);
  };

  useEffect(() => {
    if (!pipes.length) addPipe();
  }, [pipes]);

  return (
    <div className="components-base-control__label">
      <label
        className="components-base-control__label"
        style={{
          fontSize: "11px",
          textTransform: "uppercase",
          fontWeight: 500,
          marginBottom: "calc(8px)",
        }}
      >
        {__("Form format pipes", "wpct-erp-forms")}
      </label>
      <table style={{ width: "100%" }}>
        <tbody>
          {pipes.map(({ from, to, cast }, i) => (
            <tr key={i}>
              <td>
                <SelectControl
                  placeholder={__("From", "wpct-erp-forms")}
                  value={from}
                  onChange={(value) => setPipe("from", i, value)}
                  options={fromOptions}
                  __nextHasNoMarginBottom
                />
              </td>
              <td>
                <TextControl
                  placeholder={__("To", "wpct-erp-forms")}
                  value={to}
                  onChange={(value) => setPipe("to", i, value)}
                  __nextHasNoMarginBottom
                />
              </td>
              <td style={{ borderLeft: "1rem solid transparent" }}>
                <SelectControl
                  placeholder={__("Cast as", "wpct-erp-forms")}
                  value={cast || "string"}
                  onChange={(value) => setPipe("cast", i, value)}
                  options={castOptions.map(({ label, value }) => ({
                    label: __(label, "wpct-erp-forms"),
                    value,
                  }))}
                  __nextHasNoMarginBottom
                />
              </td>
              <td style={{ borderLeft: "1rem solid transparent" }}>
                <Button
                  isDestructive
                  variant="secondary"
                  onClick={() => dropPipe(i)}
                  style={{ height: "32px" }}
                >
                  {__("Drop", "wpct-erp-forms")}
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <Spacer paddingY="calc(3px)" />
      <Button
        variant="secondary"
        onClick={() => addPipe()}
        style={{ height: "32px" }}
      >
        {__("Add", "wpct-erp-forms")}
      </Button>
    </div>
  );
}
