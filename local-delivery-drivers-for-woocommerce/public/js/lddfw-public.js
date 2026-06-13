window._lddfw_live_maps = [];

// Define SVG icons (assuming similar style to existing icons)
var lddfw_loader_icon_svg  = '<svg class="lddfw_status_icon lddfw_loader_icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" style="background: none; shape-rendering: auto;" width="20px" height="20px"><circle cx="50" cy="50" r="32" stroke-width="8" stroke="#fff" stroke-dasharray="50.26548245743669 50.26548245743669" fill="none" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" keyTimes="0;1" values="0 50 50;360 50 50"></animateTransform></circle></svg>';
var lddfw_success_icon_svg = '<svg class="lddfw_status_icon lddfw_success_icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="20px" height="20px"><circle cx="26" cy="26" r="25" fill="none"   stroke-width="4"/><path fill="none" stroke="#fff" stroke-width="4" d="M14 27l8 8 16-16"/></svg>';
var lddfw_failure_icon_svg = '<svg class="lddfw_status_icon lddfw_failure_icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="20px" height="20px"><circle cx="26" cy="26" r="25" fill="none"   stroke-width="4"/><path fill="none" stroke="#fff" stroke-width="4" d="M16 16 36 36 M36 16 16 36"/></svg>';
var lddfw_trash_icon_svg   = '<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg>';

/**
 * Global toast helper.
 * type: "success" | "danger" | "warning" | "info"
 * options: { duration: ms (default 5000; 0 = sticky) }
 */
function lddfw_show_toast(message, type, options) {
    if (!message) { return; }
    type = type || "info";
    options = options || {};
    var duration = (typeof options.duration === "number") ? options.duration : 5000;

    var icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15-5-5 1.41-1.41L11 14.17l7.59-7.59L20 8l-9 9z"/></svg>',
        danger:  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
        info:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
    };

    var $toast = jQuery(
        '<div class="lddfw-toast lddfw-toast--' + type + '" role="alert" aria-live="polite">' +
            '<span class="lddfw-toast__icon" aria-hidden="true">' + (icons[type] || icons.info) + '</span>' +
            '<span class="lddfw-toast__message"></span>' +
            '<button type="button" class="lddfw-toast__close" aria-label="' + ((typeof lddfw_alert_texts !== "undefined" && lddfw_alert_texts.toast_close) ? lddfw_alert_texts.toast_close : "Close") + '">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>' +
            '</button>' +
        '</div>'
    );
    $toast.find(".lddfw-toast__message").html(message);
    jQuery("body").append($toast);

    // Stack multiple toasts by offsetting downward from the header.
    var stackToasts = function() {
        jQuery("body > .lddfw-toast").each(function(i) {
            jQuery(this).css("top", "calc(70px + 0.75rem + " + (i * 4.25) + "rem)");
        });
    };
    stackToasts();

    requestAnimationFrame(function() { $toast.addClass("is-visible"); });

    var dismiss = function() {
        $toast.removeClass("is-visible");
        setTimeout(function() {
            $toast.remove();
            stackToasts();
        }, 250);
    };
    $toast.find(".lddfw-toast__close").on("click", dismiss);
    if (duration > 0) {
        setTimeout(dismiss, duration);
    }
    return $toast;
}

