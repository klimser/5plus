// dynamically change the indentation in the plates under the menu relative to the height of the menu
$(window).bind('resize', function () {
  var $mt = $('.main-menu-block').outerHeight(true);
  if (window.matchMedia("(max-width: 992px)").matches) {
    $('.page-title').css({ 
      'margin-top': 0,
      'padding-top': 19
    });
    $('.main-slider').css({ 
      'margin-top': 0,
    });
  } else {
    $('.page-title').css({ 
      'margin-top': -$mt + 12,
      'padding-top': $mt + 6
    });
    $('.main-slider').css({ 
      'margin-top': -$mt + 12,
    });
  }
}).trigger('resize');

// main slider
var swiper = new Swiper('.swiper-main-slider', {
  speed: 500,
  effect: 'fade',
  spaceBetween: 0,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-main-slider ~ .swiper-button-next',
    prevEl: '.swiper-main-slider ~ .swiper-button-prev',
  },
});

// swiper-preparation-for-admission-to-universities-slider
var swiper = new Swiper('.swiper-preparation-for-admission-to-universities-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 25,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-preparation-for-admission-to-universities-slider ~ .swiper-button-next',
    prevEl: '.swiper-preparation-for-admission-to-universities-slider ~ .swiper-button-prev',
  },
  breakpoints: {
    576: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    992: {
      slidesPerView: 4,
    },
  }
});

// swiper-language-classes-slider
var swiper = new Swiper('.swiper-language-classes-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 22,
  slidesPerView: 2,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-language-classes-slider ~ .swiper-button-next',
    prevEl: '.swiper-language-classes-slider ~ .swiper-button-prev',
  },
  breakpoints: {
    768: {
      slidesPerView: 3,
    },
    992: {
      slidesPerView: 4,
    },
  }
});

// swiper-preparatory-courses-for-students-slider
var swiper = new Swiper('.swiper-preparatory-courses-for-students-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 20,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-preparatory-courses-for-students-slider ~ .swiper-button-next',
    prevEl: '.swiper-preparatory-courses-for-students-slider ~ .swiper-button-prev',
  },
  breakpoints: {
    576: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    992: {
      slidesPerView: 4,
    },
  }
});

// swiper-business-courses-slider
var swiper = new Swiper('.swiper-business-courses-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 13,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-business-courses-slider ~ .swiper-button-next',
    prevEl: '.swiper-business-courses-slider ~ .swiper-button-prev',
  },
  breakpoints: {
    576: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    992: {
      slidesPerView: 4,
    },
  }
});

// swiper-our-specialists-slider
var swiper = new Swiper('.swiper-our-specialists-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 30,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-our-specialists-slider ~ .swiper-button-next',
    prevEl: '.swiper-our-specialists-slider ~ .swiper-button-prev',
  },
  breakpoints: {
    576: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    992: {
      slidesPerView: 4,
      spaceBetween: 45,
    },
  }
});

// swiper-our-specialists-slider
var swiper = new Swiper('.swiper-reviews-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 30,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.swiper-reviews-slider ~ .swiper-button-next',
    prevEl: '.swiper-reviews-slider ~ .swiper-button-prev',
  },
  pagination: {
    el: '.swiper-pagination',
    clickable: true,
  },
  breakpoints: {
    768: {
      slidesPerView: 2,
    },
  }
});

// swiper-received-slider
var swiper = new Swiper('.swiper-received-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 21,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.received-box .swiper-button-next',
    prevEl: '.received-box .swiper-button-prev',
  },
  breakpoints: {
    360: {
      slidesPerView: 2,
    },
    576: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    992: {
      slidesPerView: 3,
    },
  }
});

// swiper-ielts-slider
var swiper = new Swiper('.swiper-ielts-slider', {
  speed: 500,
  loop: true,
  spaceBetween: 21,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.ielts-box .swiper-button-next',
    prevEl: '.ielts-box .swiper-button-prev',
  },
  breakpoints: {
    360: {
      slidesPerView: 2,
    },
    576: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    992: {
      slidesPerView: 3,
    },
  }
});

// Stop autoplay while hovering
$(".swiper-container").hover(function() {
    (this).swiper.autoplay.stop();
}, function() {
    (this).swiper.autoplay.start();
});

