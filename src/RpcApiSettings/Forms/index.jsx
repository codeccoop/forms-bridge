// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import { TabPanel } from "@wordpress/components";

// source
import Form from "./Form";

export default function Forms({ forms, setForms }) {
  const tabs = forms
    .map(({ backend, model, form_id, ref }) => ({
      name: ref,
      title: ref,
      form_id,
      model,
      backend,
    }))
    .concat([
      {
        title: __("Add Form", "wpct-erp-forms"),
        name: "add",
      },
    ]);

  const updateForm = (index, data) => {
    data = { ...data, ref: data.name };
    delete data.name;

    if (index === -1) index = forms.length;
    const newForms = forms
      .slice(0, index)
      .concat([data])
      .concat(forms.slice(index + 1, forms.length));
    setForms(newForms);
  };

  const removeForm = ({ name }) => {
    const index = forms.findIndex((f) => f.ref === name);
    const newForms = forms.slice(0, index).concat(forms.slice(index + 2));
    setForms(newForms);
  };

  return (
    <div style={{ width: "100%" }}>
      <label
        className="components-base-control__label"
        style={{
          fontSize: "11px",
          textTransform: "uppercase",
          fontWeight: 500,
          marginBottom: "calc(8px)",
        }}
      >
        {__("Forms", "wpct-erp-forms")}
      </label>
      <TabPanel tabs={tabs}>
        {(form) => (
          <Form
            {...form}
            remove={removeForm}
            update={(newForm) =>
              updateForm(
                forms.findIndex(({ ref }) => ref === form.name),
                newForm
              )
            }
          />
        )}
      </TabPanel>
    </div>
  );
}
