<?php
use PHPMailer\PHPMailer\PHPMailer;

class Mailer {

  private static function smtpConfig(PHPMailer $mail): void {
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)$_ENV['MAIL_PORT'];
    $mail->CharSet    = 'UTF-8';
  }

  public static function enviarRecuperacion(string $toEmail, string $toName, string $resetLink): void {
    $mail = new PHPMailer(true);
    self::smtpConfig($mail);

    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($toEmail, $toName ?: $toEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Recuperar contraseña — válido 30 minutos';

    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Restablecer contraseña</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500&display=swap');
  </style>
</head>
<body style="margin:0;padding:0;background-color:#070c1a;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#070c1a" style="background-color:#070c1a;">
  <tr>
    <td align="center" style="padding:48px 16px 40px;">

      <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="max-width:580px;width:100%;">

        <!-- Tarjeta -->
        <tr>
          <td bgcolor="#0d1829" style="background-color:#0d1829;border-radius:16px;border:1px solid #162538;overflow:hidden;">

            <!-- Franja naranja superior -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
              <tr>
                <td height="3" style="height:3px;line-height:3px;background:linear-gradient(90deg,#c94510 0%,#F15A22 45%,#ff7033 100%);font-size:0;mso-line-height-rule:exactly;">&nbsp;</td>
              </tr>
            </table>

            <!-- Cuerpo -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">

              <!-- Marca -->
              <tr>
                <td align="center" style="padding:26px 0 4px;">
                  <span style="font-family:'Syne',Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#2e3f5c;">
                    &#9670;&nbsp;&nbsp;AlertHub
                  </span>
                </td>
              </tr>

              <tr>
                <td style="padding:28px 52px 48px;">

                  <!-- Título -->
                  <h1 style="margin:0 0 16px;font-family:'Syne',Arial,Helvetica,sans-serif;font-size:28px;font-weight:800;color:#f1f5f9;text-align:center;letter-spacing:-0.01em;line-height:1.2;">
                    Restablece tu contraseña
                  </h1>

                  <!-- Descripción -->
                  <p style="margin:0 0 38px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.8;color:#4d6480;text-align:center;">
                    Recibimos una solicitud para restablecer la contraseña de tu cuenta.<br>
                    Si fuiste tú, usa el botón de abajo para crear una nueva contraseña.
                  </p>

                  <!-- Botón CTA -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                      <td align="center" style="padding-bottom:32px;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$safeLink}" style="height:50px;v-text-anchor:middle;width:260px;" arcsize="22%" stroke="f" fillcolor="#F15A22">
                          <w:anchorlock/>
                          <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Crear nueva contraseña</center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-->
                        <a href="{$safeLink}" style="display:inline-block;padding:16px 42px;background:linear-gradient(135deg,#F15A22 0%,#c94510 100%);border-radius:11px;font-family:'Syne',Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#ffffff;text-decoration:none;mso-hide:all;">
                          Crear nueva contraseña
                        </a>
                        <!--<![endif]-->
                      </td>
                    </tr>
                  </table>

                  <!-- Aviso de expiración -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                      <td bgcolor="#12100a" style="background-color:#12100a;border:1px solid #2a1d09;border-radius:9px;padding:13px 20px;text-align:center;">
                        <span style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13px;color:#5c4019;">Este enlace expira en&nbsp;</span><strong style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13px;color:#f59e0b;">30&nbsp;minutos</strong>
                      </td>
                    </tr>
                  </table>

                  <!-- Separador -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                      <td style="padding:32px 0 26px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                          <tr>
                            <td bgcolor="#0e1c30" style="background-color:#0e1c30;height:1px;font-size:1px;line-height:1px;mso-line-height-rule:exactly;">&nbsp;</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  <!-- Nota de seguridad -->
                  <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;color:#243347;text-align:center;">
                    Si no solicitaste este cambio, puedes ignorar este correo con seguridad.<br>
                    Tu contraseña permanecerá sin modificaciones.
                  </p>

                </td>
              </tr>
            </table>

          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;

    $mail->AltBody =
      "AlertHub — Gestor de Incidencias\n\n" .
      "RESTABLECER CONTRASEÑA\n" .
      str_repeat('-', 38) . "\n\n" .
      "Recibimos una solicitud para restablecer la contraseña de tu cuenta.\n" .
      "Si fuiste tú, usa el siguiente enlace (válido 30 minutos):\n\n" .
      $resetLink . "\n\n" .
      str_repeat('-', 38) . "\n" .
      "Si no solicitaste este cambio, puedes ignorar este correo.\n" .
      "Tu contraseña permanecerá sin modificaciones.";

    $mail->send();
  }

  public static function enviarSoporte(string $fromName, string $fromEmail, string $subject, string $body, array $adjuntos = []): void {
    $mail = new PHPMailer(true);
    self::smtpConfig($mail);

    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress('gestiondeincidenciasdaw@gmail.com', 'Soporte AlertHub');
    $mail->addReplyTo($fromEmail, $fromName);
    $mail->isHTML(true);
    $mail->Subject = '[Soporte] ' . $subject;

    $safeNombre  = htmlspecialchars($fromName,  ENT_QUOTES, 'UTF-8');
    $safeEmail   = htmlspecialchars($fromEmail, ENT_QUOTES, 'UTF-8');
    $safeAsunto  = htmlspecialchars($subject,   ENT_QUOTES, 'UTF-8');
    $safeMensaje = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

    // Bloque HTML de adjuntos (vacío si no hay ninguno)
    $adjuntosHtml = '';
    $altAdjuntos  = '';
    if (!empty($adjuntos)) {
      $count = count($adjuntos);
      $label = $count === 1 ? '1 archivo adjunto' : "{$count} archivos adjuntos";
      $adjuntosHtml  = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:20px;">';
      $adjuntosHtml .= '<tr><td>';
      $adjuntosHtml .= '<p style="margin:0 0 8px;font-family:\'Inter\',Arial,Helvetica,sans-serif;font-size:11px;color:#2e3f5c;text-transform:uppercase;letter-spacing:0.08em;">' . $label . '</p>';
      $altAdjuntos   = "\nArchivos adjuntos:\n";
      foreach ($adjuntos as $adj) {
        $safeName     = htmlspecialchars($adj['name'], ENT_QUOTES, 'UTF-8');
        $adjuntosHtml .= '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:5px;">';
        $adjuntosHtml .= '<tr><td bgcolor="#0a1526" style="background-color:#0a1526;border:1px solid #132040;border-radius:7px;padding:9px 14px;">';
        $adjuntosHtml .= '<span style="font-family:\'Inter\',Arial,Helvetica,sans-serif;font-size:13px;color:#7a9bc0;">&#128206;&nbsp;' . $safeName . '</span>';
        $adjuntosHtml .= '</td></tr></table>';
        $altAdjuntos  .= '  - ' . $adj['name'] . "\n";
      }
      $adjuntosHtml .= '</td></tr></table>';
    }

    $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mensaje de soporte</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500&display=swap');
  </style>
</head>
<body style="margin:0;padding:0;background-color:#070c1a;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#070c1a" style="background-color:#070c1a;">
  <tr>
    <td align="center" style="padding:48px 16px 40px;">

      <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="max-width:580px;width:100%;">

        <!-- Tarjeta -->
        <tr>
          <td bgcolor="#0d1829" style="background-color:#0d1829;border-radius:16px;border:1px solid #162538;overflow:hidden;">

            <!-- Franja índigo superior -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
              <tr>
                <td height="3" style="height:3px;line-height:3px;background:linear-gradient(90deg,#3730a3 0%,#4f46e5 50%,#818cf8 100%);font-size:0;mso-line-height-rule:exactly;">&nbsp;</td>
              </tr>
            </table>

            <!-- Cuerpo -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">

              <!-- Marca -->
              <tr>
                <td align="center" style="padding:26px 0 4px;">
                  <span style="font-family:'Syne',Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#2e3f5c;">
                    &#9670;&nbsp;&nbsp;AlertHub
                  </span>
                </td>
              </tr>

              <tr>
                <td style="padding:20px 52px 44px;">

                  <!-- Título -->
                  <h1 style="margin:0 0 6px;font-family:'Syne',Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;color:#f1f5f9;letter-spacing:-0.01em;line-height:1.2;">
                    Nuevo mensaje de soporte
                  </h1>
                  <p style="margin:0 0 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13px;color:#2e3f5c;">
                    Un usuario ha enviado un mensaje a través del formulario de soporte.
                  </p>

                  <!-- Metadatos del remitente -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
                    <tr>
                      <td bgcolor="#0a1526" style="background-color:#0a1526;border-radius:10px;border:1px solid #132040;padding:18px 22px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                          <tr>
                            <td style="padding:5px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;color:#2e3f5c;text-transform:uppercase;letter-spacing:0.08em;width:64px;vertical-align:top;">
                              De
                            </td>
                            <td style="padding:5px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;color:#e2e8f0;">
                              {$safeNombre}
                            </td>
                          </tr>
                          <tr>
                            <td style="padding:5px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;color:#2e3f5c;text-transform:uppercase;letter-spacing:0.08em;vertical-align:top;">
                              Email
                            </td>
                            <td style="padding:5px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;color:#818cf8;">
                              {$safeEmail}
                            </td>
                          </tr>
                          <tr>
                            <td style="padding:5px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;color:#2e3f5c;text-transform:uppercase;letter-spacing:0.08em;vertical-align:top;">
                              Asunto
                            </td>
                            <td style="padding:5px 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;font-weight:500;color:#e2e8f0;">
                              {$safeAsunto}
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  <!-- Bloque del mensaje con acento índigo -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                      <td width="3" bgcolor="#4f46e5" style="background-color:#4f46e5;border-radius:3px 0 0 3px;width:3px;">&nbsp;</td>
                      <td bgcolor="#0a1526" style="background-color:#0a1526;border-radius:0 10px 10px 0;border:1px solid #132040;border-left:0;padding:18px 22px;">
                        <p style="margin:0 0 10px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;color:#2e3f5c;text-transform:uppercase;letter-spacing:0.08em;">Mensaje</p>
                        <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.75;color:#7a9bc0;">
                          {$safeMensaje}
                        </p>
                      </td>
                    </tr>
                  </table>

                  <!-- Adjuntos (solo si los hay) -->
                  {$adjuntosHtml}

                  <!-- Separador -->
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                      <td style="padding:28px 0 22px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                          <tr>
                            <td bgcolor="#0e1c30" style="background-color:#0e1c30;height:1px;font-size:1px;line-height:1px;mso-line-height-rule:exactly;">&nbsp;</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  <!-- Nota de respuesta -->
                  <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;color:#243347;text-align:center;">
                    Puedes responder directamente a este correo para contactar con el remitente.
                  </p>

                </td>
              </tr>
            </table>

          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;

    $mail->AltBody =
      "AlertHub — Gestor de Incidencias\n\n" .
      "NUEVO MENSAJE DE SOPORTE\n" .
      str_repeat('-', 38) . "\n\n" .
      "De: {$fromName} <{$fromEmail}>\n" .
      "Asunto: {$subject}\n\n" .
      "Mensaje:\n" .
      $body . "\n" .
      $altAdjuntos . "\n" .
      str_repeat('-', 38) . "\n" .
      "Puedes responder directamente a este correo para contactar con el remitente.";

    foreach ($adjuntos as $adj) {
      if (is_file($adj['tmp_name'])) {
        $mail->addAttachment($adj['tmp_name'], $adj['name']);
      }
    }

    $mail->send();
  }
}
