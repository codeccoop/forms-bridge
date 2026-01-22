import { useAddons } from "../providers/Settings";

const { useMemo } = wp.element;

export default function useBridges() {
  const [addons, patch] = useAddons();

  const bridges = useMemo(() => {
    return Object.keys(addons).reduce((bridges, addon) => {
      const addonBridges = addons[addon].bridges || [];
      return bridges.concat(
        addonBridges.map((bridge) => ({ ...bridge, addon }))
      );
    }, []);
  }, [addons]);

  const setBridges = (bridges) => {
    const newAddons = Object.keys(addons).reduce((newAddons, addon) => {
      const addonBridges = bridges
        .filter((bridge) => bridge.addon === addon)
        .map((bridge) => {
          const newBridge = { ...bridge };
          delete newBridge.addon;
          return newBridge;
        });

      newAddons[addon] = { ...addons[addon], bridges: addonBridges };
      return newAddons;
    }, {});

    patch(newAddons);
  };

  return [bridges, setBridges];
}
