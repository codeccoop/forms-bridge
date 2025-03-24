// source
import Bridges from "../../../../src/components/Bridges";
import GSBridge from "./Bridge";
import useGSApi from "../hooks/useGSApi";
import useAjaxGrant from "../hooks/useAjaxGrant";

const {
  PanelBody,
  PanelRow,
  ToggleControl,
  FormFileUpload,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;

export default function GoogleSheetsSetting() {
  const [{ authorized, bridges, templates, workflow_jobs }, save] = useGSApi();

  const { grant, revoke, loading, result } = useAjaxGrant();

  const [file, setFile] = useState(null);

  const update = (field) =>
    save({ authorized, bridges, templates, workflow_jobs, ...field });

  const onGrant = () => {
    if (file) grant(file);
    else revoke();
  };

  useEffect(() => {
    if (loading || result === null) return;
    if (result) {
      window.location.reload();
    }
  }, [loading]);

  return (
    <>
      <PanelRow>
        <Bridges
          bridges={bridges}
          setBridges={(bridges) => update({ bridges })}
          Bridge={GSBridge}
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <PanelBody
        title={__("Google Service Credentials", "forms-bridge")}
        initialOpen={!authorized}
      >
        <Spacer paddingY="calc(8px)" />
        <ToggleControl
          disabled={!(authorized || file)}
          checked={authorized}
          onChange={onGrant}
          label={
            authorized
              ? __("Revoke access", "forms-bridge")
              : __("Grant access", "forms-bridge")
          }
          help={__(
            "You have to create a service account credentials to grant Forms Bridge access to your spreadhseets",
            "forms-bridge"
          )}
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />
        <Spacer paddingY="calc(8px)" />
        {!authorized && (
          <FormFileUpload
            __next40pxDefaultSize
            accept="application/json"
            onChange={({ target }) => setFile(target.files[0])}
          >
            {__("Upload credentials", "forms-bridge")}
          </FormFileUpload>
        )}
        <p
          dangerouslySetInnerHTML={{
            __html: __(
              "Follow <a href='https://github.com/juampynr/google-spreadsheet-reader?tab=readme-ov-file' target='_blank'>example</a> if do you need help with the process.",
              "forms-bridge"
            ),
          }}
        />
      </PanelBody>
    </>
  );
}
