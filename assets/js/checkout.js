/* ===================================
   checkout.js - Checkout Page Scripts
   =================================== */

jQuery(document).ready(function ($) {
  "use strict";

  var processing = false;

  // Show error message
  function showError(message) {
    $("#payment-error-message").text(message);
    $("#payment-error").slideDown();

    // Auto-hide after 8 seconds
    setTimeout(function () {
      $("#payment-error").slideUp();
    }, 8000);
  }

  // Disable all payment buttons
  function disableButtons() {
    $(".payment-method-button").prop("disabled", true).addClass("processing");
  }

  // Enable all payment buttons
  function enableButtons() {
    $(".payment-method-button")
      .prop("disabled", false)
      .removeClass("processing");
  }

  // Add loading spinner
  function addLoadingSpinner($button) {
    var $spinner = $('<span class="spinner"></span>');
    $button.find(".payment-method-content").prepend($spinner);
  }

  // Remove loading spinner
  function removeLoadingSpinner($button) {
    $button.find(".spinner").remove();
  }

  // Stripe checkout handler
  $("#stripe-checkout-btn").on("click", function () {
    if (processing) return;
    processing = true;

    var $btn = $(this);
    disableButtons();
    addLoadingSpinner($btn);

    $.ajax({
      url: premiumCheckout.ajaxUrl,
      type: "POST",
      data: {
        action: "premium_create_stripe_checkout",
        nonce: premiumCheckout.nonce,
        plan_id: $btn.data("plan"),
      },
      success: function (response) {
        if (response.success && response.data.url) {
          // Redirect to Stripe Checkout
          window.location.href = response.data.url;
        } else {
          var errorMsg =
            response.data ||
            "Failed to initialize Stripe checkout. Please try again.";
          showError(errorMsg);
          removeLoadingSpinner($btn);
          enableButtons();
          processing = false;
        }
      },
      error: function (xhr, status, error) {
        console.error("Stripe checkout error:", error);
        showError("Network error. Please check your connection and try again.");
        removeLoadingSpinner($btn);
        enableButtons();
        processing = false;
      },
    });
  });

  // PayPal checkout handler
  $("#paypal-checkout-btn").on("click", function () {
    if (processing) return;
    processing = true;

    var $btn = $(this);
    disableButtons();
    addLoadingSpinner($btn);

    $.ajax({
      url: premiumCheckout.ajaxUrl,
      type: "POST",
      data: {
        action: "premium_create_paypal_order",
        nonce: premiumCheckout.nonce,
        plan_id: $btn.data("plan"),
      },
      success: function (response) {
        if (response.success && response.data.approve_url) {
          // Redirect to PayPal
          window.location.href = response.data.approve_url;
        } else {
          var errorMsg =
            response.data ||
            "Failed to initialize PayPal checkout. Please try again.";
          showError(errorMsg);
          removeLoadingSpinner($btn);
          enableButtons();
          processing = false;
        }
      },
      error: function (xhr, status, error) {
        console.error("PayPal checkout error:", error);
        showError("Network error. Please check your connection and try again.");
        removeLoadingSpinner($btn);
        enableButtons();
        processing = false;
      },
    });
  });

  // Handle return from Stripe/PayPal with session/order ID
  var urlParams = new URLSearchParams(window.location.search);
  var sessionId = urlParams.get("session_id");
  var orderId = urlParams.get("order_id");

  if (sessionId) {
    console.log("Stripe session completed:", sessionId);
  }

  if (orderId) {
    console.log("PayPal order completed:", orderId);
  }
});
