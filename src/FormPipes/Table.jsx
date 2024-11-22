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
  const __ = wp.i18n.__;
  const fields = useFormFields({ formId });
  const fromOptions = [
    { label: __("Submission ID", "forms-bridge"), value: "submission_id" },
  ].concat(
    fields.map((field) => ({
      label: __(field.label, "forms-bridge"),
      value: field.name,
    }))
  );

  const setPipe = (attr, index, value) => {
    const newPipes = pipes.map((pipe, i) => {
      if (index === i) {
        pipe[attr] = value;
        if (attr === "from" && pipe.to !== value) {
          pipe.to = value;
        }
      }
      return { ...pipe };
    });

    setPipes(newPipes);
  };

  const addPipe = () => {
    const newPipes = pipes.concat([
      { from: "submission_id", to: "submission_id", cast: "string" },
    ]);
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
        {__("Form format pipes", "forms-bridge")}
      </label>
      <table style={{ width: "100%" }}>
        <tbody>
          {pipes.map(({ from, to, cast }, i) => (
            <tr key={i}>
              <td>
                <SelectControl
                  placeholder={__("From", "forms-bridge")}
                  value={from}
                  onChange={(value) => setPipe("from", i, value)}
                  options={fromOptions}
                  __nextHasNoMarginBottom
                />
              </td>
              <td>
                <TextControl
                  placeholder={__("To", "forms-bridge")}
                  value={to}
                  onChange={(value) => setPipe("to", i, value)}
                  __nextHasNoMarginBottom
                />
              </td>
              <td style={{ borderLeft: "1rem solid transparent" }}>
                <SelectControl
                  placeholder={__("Cast as", "forms-bridge")}
                  value={cast || "string"}
                  onChange={(value) => setPipe("cast", i, value)}
                  options={castOptions.map(({ label, value }) => ({
                    label: __(label, "forms-bridge"),
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
                  {__("Drop", "forms-bridge")}
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
        {__("Add", "forms-bridge")}
      </Button>
    </div>
  );
}
