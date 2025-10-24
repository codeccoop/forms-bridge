import Spinner from "../components/Spinner";

const { createContext, useContext, useState } = wp.element;

const LoadingContext = createContext([]);

export default function LoadingProvider({ children }) {
  const [queue, setQueue] = useState([]);

  const updateQueue = (loading) => {
    if (loading) setQueue([...queue, !!loading]);
    else setQueue(queue.slice(1));
  };

  return (
    <LoadingContext.Provider value={[queue.length > 0, updateQueue]}>
      {children}
      <Spinner show={queue.length} />
    </LoadingContext.Provider>
  );
}

export function useLoading() {
  return useContext(LoadingContext);
}
