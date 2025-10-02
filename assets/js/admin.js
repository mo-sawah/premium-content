/* ===================================
   admin.js - Admin Interface Scripts ONLY
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

  // Auto-save form improvements
  $(".premium-settings-form input, .premium-settings-form select").on(
    "change",
    function () {
      $(this)
        .closest("form")
        .find(".premium-form-actions")
        .addClass("has-changes");
    }
  );
});