(function() {
    "use strict";

    
 

    jQuery(window).bind("pageshow", function(event) {
        lddfw_hide_loader();
    });

    jQuery('body').on('click', '.lddfw_loader', function() {
        lddfw_show_loader(jQuery(this));
    });

    

    jQuery("body").on("click", "#lddfw-panel-listing-toggle", function() {
        jQuery("#lddfw_directions-panel-lightbox").show();
        jQuery("html, body").addClass("lddfw-scroll-lock");
        jQuery("#google_map").addClass("lddfw-map-above-lightbox");

        return false;
    });

    jQuery(".lddfw_premium-feature button").click(function() {
        jQuery(this).parent().find(".lddfw_lightbox").show();
        jQuery("html, body").addClass("lddfw-scroll-lock");
    });

    jQuery("body").on("click", ".lddfw_premium-modal", function(e) {
        if (jQuery(e.target).is(".lddfw_premium-modal")) {
            jQuery(this).hide();
            jQuery("html, body").removeClass("lddfw-scroll-lock");
        }
    });

    jQuery("#lddfw_out_for_delivery_button").click(
        function() {
            jQuery("#lddfw_out_for_delivery_button").hide();
            jQuery("#lddfw_out_for_delivery_button_loading").show();

            var lddfw_order_list = '';
            jQuery("#lddfw_alert").html();
            jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                function(index, item) {
                    if (jQuery(this).prop("checked") == true) {
                        if (lddfw_order_list != "") {
                            lddfw_order_list = lddfw_order_list + ",";
                        }
                        lddfw_order_list = lddfw_order_list + jQuery(this).val();
                    }
                }
            );
            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_out_for_delivery',
                    lddfw_orders_list: lddfw_order_list,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {

                    var lddfw_json = JSON.parse(data);
                    var lddfw_msg  = lddfw_json["error"] || "";

                    if (lddfw_json["result"] == "0") {
                        if (typeof lddfw_show_toast === "function") {
                            lddfw_show_toast(lddfw_msg, "warning");
                        } else {
                            jQuery("#lddfw_alert").show().html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_msg + "</div>");
                        }
                    }

                    if (lddfw_json["result"] == "1") {
                        // Capture the order IDs being sent out for delivery
                        // *before* removing the boxes - the boxes are the
                        // only DOM source of orderId, so we must collect
                        // them in the same pass that does the removal.
                        // This mirrors the claim-orders flow and feeds the
                        // assign-screen Map view's `lddfw:assign:after`
                        // listener so its pins drop in lockstep with the list.
                        var lddfw_assign_sent_ids = [];
                        jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                            function(index, item) {
                                if (jQuery(this).prop("checked") == true) {
                                    var $box = jQuery(this).parents(".lddfw_multi_checkbox").first();
                                    var oid  = $box.attr("data-orderid");
                                    if (oid) { lddfw_assign_sent_ids.push(oid); }
                                    $box.remove();
                                }
                            }
                        );

                        // Notify decoupled views (Map view) that these
                        // orders have left the assign screen. Future views
                        // can subscribe without touching this AJAX handler.
                        if (lddfw_assign_sent_ids.length) {
                            jQuery(document).trigger("lddfw:assign:after", [lddfw_assign_sent_ids]);
                        }

                        // Mirrors the claim-orders success flow: the server
                        // returns one blob mixing a Bootstrap .alert with a
                        // short message and a "View out for delivery orders"
                        // CTA <a>. Split them so the message goes to a
                        // transient toast and the CTA goes to a persistent
                        // success card that replaces the submit button in
                        // the fixed footer slot.
                        var $parsed    = jQuery("<div>").html(lddfw_msg);
                        var $alert_src = $parsed.find(".alert").first();
                        var $cta       = $parsed.find("a.btn, #view_out_of_delivery_orders_button").first();
                        var text_msg   = $alert_src.length
                            ? jQuery.trim($alert_src.clone().find("button, .close").remove().end().text())
                            : jQuery.trim($parsed.text());

                        if (text_msg && typeof lddfw_show_toast === "function") {
                            lddfw_show_toast(text_msg, "success");
                        } else if (text_msg) {
                            jQuery("#lddfw_alert").show().prepend(lddfw_msg);
                        }

                        if ($cta.length) {
                            // Strip every Bootstrap button class from the
                            // server-rendered <a> so our .lddfw-assign-cta__btn
                            // styles apply cleanly.
                            $cta.removeClass(
                                "btn btn-lg btn-sm btn-block btn-primary btn-success btn-info btn-warning btn-danger btn-secondary btn-light btn-dark d-block"
                            ).addClass(
                                "lddfw-assign-cta__btn lddfw-assign-cta__btn--primary"
                            );

                            var view_label = (typeof lddfw_alert_texts !== "undefined"
                                && lddfw_alert_texts
                                && lddfw_alert_texts.view_out_for_delivery)
                                ? lddfw_alert_texts.view_out_for_delivery
                                : "View orders";
                            $cta.empty().append(
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9zm0-7a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/></svg>' +
                                '<span class="lddfw-assign-cta__btn-label"></span>'
                            );
                            $cta.find(".lddfw-assign-cta__btn-label").text(view_label);

                            var ordersRemain = jQuery('.lddfw_multi_checkbox').length > 0;

                            var $card = jQuery(
                                '<div class="lddfw-assign-cta" role="status">' +
                                    '<div class="lddfw-assign-cta__body">' +
                                        '<div class="lddfw-assign-cta__icon" aria-hidden="true">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>' +
                                        '</div>' +
                                        '<div class="lddfw-assign-cta__text">' +
                                            '<div class="lddfw-assign-cta__title"></div>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="lddfw-assign-cta__actions"></div>' +
                                '</div>'
                            );
                            $card.find(".lddfw-assign-cta__title").text(text_msg);
                            var $actions = $card.find(".lddfw-assign-cta__actions");
                            $actions.append($cta);

                            // Hold references to the original submit buttons
                            // so "Send more" can restore them. jQuery ID
                            // selectors cannot find detached nodes.
                            //
                            // Also detach `.lddfw-preview-route-row` (wraps
                            // #lddfw_preview_route_button + its loading twin)
                            // for the same reason: `$footerSlot.empty()` below
                            // would otherwise wipe the Preview Route button
                            // out of the DOM, and `lddfw_update_preview_button_visibility()`
                            // no-ops when the button id isn't found - so
                            // "Send more" would restore OFD but not Preview.
                            // The selector is safe on list-only / no-map-key
                            // installs: `.detach()` on an empty set returns
                            // an empty jQuery object and later `.appendTo()`
                            // is a no-op.
                            var $sendBtn        = jQuery("#lddfw_out_for_delivery_button").detach();
                            var $sendBtnLoading = jQuery("#lddfw_out_for_delivery_button_loading").detach();
                            var $previewRow     = jQuery(".lddfw-assign-footer .lddfw-preview-route-row").detach();

                            if (ordersRemain) {
                                var $more = jQuery(
                                    '<button type="button" class="lddfw-assign-cta__btn lddfw-assign-cta__btn--secondary">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg>' +
                                        '<span class="lddfw-assign-cta__btn-label"></span>' +
                                    '</button>'
                                );
                                $more.find(".lddfw-assign-cta__btn-label").text(
                                    (typeof lddfw_alert_texts !== "undefined"
                                        && lddfw_alert_texts
                                        && lddfw_alert_texts.send_more)
                                        ? lddfw_alert_texts.send_more
                                        : "Send more"
                                );
                                $more.on("click", function(e) {
                                    e.preventDefault();
                                    var $slot = jQuery(".lddfw_footer_buttons")
                                        .removeClass("lddfw-assign-footer--cta")
                                        .find(".col-12").first();
                                    $slot.empty();
                                    // Re-append preview row first so visual
                                    // order matches the initial render
                                    // (preview secondary CTA above the
                                    // primary OFD button). After detach the
                                    // preview button's inline style carries
                                    // whatever visibility it had at OFD
                                    // click time - force hidden because none
                                    // of the *remaining* boxes is checked
                                    // yet; the existing change/click handler
                                    // on .custom-control-input will call
                                    // lddfw_update_preview_button_visibility()
                                    // and reveal it the moment the driver
                                    // selects something.
                                    if ($previewRow.length) {
                                        $previewRow.appendTo($slot);
                                        $previewRow.find("#lddfw_preview_route_button").hide();
                                        $previewRow.find("#lddfw_preview_route_button_loading").hide();
                                    }
                                    $sendBtn.show().appendTo($slot);
                                    $sendBtnLoading.hide().appendTo($slot);
                                });
                                $actions.append($more);
                            }

                            var $footerSlot = jQuery(".lddfw-assign-footer .col-12").first();
                            if ($footerSlot.length) {
                                $footerSlot.empty().append($card);
                                jQuery(".lddfw_footer_buttons").show().addClass("lddfw-assign-footer--cta");
                                jQuery("#lddfw_alert").empty().hide();
                            } else {
                                if ($sendBtn.length) {
                                    jQuery(".lddfw_footer_buttons .col-12").first()
                                        .append($sendBtn.show())
                                        .append($sendBtnLoading.hide());
                                }
                                jQuery("#lddfw_alert").empty().append($card).show();
                            }
                        } else {
                            // No CTA returned - keep previous behaviour.
                            jQuery("#lddfw_alert").show().html(lddfw_msg);
                            jQuery("#lddfw_out_for_delivery_button").show();
                            jQuery("#lddfw_out_for_delivery_button_loading").hide();
                        }

                        if (jQuery('.lddfw_multi_checkbox').length == 0
                            && !jQuery(".lddfw-assign-footer .lddfw-assign-cta").length) {
                            jQuery(".lddfw_footer_buttons").hide();
                        }
                    } else {
                        jQuery("#lddfw_out_for_delivery_button").show();
                        jQuery("#lddfw_out_for_delivery_button_loading").hide();
                    }
                }
            );
            return false;
        }
    );

    // Preview route flow - show the "Preview route" CTA on the assign
    // screen only when the driver has selected at least one assigned
    // order. The button itself only exists in the DOM when premium is
    // active AND a Google Maps API key is configured (see
    // LDDFW_Screens::lddfw_assign_to_driver_screen()), so on non-premium
    // sites the selector simply finds nothing and this is a no-op.
    function lddfw_update_preview_button_visibility() {
        var $btn = jQuery("#lddfw_preview_route_button");
        if ($btn.length === 0) {
            return;
        }
        // If the loading state is currently shown, don't fight it - the
        // AJAX callback will restore the idle button on its own.
        if (jQuery("#lddfw_preview_route_button_loading").is(":visible")) {
            return;
        }
        var selected = jQuery(".lddfw_multi_checkbox .custom-control-input:checked").length;
        if (selected > 0) {
            $btn.show();
        } else {
            $btn.hide();
        }
    }
    jQuery(document).on(
        "change click",
        ".lddfw_multi_checkbox .custom-control-input, .lddfw_multi_checkbox .lddfw_wrap",
        function() {
            // Defer one tick so the native checkbox has finished toggling
            // (the .lddfw_wrap click handler above flips the prop
            // synchronously, but jQuery's change event for checkboxes
            // fires *before* our :checked selector would see the new
            // value in some browsers).
            setTimeout(lddfw_update_preview_button_visibility, 0);
        }
    );
    // Run once on ready in case the page renders with pre-checked items
    // (e.g. after a validation round-trip).
    lddfw_update_preview_button_visibility();

    jQuery("#lddfw_preview_route_button").click(
        function() {
            var $btn        = jQuery("#lddfw_preview_route_button");
            var $btnLoading = jQuery("#lddfw_preview_route_button_loading");

            var lddfw_order_list = "";
            jQuery(".lddfw_multi_checkbox .custom-control-input").each(
                function() {
                    if (jQuery(this).prop("checked") === true) {
                        if (lddfw_order_list !== "") {
                            lddfw_order_list += ",";
                        }
                        lddfw_order_list += jQuery(this).val();
                    }
                }
            );

            if (lddfw_order_list === "") {
                // Defensive: button is hidden in this state, but a stray
                // click (e.g. keyboard) shouldn't fire an empty request.
                return false;
            }

            $btn.hide();
            $btnLoading.show();

            jQuery.ajax({
                url: lddfw_ajax_url,
                type: "POST",
                data: {
                    action: "lddfw_ajax",
                    lddfw_service: "lddfw_preview_route",
                    lddfw_orders_list: lddfw_order_list,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: "json"
                }
            }).done(
                function(data) {
                    var payload;
                    try {
                        payload = JSON.parse(data);
                    } catch (e) {
                        payload = null;
                    }

                    $btnLoading.hide();
                    lddfw_update_preview_button_visibility();

                    if (!payload || payload.result != 1) {
                        var msg = (payload && payload.error)
                            ? payload.error
                            : ((typeof lddfw_alert_texts !== "undefined"
                                && lddfw_alert_texts
                                && lddfw_alert_texts.directions_request_failed)
                                ? lddfw_alert_texts.directions_request_failed
                                : "Could not build preview.");
                        if (typeof lddfw_show_toast === "function") {
                            lddfw_show_toast(msg, "warning");
                        } else {
                            jQuery("#lddfw_alert").show().html(
                                "<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" +
                                "<a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" +
                                msg + "</div>"
                            );
                        }
                        return;
                    }

                    // Cache the payload for the Google Maps callback. The
                    // script tag loads async, so we can't close over it
                    // in a local variable.
                    window.lddfw_preview_payload = payload;

                    jQuery(".lddfw_page_content").hide();
                    jQuery("#lddfw_preview_directions").show();

                    if (typeof google === "undefined"
                        && jQuery("#lddfw_google_map_script").length === 0) {
                        jQuery("body").append(
                            "<script " +
                              "id='lddfw_google_map_script' " +
                              "async defer " +
                              "src='https://maps.googleapis.com/maps/api/js" +
                                "?key="       + lddfw_google_api_key +
                                "&language="  + lddfw_map_language   +
                                "&callback="  + "lddfw_initPreviewMap" +
                                "&loading=async" +
                              "'></script>"
                        );
                    } else {
                        lddfw_initPreviewMap();
                    }
                }
            ).fail(function() {
                $btnLoading.hide();
                lddfw_update_preview_button_visibility();
                if (typeof lddfw_show_toast === "function"
                    && typeof lddfw_alert_texts !== "undefined"
                    && lddfw_alert_texts
                    && lddfw_alert_texts.directions_request_failed) {
                    lddfw_show_toast(lddfw_alert_texts.directions_request_failed, "warning");
                }
            });

            return false;
        }
    );

    jQuery("#lddfw_preview_hide_map_btn").click(
        function() {
            jQuery("#lddfw_preview_directions").hide();
            jQuery(".lddfw_page_content").show();
            return false;
        }
    );

    jQuery(".lddfw_multi_checkbox .lddfw_wrap").click(
        function(e) {
            // Skip when the click originated on the checkbox / label itself.
            // The native <input> already toggles on click, and the <label>
            // forwards to the input via its `for` attribute. Without this
            // guard the bubbled click re-toggles the checkbox and the user
            // sees no change.
            var $target = jQuery(e.target);
            if ($target.closest(".lddfw_order_checkbox").length > 0) {
                return;
            }

            var lddfw_chk = jQuery(this).find(".custom-control-input");
            if (lddfw_chk.prop("checked") == true) {
                jQuery(this).parents(".lddfw_multi_checkbox").removeClass("lddfw_active");
                lddfw_chk.prop("checked", false);
            } else {
                jQuery(this).parents(".lddfw_multi_checkbox").addClass("lddfw_active");
                lddfw_chk.prop("checked", true);
            }
        }
    );

    // Keep the card "active" class in sync whenever the native checkbox
    // itself is toggled (either by direct click on the input / label or
    // programmatically). This avoids relying solely on the card-wide
    // .lddfw_wrap click handler above.
    jQuery("body").on("change", ".lddfw_multi_checkbox .custom-control-input", function() {
        var $card = jQuery(this).parents(".lddfw_multi_checkbox");
        if (jQuery(this).prop("checked") == true) {
            $card.addClass("lddfw_active");
        } else {
            $card.removeClass("lddfw_active");
        }
    });

    jQuery("#lddfw_start").click(
        function() {
            jQuery("#lddfw_home").hide();
            jQuery("#lddfw_login").show();
        }
    );

    jQuery("#lddfw_login_button").click(
        function() {
            // hide the sign up button
            jQuery("#lddfw_signup_button").hide();
            // show the login form
            jQuery("#lddfw_login_wrap").toggle();
            return false;
        }
    );

    // Inline SVG for #lddfw_availability (must match includes/class-lddfw-screens.php).
    var lddfw_availability_svg_on =
        '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg>';
    var lddfw_availability_svg_off =
        '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg>';

    jQuery("#lddfw_availability").click(
        function() {
            var $toggle = jQuery(this);
            var turningOff = $toggle.hasClass("lddfw_active");
            var legacySvgToggle =
                $toggle.find("svg.fa-toggle-on, svg.fa-toggle-off").length > 0;
            if (turningOff) {
                $toggle.removeClass("lddfw_active is-on").attr("aria-checked", "false");
                if (legacySvgToggle) {
                    $toggle.html(lddfw_availability_svg_off);
                }
                jQuery("#lddfw_availability_status").html(jQuery("#lddfw_availability_status").attr("unavailable"));
                jQuery("#lddfw_menu .lddfw_availability").removeClass("text-success").addClass("text-danger");
            } else {
                $toggle.addClass("lddfw_active is-on").attr("aria-checked", "true");
                if (legacySvgToggle) {
                    $toggle.html(lddfw_availability_svg_on);
                }
                jQuery("#lddfw_availability_status").html(jQuery("#lddfw_availability_status").attr("available"));
                jQuery("#lddfw_menu .lddfw_availability").removeClass("text-danger").addClass("text-success");
            }
            jQuery.post(
                lddfw_ajax_url, {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_availability',
                    lddfw_availability: turningOff ? "0" : "1",
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'html'
                }
            );
            return false;
        }
    );

    jQuery("#lddfw_dates_range").change(
        function() {
            var lddfw_location = jQuery(this).attr("data") + '&lddfw_dates=' + this.value;
            lddfw_show_loader(jQuery(this));
            window.location.replace(lddfw_location);
            return false;
        }
    );

    if (typeof lddfw_dates !== 'undefined') {
        if (lddfw_dates != "") {
            jQuery("#lddfw_dates_range").val(lddfw_dates);
        }
    }


    function lddfw_delivered_screen_open() {
        jQuery("#lddfw_driver_complete_btn").show();
        jQuery(".lddfw_page_content").hide();
        jQuery("#lddfw_delivery_signature").hide();
        jQuery("#lddfw_delivery_photo").hide();
        jQuery("#lddfw_delivered_form").hide();
        jQuery("#lddfw_failed_delivery_form").hide();
        jQuery(".delivery_proof_bar a").removeClass("active");
        jQuery(".delivery_proof_bar a").eq(0).addClass("active");
        lddfw_refresh_proof_state();
    }

    /**
     * Refresh the completion / required state on the proof bar pills.
     * - Marks a tab "done" when the underlying input has content.
     * - Marks "required" when the settings flag says so, unless already "done".
     * Safe to call multiple times; purely UI (no side effects on data).
     */
    window.lddfw_refresh_proof_state = function() {
        var $bar = jQuery(".delivery_proof_bar");
        if (!$bar.length) { return; }

        // Signature: done if #signature-image has an <img>.
        var sigDone = jQuery("#signature-image img").length > 0;
        // Photo: done if #delivery_image has a non-empty JSON value.
        var photoVal = (jQuery("#delivery_image").val() || "").trim();
        var photoDone = photoVal !== "" && photoVal !== "[]";
        // Note: done based on current delivery mode (success/failed), regardless of which proof panel is visible.
        // Reads persistent form state so the pill stays green when the user switches to Signature/Photo tabs.
        var noteDone = false;
        var mode = (jQuery("#lddfw_driver_complete_btn").attr("delivery") || "").toLowerCase();
        if (mode === "success") {
            var pickedDel = jQuery('input[name=lddfw_delivery_dropoff_location]:checked', "#lddfw_delivered_form");
            var delNote = (jQuery("#lddfw_driver_delivered_note").val() || "").trim();
            if (pickedDel.length && pickedDel.attr("id") !== "lddfw_delivery_dropoff_other") {
                noteDone = true;
            } else if (delNote !== "") {
                noteDone = true;
            }
        } else if (mode === "failed") {
            var pickedFail = jQuery('input[name=lddfw_delivery_failed_reason]:checked', "#lddfw_failed_delivery_form");
            var failNote = (jQuery("#lddfw_driver_note").val() || "").trim();
            if (pickedFail.length && pickedFail.attr("id") !== "lddfw_delivery_failed_6") {
                noteDone = true;
            } else if (failNote !== "") {
                noteDone = true;
            }
        }

        $bar.find('a[data-proof="note"]').toggleClass("lddfw_proof_done", noteDone);
        $bar.find('a[data-proof="signature"]').toggleClass("lddfw_proof_done", sigDone);
        $bar.find('a[data-proof="photo"]').toggleClass("lddfw_proof_done", photoDone);

        // "One of photo/signature required" (POD) - drop required hint once either is done.
        var $sigTab = $bar.find('a[data-proof="signature"]');
        var $photoTab = $bar.find('a[data-proof="photo"]');
        var podRequired = $photoTab.attr("data-required-pod") === "1";
        if (podRequired) {
            var podSatisfied = sigDone || photoDone;
            $sigTab.toggleClass("lddfw_proof_required", !podSatisfied && !sigDone);
            $photoTab.toggleClass("lddfw_proof_required", !podSatisfied && !photoDone);
        } else {
            $sigTab.toggleClass("lddfw_proof_required", $sigTab.attr("data-required") === "1" && !sigDone);
            $photoTab.toggleClass("lddfw_proof_required", $photoTab.attr("data-required") === "1" && !photoDone);
        }
    };

    jQuery("#lddfw_delivered_screen_btn").click(
        function() {
            jQuery("#lddfw_driver_complete_btn").attr("delivery", "success");
            jQuery(".delivery_proof_notes").attr("href", "lddfw_delivered_form");
            lddfw_delivered_screen_open();
            jQuery("#lddfw_delivered_form").show();
            jQuery("#lddfw_delivery_screen").show();
            return false;
        }
    );

    jQuery("#lddfw_failed_delivered_screen_btn").click(
        function() {
            jQuery("#lddfw_driver_complete_btn").attr("delivery", "failed");
            jQuery(".delivery_proof_notes").attr("href", "lddfw_failed_delivery_form");
            lddfw_delivered_screen_open();
            jQuery("#lddfw_failed_delivery_form").show();
            jQuery("#lddfw_delivery_screen").show();
            return false;
        }
    );

    jQuery(".lddfw_dashboard .lddfw_box a").click(function() {
        jQuery(this).parent().addClass("lddfw_active");
    });

    jQuery(".lddfw_confirmation .lddfw_cancel").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery(this).parents(".lddfw_lightbox").hide();
            return false;
        }
    );

   

    // Function to process the confirmation click (delivered or failed)
    function lddfw_process_delivery_confirmation(status_attribute, reason_form_selector, reason_radio_name, reason_other_id, note_input_selector, confirmation_div_selector, error_context_message) {
        // Get reason/note
        var lddfw_reason = jQuery('input[name=' + reason_radio_name + ']:checked', reason_form_selector);
        if (lddfw_reason.attr("id") != reason_other_id) {
            jQuery(note_input_selector).val(lddfw_reason.val());
        }

        // Update UI
        jQuery(confirmation_div_selector).hide();
        jQuery("#lddfw_thankyou").show();

        // Get common data
        var lddfw_orderid = jQuery("#lddfw_driver_complete_btn").attr("order_id");
        var lddfw_signature = '';
        var lddfw_delivery_image = '';
        

        // Make the main status update AJAX call
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_status',
                lddfw_order_id: lddfw_orderid,
                lddfw_order_status: jQuery("#lddfw_driver_complete_btn").attr(status_attribute),
                lddfw_driver_id: lddfw_driver_id,
                lddfw_note: jQuery(note_input_selector).val(),
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_signature: lddfw_signature,
                lddfw_data_type: 'html' // Main status update doesn't need JSON response
                // Proofs handled separately in success callback
            },
            success: function(data) {
                
            },
            error: function(request, status, error) {
                // Handle error for the main status update AJAX call
                console.error('Error updating order status ' + error_context_message + ':', status, error);
            }
        });

        return false; // Prevent default form submission/link behavior
    }

    jQuery("#lddfw_delivered_confirmation .lddfw_ok").click(
        function() {
            lddfw_process_delivery_confirmation(
                'delivered_status',                 // status_attribute
                '#lddfw_delivered_form',            // reason_form_selector
                'lddfw_delivery_dropoff_location', // reason_radio_name
                'lddfw_delivery_dropoff_other',     // reason_other_id
                '#lddfw_driver_delivered_note',     // note_input_selector
                '#lddfw_delivered',                 // confirmation_div_selector
                '(delivered)'                       // error_context_message
            );
            return false; // Keep return false here as well
        }
    );

    if (jQuery("#lddfw_delivered_form .custom-control.custom-radio").length == 1) {
        jQuery("#lddfw_delivered_form .custom-control.custom-radio").hide();
    }
    if (jQuery("#lddfw_failed_delivery_form .custom-control.custom-radio").length == 1) {
        jQuery("#lddfw_failed_delivery_form .custom-control.custom-radio").hide();
    }


    jQuery(".lddfw_alert_screen .lddfw_ok").click(function() {
        jQuery(".lddfw_alert_screen .lddfw_lightbox_close").trigger("click");
    });


    jQuery("#lddfw_driver_complete_btn").click(
        function() {

            


            jQuery("#lddfw_delivery_screen").hide();
            if (jQuery(this).attr("delivery") == "success") {
                jQuery("#lddfw_delivered_confirmation").show();
            } else {
                jQuery("#lddfw_failed_delivery_confirmation").show();
            }
            return false;
        }
    );
    jQuery("#lddfw_failed_delivery_confirmation .lddfw_ok").click(
        function() {
            lddfw_process_delivery_confirmation(
                'failed_status',                    // status_attribute
                '#lddfw_failed_delivery_form',      // reason_form_selector
                'lddfw_delivery_failed_reason',    // reason_radio_name
                'lddfw_delivery_failed_6',         // reason_other_id
                '#lddfw_driver_note',              // note_input_selector
                '#lddfw_failed_delivery',          // confirmation_div_selector
                '(failed delivery)'                 // error_context_message
            );
            return false; // Keep return false here as well
        }
    );

    jQuery("#lddfw_delivered_form input[type=radio]").click(
        function() {
            jQuery("#lddfw_driver_delivered_note").val("");
            if (jQuery(this).attr("id") == "lddfw_delivery_dropoff_other") {
                jQuery("#lddfw_driver_delivered_note_wrap").show();
            } else {
                jQuery("#lddfw_driver_delivered_note_wrap").hide();
            }
            if (typeof window.lddfw_refresh_proof_state === "function") { window.lddfw_refresh_proof_state(); }
        }
    );

    jQuery("#lddfw_failed_delivery_form input[type=radio]").click(
        function() {
            jQuery("#lddfw_driver_note").val("");
            if (jQuery(this).attr("id") == "lddfw_delivery_failed_6") {
                jQuery("#lddfw_driver_note_wrap").show();
            } else {
                jQuery("#lddfw_driver_note_wrap").hide();
            }
            if (typeof window.lddfw_refresh_proof_state === "function") { window.lddfw_refresh_proof_state(); }
        }
    );

    jQuery(document).on("input", "#lddfw_driver_delivered_note, #lddfw_driver_note", function() {
        if (typeof window.lddfw_refresh_proof_state === "function") { window.lddfw_refresh_proof_state(); }
    });

    // Refresh proof state whenever the hidden delivery_image value changes or photos are cleared.
    jQuery(document).on("change", "#delivery_image, #upload_image", function() {
        if (typeof window.lddfw_refresh_proof_state === "function") { window.lddfw_refresh_proof_state(); }
    });
    jQuery(document).on("click", "#delivery-image-clear, .lddfw_delete_image, .signature-clear", function() {
        setTimeout(function() {
            if (typeof window.lddfw_refresh_proof_state === "function") { window.lddfw_refresh_proof_state(); }
        }, 50);
    });

    jQuery(".lddfw_lightbox_close,#lddfw_driver_cancel_btn").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery(this).parents(".lddfw_lightbox").hide();
            jQuery("html, body").removeClass("lddfw-scroll-lock");
            jQuery("#google_map").removeClass("lddfw-map-above-lightbox");
            return false;
        }
    );

    jQuery("#lddfw_login_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            var lddfw_nextpage = lddfw_form.attr('nextpage');

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");

            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_login',
                    lddfw_login_email: jQuery("#lddfw_login_email").val(),
                    lddfw_login_password: jQuery("#lddfw_login_password").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        window.location.replace(lddfw_nextpage);
                    }
                },
                error: function(request, status, error) {
                    lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + status + ' ' + error + "</div>");
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#lddfw_back_to_forgot_password_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_login_button").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();
        }
    );
    jQuery("#lddfw_new_password_login_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();
        }
    );
    jQuery("#lddfw_new_password_reset_link").click(
        function() {
            jQuery("#lddfw_create_new_password").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_forgot_password_link").click(
        function() {
            jQuery("#lddfw_login").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery(".lddfw_back_to_login_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();

        }
    );
    jQuery("#lddfw_resend_button").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_application_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_application").show();
        }
    );

    jQuery("#lddfw_forgot_password_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");


            var lddfw_nextpage = lddfw_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_forgot_password',
                    lddfw_user_email: jQuery("#lddfw_user_email").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'

                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);

                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_forgot_password_email_sent").show();

                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#lddfw_new_password_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");


            var lddfw_nextpage = lddfw_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_newpassword',
                    lddfw_new_password: jQuery("#lddfw_new_password").val(),
                    lddfw_confirm_password: jQuery("#lddfw_confirm_password").val(),
                    lddfw_reset_key: jQuery("#lddfw_reset_key").val(),
                    lddfw_reset_login: jQuery("#lddfw_reset_login").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },

                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_new_password_created").show();

                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("body").on("click", "#lddfw_orders_table .lddfw_box a", function() {
        jQuery(this).closest(".lddfw_box").addClass("lddfw_active");
    });
    

})(jQuery);


