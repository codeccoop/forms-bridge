// source
import { useIntegrations } from "../../providers/Settings";
import { useTemplate, useTemplates } from "../../providers/Templates";

const {
  Modal,
  Button,
  SelectControl,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useEffect, useRef } = wp.element;
const { __ } = wp.i18n;

export default function Templates({ Wizard }) {
  const templates = useTemplates();
  const [, setTemplate] = useTemplate();

  const templateOptions = [{ label: "", value: "" }].concat(
    templates.map(({ name, title }) => ({
      label: title,
      value: name,
    }))
  );

  const integrations = useIntegrations();
  const [integration, setIntegration] = useState(integrations[0]?.name || "");

  const integrationOptions = integrations.map(({ name, label }) => ({
    value: name,
    label,
  }));

  const [isOpen, setIsOpen] = useState(false);

  const onError = useRef(() => setIsOpen(false)).current;

  useEffect(() => {
    wpfb.on("error", onError);
    return () => {
      wpfb.off("error", onError);
    };
  }, []);

  useEffect(() => {
    if (!integration && integrations.length) {
      setIntegration(integrations[0].name);
    }
  }, [integrations]);

  if (!templates.length || !integrations.length) return;

  return (
    <>
      <Button
        variant="secondary"
        onClick={() => setIsOpen(true)}
        style={{
          width: "150px",
          marginTop: "auto",
          justifyContent: "center",
        }}
        __next40pxDefaultSize
      >
        {__("Use tempalte", "forms-bridge")}
      </Button>
      {isOpen && (
        <Modal
          title={__("Templates", "forms-bridge")}
          onRequestClose={() => setIsOpen(false)}
        >
          {integrations.length > 1 && (
            <>
              <SelectControl
                label={__("Target integration", "forms-bridge")}
                options={integrationOptions}
                value={integration}
                onChange={setIntegration}
                __nextHasNoMarginBottom
              />
              <Spacer paddingY="calc(6px)" />
            </>
          )}
          <SelectControl
            label={__("Select a template", "forms-bridge")}
            options={templateOptions}
            onChange={setTemplate}
            __nextHasNoMarginBottom
          />
          <Wizard
            integration={integration}
            onDone={() => setTimeout(() => setIsOpen(false), 500)}
          />
        </Modal>
      )}
    </>
  );
}
