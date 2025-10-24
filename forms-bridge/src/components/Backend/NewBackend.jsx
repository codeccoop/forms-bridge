import { useError } from "../../providers/Error";
import useBackendNames from "../../hooks/useBackendNames";
import { uploadJson, validateBackend, validateUrl } from "../../lib/utils";
import BackendFields from "./Fields";
import BackendHeaders from "./Headers";
import useResponsive from "../../hooks/useResponsive";
import ArrowUpIcon from "../icons/ArrowUp";

const { Button } = wp.components;
const { useState, useMemo, useCallback } = wp.element;
const { __ } = wp.i18n;

const TEMPLATE = {
  name: "",
  base_url: "https://",
  headers: [
    {
      name: "Content-Type",
      value: "application/json",
    },
  ],
  authentication: {},
};

export default function NewBackend({ add }) {
  const isResponsive = useResponsive();

  const [data, setData] = useState(TEMPLATE);

  const [error, setError] = useError();
  const names = useBackendNames();

  const nameConflict = useMemo(() => {
    if (!data.name) return false;
    return names.has(data.name.trim());
  }, [names, data.name]);

  const invalidUrl = useMemo(() => {
    return !validateUrl(data.base_url, true);
  }, [data.base_url]);

  const create = () => {
    window.__wpfbInvalidated = true;

    setData(TEMPLATE);
    add({ ...data });
  };

  const isValid = useMemo(() => {
    return !nameConflict && !invalidUrl && validateBackend(data);
  }, [data, nameConflict, invalidUrl]);

  const uploadConfig = useCallback(() => {
    uploadJson()
      .then((data) => {
        const isValid = validateBackend(data);

        if (!isValid) {
          setError(__("Invalid backend config", "forms-bridge"));
          return;
        }

        let i = 1;
        while (names.has(data.name)) {
          data.name = data.name.replace(/\([0-9]+\)/, "") + ` (${i})`;
          i++;
        }

        data.headers =
          (Array.isArray(data.headers) &&
            data.headers.filter(
              (header) => header && header.name && header.value
            )) ||
          [];

        add(data);
      })
      .catch((err) => {
        if (!err) return;

        console.error(err);
        setError(
          __(
            "An error has ocurred while uploading the backend config",
            "forms-bridge"
          )
        );
      });
  }, [names]);

  return (
    <div
      style={{
        padding: "calc(24px) calc(32px)",
        width: "calc(100% - 64px)",
        backgroundColor: "rgb(245, 245, 245)",
        display: "flex",
        flexDirection: isResponsive ? "column" : "row",
        gap: "2rem",
      }}
    >
      <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem" }}>
        <BackendFields
          state={data}
          setState={setData}
          errors={{
            name: nameConflict,
            base_url: invalidUrl,
          }}
        />
        <div
          style={{
            marginTop: "0.5rem",
            display: "flex",
            gap: "0.5rem",
          }}
        >
          <Button
            variant="primary"
            onClick={create}
            style={{ width: "100px", justifyContent: "center" }}
            disabled={nameConflict || !isValid}
            __next40pxDefaultSize
          >
            {__("Add", "forms-bridge")}
          </Button>
          <Button
            disabled={!!error}
            variant="tertiary"
            size="compact"
            style={{
              width: "40px",
              height: "40px",
              justifyContent: "center",
              fontSize: "1.5em",
              border: "1px solid",
              color: "gray",
            }}
            onClick={uploadConfig}
            __next40pxDefaultSize
            label={__("Upload", "forms-bridge")}
            showTooltip
          >
            <ArrowUpIcon width="12" height="20" color="gray" />
          </Button>
        </div>
      </div>
      <div
        style={
          isResponsive
            ? {
                paddingTop: "2rem",
                borderTop: "1px solid",
              }
            : {
                paddingLeft: "2rem",
                borderLeft: "1px solid",
                flex: 1,
              }
        }
      >
        <BackendHeaders
          headers={data.headers}
          setHeaders={(headers) => setData({ ...data, headers })}
        />
      </div>
    </div>
  );
}
