/*
 * * Purpose: Javascript library for Geolocalization of hosts
 * * Adail Horst / Aristoteles Araujo.
 * *
 * * This program is free software; you can redistribute it and/or modify
 * * it under the terms of the GNU General Public License as published by
 * * the Free Software Foundation; either version 2 of the License, or
 * * (at your option) any later version.
 * *
 * * This program is distributed in the hope that it will be useful,
 * * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * * GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License
 * * along with this program; if not, write to the Free Software
 * * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * */

 function zbxeConsole(msg) {
   console.log('EveryZ - ' + msg);
 }

function drawControler() {
  var toolbar = L.Toolbar();
  toolbar.addToolbar(map);
}

function addMapTile(description, url, attribution, maxZoom) {
  baseMaps[description] = L.tileLayer(url, {
    maxZoom: maxZoom,
    attribution: attribution
  });
}

function dynamicPopUp(element) {
  //marker.bindPopup("Popup content");
  element.on('mouseover', function(e) {
    this.openPopup();
  });
  element.on('mouseout', function(e) {
    this.closePopup();
  });
}

function addHost(lat, lon, hostid, popUpContent) {
  marker = L.marker([lat, lon], {
    icon: zbxImage(hostid)
  }).addTo(ZabGeomap);
  //.bindPopup(popUpContent)
  marker.desc = popUpContent;
  oms.addMarker(marker);
}

function addErrorHost(lat, lon, hostid, popUpContent, severityLevel) {
  var fillColor = severityColors[severityLevel];
  var pulsingIcon = L.icon.pulse({
    iconSize: [14, 14],
    color: fillColor,
    fillColor: fillColor
  });
  L.marker([lat, lon], {
    icon: pulsingIcon,
    //title: popUpContent
  }).addTo(ZabGeomap).bindPopup(popUpContent);
  // Aqui 2
  /*  L.marker([lat, lon], {
      icon: zbxImage(hostid, 42, 42)
    }).addTo(ZabGeomap).bindPopup(popUpContent); */
}

function editHostMetadata(hostid) {
  zbxePopUp("everyz.php?action=zbxe-geometadata&fullscreen=1&hidetitle=1&sourceHostID=" + hostid);
}

function hostLatest(hostid) {
  zbxePopUp("latest.php?fullscreen=0&hostids[]=" + hostid + "&application=&select=&show_without_data=1&fullscreen=1&filter_set=Filter");
}

function hostIncidents(hostid) {
  zbxePopUp("tr_status.php?fullscreen=1&groupid=0&hostid=" + hostid + "&show_triggers=1&ack_status=1&show_events=1&show_severity=0&filter_set=Filter");
}

function hostAvailability(hostid) {
  zbxePopUp("report2.php?config=0&filter_groupid=0&filter_hostid=" + hostid + "&filter_set=1");
}

function addTileLayer(name) {
  return L.tileLayer(mbUrl, {
    id: 'mapbox.' + name,
    attribution: mbAttr
  });
}

function onMapClick(e) {
  popup.setLatLng(e.latlng)
    .setContent("You selected here: " + e.latlng.toString())
    .openOn(ZabGeomap);
}

function onEachFeature(feature, layer) {
  var popupContent = "";
  if (feature.properties && feature.properties.popupContent) {
    popupContent += feature.properties.popupContent;
  }
  layer.bindPopup(popupContent);
}

// Cria dinamicamente a referencia para o icone do host
function zbxImage(p_iconid, width, height) {
  width = typeof width !== 'undefined' ? width : 32;
  height = typeof height !== 'undefined' ? height : 32;
  return L.icon({
    iconUrl: (p_iconid === parseInt(p_iconid, 0) ? 'imgstore.php?iconid=' + p_iconid : "local/app/everyz/images/zpoi_" + p_iconid + ".png"),
    iconSize: [width, height],
    iconAnchor: [Math.round(width / 2), height],
    popupAnchor: [2, -38],
  });
}

