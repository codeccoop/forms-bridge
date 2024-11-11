// vendor
import React from "react";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import {
  createContext,
  useContext,
  useState,
  useEffect,
} from "@wordpress/element";

// source
import Loading from "../Loading";

const noop = () => {};

const defaultSettings = {
  "general": {
    notification_receiver: `admin@${window.location.hostname}`,
    backends: [],
  },
  "rest-api": {
    form_hooks: [],
  },
  "rpc-api": {
    endpoint: "/jsonrpc",
    database: "crm.lead",
    user: "admin",
    password: "admin",
    form_hooks: [],
  },
};

const SettingsContext = createContext([defaultSettings, noop]);

export default function SettingsProvider({ children }) {
  const [general, setGeneral] = useState({ ...defaultSettings.general });
  const [restApi, setRestApi] = useState({ ...defaultSettings["rest-api"] });
  const [rpcApi, setRpcApi] = useState({ ...defaultSettings["rpc-api"] });

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiFetch({
      path: `${window.wpApiSettings.root}wpct/v1/erp-forms/settings`,
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
    })
      .then((settings) => {
        setGeneral(settings.general);
        setRestApi(settings["rest-api"]);
        setRpcApi(settings["rpc-api"]);
      })
      .finally(() => setLoading(false));
  }, []);

  const saveSettings = () => {
    return apiFetch({
      path: `${window.wpApiSettings.root}wpct/v1/erp-forms/settings`,
      method: "POST",
      headers: {
        "X-WP-Nonce": wpApiSettings.nonce,
      },
      data: {
        general,
        "rest-api": restApi,
        "rpc-api": rpcApi,
      },
    });
  };

  return (
    <SettingsContext.Provider
      value={[
        {
          general,
          setGeneral,
          restApi,
          setRestApi,
          rpcApi,
          setRpcApi,
        },
        saveSettings,
      ]}
    >
      {(loading && <Loading message={__("Loading")} />) || children}
    </SettingsContext.Provider>
  );
}

export function useGeneral() {
  const [{ general, setGeneral }] = useContext(SettingsContext);

  const { notification_receiver: receiver, backends } = general;

  const update = ({ receiver, backends }) =>
    setGeneral({
      notification_receiver: receiver,
      backends,
    });

  return [{ receiver, backends }, update];
}

export function useRestApi() {
  const [{ restApi, setRestApi }] = useContext(SettingsContext);
  return [restApi, setRestApi];
}

export function useRpcApi() {
  const [{ rpcApi, setRpcApi }] = useContext(SettingsContext);
  return [rpcApi, setRpcApi];
}

export function useSubmitSettings() {
  const [, submit] = useContext(SettingsContext);
  return submit;
}