function lddfw_show_loader(obj) {

    if (obj.hasClass("lddfw_loader_fixed")) {
        jQuery('#lddfw_loader').show();
                                     } else {
        jQuery(".lddfw_back_link").hide();
        jQuery('#lddfw_loader').appendTo(".lddfw_back_column");
        jQuery('#lddfw_loader').show();
    }
}

function lddfw_hide_loader() {
    jQuery('#lddfw_loader').hide();
    jQuery(".lddfw_back_link").show();
    jQuery('#lddfw_loader').appendTo("body");
}

function lddfw_openNav() {
    jQuery(".lddfw_page_content").hide();
    var sidenav = document.getElementById("lddfw_mySidenav");
    if (sidenav) {
        sidenav.style.width = "100%";
        sidenav.classList.add("is-open");
    }
    var backdrop = document.getElementById("lddfw_menu_backdrop");
    if (backdrop) {
        backdrop.classList.add("is-visible");
    }
    document.body.classList.add("lddfw-menu-open");
}

function lddfw_closeNav() {
    jQuery(".lddfw_page_content").show();
    var sidenav = document.getElementById("lddfw_mySidenav");
    if (sidenav) {
        sidenav.style.width = "0";
        sidenav.classList.remove("is-open");
    }
    var backdrop = document.getElementById("lddfw_menu_backdrop");
    if (backdrop) {
        backdrop.classList.remove("is-visible");
    }
    document.body.classList.remove("lddfw-menu-open");
}

