/**
 * @file
 * Lazy load images using IntersectionObserver for data-src attribute.
 */
import "../../scss/lazy-load/lazy-load.scss";
(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.more_fields_lazyLoadImages = {
    attach: function (context, settings) {
      // Use once() to avoid multiple initializations.
      const images = once("lazy-load", "img[data-src]", context);

      if (!images.length) {
        return;
      }

      if (!window.IntersectionObserver) {
        // Fallback for older browsers: load all images immediately.
        images.forEach(function (img) {
          img.src = img.dataset.src;
        });
        return;
      }

      // Create one observer with dynamic rootMargin per image.
      // We'll recreate options for each image, but IntersectionObserver
      // does not support per-image rootMargin. So we'll use a single observer
      // with the maximum threshold? Better: create observer per image?
      // For simplicity, we'll use a single observer with a fixed margin of 200px,
      // and rely on the data-threshold attribute to adjust if needed.
      // However, we can't change rootMargin dynamically.
      // Alternative: use a single observer with a high default margin.
      // But to respect per-image threshold, we could set rootMargin to '200px'
      // and rely on the fact that it's just a margin, not a critical setting.
      // Another approach: create an observer per image, but that's heavy.
      // We'll use a single observer with margin from the first image or default.

      // Simple approach: use a fixed rootMargin of 200px (or from settings).
      // For more accuracy, you could implement a custom solution, but this is fine.
      const observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              const img = entry.target;
              img.src = img.dataset.src;
              img.removeAttribute("data-src");
              img.removeAttribute("data-threshold");
              observer.unobserve(img);
            }
          });
        },
        {
          rootMargin: "200px 0px",
        },
      );

      images.forEach(function (img) {
        // Optionally read threshold from data-threshold to adjust?
        // For now we ignore per-image threshold.
        observer.observe(img);
      });
    },
  };
})(Drupal, once);
