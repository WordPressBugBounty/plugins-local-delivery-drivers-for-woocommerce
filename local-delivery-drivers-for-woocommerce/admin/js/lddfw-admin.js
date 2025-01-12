jQuery(document).ready(
    function($) {


        $("body").on("click", "#lddfw_check_google_keys", function() {

            var lddfw_loading = $(this).attr("data-loading");
            var lddfw_title = $(this).attr("data-title");
            var lddfw_alert = $(this).attr("data-alert");
            var lddfw_google_api_key = $("#lddfw_google_api_key").val();
            var lddfw_google_api_key_server = $("#lddfw_google_api_key_server").val();

            $("#lddfw_check_google_keys_wrap").show();
            if (lddfw_google_api_key == "" || lddfw_google_api_key_server == "") {
                $("#lddfw_check_google_keys_wrap").html(lddfw_alert);
                return false;
            }

            $("#lddfw_check_google_keys_wrap").html(lddfw_loading);
            $.post(
                lddfw_ajax.ajaxurl, {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_check_google_keys',
                    lddfw_obj_id: lddfw_google_api_key_server,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                },
                function(data) {
                    $("#lddfw_check_google_keys_wrap").html("");
                    $("#lddfw_check_google_keys_wrap").append('<p class="title">' + lddfw_title + ' <b>' + lddfw_google_api_key_server + '</b></p>');
                    $("#lddfw_check_google_keys_wrap").append(data);

                    $("#lddfw_check_google_keys_wrap").append('<p class="title">' + lddfw_title + ' <b>' + lddfw_google_api_key + '</b></p>');
                    $("#lddfw_check_google_keys_wrap").append('<p>Maps Embed API:</p><iframe width="450" height="250" style="border:0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps/embed/v1/place?key=' + lddfw_google_api_key + '&q=chicago+il"></iframe>');
                    $("#lddfw_check_google_keys_wrap").append('<p>Maps JavaScript API:</p><div style="width:450px;height:250px;" id="ddfw_test_map"></div><script src="https://maps.googleapis.com/maps/api/js?key=' + lddfw_google_api_key + '&callback=initMap&v=weekly" defer></script>');


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
                                $("#lddfw_check_google_keys_wrap").append('<p>Directions API: OK');
                            } else {
                                $("#lddfw_check_google_keys_wrap").append('<p>Directions API:' + status);
                            }
                        });

                        var geocoder = new google.maps.Geocoder();
                        var address = 'indiana, in';
                        geocoder.geocode({ 'address': address }, function(results, status) {
                            if (status == 'OK') {
                                $("#lddfw_check_google_keys_wrap").append('<p>Geocoding API: OK');
                                map.setCenter(results[0].geometry.location);
                                var marker = new google.maps.Marker({
                                    map: map,
                                    position: results[0].geometry.location
                                });
                            } else {
                                $("#lddfw_check_google_keys_wrap").append('<p>Geocoding API: ' + status);

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
        $("body").on("click", ".lddfw_star_button", function() {
            if ($(this).next().is(":visible")) {
                $(this).next().hide();
            } else {
                $(".lddfw_premium_feature_note").hide();
                $(this).next().show();
            }
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

        

    }
);

