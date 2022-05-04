/**
 *
 * @param target
 * @param value
 */
function callPlaces(target, value, idn = 'payment') {
    $.ajax({
        url: 'index.php?route=checkout/checkout/places&' + target + '=' + value,
        dataType: 'json',
        success: function(json) {
            console.log(json);

            drawPlaces(target, json, idn);
        },
        error: function(xhr, ajaxOptions, thrownError) {
            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });
}

/**
 *
 * @param target
 * @param json
 */
function drawPlaces(target, json, idn) {
    let html = '';
    let naziv = '';
    let link = '';
    let obj = Object.keys(json);

    console.log(target)

    for (let i = 0; i < obj.length; i++) {
        console.log(json[obj[i]].cityname);

        if (target == 'city') {
            naziv = '<strong>' + json[obj[i]].cityname + '</strong> ' + json[obj[i]].zipcode;
        } else {
            naziv = '<strong>' + json[obj[i]].zipcode + '</strong> ' + json[obj[i]].cityname;
        }

        link = "selectPlace('" + json[obj[i]].cityname + "', '" + json[obj[i]].zipcode + "', '" + idn + "');";

        if (i == (obj.length - 1)) {
            html += '<a href="javascript:void(0);" onclick="' + link + '" class="dropdown-item">' + naziv + '</a>';
        } else {
            html += '<a href="javascript:void(0);" onclick="' + link + '" class="dropdown-item">' + naziv + '</a><div class="dropdown-divider"></div>';
        }
    }

    document.getElementById(idn + '-' + target + '-drop').style['display'] = 'block';
    $('#' + idn + '-' + target + '-drop').html(html);
}

/**
 *
 * @param naziv
 * @param zip
 */
function selectPlace(naziv, zip, idn) {
    console.log(naziv, zip, idn)

    $('#input-' + idn + '-city').val(naziv);
    $('#input-' + idn + '-postcode').val(zip);
}