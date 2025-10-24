const { useState, useEffect, useRef } = wp.element;

export default function useResponsive(breakpoint = 1080) {
  const [responsive, setResponsive] = useState(window.innerWidth <= breakpoint);

  const timeout = useRef();
  const onResize = useRef(() => {
    clearTimeout(timeout.current);
    timeout.current = setTimeout(
      () => setResponsive(window.innerWidth <= breakpoint),
      100
    );
  }).current;

  useEffect(() => {
    window.addEventListener("resize", onResize);
    return () => {
      window.removeEventListener("resize", onResize);
    };
  }, []);

  return responsive;
}
