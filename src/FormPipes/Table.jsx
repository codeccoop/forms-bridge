// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import {
  SelectControl,
  TextControl,
  Button,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// vendor
import useFormFields from "../hooks/useFormFields";

const castOptions = [
  {
    value: "string",
    label: __("String", "wpct-erp-forms"),
  },
  {
    value: "int",
    label: __("Integer", "wpct-erp-forms"),
  },
  {
    value: "float",
    label: __("Decimal", "wpct-erp-forms"),
  },
  {
    value: "boolean",
    label: __("Boolean", "wpct-erp-forms"),
  },
  {
    value: "json",
    label: __("JSON", "wpct-erp-forms"),
  },
];

export default function PipesTable({ formId, pipes, setPipes }) {
  const fields = useFormFields({ formId });
  const fromOptions = fields.map((field) => ({
    label: field.label,
    value: field.name,
  }));

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
                  options={castOptions}
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
