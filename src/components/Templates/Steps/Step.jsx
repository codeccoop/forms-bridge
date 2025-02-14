const { __experimentalHeading: Heading } = wp.components;

export default function TemplateStep({ name, description, children }) {
  return (
    <>
      <Heading level={3}>{name}</Heading>
      <p style={{ marginTop: 0 }}>{description}</p>
      <div style={{ display: "flex", flexDirection: "column" }}>{children}</div>
    </>
  );
}
