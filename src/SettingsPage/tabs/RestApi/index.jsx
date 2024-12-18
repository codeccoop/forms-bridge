// vendor
import React from "react";
import { PanelRow } from "@wordpress/components";
import { useEffect } from "@wordpress/element";

// source
import FormHooks from "../../../components/FormHooks";
import RestFormHook from "./FormHook";
import useRestApi from "./useRestApi";

// assets
import logo from "../../../../assets/rest.png";

export default function RestApiSetting() {
  const [{ form_hooks: hooks }, save] = useRestApi();

  useEffect(() => {
    const img = document.querySelector("#rest-api .addon-logo");
    if (!img) return;
    img.setAttribute("src", "data:image/png;base64," + logo);
    img.style.width = "65px";
  }, []);

  return (
    <PanelRow>
      <FormHooks
        hooks={hooks}
        setHooks={(form_hooks) => save({ form_hooks })}
        FormHook={RestFormHook}
      />
    </PanelRow>
  );
}