function addCircle(lat, lon, radiusSize, fillColor, borderColor, opacity) {
  var fillColor = typeof fillColor !== 'undefined' ? fillColor : '#303';
  var borderColor = typeof borderColor !== 'undefined' ? borderColor : '';
  var opacity = typeof opacity !== 'undefined' ? opacity : 0.3;
  if (showCircles == 1) {
    L.circle([lat, lon], {
      color: borderColor,
      fillColor: fillColor,
      fillOpacity: opacity,
      radius: radiusSize
    }).addTo(ZabGeocircle).bindPopup(radiusSize + 'm');
  }
}

function addAlert(lat, lon, radiusSize, fillColor, borderColor, opacity, title) {
  var fillColor = typeof fillColor !== 'undefined' ? fillColor : '#303';
  var borderColor = typeof borderColor !== 'undefined' ? borderColor : '';
  var opacity = typeof opacity !== 'undefined' ? opacity : 0.2;
  var title = typeof title !== 'undefined' ? title : '';

  L.circle([lat, lon], {
    color: borderColor,
    fillColor: fillColor,
    fillOpacity: opacity,
    radius: radiusSize
  }).addTo(ZabGeoalert).bindPopup(title);

}

function addLine(from, to, popup, fillColor, weight, opacity, text) {
  var popup = typeof popup !== 'undefined' ? popup : '#303';
  var fillColor = typeof fillColor !== 'undefined' ? fillColor : '#000088';
  var weight = typeof weight !== 'undefined' ? weight : 6;
  var opacity = typeof opacity !== 'undefined' ? opacity : 1;
  if (showLines == 1) {
    tmp = new L.polyline([new L.LatLng(from[0], from[1]), new L.LatLng(to[0], to[1])], {
      color: fillColor,
      weight: weight,
      opacity: opacity
    });

    if (popup !== "") {
      //tmp.bindPopup(popup);
      //dynamicPopUp(tmp);
      tmp.bindTooltip(popup, {
        permanent: false,
        className: "my-label",
        offset: [0, 0]
      }).bindPopup(popup);
    }
    ZabGeomap.addLayer(tmp);
    // Add a arrow head
    var decorator = L.polylineDecorator(tmp, {
      patterns: [{
        offset: '100%',
        repeat: 0,
        symbol: L.Symbol.arrowHead({
          pixelSize: 15,
          polygon: false,
          pathOptions: {
            stroke: true,
            color: fillColor
          }
        })
      }]
    }).addTo(ZabGeomap);

    tmp.on('click', function() {
      ZabGeomap.removeLayer(tmp);
    });
    //            tmp.showExtremities('arrowM'); nao esta funcionando ainda
  }
}

function parse_html(html, args) {
  for (var key in args) {
    var re = new RegExp("<" + key + ">", 'g');
    html = html.replace(re, args[key]);
  }
  return html;
}

function actionButton(title, img, onclick, className) {
  if (className === undefined) {
    className = "everyzShortcutIMG";
  }
  return '<img class="' + className + '" hspace=10 vspace=10  title="' + title + '" src="local/app/everyz/images/' + img + '" onclick=\'' + onclick + '\'/>&nbsp;';
}

function popupHost(hostid, hostname, events, hostConn, extraButtons) {
  var hasEvent = events.length > 0;
  var buttonList = actionButton(geoTitleLatest, "zbxe-latest.png", 'javascript:hostLatest(' + hostid + ');') +
    actionButton(geoTitleTriggers, 'zbxe-' + (hasEvent ? "incident" : "ok") + '.png', 'javascript:hostIncidents(' + hostid + ');');
  var extraButtons = typeof extraButtons !== 'undefined' ? extraButtons : "";

  extraButtons = parse_html(extraButtons, {
    host: hostname,
    conn: hostConn,
    hostid: hostid
  });
  return parse_html(geoPopUpTemplate, {
    hostIdentify: geoHostWord,
    host: hostname,
    conn: hostConn,
    shortcuts: buttonList + extraButtons,
    editbutton: actionButton(geoTitleMetadata, "zbxe-geometadata.png", 'javascript:editHostMetadata(' + hostid + ');', "everyzEditIMG"),
    eventsList: events
  })
  //geoHostWord + ': <b>' + hostname + '</b><br>'
  /*
  +'<img class="everyzEditIMG" title="' + geoTitleMetadata + '" src="local/app/everyz/images/zbxe-geometadata.png" onclick=\'javascript:editHostMetadata('
  + hostid + ');\'/>&nbsp;<img class="everyzEditIMG" title="' + geoTitleLatest + '" src="local/app/everyz/images/zbxe-latest.png" onclick=\'javascript:hostLatest('
  + hostid + ');\'/>&nbsp;<img class="everyzEditIMG" title="' + geoTitleTriggers + '" src="local/app/everyz/images/zbxe-'
  + (hasEvent ? "incident" : "ok") + '.png" onclick=\'javascript:hostIncidents('
  + hostid + ');\'/>'
  */
  //+ events

  ;
}