jQuery(function($) {
    var $toggle = $("#lddfw_theme_toggle");
    if (!$toggle.length) { return; }

    function applyMode(mode) {
        if (mode === "dark") {
            $("body").addClass("dark").removeClass("light");
        } else {
            $("body").addClass("light").removeClass("dark");
        }
        $toggle.attr("data-mode", mode);

        if (typeof window.lddfw_claim_update_theme === "function") {
            window.lddfw_claim_update_theme();
        }
        if (typeof window.lddfw_assign_update_theme === "function") {
            window.lddfw_assign_update_theme();
        }
        if (typeof lddfw_map_style === "function" && window._lddfw_live_maps) {
            var freshStyles = lddfw_map_style();
            window._lddfw_live_maps.forEach(function(m) {
                if (m && typeof m.setOptions === "function") {
                    m.setOptions({ styles: freshStyles });
                }
            });
        }
    }

    // Initialize from current body class (covers pages where body class wasn't set yet).
    if (!$("body").hasClass("dark") && !$("body").hasClass("light")) {
        applyMode($toggle.attr("data-mode") || "light");
    }

    $toggle.on("click", function(e) {
        e.preventDefault();
        var nextMode = $("body").hasClass("dark") ? "light" : "dark";
        applyMode(nextMode);

        if (typeof lddfw_ajax_url === "undefined" || typeof lddfw_nonce === "undefined") {
            return;
        }

        $.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: "lddfw_ajax",
                lddfw_service: "lddfw_driver_app_mode",
                lddfw_mode: nextMode,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: "json"
            },
            dataType: "json",
            error: function() {
                // Revert on server failure so the UI reflects persisted state.
                applyMode(nextMode === "dark" ? "light" : "dark");
            }
        });
    });
});




    jQuery("#cancel_password_button").on("click", function(e) {
        e.preventDefault();
        jQuery("#lddfw_password_holder").hide();
        jQuery("#lddfw_password").val("").attr("type", "password").trigger("input");
        jQuery(".lddfw-password-eye").attr("aria-pressed", "false");
        jQuery(".lddfw-password-eye__show").show();
        jQuery(".lddfw-password-eye__hide").hide();
    });

    jQuery("#new_password_button").on("click", function() {
        jQuery("#lddfw_password_holder").show();
        jQuery("#lddfw_password").val(Math.random().toString(36).slice(2)).trigger("input");
    });

    // Password show/hide eye.
    jQuery(document).on("click", ".lddfw-password-eye", function() {
        var $btn = jQuery(this);
        var $input = $btn.siblings("input[name='lddfw_password']");
        if ($input.attr("type") === "password") {
            $input.attr("type", "text");
            $btn.attr("aria-pressed", "true").attr("aria-label", (typeof lddfw_alert_texts !== "undefined" && lddfw_alert_texts.hide_password) ? lddfw_alert_texts.hide_password : "Hide password");
            $btn.find(".lddfw-password-eye__show").hide();
            $btn.find(".lddfw-password-eye__hide").show();
        } else {
            $input.attr("type", "password");
            $btn.attr("aria-pressed", "false").attr("aria-label", (typeof lddfw_alert_texts !== "undefined" && lddfw_alert_texts.show_password) ? lddfw_alert_texts.show_password : "Show password");
            $btn.find(".lddfw-password-eye__show").show();
            $btn.find(".lddfw-password-eye__hide").hide();
        }
    });

    // Password strength meter.
    jQuery(document).on("input", "#lddfw_password", function() {
        var val = jQuery(this).val() || "";
        var score = 0;
        if (val.length >= 8) { score++; }
        if (val.length >= 12) { score++; }
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) { score++; }
        if (/\d/.test(val)) { score++; }
        if (/[^A-Za-z0-9]/.test(val)) { score++; }
        // Clamp 0-4.
        if (score > 4) { score = 4; }
        var at = (typeof lddfw_alert_texts !== "undefined") ? lddfw_alert_texts : {};
        var labels = [
            "",
            at.password_strength_weak   || "Weak",
            at.password_strength_fair   || "Fair",
            at.password_strength_good   || "Good",
            at.password_strength_strong || "Strong"
        ];
        var colors = ["transparent", "#dc3545", "#f0ad4e", "#0d6efd", "#198754"];
        var pct    = val.length ? (25 * score) : 0;
        var $meter = jQuery(".lddfw-password-strength");
        $meter.find(".lddfw-password-strength__fill").css({ width: pct + "%", background: colors[score] });
        $meter.find(".lddfw-password-strength__label").text(labels[score]).css("color", colors[score]);
    });

    // Settings TOC scroll-spy + smooth scroll.
    (function() {
        var $toc = jQuery(".lddfw-settings-toc");
        if (!$toc.length) { return; }
        var $links = $toc.find(".lddfw-settings-toc__item");
        $links.on("click", function(e) {
            var href = jQuery(this).attr("href");
            if (href && href.charAt(0) === "#") {
                var $target = jQuery(href);
                if ($target.length) {
                    e.preventDefault();
                    jQuery("html, body").animate({ scrollTop: $target.offset().top - 90 }, 350);
                }
            }
        });
        jQuery(window).on("scroll", function() {
            var scrollTop = jQuery(window).scrollTop() + 120;
            var activeId = null;
            $links.each(function() {
                var href = jQuery(this).attr("href");
                var $t = jQuery(href);
                if ($t.length && $t.offset().top <= scrollTop) {
                    activeId = href;
                }
            });
            if (activeId) {
                $links.removeClass("is-active");
                $links.filter("[href='" + activeId + "']").addClass("is-active");
            }
        });
    })();

    jQuery("#billing_state_select").on("change", function() {
        jQuery("#billing_state_input").val(jQuery(this).val());
    });
    jQuery("#billing_country").on("change", function() {
        if (jQuery(this).val() == "US") {
            jQuery("#billing_state_select").show();
            jQuery("#billing_state_input").hide();
        } else {
            jQuery("#billing_state_input").show();
            jQuery("#billing_state_select").hide();
        }
    });
    if (jQuery("#billing_country").length) {
        jQuery("#billing_country").trigger("change");
    }

    function scrolltoelement(element) {
        jQuery('html, body').animate({
            scrollTop: element.offset().top - 100
        }, 1000);
    }

    jQuery(".lddfw_form").validate({
        submitHandler: function(form) {
            var lddfw_form = jQuery(form);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");
            var lddfw_service = lddfw_form.attr("service");
            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: lddfw_form.serialize() + '&action=lddfw_ajax&lddfw_service=' + lddfw_service + '&lddfw_data_type=json',
                success: function(data) {
                    try {
                        var lddfw_json = JSON.parse(data);
                        if (lddfw_json["result"] == "0") {
                            lddfw_show_toast(lddfw_json["error"] || "Error", "danger");
                            lddfw_submit_btn.show();
                            lddfw_loading_btn.hide();
                        }
                        if (lddfw_json["result"] == "1") {
                            var lddfw_hide_on_success = lddfw_form.find(".lddfw_hide_on_success");
                            if (lddfw_hide_on_success.length) {
                                lddfw_hide_on_success.replaceWith("");
                            }
                            lddfw_show_toast(lddfw_json["error"] || "Saved", "success");
                            lddfw_submit_btn.show();
                            lddfw_loading_btn.hide();
                            if (lddfw_json["nonce"] != "") {
                                lddfw_form.find("#lddfw_wpnonce").val(lddfw_json["nonce"]);
                                lddfw_nonce = { "nonce": lddfw_json["nonce"] };
                            }

                            //Switch theme mode.
                            if (jQuery("select[name='lddfw_driver_app_mode']").length) {
                                jQuery("body").attr("class", jQuery("select[name='lddfw_driver_app_mode']").val());
                            }
                        }

                    } catch (e) {
                        lddfw_show_toast(String(e), "danger");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    lddfw_show_toast((status || "") + " " + (error || ""), "danger");
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });

            return false;
        }
    });


    jQuery("#lddfw_driver_add_signature_btn").click(function() {

        jQuery(".signature-wrapper").show();
        
    });

    jQuery(".delivery_proof_bar a").click(function() {

        var $lddfw_this = jQuery(this);
        var $lddfw_screen_class = $lddfw_this.attr("href")
        $lddfw_this.parents(".delivery_proof_bar").find("a").removeClass("active");
        $lddfw_this.addClass("active");
        $lddfw_this.parents(".lddfw_lightbox").find(".screen_wrap").hide();
        $lddfw_this.parents(".lddfw_lightbox").find("." + $lddfw_screen_class).show();

        
        if (typeof window.lddfw_refresh_proof_state === "function") { window.lddfw_refresh_proof_state(); }
        return false;
    });

    // Live-refresh proof bar state when the user edits note text or picks a radio.
    jQuery(document).on(
        "input change",
        "#lddfw_driver_delivered_note, #lddfw_driver_note, " +
        "input[name=lddfw_delivery_dropoff_location], input[name=lddfw_delivery_failed_reason]",
        function() {
            if (typeof window.lddfw_refresh_proof_state === "function") {
                window.lddfw_refresh_proof_state();
            }
        }
    );

    //switch lazyload
    jQuery("img.lazyload").each(function() {
        var $lddfw_src = jQuery(this).attr("data-src");
        jQuery(this).attr("src", $lddfw_src);
    });
    jQuery("iframe.lazyload").each(function() {
        var $lddfw_src = jQuery(this).attr("data-src");
        jQuery(this).attr("src", $lddfw_src);
    });

    


    function lddfw_order_map() {
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer();

        var LatLng = { lat: 41.85, lng: -87.65 };

        if (lddfw_map_center != "") {
            var lddfw_map_center_array = lddfw_map_center.split(",");
            LatLng = new google.maps.LatLng(parseFloat(lddfw_map_center_array[0]), parseFloat(lddfw_map_center_array[1]));
        }

        const map = new google.maps.Map(document.getElementById("google_map"), {
            zoom: 7,
            center: LatLng,
            styles: lddfw_map_style(),
            disableDefaultUI: true,
        });
        window._lddfw_live_maps.push(map);

        directionsRenderer.setMap(map);

        

        directionsService.route({
                origin: driver_origin,
                destination: driver_destination,
                travelMode: driver_travel_mode,
                optimizeWaypoints: true,
                transitOptions: { modes: ['SUBWAY', 'RAIL', 'TRAM', 'BUS', 'TRAIN'], routingPreference: 'LESS_WALKING' },
            })
            .then((response) => {
                directionsRenderer.setDirections(response);

                

            })
            .catch((e) => console.log("Directions request failed due to " + e));
    }



    function lddfw_computeTotalDistance(result) {
        var lddfw_totalDist = 0;
        var lddfw_totalTime = 0;
        var lddfw_distance_text = '';
        var lddfw_distance_array = '';
        var lddfw_distance_type = '';

        var lddfw_myroute = result.routes[0];
        for (i = 0; i < lddfw_myroute.legs.length; i++) {
            lddfw_totalTime += lddfw_myroute.legs[i].duration.value;
            lddfw_distance_text = lddfw_myroute.legs[i].distance.text;
            lddfw_distance_array = lddfw_distance_text.split(" ");
            lddfw_totalDist += parseFloat(lddfw_distance_array[0]);
            lddfw_distance_type = lddfw_distance_array[1];
        }
        lddfw_totalTime = (lddfw_totalTime / 60).toFixed(0);
        lddfw_TotalTimeText = lddfw_timeConvert(lddfw_totalTime);
        document.getElementById("lddfw_total_route").innerHTML = "<b>" + lddfw_TotalTimeText + "</b> <span>(" + (lddfw_totalDist).toFixed(1) + " " + lddfw_distance_type + ")</span> ";
    }


    function lddfw_timeConvert(n) {
        var lddfw_num = n;
        var lddfw_hours = (lddfw_num / 60);
        var lddfw_rhours = Math.floor(lddfw_hours);
        var lddfw_minutes = (lddfw_hours - lddfw_rhours) * 60;
        var lddfw_rminutes = Math.round(lddfw_minutes);
        var lddfw_result = '';
        if (lddfw_rhours > 1) {
            lddfw_result = lddfw_rhours + " " + lddfw_hours_text + " ";
        }
        if (lddfw_rhours == 1) {
            lddfw_result = lddfw_rhours + " " + lddfw_hour_text + " ";
        }
        if (lddfw_rminutes > 0) {
            lddfw_result += lddfw_rminutes + " " + lddfw_mins_text;
        }
        return lddfw_result;
    }


    


    function lddfw_numtoletter(lddfw_num) {
        var lddfw_s = '',
            lddfw_t;

        while (lddfw_num > 0) {
            lddfw_t = (lddfw_num - 1) % 26;
            lddfw_s = String.fromCharCode(65 + lddfw_t) + lddfw_s;
            lddfw_num = (lddfw_num - lddfw_t) / 26 | 0;
        }
        return lddfw_s || undefined;
    }

    function lddfw_map_style() {
        let lddfw_dark_mode_style = [{
                "featureType": "administrative",
                "elementType": "geometry",
                "stylers": [{
                    "visibility": "off"
                }]
            },
            {
                "featureType": "poi",
                "stylers": [{
                    "visibility": "off"
                }]
            },
            {
                "featureType": "road",
                "elementType": "labels.icon",
                "stylers": [{
                    "visibility": "off"
                }]
            },
            {
                "featureType": "transit",
                "stylers": [{
                    "visibility": "off"
                }]
            }
        ];

        if (jQuery("body").hasClass("dark")) {
            lddfw_dark_mode_style = [{
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#242f3e"
                    }]
                },
                {
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#746855"
                    }]
                },
                {
                    "elementType": "labels.text.stroke",
                    "stylers": [{
                        "color": "#242f3e"
                    }]
                },
                {
                    "featureType": "administrative",
                    "elementType": "geometry",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "administrative.locality",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#d59563"
                    }]
                },
                {
                    "featureType": "poi",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "poi",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#d59563"
                    }]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#263c3f"
                    }]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#6b9a76"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#38414e"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "geometry.stroke",
                    "stylers": [{
                        "color": "#212a37"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "labels.icon",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "road",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#9ca5b3"
                    }]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#746855"
                    }]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry.stroke",
                    "stylers": [{
                        "color": "#1f2835"
                    }]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#f3d19c"
                    }]
                },
                {
                    "featureType": "transit",
                    "stylers": [{
                        "visibility": "off"
                    }]
                },
                {
                    "featureType": "transit",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#2f3948"
                    }]
                },
                {
                    "featureType": "transit.station",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#d59563"
                    }]
                },
                {
                    "featureType": "water",
                    "elementType": "geometry",
                    "stylers": [{
                        "color": "#17263c"
                    }]
                },
                {
                    "featureType": "water",
                    "elementType": "labels.text.fill",
                    "stylers": [{
                        "color": "#515c6d"
                    }]
                },
                {
                    "featureType": "water",
                    "elementType": "labels.text.stroke",
                    "stylers": [{
                        "color": "#17263c"
                    }]
                }
            ];
        }
        return lddfw_dark_mode_style;
    }


    function lddfw_delivery_timer() {

        

    }



    if ( jQuery("#lddfw_page").hasClass("order") ) {
        if ( jQuery("#google_map").length ) {
          jQuery("body").append(
            "<script " +
              "id='lddfw_google_map_script' " +
              "async defer " +
              "src='https://maps.googleapis.com/maps/api/js" +
                "?key="   + lddfw_google_api_key   +
                "&language=" + lddfw_map_language +
                "&callback=lddfw_order_map"        +
                "&loading=async"                   +  
            "'></script>"
          );
        }
        lddfw_delivery_timer();
      }
      

    

