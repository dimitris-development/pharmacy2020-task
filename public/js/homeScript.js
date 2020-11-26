let bearerToken = readCookie('bearerToken');
let refreshToken = readCookie('refreshToken');

if (bearerToken === null && refreshToken === null){
    location.href = "/login";
}
$(function () {
    $("#logout").on("click", function(){
        logout();
    });

    $.ajax({
        type: "GET",
        url: "/api/get_user_info",
        beforeSend: function(xhr){
            xhr.setRequestHeader('Authorization', 'Bearer '+bearerToken);
            xhr.setRequestHeader('Accept', 'application/json');
        }
    })
    .done(function(data){
        $("h3").append(data.first_name + " " + data.last_name);
    })
    .fail(function(err){
        if(err["responseJSON"].error_message == "The access token is expired"){
            $.ajax({
                type: "POST",
                url: "/api/refresh_token",
                data: "refresh_token="+refreshToken,
            })
            .done(function(){
                createCookie('bearerToken', data.access_token, 15);
                createCookie('refreshToken', data.refresh_token, 15);
            });
        } else {
            eraseCookie('bearerToken');
            eraseCookie('refreshToken');
            location.href = "/login"
        }
    
    });
});




function logout(){
    $.ajax({
        type: "GET",
        url: "/api/logout",
        beforeSend: function(xhr){
            xhr.setRequestHeader('Authorization', 'Bearer '+bearerToken);
            xhr.setRequestHeader('Accept', 'application/json');
        },
    })
    .done(function(data){
        eraseCookie('bearerToken');
        eraseCookie('refreshToken');
        location.href = "/login"
    })
    .fail(function(err){
        if(err["responseJSON"].error_message == "The access token is expired"){
            $.ajax({
                type: "POST",
                url: "/api/refresh_token",
                data: "refresh_token="+refreshToken,
            })
            .done(function(){
                $.ajax({
                    type: "POST",
                    url: "/api/logout",
                    beforeSend: function(xhr){
                        xhr.setRequestHeader('Authorization', 'Bearer '+bearerToken);
                        xhr.setRequestHeader('Accept', 'application/json');
                    },
                })
                .done(function(data){
                    eraseCookie('bearerToken');
                    eraseCookie('refreshToken');
                    location.href = "/login"
                })
            });
        } else {
            eraseCookie('bearerToken');
            eraseCookie('refreshToken');
            location.href = "/login"
        }
    });
}

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