function easterEgg(type, name, extra) {
  return '<div class="popUpEgg"><table><tr><td><img width="60px" height="60px" src="local/app/everyz/images/icon_' + type + '.png"/></td>' +
    '<td class="tdEgg">' +
    '<b>' + name + '</b>, very thank you!' +
    extra +
    '</td>' +
    '</tr></table></div>';
}

function easterEggAnimated() {
  // Easter Egg com os tradutores e desenvolvedores do EveryZ --------------------
  switch (eeCont) {
    case 1:
      addHost(-15.791246, -47.8932317, "adail", easterEgg("developer", "Adail Horst", "<br>For being crazy and decide to create me,<br> besides giving me a very charming name!  ;-)"));
      addHost(-3.736878, -38.5334797, "ari", easterEgg("developer", "Aristoteles", "<br>For code together the Zab-Geo!"));
      addLine([27.43419, -28.125], [-3.736878, -38.5334797], '', '#770077', 4, 0.8);
      addLine([27.43419, -28.125], [-15.791246, -47.8932317], '', '#FF6600', 4, 0.8);
      break;
    case 2:
      addHost(45.066836045, 7.63612707, "italy", easterEgg("translate", "Dimitri Bellini", "<br>For your help translating to<br>italian language!<br>http://quadrata.it/"));
      addLine([27.43419, -28.125], [45.066836045, 7.63612707], '', '#006600', 4, 0.8);
      break;
    case 3:
      addHost(43.633175, -79.470457, "canada", easterEgg("translate", "Shary Ann", "<br>For your help translating to<br>french language!"));
      addHost(56.952304, 24.111023, "riga", easterEgg("developer", "Zabbix", "<br>For create the Zabbix!"));
      addLine([27.43419, -28.125], [43.633175, -79.470457], '', '#CC0066', 4, 0.8);
      addLine([27.43419, -28.125], [56.952304, 24.111023], '', '#660000', 4, 0.8);
      break;
    case 4:
      addHost(48.857482, 2.2935243, "france", easterEgg("translate", "Steve Destivelle", "<br>For your help translating to<br>french language!"));
      addHost(-25.526181, -54.537174, "latinoware", easterEgg("latinoware", "Latinoware", "<br>For your contribution in use of <br> Free Software on Latin America!" +
        "<br>This is the best free software event<br> in the Americas, join US!!!<br><a href='http://latinoware.org/'>http://latinoware.org/</a>"));
      addLine([27.43419, -28.125], [48.857482, 2.2935243], '', '#000077', 4, 0.8);
      addLine([27.43419, -28.125], [-25.526181, -54.537174], '', '#000077', 4, 1);
      break;
    case 5:
      L.controlCredits({
        image: "local/app/everyz/images/zpoi_whynotwork.png",
        link: "http://www.everyz.org/docs",
        text: '<div id="everyzTopMenuInfo">' + easterEggInfo + '</div>',
        height: "64",
        width: "103"
      }).addTo(ZabGeomap).setPosition('topright');
      break;
    default:
      addHost(27.43419, -28.125, "everyz", easterEgg("developer", "EveryZ", "<br>For increase the Zabbix<br>native functionalities!" +
        "<br><a href='http://www.everyz.org'>www.everyz.org</a>"));
  }
  eeCont = eeCont + 1;
  if (eeCont < 6) {
    setTimeout(easterEggAnimated, 1000);
  }
}

function showEasterEgg() {
  setTimeout(easterEggAnimated, 1000);
}
