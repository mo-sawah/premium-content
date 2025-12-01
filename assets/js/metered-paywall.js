/* ===================================
   metered-paywall.js - Frontend View Tracking & Unlock
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
    unlockContent("Email submitted successfully! Access granted.");
  });

  // Handle social media unlock
  jQuery(document).on("click", ".social-button", function (e) {
    e.preventDefault();

    var $button = jQuery(this);
    var network = $button.data("network");
    var socialUrl = $button.attr("href");

    // Open social media in new tab
    window.open(socialUrl, "_blank");

    // Show loading status
    jQuery("#social-unlock-status").show();
    jQuery(".social-buttons").hide();

    // Get unlock delay from settings (default 4 seconds)
    var unlockDelay =
      typeof premiumContentPaywall.unlockDelay !== "undefined"
        ? premiumContentPaywall.unlockDelay * 1000
        : 4000;

    // Simulate verification delay then unlock
    setTimeout(function () {
      // Send AJAX to record unlock
      jQuery.ajax({
        url: premiumContentPaywall.ajaxUrl,
        type: "POST",
        data: {
          action: "premium_social_unlock",
          nonce: premiumContentPaywall.nonce,
          post_id: premiumContentPaywall.postId,
          network: network,
        },
        success: function (response) {
          if (response.success) {
            unlockContent("Thanks for following! Unlocking content now...");
          } else {
            showSocialError("Failed to unlock. Please try email option.");
          }
        },
        error: function () {
          // Even on error, unlock (graceful degradation)
          unlockContent("Access granted!");
        },
      });
    }, unlockDelay);
  });

  // Function to unlock content
  function unlockContent(message) {
    // Hide truncated content and paywall
    jQuery(".premium-truncated-content").hide();
    jQuery("#premium-content-gate").hide();

    // Show full content
    jQuery(".premium-full-content").show();

    // Show success message
    var $successMsg = jQuery(
      '<div class="premium-alert premium-alert-success" style="margin: 20px 0; padding: 16px 20px; background: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 8px; animation: fadeIn 0.3s;">' +
        message +
        "</div>"
    );
    jQuery(".premium-content-wrapper").prepend($successMsg);

    // Scroll to top of content
    jQuery("html, body").animate(
      {
        scrollTop: jQuery(".premium-content-wrapper").offset().top - 100,
      },
      500
    );

    setTimeout(function () {
      $successMsg.fadeOut(300, function () {
        jQuery(this).remove();
      });
    }, 5000);
  }

  // Function to show social unlock error
  function showSocialError(message) {
    jQuery("#social-unlock-status").hide();
    jQuery(".social-buttons").show();

    var $errorMsg = jQuery(
      '<div class="premium-alert premium-alert-error" style="margin: 15px 0; padding: 12px 16px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px;">' +
        message +
        "</div>"
    );
    jQuery(".premium-social-unlock").prepend($errorMsg);

    setTimeout(function () {
      $errorMsg.fadeOut(300, function () {
        jQuery(this).remove();
      });
    }, 5000);
  }
})();
