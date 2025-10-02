/* ===================================
   metered-paywall.js - Frontend View Tracking
   =================================== */

(function () {
  "use strict";

  // Only run on singular posts/pages
  if (typeof premiumContentPaywall === "undefined") {
    return;
  }

  // Track article view
  function trackView() {
    // Don't track if user has active subscription
    if (premiumContentPaywall.hasSubscription) {
      return;
    }

    // Check if already tracked in this session
    var sessionKey = "premium_tracked_" + premiumContentPaywall.postId;
    if (sessionStorage.getItem(sessionKey)) {
      return;
    }

    jQuery.ajax({
      url: premiumContentPaywall.ajaxUrl,
      type: "POST",
      data: {
        action: "premium_track_view",
        nonce: premiumContentPaywall.nonce,
        post_id: premiumContentPaywall.postId,
      },
      success: function (response) {
        if (response.success && response.data) {
          // Mark as tracked in session
          sessionStorage.setItem(sessionKey, "1");

          // Update counter if shown
          if (response.data.remaining !== undefined) {
            updateCounter(response.data.remaining);
          }

          // Show warning if last article
          if (response.data.remaining === 1) {
            showLastArticleWarning();
          }

          // Show limit reached if applicable
          if (response.data.limit_reached) {
            showLimitReached();
          }
        }
      },
      error: function () {
        console.log("Failed to track article view");
      },
    });
  }

  // Update counter banner
  function updateCounter(remaining) {
    var $banner = jQuery("#premium-counter-banner");
    if ($banner.length) {
      var text = premiumContentPaywall.strings.articlesRemaining.replace(
        "%d",
        remaining
      );
      $banner.find(".premium-counter-text").text(text);

      // Add warning class if last article
      if (remaining === 1) {
        $banner.addClass("premium-counter-warning");
      }
    }
  }

  // Show last article warning
  function showLastArticleWarning() {
    var $banner = jQuery("#premium-counter-banner");
    if ($banner.length) {
      $banner.addClass("premium-counter-warning");
      $banner
        .find(".premium-counter-text")
        .text(premiumContentPaywall.strings.lastArticle);
    }
  }

  // Show limit reached message
  function showLimitReached() {
    var $banner = jQuery("#premium-counter-banner");
    if ($banner.length) {
      $banner.addClass("premium-counter-warning");
      $banner
        .find(".premium-counter-text")
        .text(premiumContentPaywall.strings.limitReached);
    }
  }

  // Track view when page loads
  jQuery(document).ready(function () {
    // Small delay to ensure proper page load
    setTimeout(trackView, 1000);
  });

  // Handle CF7 form submission success (for email gate mode)
  jQuery(document).on("wpcf7mailsent", function (event) {
    // Hide paywall and show full content
    jQuery(".premium-truncated-content").hide();
    jQuery("#premium-paywall").hide();
    jQuery(".premium-full-content").show();

    // Show success message
    var $successMsg = jQuery(
      '<div class="premium-alert premium-alert-success" style="margin: 20px 0; animation: fadeIn 0.3s;">Access granted! Enjoy the full article.</div>'
    );
    jQuery(".premium-content-wrapper").prepend($successMsg);

    setTimeout(function () {
      $successMsg.fadeOut(300, function () {
        jQuery(this).remove();
      });
    }, 5000);
  });
})();
