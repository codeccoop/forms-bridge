// source
import { useIntegrations } from "../../providers/Settings";
import {
  useTemplate,
  useTemplateConfig,
  useTemplates,
} from "../../providers/Templates";

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
  const templateConfig = useTemplateConfig();

  const [templateData, setTemplateData] = useState({});
  const [wired, setWired] = useState(null);
  const [done, setDone] = useState(false);

  const integrations = useIntegrations();
  const [integration, setIntegration] = useState(integrations[0]?.name || "");

  const integrationOptions = integrations.map(({ name, label }) => ({
    value: name,
    label,
  }));

  const templateOptions = [{ label: "", value: "" }].concat(
    templates
      .filter(({ integrations }) => integrations.includes(integration))
      .map(({ name, title }) => ({
        label: title,
        value: name,
      }))
  );

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

  useEffect(() => {
    if (done) setTemplate(null);
  }, [done]);

  useEffect(() => {
    if (!isOpen) {
      setTemplate(null);
      setDone(false);
    }
  }, [isOpen]);

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
        {__("Use a template", "forms-bridge")}
      </Button>
      {isOpen && (
        <Modal
          title={__("Templates", "forms-bridge")}
          onRequestClose={() => setIsOpen(false)}
        >
          {(done && (
            <>
              <p style={{ fontSize: "1rem" }}>
                {__(
                  "Congratulations, you've created a new form bridge!",
                  "forms-bridge"
                )}
              </p>
              <Button
                variant="primary"
                onClick={() => setIsOpen(false)}
                style={{
                  width: "150px",
                  margin: "1.5rem auto 0",
                  display: "block",
                }}
                __next40pxDefaultSize
              >
                {__("Close", "forms-bridge")}
              </Button>
            </>
          )) || (
            <>
              {integrations.length > 1 && (
                <>
                  <SelectControl
                    label={__("Target integration", "forms-bridge")}
                    options={integrationOptions}
                    value={integration}
                    onChange={setIntegration}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                  <Spacer paddingY="calc(6px)" />
                </>
              )}
              <SelectControl
                label={__("Select a template", "forms-bridge")}
                options={templateOptions}
                onChange={setTemplate}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
              />
            </>
          )}
          {(templateConfig?.description && (
            <p
              style={{ maxWidth: "575px" }}
              dangerouslySetInnerHTML={{ __html: templateConfig.description }}
            ></p>
          )) ||
            null}
          <Wizard
            integration={integration}
            onDone={() => setDone(true)}
            data={templateData}
            setData={setTemplateData}
            wired={wired}
            setWired={setWired}
          />
        </Modal>
      )}
    </>
  );
}
