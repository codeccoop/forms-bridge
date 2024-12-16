// vendor
import React from "react";
import {
  PanelBody,
  PanelRow,
  __experimentalSpacer as Spacer,
} from "@wordpress/components";

// source
import FormHooks from "../../../../src/components/FormHooks";
import OdooFormHook from "./FormHook";
import useOdooApi from "../hooks/useOdooSetting";
import Databases from "../components/Databases";

export default function OdooSetting() {
  const __ = wp.i18n.__;
  const [{ databases, form_hooks: hooks }, save] = useOdooApi();

  const update = (field) => save({ databases, form_hooks: hooks, ...field });

  return (
    <>
      <PanelRow>
        <FormHooks
          hooks={hooks}
          setHooks={(form_hooks) => update({ form_hooks })}
          FormHook={OdooFormHook}
        />
      </PanelRow>
      <Spacer paddingY="calc(8px)" />
      <PanelBody
        title={__("Databases", "posts-bridge")}
        initialOpen={databases.length === 0}
      >
        <Databases
          databases={databases}
          setDatabases={(databases) => update({ databases })}
        />
      </PanelBody>
    </>
  );
}
