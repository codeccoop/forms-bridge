// source
import Bridges from "../../../../src/components/Bridges";
import GSBridge from "./Bridge";
import useGSApi from "../hooks/useGSApi";
import useAjaxGrant from "../hooks/useAjaxGrant";

const {
  PanelBody,
  PanelRow,
  FormFileUpload,
  Button,
  __experimentalSpacer: Spacer,
} = wp.components;
const { useEffect } = wp.element;
const { __ } = wp.i18n;

export default function GoogleSheetsSetting() {
  const [{ authorized, bridges, templates, workflow_jobs }, save] = useGSApi();

  const { grant, revoke, loading, result } = useAjaxGrant();

  const update = (field) =>
    save({ authorized, bridges, templates, workflow_jobs, ...field });

  useEffect(() => {
    if (loading || result === null) return;
    if (result) {
      window.location.reload();
    }
  }, [loading]);

  return (
    <>
      <p style={{ marginTop: 0 }}>
        {__(
          "Syncrhonize your form submission with Google Sheets and work with your web leads out of the WordPress admin site",
          "forms-bridge"
        )}
      </p>
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
        <p
          dangerouslySetInnerHTML={{
            __html: __(
              "You have to create a service account credentials to grant Forms Bridge access to your spreadsheets. Follow this <a href='https://github.com/juampynr/google-spreadsheet-reader?tab=readme-ov-file' target='_blank'>example</a> if you need help with the process.",
              "forms-bridge"
            ),
          }}
        />
        <div style={{ display: "flex", alignItems: "center", gap: "1em" }}>
          {authorized ? (
            <Button
              variant="primary"
              isDestructive
              onClick={revoke}
              style={{ width: "150px", justifyContent: "center" }}
              __next40pxDefaultSize
            >
              {__("Revoke credentials", "forms-bridge")}
            </Button>
          ) : (
            <FormFileUpload
              __next40pxDefaultSize
              variant="secondary"
              accept="application/json"
              style={{ width: "150px", justifyContent: "center" }}
              onChange={({ target }) => grant(target.files[0])}
            >
              {__("Upload credentials", "forms-bridge")}
            </FormFileUpload>
          )}
        </div>
      </PanelBody>
    </>
  );
}