/**
 * Initialize the review widget interactions.
 */
function lddfw_select_review_emoji($option) {
    if (!$option || !$option.length) return;
    var rating = parseInt($option.data('rating'), 10);
    if (!(rating >= 1 && rating <= 5)) return;

    jQuery('#lddfw_review_rating').val(rating);

    jQuery('.lddfw_review_emoji_option')
        .removeClass('lddfw_review_emoji_selected')
        .attr('aria-checked', 'false');
    $option.addClass('lddfw_review_emoji_selected').attr('aria-checked', 'true');

    jQuery('#lddfw_review_comment_wrap').slideDown(300);
    jQuery('#lddfw_review_submit').prop('disabled', false);
}

function lddfw_update_review_counter() {
    var $ta = jQuery('#lddfw_review_comment');
    if (!$ta.length) return;
    var $cur = jQuery('#lddfw_review_counter_current');
    if (!$cur.length) return;
    var max = parseInt($ta.attr('maxlength'), 10) || 500;
    var len = ($ta.val() || '').length;
    $cur.text(len);
    var $wrap = $cur.closest('.lddfw_review_counter');
    if (len >= max - 20) {
        $wrap.addClass('is-near-limit');
    } else {
        $wrap.removeClass('is-near-limit');
    }
}

function lddfw_init_review_widget() {
    jQuery('.lddfw_review_emoji_option').off('click.lddfwReview keydown.lddfwReview')
        .on('click.lddfwReview', function() {
            lddfw_select_review_emoji(jQuery(this));
        })
        .on('keydown.lddfwReview', function(e) {
            if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
                e.preventDefault();
                lddfw_select_review_emoji(jQuery(this));
            }
        });

    jQuery('#lddfw_review_comment').off('input.lddfwReview').on('input.lddfwReview', lddfw_update_review_counter);
    lddfw_update_review_counter();

    jQuery('#lddfw_review_submit').off('click').on('click', function() {
        var $btn = jQuery(this);
        if ($btn.prop('disabled')) return;

        var origText = $btn.text();
        $btn.prop('disabled', true).text('...');

        var data = {
            action: 'lddfw_ajax',
            lddfw_service: 'lddfw_submit_review',
            lddfw_data_type: 'json',
            lddfw_wpnonce: lddfw_nonce.nonce,
            lddfw_review_order_id: jQuery('#lddfw_review_order_id').val(),
            lddfw_review_rating: jQuery('#lddfw_review_rating').val(),
            lddfw_review_comment: jQuery('#lddfw_review_comment').val()
        };

        jQuery.ajax({
            type: 'POST',
            url: lddfw_ajax_url,
            data: data,
            success: function(response) {
                try {
                    var res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.result === '1' || res.result === 1) {
                        jQuery('#lddfw_review_form').fadeOut(300, function() {
                            var ratingVal = parseInt(jQuery('#lddfw_review_rating').val(), 10);
                            var ratingClass = (ratingVal >= 1 && ratingVal <= 5) ? ' lddfw-review-comment-bubble--r' + ratingVal : '';
                            var rawComment = jQuery('#lddfw_review_comment').val();
                            var commentHtml = '';
                            if (rawComment && String(rawComment).trim()) {
                                var safeComment = jQuery('<div/>').text(String(rawComment).trim()).html();
                                commentHtml = '<div class="lddfw-review-comment-bubble' + ratingClass + '"><span class="lddfw-review-comment-bubble__text">&ldquo;' + safeComment + '&rdquo;</span></div>';
                            }
                            var $selectedSvg = jQuery('.lddfw_review_emoji_option.lddfw_review_emoji_selected .lddfw_review_emoji_icon svg').clone();
                            var ratingHtml = '';
                            if ($selectedSvg.length) {
                                $selectedSvg.attr('style', 'width:72px;height:72px;');
                                var selectedLabel = jQuery('.lddfw_review_emoji_option.lddfw_review_emoji_selected .lddfw_review_emoji_label').text();
                                var labelHtml = selectedLabel ? '<div class="lddfw-review-single__label">' + jQuery('<div/>').text(selectedLabel).html() + '</div>' : '';
                                var singleClass = 'lddfw-review-single lddfw-review-single--lg' + ((ratingVal >= 1 && ratingVal <= 5) ? ' lddfw-review-single--r' + ratingVal : '');
                                ratingHtml = '<div class="lddfw_review_submitted_rating">' +
                                    '<div class="' + singleClass + '">' +
                                        '<div class="lddfw-review-single__icon" style="width:72px;height:72px;">' + $selectedSvg.prop('outerHTML') + '</div>' +
                                        labelHtml +
                                    '</div>' +
                                '</div>';
                            }
                            var thankHtml = '<div class="lddfw_review_thankyou">' +
                                '<h2>Thanks for your feedback!</h2>' +
                                ratingHtml +
                                commentHtml +
                                '</div>';
                            jQuery(this).html(thankHtml).fadeIn(400);
                            jQuery('#lddfw_review_skip_link').text('Back to store');
                        });
                    } else {
                        $btn.prop('disabled', false).text(origText);
                        if (res.error && typeof lddfw_show_toast === "function") {
                            lddfw_show_toast(String(res.error), "danger");
                        }
                    }
                } catch(e) {
                    $btn.prop('disabled', false).text(origText);
                }
            },
            error: function() {
                $btn.prop('disabled', false).text(origText);
            }
        });
    });
}

jQuery(document).ready(function() {
    if (typeof lddfw_review_mode !== 'undefined' && lddfw_review_mode === true) {
        lddfw_init_review_widget();
    }

    jQuery(document).on('click', '#lddfw_review_edit_btn', function(e) {
        e.preventDefault();
        jQuery('#lddfw_review_thankyou').slideUp(300, function() {
            jQuery('#lddfw_review_form').slideDown(300);
        });
    });

    jQuery(document).on('click', '#lddfw_review_cancel_edit', function(e) {
        e.preventDefault();
        jQuery('#lddfw_review_form').slideUp(300, function() {
            jQuery('#lddfw_review_thankyou').slideDown(300);
        });
    });
});