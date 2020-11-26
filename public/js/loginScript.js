let bearerToken = readCookie('bearerToken');
let refreshToken = readCookie('refreshToken');

if (bearerToken !== null){
    $.ajax({
        type: "POST",
        url: "/api/is_token_valid",
        beforeSend: function(xhr){
            xhr.setRequestHeader('Authorization', 'Bearer '+bearerToken);
            xhr.setRequestHeader('Accept', 'application/json');
        }
    })
    .done(function(data){
        location.href = "/";
    })
    .fail(function(err) {
            $.ajax({
                type: "POST",
                url: "/api/refresh_token",
                data: "refresh_token="+refreshToken,
            })
            .done(function(){
                $.ajax({
                    type: "POST",
                    url: "/api/is_token_valid",
                    beforeSend: function(xhr){
                        xhr.setRequestHeader('Authorization', 'Bearer '+bearerToken);
                        xhr.setRequestHeader('Accept', 'application/json');
                    },
                })
                .done(function(data){
                    createCookie('bearerToken', data.access_token, 15);
                    createCookie('refreshToken', data.refresh_token, 15);
                    location.href = "/";
                })
                .fail(function(){
                    eraseCookie('bearerToken');
                    eraseCookie('refreshToken');
                });
            });
    });
}

$(function () {
    $(".alert").on("click", function(){
        $(this).fadeOut();
        $(this).contents().filter(function() {
            return this.nodeType == 3; //Node.TEXT_NODE
        }).remove();
    })
    $("#loginForm").submit(function(e) {
        e.preventDefault();
        let form = $(this);
        
        $.ajax({
            type: "POST",
            url: "/api/login",
            data: form.serialize(),
            success: function(data){
                createCookie('bearerToken', data.access_token, 15);
                createCookie('refreshToken', data.refresh_token, 15);
                location.href = "/";
            },
            error: function(err) {
                console.log(JSON.stringify(err));
                $(".alert").fadeIn();
                $(".alert").prepend(err["responseJSON"].reason);
            }
        });
            
    });

});

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    }
    else var expires = "";               

    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}