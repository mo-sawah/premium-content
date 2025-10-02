/* ===================================
   pricing.js - Pricing Page Enhancement
   =================================== */

(function ($) {
  "use strict";

  // Price animation on load
  function animatePrices() {
    $(".amount").each(function () {
      const $this = $(this);
      const finalValue = parseInt($this.text().replace(/,/g, ""));
      let currentValue = 0;
      const increment = Math.ceil(finalValue / 30);
      const duration = 800;
      const stepTime = duration / 30;

      const timer = setInterval(function () {
        currentValue += increment;
        if (currentValue >= finalValue) {
          currentValue = finalValue;
          clearInterval(timer);
        }
        $this.text(currentValue.toLocaleString());
      }, stepTime);
    });
  }

  // Card hover effect enhancement
  function enhanceCardInteraction() {
    $(".pcp-card")
      .on("mouseenter", function () {
        $(this).addClass("hovered");
      })
      .on("mouseleave", function () {
        $(this).removeClass("hovered");
      });
  }

  // Intersection Observer for scroll animations
  function initScrollAnimations() {
    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add("visible");
              observer.unobserve(entry.target);
            }
          });
        },
        {
          threshold: 0.1,
          rootMargin: "0px 0px -50px 0px",
        }
      );

      document.querySelectorAll(".pcp-card, .faq-item").forEach((el) => {
        el.classList.add("animate-on-scroll");
        observer.observe(el);
      });
    }
  }

  // Initialize on document ready
  $(document).ready(function () {
    // Only run on pricing page
    if (!$(".pcp-pricing-wrapper").length) return;

    setTimeout(animatePrices, 300);
    enhanceCardInteraction();
    initScrollAnimations();
  });
})(jQuery);
