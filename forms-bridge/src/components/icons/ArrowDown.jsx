import ArrowUpIcon from "./ArrowUp";

export default function ArrowDownIcon({
  width = 100,
  height = 145,
  color = "#000000",
}) {
  return (
    <div style={{ transform: "translateY(-2px) rotate(180deg)" }}>
      <ArrowUpIcon width={width} height={height} color={color} />
    </div>
  );
}
