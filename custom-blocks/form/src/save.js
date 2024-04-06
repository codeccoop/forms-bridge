import { useBlockProps, InnerBlocks } from "@wordpress/block-editor";

export default function save({ attributes }) {
  const {
    infinite,
    slidesToShow,
    slidesToScroll,
    initialSlide,
    centerMode,
    showDots,
    showArrows,
    autoPlay,
  } = attributes;

  const blockProps = useBlockProps.save();

  return (
    <div
      {...blockProps}
      infinite={String(infinite)}
      centerMode={String(centerMode)}
      slidesToShow={slidesToShow}
      slidesToScroll={slidesToScroll}
      initialSlide={initialSlide}
      showDots={String(showDots)}
      showArrows={String(showArrows)}
      autoPlay={String(autoPlay)}
    >
      <InnerBlocks.Content />
    </div>
  );
}
