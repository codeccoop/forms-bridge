const { createContext, useContext, useState, useEffect } = wp.element;
const { Notice } = wp.components;

const ErrorContext = createContext([]);

export default function ErrorProvider({ children }) {
  const [errors, setErrors] = useState([]);

  const updateErrors = (error) => {
    if (error) setErrors([...errors, error]);
    else setErrors(errors.slice(1));
  };

  useEffect(() => {
    if (errors.length) {
      window.scrollTo({ left: 0, top: 0, behavior: "smooth" });
    }
  }, [errors]);

  return (
    <ErrorContext.Provider value={[errors[0], updateErrors]}>
      {(errors.length && (
        <div style={{ marginBottom: "calc(-8px)" }}>
          <Notice
            status="error"
            onRemove={() => updateErrors(null)}
            politeness="assertive"
          >
            {errors[0]}
          </Notice>
        </div>
      )) ||
        null}
      {children}
    </ErrorContext.Provider>
  );
}

export function useError() {
  return useContext(ErrorContext);
}
