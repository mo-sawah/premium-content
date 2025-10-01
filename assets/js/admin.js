/* ===================================
   admin.js - Admin Interface Scripts
   =================================== */

jQuery(document).ready(function ($) {
  "use strict";

  // Mode selector active state
  $('.premium-mode-option input[type="radio"]').on("change", function () {
    $(".premium-mode-option").removeClass("active");
    $(this).closest(".premium-mode-option").addClass("active");
  });

  // Toggle metered settings visibility
  $('input[name="access_mode"]').on("change", function () {
    if ($(this).val() === "metered") {
      $(".metered-settings").slideDown(300);
    } else {
      $(".metered-settings").slideUp(300);
    }
  });

  // Toggle counter position visibility
  $('input[name="metered_show_counter"]').on("change", function () {
    if ($(this).is(":checked")) {
      $(".counter-position").slideDown(300);
    } else {
      $(".counter-position").slideUp(300);
    }
  });

  // Create CF7 Form button
  $("#premium-create-cf7-form").on("click", function (e) {
    e.preventDefault();

    var $button = $(this);
    var originalText = $button.text();

    $button
      .prop("disabled", true)
      .html('<span class="premium-loading"></span> Creating...');

    $.ajax({
      url: premiumContentAdmin.ajaxUrl,
      type: "POST",
      data: {
        action: "premium_create_cf7_form",
        nonce: premiumContentAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotice("success", response.data.message);
          setTimeout(function () {
            location.reload();
          }, 1000);
        } else {
          showNotice("error", response.data);
          $button.prop("disabled", false).text(originalText);
        }
      },
      error: function () {
        showNotice("error", premiumContentAdmin.strings.error);
        $button.prop("disabled", false).text(originalText);
      },
    });
  });

  // Test Stripe connection
  $("#test-stripe-connection").on("click", function (e) {
    e.preventDefault();
    testConnection("stripe", $(this));
  });

  // Test PayPal connection
  $("#test-paypal-connection").on("click", function (e) {
    e.preventDefault();
    testConnection("paypal", $(this));
  });

  // Generic connection test function
  function testConnection(gateway, $button) {
    var originalText = $button.text();
    $button
      .prop("disabled", true)
      .html('<span class="premium-loading"></span> Testing...');

    $.ajax({
      url: premiumContentAdmin.ajaxUrl,
      type: "POST",
      data: {
        action: "premium_test_" + gateway,
        nonce: premiumContentAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotice("success", response.data.message);
        } else {
          showNotice("error", response.data.message || response.data);
        }
        $button.prop("disabled", false).text(originalText);
      },
      error: function () {
        showNotice("error", premiumContentAdmin.strings.error);
        $button.prop("disabled", false).text(originalText);
      },
    });
  }

  // Show admin notice
  function showNotice(type, message) {
    var noticeClass = type === "success" ? "notice-success" : "notice-error";
    var $notice = $(
      '<div class="notice ' +
        noticeClass +
        ' is-dismissible"><p>' +
        message +
        "</p></div>"
    );

    $(".premium-admin-wrap").prepend($notice);

    // Auto-dismiss after 5 seconds
    setTimeout(function () {
      $notice.fadeOut(300, function () {
        $(this).remove();
      });
    }, 5000);
  }

  // Confirm delete actions
  $(".button-link-delete").on("click", function (e) {
    if (!confirm(premiumContentAdmin.strings.confirmDelete)) {
      e.preventDefault();
    }
  });

  // Initialize tooltips if available
  if (typeof $.fn.tooltip === "function") {
    $("[data-tooltip]").tooltip();
  }
});

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
    // Don't track if user has active subscription or is logged in admin
    if (premiumContentPaywall.isUserLoggedIn) {
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
        }
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

  // Track view when page loads
  jQuery(document).ready(function () {
    // Small delay to ensure proper page load
    setTimeout(trackView, 1000);
  });

  // Handle CF7 form submission success
  jQuery(document).on("wpcf7mailsent", function (event) {
    // Hide paywall and show full content
    jQuery(".premium-truncated-content").hide();
    jQuery("#premium-paywall").hide();
    jQuery(".premium-full-content").show();
  });
})();
