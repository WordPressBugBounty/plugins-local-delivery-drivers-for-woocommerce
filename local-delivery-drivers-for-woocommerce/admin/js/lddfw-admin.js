jQuery(document).ready(
    function($) {

        $("body").on("click", ".lddfw-copy-tracking-url", function(e) {
            e.preventDefault();
            var url = $(this).data("url");
            var $btn = $(this);
            var $tooltip = $btn.find(".lddfw-copy-tooltip");

            function showCopied() {
                $btn.css({  "color": "#46b450" });
                $tooltip.fadeIn(150);
                setTimeout(function() {
                    $tooltip.fadeOut(150);
                    $btn.css({  "color": "#666" });
                }, 1500);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(showCopied);
            } else {
                var $temp = $("<input>");
                $("body").append($temp);
                $temp.val(url).select();
                document.execCommand("copy");
                $temp.remove();
                showCopied();
            }
        });

        $("body").on("click", ".lddfw_banner_dismiss", function() {
            var $banner = $(this).closest(".lddfw_sms_cta_banner");
            var bannerType = $banner.data("banner");
            if (!bannerType) return;
            $banner.fadeOut(300, function() { $(this).remove(); });
            $.post(lddfw_ajax.ajaxurl, {
                action: "lddfw_dismiss_banner",
                banner: bannerType,
                nonce: lddfw_nonce.nonce
            });
        });

        // Check if elements with the class 'lddfw_tagify' exist
        if ($('.lddfw_tagify').length > 0) {
            // Iterate over each element and initialize Tagify
            $('.lddfw_tagify').each(function() {
                new Tagify(this); // 'this' refers to the current DOM element in the loop
            });
        }

        $("body").on("click", "#lddfw_check_google_keys", function() {

            var lddfw_loading = $(this).attr("data-loading");
            var lddfw_title = $(this).attr("data-title");
            var lddfw_alert = $(this).attr("data-alert");
            var lddfw_google_api_key = $("#lddfw_google_api_key").val();
            var lddfw_google_api_key_server = $("#lddfw_google_api_key_server").val();
            var $wrap = $("#lddfw_check_google_keys_wrap");

            // Builds a single result row (label + OK/ERROR badge).
            function lddfw_gtest_row(label, status) {
                var isOk = (typeof status === "string" && status.toUpperCase().indexOf("OK") === 0);
                var cls = isOk ? "is-ok" : "is-error";
                var badgeText = isOk ? "OK" : status;
                return '<div class="lddfw-gtest-row ' + cls + '">' +
                    '<span class="lddfw-gtest-row__label">' + label + '</span>' +
                    '<span class="lddfw-gtest-row__badge">' + $("<div>").text(badgeText).html() + '</span>' +
                    '</div>';
            }

            // Builds a group block (key title + rows container).
            function lddfw_gtest_group(titleText, keyValue) {
                return '<div class="lddfw-gtest-group">' +
                    '<div class="lddfw-gtest-group__title">' +
                        '<span class="dashicons dashicons-admin-network"></span>' +
                        '<span class="lddfw-gtest-group__title-text">' + titleText + '</span>' +
                        '<code class="lddfw-gtest-group__key">' + $("<div>").text(keyValue).html() + '</code>' +
                    '</div>' +
                    '<div class="lddfw-gtest-group__body"></div>' +
                '</div>';
            }

            // Parse the server-returned HTML (paragraphs like "<p>Directions API: OK</p>")
            // into structured result rows.
            function lddfw_parse_server_rows(serverHtml) {
                var rows = "";
                var $tmp = $("<div>").html(serverHtml);
                $tmp.find("p").each(function() {
                    var text = $(this).text();
                    var idx = text.indexOf(":");
                    var label = idx > -1 ? text.substr(0, idx).trim() : text.trim();
                    var status = idx > -1 ? text.substr(idx + 1).trim() : "";
                    rows += lddfw_gtest_row(label, status);
                });
                return rows;
            }

            $wrap.addClass("lddfw-gtest").show();
            if (lddfw_google_api_key == "" || lddfw_google_api_key_server == "") {
                $wrap.html('<div class="lddfw-gtest-notice">' + $("<div>").text(lddfw_alert).html() + '</div>');
                return false;
            }

            $wrap.html('<div class="lddfw-gtest-loading">' + lddfw_loading + '</div>');
            $.post(
                lddfw_ajax.ajaxurl, {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_check_google_keys',
                    lddfw_obj_id: lddfw_google_api_key_server,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                },
                function(data) {
                    var serverGroup = lddfw_gtest_group(lddfw_title, lddfw_google_api_key_server);
                    var browserGroup = lddfw_gtest_group(lddfw_title, lddfw_google_api_key);

                    $wrap.html(
                        '<div class="lddfw-gtest-header">' +
                            '<span class="dashicons dashicons-yes-alt"></span>' +
                            '<span>' + (window.lddfw_admin_i18n && window.lddfw_admin_i18n.googleApiResults ? window.lddfw_admin_i18n.googleApiResults : 'Google API Test Results') + '</span>' +
                        '</div>' +
                        serverGroup +
                        browserGroup
                    );

                    // Server key rows (parsed from AJAX HTML).
                    $wrap.find(".lddfw-gtest-group").eq(0).find(".lddfw-gtest-group__body")
                        .html(lddfw_parse_server_rows(data));

                    // Browser key section: API rows + live map previews.
                    var $browserBody = $wrap.find(".lddfw-gtest-group").eq(1).find(".lddfw-gtest-group__body");
                    $browserBody.html(
                        '<div class="lddfw-gtest-rows-js"></div>' +
                        '<div class="lddfw-gtest-maps">' +
                            '<div class="lddfw-gtest-map-card">' +
                                '<div class="lddfw-gtest-map-card__title">' + (window.lddfw_admin_i18n && window.lddfw_admin_i18n.mapsEmbedApi ? window.lddfw_admin_i18n.mapsEmbedApi : 'Maps Embed API') + '</div>' +
                                '<iframe width="100%" height="250" style="border:0;border-radius:6px;" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps/embed/v1/place?key=' + encodeURIComponent(lddfw_google_api_key) + '&q=chicago+il"></iframe>' +
                            '</div>' +
                            '<div class="lddfw-gtest-map-card">' +
                                '<div class="lddfw-gtest-map-card__title">' + (window.lddfw_admin_i18n && window.lddfw_admin_i18n.mapsJsApi ? window.lddfw_admin_i18n.mapsJsApi : 'Maps JavaScript API') + '</div>' +
                                '<div id="ddfw_test_map" style="width:100%;height:250px;border-radius:6px;"></div>' +
                            '</div>' +
                        '</div>'
                    );

                    // Inject the JS API loader after the map containers exist.
                    var mapsScript = document.createElement('script');
                    mapsScript.src = "https://maps.googleapis.com/maps/api/js?key=" + encodeURIComponent(lddfw_google_api_key) + "&callback=initMap&v=weekly";
                    mapsScript.defer = true;
                    document.body.appendChild(mapsScript);

                    function initMap() {

                        var directionsService = new google.maps.DirectionsService;
                        var directionsDisplay = new google.maps.DirectionsRenderer;
                        var map = new google.maps.Map(document.getElementById('ddfw_test_map'), {
                            zoom: 8,
                            center: { lat: 41.85, lng: -87.65 }
                        });
                        directionsDisplay.setMap(map);

                        directionsService.route({
                            origin: 'oklahoma city, ok',
                            destination: 'chicago, il',
                            travelMode: 'DRIVING'
                        }, function(response, status) {
                            if (status === 'OK') {
                                directionsDisplay.setDirections(response);
                                $browserBody.find(".lddfw-gtest-rows-js").append(lddfw_gtest_row("Directions API", "OK"));
                            } else {
                                $browserBody.find(".lddfw-gtest-rows-js").append(lddfw_gtest_row("Directions API", status));
                            }
                        });

                        var geocoder = new google.maps.Geocoder();
                        var address = 'indiana, in';
                        geocoder.geocode({ 'address': address }, function(results, status) {
                            if (status == 'OK') {
                                $browserBody.find(".lddfw-gtest-rows-js").append(lddfw_gtest_row("Geocoding API", "OK"));
                                map.setCenter(results[0].geometry.location);
                                var marker = new google.maps.Marker({
                                    map: map,
                                    position: results[0].geometry.location
                                });
                            } else {
                                $browserBody.find(".lddfw-gtest-rows-js").append(lddfw_gtest_row("Geocoding API", status));
                            }
                        });

                    }

                    window.initMap = initMap;

                }
            );
            return false;
        });

        $("body").on("click", ".lddfw_premium_close", function() {
            $(this).parent().hide();
            return false;
        });
        // Link each star button to its own note when the page is first
        // interacted with. We move the note up to <body> so no ancestor's
        // overflow:hidden / transform / filter / etc. can clip or re-anchor
        // the position:fixed bubble.
        function lddfwGetOrAttachNote($star) {
            var noteId = $star.data("lddfwNoteId");
            if (noteId) {
                var $existing = $("#" + noteId);
                if ($existing.length) {
                    return $existing;
                }
            }
            // Prefer siblings() so a whitespace-only text node between </a> and the
            // note <div> (common in PHP output) does not break .next(selector), which
            // only considers the *immediate* next sibling.
            var $note = $star.siblings(".lddfw_premium_feature_note").first();
            if (!$note.length) {
                $note = $star.next(".lddfw_premium_feature_note");
            }
            if (!$note.length) {
                return $();
            }
            var newId = "lddfw_pn_" + Math.random().toString(36).substr(2, 9);
            $note.attr("id", newId);
            $star.data("lddfwNoteId", newId);
            // Detach from its original parent (which may have overflow:hidden
            // or a transform that breaks position:fixed) and reparent to body.
            $note.appendTo(document.body);
            return $note;
        }

        $("body").on("click", ".lddfw_star_button", function() {
            var $star = $(this);
            var $note = lddfwGetOrAttachNote($star);
            if (!$note.length) {
                return false;
            }
            if ($note.is(":visible")) {
                $note.hide();
                return false;
            }
            $(".lddfw_premium_feature_note").hide();
            // Force position:fixed inline (beats any cached external CSS)
            // and pre-show off-screen so we can measure the bubble.
            $note.css({
                position: "fixed",
                top: "-9999px",
                left: "-9999px",
                margin: 0,
                visibility: "hidden",
                display: "block",
                zIndex: 200000
            });
            var starRect = this.getBoundingClientRect();
            var noteW = $note.outerWidth();
            var noteH = $note.outerHeight();
            var margin = 10;
            var viewportW = document.documentElement.clientWidth || $(window).width();
            var viewportH = document.documentElement.clientHeight || $(window).height();
            // Default: center the bubble above the star.
            var left = starRect.left + (starRect.width / 2) - (noteW / 2);
            var top = starRect.top - noteH - 14;
            // If not enough room above, show below the star.
            if (top < margin) {
                top = starRect.bottom + 14;
            }
            // Clamp horizontally inside the viewport.
            if (left < margin) { left = margin; }
            if (left + noteW > viewportW - margin) { left = viewportW - noteW - margin; }
            // Clamp vertically inside the viewport as a last resort.
            if (top + noteH > viewportH - margin) {
                top = Math.max(margin, viewportH - noteH - margin);
            }
            $note.css({
                top: Math.round(top) + "px",
                left: Math.round(left) + "px",
                visibility: "visible",
                zIndex: 200000
            });
            return false;
        });

        function lddfw_dates_range() {
            var $lddfw_this = $("#lddfw_dates_range");
            if ($lddfw_this.val() == "custom") {
                $("#lddfw_dates_custom_range").show();
            } else {
                var lddfw_fromdate = $('option:selected', $lddfw_this).attr('fromdate');
                var lddfw_todate = $('option:selected', $lddfw_this).attr('todate');
                $("#lddfw_dates_custom_range").hide();
                $("#lddfw_dates_range_from").val(lddfw_fromdate);
                $("#lddfw_dates_range_to").val(lddfw_todate);
            }
        }

        $("#lddfw_dates_range").change(
            function() {
                lddfw_dates_range()
            }
        );

        if ($("#lddfw_dates_range").length) {
            lddfw_dates_range();
        }

        if ($(".lddfw-datepicker").length) {
            $(".lddfw-datepicker").datepicker({ dateFormat: "yy-mm-dd" });
        }

        

        // SMS provider field toggle
        function lddfw_toggle_sms_provider_fields() {
            var provider = $('#lddfw_sms_provider').val();
            $('.lddfw-provider-powerfulwp').closest('tr').toggle(provider === 'powerfulwp');
            $('.lddfw-provider-twilio').closest('tr').toggle(provider === 'twilio');
        }

        if ($('#lddfw_sms_provider').length) {
            lddfw_toggle_sms_provider_fields();
            $('#lddfw_sms_provider').on('change', lddfw_toggle_sms_provider_fields);
        }

        var $senderInput = $('#lddfw_sms_api_sender_id');
        if ($senderInput.length) {
            var $counter = $('#lddfw-sender-id-counter');
            var $error = $('#lddfw-sender-id-error');

            function isPowerfulWP() {
                return $('#lddfw_sms_provider').val() === 'powerfulwp';
            }

            function validateSenderId() {
                var val = $senderInput.val();
                var clean = val.replace(/[^A-Za-z0-9]/g, '');
                var errors = [];
                var ai18n = window.lddfw_admin_i18n || {};

                $counter.text(clean.length + '/11');

                if (clean.length === 0 && isPowerfulWP()) {
                    errors.push(ai18n.senderIdRequired || 'Sender ID is required for the PowerfulWP provider.');
                }
                if (val.length > 0 && val !== clean) {
                    errors.push(ai18n.senderIdCharsOnly || 'Only letters (A-Z) and numbers (0-9) are allowed. No spaces or special characters.');
                }
                if (clean.length > 11) {
                    errors.push(ai18n.senderIdMaxLen || 'Maximum 11 characters allowed.');
                }

                if (errors.length > 0) {
                    $error.html(errors.join('<br>')).show();
                    $senderInput.css('border-color', '#d63638');
                    $counter.css('color', '#d63638');
                } else {
                    $error.hide();
                    $senderInput.css('border-color', '');
                    $counter.css('color', '#666');
                }
            }

            $senderInput.on('input keyup', function() {
                var val = $(this).val();
                var clean = val.replace(/[^A-Za-z0-9]/g, '');
                if (val !== clean) {
                    $(this).val(clean);
                }
                if (clean.length > 11) {
                    $(this).val(clean.substring(0, 11));
                }
                validateSenderId();
            });

            $('#lddfw_sms_provider').on('change', validateSenderId);
            validateSenderId();
        }

        $("body").on("click",".lddf_button_toggle",
            function() {
                 $(this).next().toggle();
                return false;
            }
        );


        function checkbox_toggle(element) {
            if (!element.is(':checked')) {
                element.parent().next().hide();
            } else {
                element.parent().next().show();
            }

        }

        $(".checkbox_toggle input").click(
            function() {
                checkbox_toggle($(this))

            }
        );

        $(".checkbox_toggle input").each(
            function() {
                checkbox_toggle($(this))
            }
        );

        function lddfw_select_toggle(lddfw_toggle_select) {
            var lddfw_toggle_select_value = lddfw_toggle_select.val();
            var lddfw_toggle_select_data_array = lddfw_toggle_select.attr("data").split(',');
            var lddfw_toggle = false;

            $.each(lddfw_toggle_select_data_array, function(key, value) {
                if (value === lddfw_toggle_select_value) {
                    lddfw_toggle = true;
                    return false;
                }
            });

            if (lddfw_toggle) {
                lddfw_toggle_select.parent().next().show();
            } else {
                lddfw_toggle_select.parent().next().hide();
            }
        }

        $(".lddfw_toggle_select").change(function() {
            lddfw_select_toggle($(this));
        });

        /*
			$(".lddfw_toggle_select").each(function() {
				lddfw_select_toggle($(this));
			});
		*/

        $(".lddfw_copy_template_to_textarea").click(
            function() {
                var textarea_id = $(this).parent().parent().find("textarea").attr("id");

                var text = $(this).attr("data");
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        $("body").on("click", ".lddfw_copy_tags_to_textarea a", function() {
        
                var textarea_id = $(this).parent().attr("data-textarea");
                var text = $("#" + textarea_id).val() + $(this).attr("data");
              
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        

        /* ================================================================
         * Dashboard Operational Tools
         * ============================================================== */

        // Getting Started checklist - dismiss button
        $("body").on("click", ".lddfw-checklist__dismiss", function() {
            var $card = $(this).closest(".lddfw-checklist");
            $card.slideUp(200, function() { $(this).remove(); });
            $.post(lddfw_ajax.ajaxurl, {
                action: "lddfw_dismiss_checklist",
                nonce: lddfw_nonce.nonce
            });
        });

        // Driver panel QR - Copy link
        $("body").on("click", ".lddfw-qr-copy", function() {
            var $btn = $(this);
            var $input = $btn.closest(".lddfw-qr-card").find(".lddfw-qr-card__url");
            var val = $input.val();
            function flash() {
                var orig = $btn.text();
                $btn.text($btn.data("copied-text") || (window.lddfw_admin_i18n && window.lddfw_admin_i18n.copied ? window.lddfw_admin_i18n.copied : "Copied!"));
                setTimeout(function() { $btn.text(orig); }, 1500);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(val).then(flash, function() {
                    $input.select(); document.execCommand("copy"); flash();
                });
            } else {
                $input.select(); document.execCommand("copy"); flash();
            }
        });

        // Driver panel QR - Download as SVG
        $("body").on("click", ".lddfw-qr-download", function() {
            var $svg = $(this).closest(".lddfw-qr-card").find(".lddfw-qr-card__image svg");
            if (!$svg.length) return;
            var serializer = new XMLSerializer();
            var xml = serializer.serializeToString($svg.get(0));
            var blob = new Blob([xml], { type: "image/svg+xml;charset=utf-8" });
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            a.href = url;
            a.download = "driver-panel-qr.svg";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function() { URL.revokeObjectURL(url); }, 200);
        });

        // Broadcast-to-drivers widget was removed from the Dashboard in 2.3.x.
        // The corresponding DOM no longer exists, and AJAX endpoints are
        // unregistered, so no JS handlers are needed here.

        // Driver panel quick-links - toggle the collapsible QR panel.
        $("body").on("click", ".lddfw-qr-toggle", function() {
            var $btn = $(this);
            var panelId = $btn.attr("aria-controls") || "lddfw-qr-panel";
            var $panel = $("#" + panelId);
            if (!$panel.length) { return; }
            var expanded = $btn.attr("aria-expanded") === "true";
            $btn.attr("aria-expanded", expanded ? "false" : "true");
            if (expanded) {
                $panel.attr("hidden", "hidden");
            } else {
                $panel.removeAttr("hidden");
            }
        });

    }
);



/* =========================================================================
 * Drivers & Applications admin page
 * ========================================================================= */
jQuery(function ($) {
    if (typeof window.lddfwDriversPage === 'undefined') {
        return;
    }

    var cfg = window.lddfwDriversPage;
    var $driverModal = $('#lddfw-driver-modal');
    var $appModal = $('#lddfw-application-modal');
    if (!$driverModal.length && !$appModal.length) {
        return;
    }

    function lddfwAdminBarHeight() {
        var $bar = $('#wpadminbar');
        if (!$bar.length || !$bar.is(':visible')) {
            return 0;
        }
        return Math.ceil($bar.outerHeight());
    }

    function lddfwDriverDialogPosition() {
        var topGap = Math.max(12, lddfwAdminBarHeight() + 12);
        return {
            my: 'center top',
            at: 'center top+' + topGap,
            of: window,
            collision: 'fit'
        };
    }

    function lddfwDriverDialogMaxHeight() {
        return Math.max(280, $(window).height() - lddfwAdminBarHeight() - 48);
    }

    function lddfwDriverDialogReposition() {
        if ($driverModal.length && $driverModal.hasClass('ui-dialog-content')) {
            $driverModal.dialog('option', 'position', lddfwDriverDialogPosition());
            $driverModal.dialog('option', 'maxHeight', lddfwDriverDialogMaxHeight());
        }
        if ($appModal.length && $appModal.hasClass('ui-dialog-content')) {
            $appModal.dialog('option', 'position', lddfwDriverDialogPosition());
            $appModal.dialog('option', 'maxHeight', lddfwDriverDialogMaxHeight());
        }
    }

    function lddfwBindDriverDialogLayout() {
        $(window).on('resize.lddfwDriverModal', lddfwDriverDialogReposition);
    }

    function lddfwUnbindDriverDialogLayout() {
        $(window).off('resize.lddfwDriverModal', lddfwDriverDialogReposition);
    }

    function showNotice(message, type) {
        type = type || 'success';
        var $wrap = $('.lddfw-drivers-page').first();
        if (!$wrap.length) { return; }
        var dismissLabel = (window.lddfw_admin_i18n && window.lddfw_admin_i18n.dismiss) ? window.lddfw_admin_i18n.dismiss : 'Dismiss';
        var $n = $('<div class="notice notice-' + type + ' is-dismissible lddfw-inline-notice"><p></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' + $('<div/>').text(dismissLabel).html() + '</span></button></div>');
        $n.find('p').text(message);
        $wrap.find('.wp-header-end').after($n);
        $n.on('click', '.notice-dismiss', function () { $n.remove(); });
        window.setTimeout(function () { $n.fadeOut(400, function () { $n.remove(); }); }, 6000);
    }

    function openDriverModal(driverId) {
        if (!$driverModal.length) {
            return;
        }
        driverPhotoFrame = null;
        if ($appModal.length && $appModal.hasClass('ui-dialog-content')) {
            $appModal.dialog('close');
        }
        if ($driverModal.hasClass('ui-dialog-content')) {
            $driverModal.dialog('close');
        }
        $driverModal.html('<p class="lddfw-driver-modal-loading"><span class="spinner is-active"></span> ' + $.trim(cfg.i18n.saving) + '</p>');
        $driverModal.dialog({
            title: driverId ? cfg.i18n.edit : cfg.i18n.addNew,
            modal: true,
            width: Math.min(720, Math.max(320, $(window).width() - 48)),
            maxHeight: lddfwDriverDialogMaxHeight(),
            height: 'auto',
            position: lddfwDriverDialogPosition(),
            appendTo: 'body',
            draggable: false,
            resizable: false,
            dialogClass: 'lddfw-driver-dialog',
            open: function () {
                lddfwBindDriverDialogLayout();
                $('.ui-widget-overlay').last().addClass('lddfw-driver-dialog-overlay');
                lddfwDriverDialogReposition();
            },
            close: function () {
                lddfwUnbindDriverDialogLayout();
                $('.lddfw-driver-dialog-overlay').removeClass('lddfw-driver-dialog-overlay');
                $driverModal.empty();
            }
        });

        $.post(cfg.ajaxUrl, {
            action: 'lddfw_driver_form',
            nonce: cfg.nonce,
            driver_id: driverId || 0
        }).done(function (res) {
            if (res && res.success && res.data && res.data.html) {
                $driverModal.html(res.data.html);
                $driverModal.dialog('option', 'title', res.data.title || cfg.i18n.edit);
            } else {
                $driverModal.html('<p class="notice notice-error" style="padding:10px;">' + (res && res.data && res.data.message ? res.data.message : cfg.i18n.error) + '</p>');
            }
            window.setTimeout(lddfwDriverDialogReposition, 0);
        }).fail(function () {
            $driverModal.html('<p class="notice notice-error" style="padding:10px;">' + cfg.i18n.error + '</p>');
            window.setTimeout(lddfwDriverDialogReposition, 0);
        });
    }

    function openApplicationModal(appId) {
        if (!$appModal.length) {
            return;
        }
        if ($driverModal.length && $driverModal.hasClass('ui-dialog-content')) {
            $driverModal.dialog('close');
        }
        if ($appModal.hasClass('ui-dialog-content')) {
            $appModal.dialog('close');
        }
        var loadMsg = (cfg.i18n.loadingDetails) ? cfg.i18n.loadingDetails : cfg.i18n.saving;
        $appModal.html('<p class="lddfw-driver-modal-loading"><span class="spinner is-active"></span> ' + $.trim(loadMsg) + '</p>');
        $appModal.dialog({
            title: cfg.i18n.applicationDetails || 'Application details',
            modal: true,
            width: Math.min(880, Math.max(320, $(window).width() - 48)),
            maxHeight: lddfwDriverDialogMaxHeight(),
            height: 'auto',
            position: lddfwDriverDialogPosition(),
            appendTo: 'body',
            draggable: false,
            resizable: false,
            dialogClass: 'lddfw-driver-dialog',
            open: function () {
                lddfwBindDriverDialogLayout();
                $('.ui-widget-overlay').last().addClass('lddfw-driver-dialog-overlay');
                lddfwDriverDialogReposition();
            },
            close: function () {
                lddfwUnbindDriverDialogLayout();
                $('.lddfw-driver-dialog-overlay').removeClass('lddfw-driver-dialog-overlay');
                $appModal.empty();
            }
        });

        $.post(cfg.ajaxUrl, {
            action: 'lddfw_application_details',
            nonce: cfg.nonce,
            app_id: appId
        }).done(function (res) {
            if (res && res.success && res.data && res.data.html) {
                $appModal.html(res.data.html);
                $appModal.dialog('option', 'title', res.data.title || cfg.i18n.applicationDetails || '');
            } else {
                $appModal.html('<p class="notice notice-error" style="padding:10px;">' + (res && res.data && res.data.message ? res.data.message : cfg.i18n.error) + '</p>');
            }
            window.setTimeout(lddfwDriverDialogReposition, 0);
        }).fail(function () {
            $appModal.html('<p class="notice notice-error" style="padding:10px;">' + cfg.i18n.error + '</p>');
            window.setTimeout(lddfwDriverDialogReposition, 0);
        });
    }

    // Open modal: application details (Applications tab).
    $(document).on('click', '.lddfw-app-view-details', function (e) {
        e.preventDefault();
        var id = parseInt($(this).data('app-id'), 10) || 0;
        if (id > 0) {
            openApplicationModal(id);
        }
    });

    // Open modal: "Add new driver".
    $(document).on('click', '.lddfw-driver-create', function (e) {
        e.preventDefault();
        openDriverModal(0);
    });

    // Open modal: "Edit driver".
    $(document).on('click', '.lddfw-driver-edit', function (e) {
        e.preventDefault();
        var id = parseInt($(this).data('driver-id'), 10) || 0;
        if (id > 0) {
            openDriverModal(id);
        }
    });

    // Cancel button inside the modal.
    $(document).on('click', '.lddfw-driver-form-cancel', function (e) {
        e.preventDefault();
        if ($driverModal.hasClass('ui-dialog-content')) {
            $driverModal.dialog('close');
        }
    });

    // Save driver from modal.
    $(document).on('submit', '#lddfw-admin-driver-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $submit = $form.find('[type="submit"]');
        var $status = $form.find('.lddfw-driver-form-status');
        $status.empty();
        $submit.prop('disabled', true).data('orig', $submit.val() || $submit.text());
        if ($submit.is('button')) { $submit.text(cfg.i18n.saving); } else { $submit.val(cfg.i18n.saving); }

        var data = $form.serializeArray();
        data.push({ name: 'action', value: 'lddfw_driver_save' });
        data.push({ name: 'nonce', value: cfg.nonce });

        $.post(cfg.ajaxUrl, $.param(data)).done(function (res) {
            if (res && res.success) {
                $driverModal.dialog('close');
                showNotice(res.data && res.data.message ? res.data.message : cfg.i18n.save, 'success');
                window.setTimeout(function () { window.location.reload(); }, 600);
            } else {
                var msg = (res && res.data && res.data.message) ? res.data.message : cfg.i18n.error;
                $status.html('<div class="notice notice-error inline"><p></p></div>').find('p').text(msg);
            }
        }).fail(function () {
            $status.html('<div class="notice notice-error inline"><p>' + cfg.i18n.error + '</p></div>');
        }).always(function () {
            $submit.prop('disabled', false);
            var orig = $submit.data('orig');
            if ($submit.is('button')) { $submit.text(orig); } else { $submit.val(orig); }
        });
    });

 

    // Application row actions.
    $(document).on('click', '.lddfw-app-action', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var appAction = $btn.data('action');
        var appId = parseInt($btn.data('app-id'), 10) || 0;
        if (!appId || !appAction) { return; }

        var reason = '';
        if ('approve' === appAction) {
            if (!window.confirm(cfg.i18n.confirmApprove)) { return; }
        } else if ('reject' === appAction) {
            if (!window.confirm(cfg.i18n.confirmReject)) { return; }
            reason = window.prompt(cfg.i18n.rejectionReason, '');
            if (reason === null) { return; }
        } else if ('delete' === appAction) {
            if (!window.confirm(cfg.i18n.confirmDelete)) { return; }
        }

        var $row = $btn.closest('tr');
        $row.css('opacity', 0.5);

        $.post(cfg.ajaxUrl, {
            action: 'lddfw_application_action',
            nonce: cfg.nonce,
            app_id: appId,
            app_action: appAction,
            reason: reason
        }).done(function (res) {
            if (res && res.success) {
                showNotice(res.data && res.data.message ? res.data.message : cfg.i18n.save, 'success');
                window.setTimeout(function () { window.location.reload(); }, 600);
            } else {
                $row.css('opacity', 1);
                showNotice((res && res.data && res.data.message) ? res.data.message : cfg.i18n.error, 'error');
            }
        }).fail(function () {
            $row.css('opacity', 1);
            showNotice(cfg.i18n.error, 'error');
        });
    });
});
