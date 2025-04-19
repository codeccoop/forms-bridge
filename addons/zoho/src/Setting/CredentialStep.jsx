import useZohoApi from "../hooks/useZohoApi";
import CredentialStep from "../../../../src/components/Templates/Steps/CredentialStep";
import { sortByNamesOrder } from "../../../../src/lib/utils";

const { useMemo } = wp.element;

const FIELDS_ORDER = ["name", "organization_id", "client_id", "client_secret"];

export default function ZohoCredentialStep({ fields, data, setData }) {
  const [{ credentials }] = useZohoApi();
  const sortedFields = useMemo(() => sortByNamesOrder(fields, FIELDS_ORDER));

  return (
    <CredentialStep
      credentials={credentials}
      fields={sortedFields}
      data={data}
      setData={setData}
    />
  );
}