// header mobile
$(document).ready(function(){
  $(".btn-menu-open").click(function(){
    $("body").toggleClass("menu-open"); return false;
  });
});
$(document).click( function(event){
  if( $(event.target).closest(".header-box").length ) 
    return;
  $('body').removeClass('menu-open');
  event.stopPropagation();
});

// universities-menu mobile show
$(".universities-menu .title").unbind('click').click(function(){  
    $(this).parent().toggleClass("show");
    $(this).parent().children(".box").slideToggle('fast');
    return false;
});

// collapseing-list show more info
$(".collapseing-list .btn").unbind('click').click(function(){  
    $(this).parent().toggleClass("full-show");
    $(this).parent().children(".full").slideToggle('fast');
    return false;
});

// google map
//google.maps.event.addDomListener(window, 'load', init);
var map, markersArray = [];

function bindInfoWindow(marker, map, location) {
    google.maps.event.addListener(marker, 'click', function() {
        function close(location) {
            location.ib.close();
            location.infoWindowVisible = false;
            location.ib = null;
        }

        if (location.infoWindowVisible === true) {
            close(location);
        } else {
            markersArray.forEach(function(loc, index){
                if (loc.ib && loc.ib !== null) {
                    close(loc);
                }
            });

            var boxText = document.createElement('div');
            boxText.style.cssText = 'background: #fff;';
            boxText.classList.add('md-whiteframe-2dp');

            function buildPieces(location, el, part, icon) {
                if (location[part] === '') {
                    return '';
                } else if (location.iw[part]) {
                    switch(el){
                        case 'photo':
                            if (location.photo){
                                return '<div class="iw-photo" style="background-image: url(' + location.photo + ');"></div>';
                             } else {
                                return '';
                            }
                            break;
                        case 'iw-toolbar':
                            return '<div class="iw-toolbar"><h3 class="md-subhead">' + location.title + '</h3></div>';
                            break;
                        case 'div':
                            switch(part){
                                case 'email':
                                    return '<div class="iw-details"><i class="material-icons" style="color:#4285f4;"><img src="//cdn.mapkit.io/v1/icons/' + icon + '.svg"/></i><span><a href="mailto:' + location.email + '" target="_blank">' + location.email + '</a></span></div>';
                                    break;
                                case 'web':
                                    return '<div class="iw-details"><i class="material-icons" style="color:#4285f4;"><img src="//cdn.mapkit.io/v1/icons/' + icon + '.svg"/></i><span><a href="' + location.web + '" target="_blank">' + location.web_formatted + '</a></span></div>';
                                    break;
                                case 'desc':
                                    return '<label class="iw-desc" for="cb_details"><input type="checkbox" id="cb_details"/><h3 class="iw-x-details">Details</h3><i class="material-icons toggle-open-details"><img src="//cdn.mapkit.io/v1/icons/' + icon + '.svg"/></i><p class="iw-x-details">' + location.desc + '</p></label>';
                                    break;
                                default:
                                    return '<div class="iw-details"><i class="material-icons"><img src="//cdn.mapkit.io/v1/icons/' + icon + '.svg"/></i><span>' + location[part] + '</span></div>';
                                break;
                            }
                            break;
                        case 'open_hours':
                            var items = '';
                            if (location.open_hours.length > 0){
                                for (var i = 0; i < location.open_hours.length; ++i) {
                                    if (i !== 0){
                                        items += '<li><strong>' + location.open_hours[i].day + '</strong><strong>' + location.open_hours[i].hours +'</strong></li>';
                                    }
                                    var first = '<li><label for="cb_hours"><input type="checkbox" id="cb_hours"/><strong>' + location.open_hours[0].day + '</strong><strong>' + location.open_hours[0].hours +'</strong><i class="material-icons toggle-open-hours"><img src="//cdn.mapkit.io/v1/icons/keyboard_arrow_down.svg"/></i><ul>' + items + '</ul></label></li>';
                                }
                                return '<div class="iw-list"><i class="material-icons first-material-icons" style="color:#4285f4;"><img src="//cdn.mapkit.io/v1/icons/' + icon + '.svg"/></i><ul>' + first + '</ul></div>';
                             } else {
                                return '';
                            }
                            break;
                     }
                } else {
                    return '';
                }
            }

            boxText.innerHTML = 
                buildPieces(location, 'photo', 'photo', '') +
                buildPieces(location, 'iw-toolbar', 'title', '') +
                buildPieces(location, 'div', 'address', 'location_on') +
                buildPieces(location, 'div', 'web', 'public') +
                buildPieces(location, 'div', 'email', 'email') +
                buildPieces(location, 'div', 'tel', 'phone') +
                buildPieces(location, 'div', 'int_tel', 'phone') +
                buildPieces(location, 'open_hours', 'open_hours', 'access_time') +
                buildPieces(location, 'div', 'desc', 'keyboard_arrow_down');

            var myOptions = {
                alignBottom: true,
                content: boxText,
                disableAutoPan: true,
                maxWidth: 0,
                pixelOffset: new google.maps.Size(-140, -40),
                zIndex: null,
                boxStyle: {
                    opacity: 1,
                    width: '280px'
                },
                closeBoxMargin: '0px 0px 0px 0px',
                infoBoxClearance: new google.maps.Size(1, 1),
                isHidden: false,
                pane: 'floatPane',
                enableEventPropagation: false
            };

            location.ib = new InfoBox(myOptions);
            location.ib.open(map, marker);
            location.infoWindowVisible = true;
        }
    });
}
function initMap() {
    let mapOptions = {
        center: new google.maps.LatLng(41.296845,69.274355),
        zoom: 15,
        gestureHandling: 'auto',
        fullscreenControl: false,
        zoomControl: false,
        disableDoubleClickZoom: false,
        mapTypeControl: false,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
        },
        scaleControl: false,
        scrollwheel: false,
        streetViewControl: false,
        draggable : true,
        clickableIcons: false,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    let mapElement = document.getElementById('map');
    let map = new google.maps.Map(mapElement, mapOptions);
    let locations = [
        {
            "title":"Пять с плюсом",
            "address":"г. Ташкент, ул. Ойбек, 16",
            "tel":"+998712000350",
            "int_tel":"",
            "email":"5plus.center@gmail.com",
            "web":"https://5plus.uz",
            "web_formatted":"",
            "open":"",
            "time":"",
            "lat":41.296845,
            "lng":69.274355,
            "vicinity":"",
            "open_hours":"",
            "marker":{
                "fillColor":"#22243f",
                "fillOpacity":1,
                "strokeWeight":0,
                "scale":0.11,
                "path":"M256,0C153.755,0,70.573,83.182,70.573,185.426c0,126.888,165.939,313.167,173.004,321.035c6.636,7.391,18.222,7.378,24.846,0c7.065-7.868,173.004-194.147,173.004-321.035C441.425,83.182,358.244,0,256,0z M256,278.719c-51.442,0-93.292-41.851-93.292-93.293S204.559,92.134,256,92.134s93.291,41.851,93.291,93.293S307.441,278.719,256,278.719z",
                "anchor":{"x":250,"y":200},
                "origin":{"x":0,"y":0},
                "style":0
            },
            "iw":{
                "address":false,
                "desc":false,
                "email":false,
                "enable":false,
                "int_tel":false,
                "open":false,
                "open_hours":false,
                "photo":false,
                "tel":false,
                "title":false,
                "web":false
            }
        }
    ];
    for (let i = 0; i < locations.length; i++) {
        let marker = new google.maps.Marker({
            icon: locations[i].marker,
            position: new google.maps.LatLng(locations[i].lat, locations[i].lng),
            map: map,
            title: locations[i].title,
            address: locations[i].address,
            desc: locations[i].desc,
            tel: locations[i].tel,
            int_tel: locations[i].int_tel,
            vicinity: locations[i].vicinity,
            open: locations[i].open,
            open_hours: locations[i].open_hours,
            photo: locations[i].photo,
            time: locations[i].time,
            email: locations[i].email,
            web: locations[i].web,
            iw: locations[i].iw
        });
        markersArray.push(marker);

        if (locations[i].iw.enable === true){
            bindInfoWindow(marker, map, locations[i]);
        }
    }
}
