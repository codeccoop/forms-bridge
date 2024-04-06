(function () {
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".wp-block-ce-block-slider").forEach((el) => {
      el.classList.add("ready");
      const {
        infinite,
        slidesToShow,
        slidesToScroll,
        initialSlide,
        centerMode,
        dots,
        arrows,
        autoplay,
      } = getAttributes(el);

      const arrowsWrapper = document.createElement("div");
      arrowsWrapper.classList.add("ce-block-slider-arrows");
      el.appendChild(arrowsWrapper);

      const slidesWrapper = getSlidesWrapper(el);
      const slideCount = slidesWrapper.children.length;
      jQuery(slidesWrapper).slick({
        dots,
        arrows,
        slidesToScroll,
        slidesToShow,
        infinite,
        centerMode,
        autoplay,
        autoplaySpeed: 5e3,
        initialSlide: Math.max(0, Math.min(slideCount, initialSlide)),
        appendArrows: jQuery(arrowsWrapper),
        // lazyLoad: "ondemand",
        adaptiveHeight: true,
      });
    });
  });

  function getAttributes(el) {
    return {
      infinite: el.getAttribute("infinite") === "true",
      centerMode: el.getAttribute("centermode") === "true",
      slidesToShow: Number(el.getAttribute("slidestoshow")),
      slidesToScroll: Number(el.getAttribute("slidestoscroll")),
      initialSlide: Number(el.getAttribute("initialslide")),
      dots: el.getAttribute("showdots") === "true",
      arrows: el.getAttribute("showarrows") === "true",
      autoplay: el.getAttribute("autoplay") === "true",
    };
  }

  function getSlidesWrapper(el) {
    const hasQuery = el.querySelector(".wp-block-query");
    if (hasQuery) {
      return hasQuery.children[0];
    }

    return el.children[0];
  }
})();
