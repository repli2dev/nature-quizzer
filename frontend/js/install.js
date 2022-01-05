$(document).on('click', '.install', event => {
    const el = $('.install');
    if (!el || !el[0]) {
        return;
    }

    if (isIOS()) {
        alert(el[0].dataset.ios);
    }
    if (isAndroid()) {
        alert(el[0].dataset.android);
    }
});

function isIOS() {
    const toMatch = [
        /iPhone/i,
        /iPad/i,
        /iPod/i,
    ];

    return toMatch.some((toMatchItem) => {
        return navigator.userAgent.match(toMatchItem);
    });
}

function isAndroid() {
    const toMatch = [
        /Android/i,
        /BlackBerry/i,
    ];

    return toMatch.some((toMatchItem) => {
        return navigator.userAgent.match(toMatchItem);
    });
}

function showInstallButton() {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }
    if (isIOS() || isAndroid()) {
        $('.install').css('display', 'inline');
    }
}

var waitForEl = function(selector, callback) {
    if (jQuery(selector).length) {
        callback();
    } else {
        setTimeout(function() {
            waitForEl(selector, callback);
        }, 100);
    }
};

waitForEl('.install', function() {
    showInstallButton();
});
