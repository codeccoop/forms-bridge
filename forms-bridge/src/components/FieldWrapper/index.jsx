export default function FieldWrapper({
  children,
  style = {},
  min = "200px",
  max = "300px",
  isResponsive = false,
}) {
  const width = isResponsive ? "100%" : "15vw";
  if (isResponsive) {
    max = width;
  }

  return (
    <div
      style={{
        maxWidth: "100%",
        width: `clamp(${min}, ${width}, ${max})`,
        ...style,
      }}
    >
      {children}
    </div>
  );
}
