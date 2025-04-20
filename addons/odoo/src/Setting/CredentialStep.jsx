import useOdooApi from "../hooks/useOdooApi";
import { sortByNamesOrder } from "../../../../src/lib/utils";
import CredentialStep from "../../../../src/components/Templates/Steps/CredentialStep";

const { useMemo } = wp.element;

const FIELDS_ORDER = ["name", "database", "user", "password"];

export default function OdooCredentialStep({ fields, data, setData }) {
  const [{ credentials }] = useOdooApi();
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
