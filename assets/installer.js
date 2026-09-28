/*
 * PowerNews – Web-Installer, Schritt „Website“: E-Mail-Versand.
 *
 * - Blendet die SMTP-Felder aus, solange „PHP-mail des Servers“ gewählt ist.
 * - Schlägt passend zur Verschlüsselung den üblichen Port vor (25, 587, 465),
 *   solange der Port nicht von Hand geändert wurde.
 *
 * Ohne JavaScript bleiben alle Felder sichtbar und der Installer funktioniert
 * genauso: Ein leerer Port ergibt denselben Vorschlag auf dem Server.
 * Eigene Datei statt Inline-Skript, weil die CSP aus der .htaccess nur Skripte
 * vom eigenen Server erlaubt.
 */
(function () {
  'use strict';

  var transport = document.getElementById('mail_transport');
  var fields = document.getElementById('smtp-fields');

  if (transport && fields) {
    var toggle = function () {
      fields.hidden = transport.value !== 'smtp';
    };
    transport.addEventListener('change', toggle);
    toggle();
  }

  var encryption = document.getElementById('smtp_encryption');
  var port = document.getElementById('smtp_port');

  if (!encryption || !port) {
    return;
  }

  var ports;

  try {
    ports = JSON.parse(port.getAttribute('data-default-ports') || '{}');
  } catch (error) {
    return;
  }

  var suggested = Object.keys(ports).map(function (key) {
    return String(ports[key]);
  });
  var isOwnValue = function () {
    var value = port.value.trim();

    return value !== '' && suggested.indexOf(value) === -1;
  };
  var edited = isOwnValue();

  port.addEventListener('input', function () {
    edited = isOwnValue();
  });
  encryption.addEventListener('change', function () {
    if (!edited && Object.prototype.hasOwnProperty.call(ports, encryption.value)) {
      port.value = String(ports[encryption.value]);
    }
  });
})();
