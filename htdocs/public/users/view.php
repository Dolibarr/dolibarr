<?php
/* Copyright (C) 2020-2022	Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/public/users/view.php
 *       \ingroup    user
 *       \brief      Public file to user profile
 */

if (!defined('NOLOGIN')) {
  define("NOLOGIN", 1); // Public page.
}
if (!defined('NOCSRFCHECK')) {
  define("NOCSRFCHECK", 1);
}
if (!defined('NOIPCHECK')) {
  define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
  define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/vcard.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 */

$langs->loadLangs(array("companies", "other", "recruitment"));

// Params
$action     = GETPOST('action', 'aZ09');
$mode       = GETPOST('mode', 'aZ09');
$cancel     = GETPOST('cancel', 'alpha');
$backtopage = '';
$id         = GETPOSTINT('id');
$securekey  = GETPOST('securekey', 'alpha');
$suffix     = GETPOST('suffix');

$object = new User($db);
$object->fetch($id, '', '', 1);

// Define $urlwithroot
$urlwithroot = DOL_MAIN_URL_ROOT;

// Security check
global $conf;
$encodedsecurekey = dol_hash($conf->file->instance_unique_id . 'uservirtualcard' . $object->id . '-' . $object->login, 'md5');
if ($encodedsecurekey != $securekey) {
  httponly_accessforbidden('Bad value for securitykey or public profile not enabled');
}
if (!getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
  httponly_accessforbidden('Bad value for securitykey or public profile not enabled');
}

/*
 * Actions
 */
if ($cancel) {
  if (!empty($backtopage)) {
    header("Location: " . $backtopage);
    exit;
  }
  $action = 'view';
}

/*
 * View / vCard
 */
$v = new vCard();

$company = $mysoc;

$modulepart = 'userphotopublic';
$dir = $conf->user->dir_output;

// --- USER PHOTO URLS -------------------------------------------------------
$logo = '';
$logosmall = '';
if (!empty($object->photo)) {
  if (dolIsAllowedForPreview($object->photo)) {
    $logosmall = get_exdir(0, 0, 0, 0, $object, 'user') . 'photos/' . getImageFileNameForSize($object->photo, '_small');
    $logo      = get_exdir(0, 0, 0, 0, $object, 'user') . 'photos/' . $object->photo;
  }
}
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($dir . '/' . $logosmall)) {
  $urllogo     = DOL_URL_ROOT . '/viewimage.php?modulepart=' . urlencode($modulepart) . ($conf->entity > 1 ? '&amp;entity=' . $conf->entity : '') . '&amp;securekey=' . urlencode($securekey) . '&amp;file=' . urlencode($logosmall);
  $urllogofull = $dolibarr_main_url_root . '/viewimage.php?modulepart=' . $modulepart . ($conf->entity > 1 ? '&entity=' . $conf->entity : '') . '&securekey=' . urlencode($securekey) . '&file=' . urlencode($logosmall);
} elseif (!empty($logo) && is_readable($dir . '/' . $logo)) {
  $urllogo     = DOL_URL_ROOT . '/viewimage.php?modulepart=' . urlencode($modulepart) . ($conf->entity > 1 ? '&amp;entity=' . $conf->entity : '') . '&amp;securekey=' . urlencode($securekey) . '&amp;file=' . urlencode($logo);
  $urllogofull = $dolibarr_main_url_root . '/viewimage.php?modulepart=' . $modulepart . ($conf->entity > 1 ? '&entity=' . $conf->entity : '') . '&securekey=' . urlencode($securekey) . '&file=' . urlencode($logo);
}
if (getDolUserInt('USER_PUBLIC_HIDE_PHOTO', 0, $object)) {
  $logo = $logosmall = $urllogo = $urllogofull = '';
}
// guardamos URL de foto de usuario para no pisarla más abajo
$userPhotoUrlFull = $urllogofull;

// --- PRIVACY FILTERS -------------------------------------------------------
if (getDolUserInt('USER_PUBLIC_HIDE_JOBPOSITION', 0, $object)) $object->job = '';
if (getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object)) $object->email = '';
if (getDolUserInt('USER_PUBLIC_HIDE_OFFICE_PHONE', 0, $object)) $object->office_phone = '';
if (getDolUserInt('USER_PUBLIC_HIDE_OFFICE_FAX', 0, $object)) $object->office_fax = '';
if (getDolUserInt('USER_PUBLIC_HIDE_USER_MOBILE', 0, $object)) $object->user_mobile = '';
if (getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS', 0, $object)) {
  $object->socialnetworks = [];
} else {
  $listofnetworks = $object->socialnetworks;
  if (!empty($listofnetworks)) {
    foreach ($listofnetworks as $key => $networkVal) {
      if (getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS_' . strtoupper($key), 0, $object)) {
        unset($object->socialnetworks[$key]);
      }
    }
  }
}
// Birth/Address off by default
if (!getDolUserInt('USER_PUBLIC_SHOW_BIRTH', 0, $object)) $object->birth = null;
if (!getDolUserInt('USER_PUBLIC_SHOW_ADDRESS', 0, $object)) {
  $object->address = '';
  $object->town = '';
  $object->zip = '';
  $object->state = '';
  $object->country = '';
}

// Company privacy
if (getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object)) $company = null;
if (getDolUserInt('SOCIETE_PUBLIC_HIDE_EMAIL', 0, $object)) $mysoc->email = '';
if (getDolUserInt('SOCIETE_PUBLIC_HIDE_OFFICE_PHONE', 0, $object)) $mysoc->phone = '';
if (getDolUserInt('SOCIETE_PUBLIC_HIDE_OFFICE_FAX', 0, $object)) $mysoc->fax = '';
if (getDolUserInt('SOCIETE_PUBLIC_HIDE_URL', 0, $object)) $mysoc->url = '';
if (getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS_BUSINESS', 0, $object) && is_object($company)) {
  $company->socialnetworks = [];
} else {
  $listofnetworks = $mysoc->socialnetworks;
  if (!empty($listofnetworks)) {
    foreach ($listofnetworks as $key => $networkVal) {
      if (getDolUserInt('SOCIETE_PUBLIC_HIDE_SOCIALNETWORKS_' . strtoupper($key), 0, $object)) {
        unset($mysoc->socialnetworks[$key]);
      }
    }
  }
}

// --- vCard direct output ---------------------------------------------------
if ($mode == 'vcard') {
  $output = $v->buildVCardString($object, $company, $langs, $userPhotoUrlFull);
  $filename = trim(urldecode($v->getFileName()));
  $filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
  top_httphead('text/vcard; name="' . $filename . '"');
  header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
  header("Content-Length: " . dol_strlen($output));
  header("Connection: close");
  print $output;
  $db->close();
  exit;
}

// Optional external CSS via conf
$head = '';
if (getDolGlobalString('MAIN_USER_PROFILE_CSS_URL')) {
  $head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_USER_PROFILE_CSS_URL') . '?lang=' . $langs->defaultlang . '">' . "\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

if (!getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
  $langs->load("errors");
  print '<div class="error">' . $langs->trans('ErrorPublicInterfaceNotEnabled') . '</div>';
  $db->close();
  exit();
}

$arrayofjs = array();
$arrayofcss = array();

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '') . '<div>';
llxHeader(
  $head,
  $object->getFullName($langs) . ' - ' . $langs->trans("PublicVirtualCard"),
  '',
  '',
  0,
  0,
  $arrayofjs,
  $arrayofcss,
  '',
  'onlinepaymentbody' . (GETPOST('mode') == 'preview' ? ' scalepreview cursorpointer virtualcardpreview' : ''),
  $replacemainarea,
  1,
  1
);


// === Fondo global: usar la misma imagen configurada en "Página de login" ===
function resolveLoginBgPublicUrl($conf, $dolibarr_main_url_root)
{
  $val = getDolGlobalString('MAIN_LOGIN_BACKGROUND', '');
  if (empty($val)) return '';

  // Caso 1: URL absoluta
  if (preg_match('#^https?://#i', $val)) return $val;

  // Normaliza (quitamos primer "/")
  $rel = ltrim($val, '/');

  // Caso 2: imagen subida a Documentos -> medias/...
  if (preg_match('#^medias/#i', $rel)) {
    return $dolibarr_main_url_root
      . '/viewimage.php?modulepart=medias'
      . ($conf->entity > 1 ? '&entity=' . $conf->entity : '')
      . '&file=' . urlencode($rel);
  }

  // Caso 3: dieron ruta dentro de documents (DOL_DATA_ROOT)
  if (!empty($conf->dolibarr_main_data_root) && strpos($val, $conf->dolibarr_main_data_root) === 0) {
    $relfile = preg_replace('#^' . preg_quote($conf->dolibarr_main_data_root, '#') . '/#', '', $val);
    return $dolibarr_main_url_root
      . '/viewimage.php?modulepart=medias'
      . ($conf->entity > 1 ? '&entity=' . $conf->entity : '')
      . '&file=' . urlencode($relfile);
  }

  // Caso 4: archivo colgado de htdocs (ruta relativa)
  return dol_buildpath($rel, 1);
}

$loginBgUrl = resolveLoginBgPublicUrl($conf, $dolibarr_main_url_root);

// Pinta el fondo detrás de la tarjeta
if (!empty($loginBgUrl)) {
  print '<style>
    body.onlinepaymentbody{ background: none !important; }
    body.onlinepaymentbody::before{
        content:""; position: fixed; inset: 0; z-index: -1;
        background-image:
            linear-gradient(rgba(255,255,255,.65), rgba(255,255,255,.65)),
            url("' . dol_escape_htmltag($loginBgUrl) . '");
        background-size: cover; background-position: center;
        background-repeat: no-repeat; background-attachment: fixed;
    }
    @media (prefers-color-scheme: dark){
        body.onlinepaymentbody::before{
            background-image:
                linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)),
                url("' . dol_escape_htmltag($loginBgUrl) . '");
        }
    }
    </style>';
}
// Si no hay imagen propia para el hero, usa la del login
if (empty($heroImg) && !empty($loginBgUrl)) $heroImg = $loginBgUrl;



// -------------------- MODERN UI CSS --------------------
print '
<style>
:root{
  --card-bg: rgba(255,255,255,0.75);
  --card-bg-dark: rgba(22,22,22,0.65);
  --border: rgba(0,0,0,0.08);
  --border-dark: rgba(255,255,255,0.12);
  --shadow: 0 10px 30px rgba(0,0,0,0.12);
  --radius: 16px;
  --radius-lg: 22px;
  --accent: #04b5d4;
  --accent-2: #22c55e;
  --text-muted: #6b7280;
}
@media (prefers-color-scheme: dark){
  :root{ --text-muted:#9CA3AF; }
}
* { box-sizing:border-box; }
.public-wrapper{ display:flex; justify-content:center; padding:32px 16px; }
.public-card{
  width:min(980px, 100%);
  background: var(--card-bg);
  backdrop-filter: blur(10px);
  border:1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow:hidden;
}
@media (prefers-color-scheme: dark){
  .public-card{ background:var(--card-bg-dark); border-color:var(--border-dark); }
}
.public-hero{
  position:relative; height:160px;
  background: linear-gradient(120deg, var(--accent) 0%, #028DA4 100%);
}
.public-hero.hasimg{ background: none; }
.public-hero img{
  position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.9;
}
.public-header{
  display:flex; gap:16px; align-items:flex-end;
  padding: 0 24px 16px 24px; transform: translateY(-50%);
}
.avatar{
  width:120px; height:120px; border-radius:50%;
  border:4px solid #fff; overflow:hidden; flex: none;
  box-shadow: 0 6px 16px rgba(0,0,0,0.2);
  background:#f0f2f5;
}
@media (prefers-color-scheme: dark){
  .avatar{ border-color:#111827; background:#111827; }
}
.avatar img{ width:100%; height:100%; object-fit:cover; }
.user-meta{ padding-bottom:6px; }
.user-name{ font-size: clamp(20px, 3.5vw, 28px); font-weight:700; line-height:1.15; }
.user-role{ font-size:14px; color:var(--text-muted); margin-top:4px; }
.user-company{ font-size:15px; font-weight:600; margin-top:6px; }

.public-body{
  padding: 8px 24px 24px 24px; margin-top:-48px;
}
.grid{
  display:grid; grid-template-columns: 1fr; gap:24px;
}
@media (min-width: 900px){
  .grid{ grid-template-columns: 1.6fr 1fr; }
}
.section{
  background: rgba(255,255,255,0.6);
  border:1px solid var(--border);
  border-radius: var(--radius);
  padding:16px;
}
@media (prefers-color-scheme: dark){
  .section{ background: rgba(17,24,39,0.6); border-color:var(--border-dark); }
}
.section-title{
  font-weight:700; margin-bottom:12px; display:flex; align-items:center; gap:8px;
}
.chips{ display:flex; flex-wrap:wrap; gap:8px; }
.chip{
  display:flex; align-items:center; gap:6px;
  padding:8px 10px; border:1px solid var(--border);
  border-radius:999px; font-size:14px; background:#fff;
}
@media (prefers-color-scheme: dark){
  .chip{ background:#0b1220; border-color:var(--border-dark); }
}
.chip .pictofixedwidth{ margin-right:4px; }
.qrbox{
  display:flex; justify-content:center; align-items:center;
  border-radius: var(--radius); overflow:hidden; border:1px dashed var(--border);
  background:#fff; padding:8px;
}
@media (prefers-color-scheme: dark){
  .qrbox{ background:#0b1220; border-color:var(--border-dark); }
}
.company-card{ display:grid; grid-template-columns: 100px 1fr; gap:16px; align-items:center; }
.company-logo img{ width:100px; height:100px; object-fit:contain; border-radius:12px; background:#fff; border:1px solid var(--border); }
@media (prefers-color-scheme: dark){
  .company-logo img{ background:#111827; border-color:var(--border-dark); }
}
.cta{
  display:flex; justify-content:center; padding:16px; border-top:1px solid var(--border);
  background: linear-gradient(180deg, transparent, rgba(0,0,0,0.02));
}
@media (prefers-color-scheme: dark){
  .cta{ border-color:var(--border-dark); }
}
.btn{
  display:inline-flex; align-items:center; gap:8px; font-weight:700;
  padding:12px 18px; border-radius:12px; text-decoration:none;
  background: var(--accent); color:#fff !important; transition: transform .05s ease;
}
.btn:hover{ transform: translateY(-1px); }
.more{ white-space:pre-wrap; line-height:1.5; font-size:15px; color:var(--text-muted); }
.small-note{ font-size:12px; color:var(--text-muted); text-align:center; margin-top:6px; }
</style>
';

// -------------- Begin HTML --------------

$bg = getDolGlobalString('MAIN_LOGIN_BACKGROUND');
$background_url = '';

if (!empty($bg)) {
  // genera la URL pública a partir de la constante
  $background_url = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=' . urlencode('logos/' . $bg);
}

// contenedor de fondo
print '<div class="login-background" style="min-height:100vh; width:100%; background-image:url(\'' . $background_url . '\'); background-size:cover; background-position:center;">';

print '<span id="dolpaymentspan"></span>' . "\n";
print '<div class="public-wrapper">' . "\n";

print '<form id="dolpaymentform" class="public-card" name="paymentform" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
print '<input type="hidden" name="action" value="dosubmit">' . "\n";
print '<input type="hidden" name="securekey" value="' . $securekey . '">' . "\n";
print '<input type="hidden" name="entity" value="' . $conf->entity . '" />' . "\n";

$heroImg = getDolGlobalString('USER_IMAGE_PUBLIC_INTERFACE');
$heroClass = $heroImg ? 'public-hero hasimg' : 'public-hero';

// HERO

if (!function_exists('kp_color_or_default')) {
  function kp_color_or_default($val, $def) {
    $v = trim((string) $val);
    if ($v === '') return $def;
    // acepta #RGB o #RRGGBB o palabras CSS (ej. 'transparent')
    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v) || preg_match('/^[a-zA-Z]+$/', $v)) return $v;
    return $def;
  }
}
$grad1 = kp_color_or_default(getDolGlobalString('VCARD_GRADIENT_COLOR1', ''), '#0aa2b0');
$grad2 = kp_color_or_default(getDolGlobalString('VCARD_GRADIENT_COLOR2', ''), '#077f92');
$grad3 = kp_color_or_default(getDolGlobalString('VCARD_GRADIENT_COLOR3', ''), '#90e0ff');
$grad4 = kp_color_or_default(getDolGlobalString('VCARD_GRADIENT_COLOR4', ''), '#ffcb57');
// Read gradient colors from global constants, fallback to defaults
print '<style>
  /* Variables comunes para héroe y canvas */
  :root{
    --vcard-grad-1: '.$grad1.';
    --vcard-grad-2: '.$grad2.';
    --vcard-grad-3: '.$grad3.';
    --vcard-grad-4: '.$grad4.';
  }

  /* Sobrescribe el fallback del héroe para que use las constantes */
  .public-hero{
    background: linear-gradient(120deg, var(--vcard-grad-1) 0%, var(--vcard-grad-2) 100%) !important;
  }

  /* Canvas del gradiente (usado por tu librería Gradient) */
  .hero-gradient{position:relative; height:220px; border-radius:20px 20px 0 0; overflow:hidden;}
  #gradient-canvas{
    position:absolute; inset:0; width:100%; height:100%;
    --gradient-color-1: var(--vcard-grad-1);
    --gradient-color-2: var(--vcard-grad-2);
    --gradient-color-3: var(--vcard-grad-3);
    --gradient-color-4: var(--vcard-grad-4);
    z-index:0;
  }
</style>';



/* ====== HERO con GRADIENT CANVAS ====== */
print '<div class="' . $heroClass . ' hero-gradient">';
print '  <canvas id="gradient-canvas" data-js-darken-top></canvas>'; // canvas del gradient
print '</div>';

/* ====== HEADER: avatar izquierda + chip blanco derecha (sin empresa) ====== */
print '<div class="public-header">';

/* Avatar (más grande y con aro blanco/sombra) */
print '  <div class="avatar" style="flex:0 0 auto;">';
if ($userPhotoUrlFull) {
  print '    <img src="' . $userPhotoUrlFull . '" alt="avatar" >';
}
print '  </div>';

/* Chip blanco (Nombre + Puesto) ligeramente más abajo */
print '  <div style="flex:1;">';
print '    <div style="
      background:#fff; border-radius:22px; padding:16px 24px;
      box-shadow:0 10px 24px rgba(0,0,0,0.12); width:100%;
      min-height:90px; display:flex; flex-direction:column; justify-content:center;
      transform:translateY(6px);">';

print '      <div style="font-weight:700; font-size:20px; color:#0b7e8f; line-height:1.25; margin:2px 0 2px;">'
  . dol_escape_htmltag($object->getFullName($langs)) . '</div>';

if ($object->job && !getDolUserInt('USER_PUBLIC_HIDE_JOBPOSITION', 0, $object)) {
  print '      <div style="font-size:15px; color:#555; line-height:1.35; margin-top:6px;">'
    . dol_escape_htmltag($object->job) . '</div>';
}

print '    </div>';
print '  </div>'; // fin chip
print '</div>';   // fin public-header

/* ====== INIT del gradient (usa tu clase Gradient) ====== */
print '<script>
  (function(){
    if(typeof Gradient !== "undefined"){
      var g = new Gradient();
      g.initGradient("#gradient-canvas");
    } else {
      console.warn("Gradient no definido: asegúrate de incluir el script del gradient antes de este bloque.");
    }
  })();
</script>';


// Build URL for vCard
$urlforqrcode = $object->getOnlineVirtualCardUrl('vcard');
$socialnetworksdict = getArrayOfSocialNetworks();

// Build USER CONTACT chips
$usersection = '';
if ($object->email && !getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object)) {
  $usersection .= '<span class="chip">' . dol_print_email($object->email, 0, 0, 1, 0, 1, 1) . '</span>';
}
if ($object->url && !getDolUserInt('USER_PUBLIC_HIDE_URL', 0, $object)) {
  $usersection .= '<span class="chip">' . img_picto('', 'globe', 'class="pictofixedwidth"') . dol_print_url($object->url ?? '', '_blank', 0, 0, '') . '</span>';
}
if ($object->office_phone && !getDolUserInt('USER_PUBLIC_HIDE_OFFICE_PHONE', 0, $object)) {
  $usersection .= '<span class="chip">' . img_picto('', 'phone', 'class="pictofixedwidth"') . dol_print_phone($object->office_phone, $object->country_code, 0, $mysoc->id, 'tel', ' ', '', '') . '</span>';
}
if ($object->office_fax && !getDolUserInt('USER_PUBLIC_HIDE_OFFICE_FAX', 0, $object)) {
  $usersection .= '<span class="chip">' . img_picto('', 'phoning_fax', 'class="pictofixedwidth"') . dol_print_phone($object->office_fax, $object->country_code, 0, $mysoc->id, 'fax', ' ', '', '') . '</span>';
}
if ($object->user_mobile && !getDolUserInt('USER_PUBLIC_HIDE_USER_MOBILE', 0, $object)) {
  $usersection .= '<span class="chip">' . img_picto('', 'phoning_mobile', 'class="pictofixedwidth"') . dol_print_phone($object->user_mobile, $object->country_code, 0, $mysoc->id, 'tel', ' ', '', '') . '</span>';
}
if (getDolUserInt('USER_PUBLIC_SHOW_BIRTH', 0, $object) && !is_null($object->birth)) {
  $usersection .= '<span class="chip">' . img_picto('', 'calendar', 'class="pictofixedwidth"') . dol_print_date($object->birth) . '</span>';
}
if (getDolUserInt('USER_PUBLIC_SHOW_ADDRESS', 0, $object) && $object->address) {
  $usersection .= '<span class="chip">' . img_picto('', 'state', 'class="pictofixedwidth"') . dol_print_address(dol_format_address($object, 0, "\n", $langs), 'map', 'user', $object->id, 1) . '</span>';
}
// Social networks
if (!empty($object->socialnetworks) && is_array($object->socialnetworks)) {
  if (!getDolUserString('USER_PUBLIC_HIDE_SOCIALNETWORKS', 0, $object)) {
    foreach ($object->socialnetworks as $key => $value) {
      if (!getDolUserString('USER_HIDE_SOCIALNETWORK_' . strtoupper($key), 0, $object)) {
        $usersection .= '<span class="chip">' . dol_print_socialnetworks($value, 0, $object->id, strtolower($key), $socialnetworksdict) . '</span>';
      }
    }
  }
}

// QR CODE (same logic as original, pero lo guardamos como HTML)
$qrcodeHtml = '';
$showbarcode = GETPOST('nobarcode') ? 0 : 1;
if ($showbarcode) {
  $outdir = $conf->user->dir_temp;
  $filename = $v->buildVCardString($object, $company, $langs, '', $outdir);
  $encodedsecurekey = dol_hash($conf->file->instance_unique_id . 'uservirtualcard' . $object->id . '-' . $object->login, 'md5');
  $entity_qr = isModEnabled('multicompany') ? '&entity=' . (int) $conf->entity : '';
  $qrcodeHtml = '<div class="qrbox"><img style="max-width: 100%; height:auto" src="' .
    $dolibarr_main_url_root . '/viewimage.php?modulepart=barcode' . $entity_qr . '&generator=tcpdfbarcode&encoding=QRCODE&code=' .
    urlencode(basename($filename)) . '&securekey=' . $encodedsecurekey .
    '" alt="QR"></div><div class="small-note">' . $langs->trans("ScanToSaveContact") . '</div>';
}

// BODY GRID
print '<div class="public-body">';
print '  <div class="grid">';

// Left: Contact
print '    <div class="section">';
print '      <div class="section-title">' . img_picto('', 'user', '') . $langs->trans("Contact") . '</div>';
if ($usersection) {
  print '      <div class="chips">' . $usersection . '</div>';
} else {
  print '      <div class="more">' . $langs->trans("NoPublicData") . '</div>';
}
$textMore = getDolUserString('USER_PUBLIC_MORE', '', $object);
if (!empty($textMore)) {
  print '      <hr style="border:none; border-top:1px solid rgba(0,0,0,.08); margin:14px 0;">';
  print '      <div class="section-title">' . img_picto('', 'info', '') . $langs->trans("Description") . '</div>';
  print '      <div class="more">' . $textMore . '</div>';
}
print '    </div>';

// Right: QR
print '    <div class="section">';
print '      <div class="section-title">' . img_picto('', 'barcode', '') . $langs->trans("QR Code") . '</div>';
if ($qrcodeHtml) {
  print $qrcodeHtml;
} else {
  print '<div class="more">' . $langs->trans("NoData") . '</div>';
}
print '    </div>';

print '  </div>'; // grid


/* ====== SIGNATURE / FIRMA (HTML con estilos inline) ====== */
// Permite ocultarla por usuario con USER_PUBLIC_HIDE_SIGNATURE = 1
$hideSignature = getDolUserInt('USER_PUBLIC_HIDE_SIGNATURE', 0, $object);

if (!$hideSignature && !empty($object->signature)) {
  $sigHtml = (string) $object->signature;

  // Si no es HTML, escapamos y convertimos saltos de línea
  if (!function_exists('dol_textishtml') || !dol_textishtml($sigHtml)) {
    $sigHtml = dol_nl2br(dol_escape_htmltag($sigHtml), 1);
  } else {
    // Purificar si está disponible (recomendado)

    // Fallback mínimo: quitar <script> y handlers on*
    $sigHtml = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $sigHtml);
    $sigHtml = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $sigHtml);
    $sigHtml = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $sigHtml);
  }

  // Helper para añadir/merger estilos inline en tags concretos
  if (!function_exists('kp_add_inline_style')) {
    function kp_add_inline_style($html, $tag, $styleToAdd)
    {
      $pattern = '/<' . $tag . '\b([^>]*?)(\/?)>/i';
      return preg_replace_callback($pattern, function ($m) use ($tag, $styleToAdd) {
        $attrs = $m[1];
        $self  = $m[2]; // puede ser '/' si es <img .../>
        if (preg_match('/\sstyle\s*=\s*("|\')(.*?)\1/i', $attrs, $sm)) {
          $q       = $sm[1];
          $current = rtrim($sm[2]);
          if ($current !== '' && substr($current, -1) !== ';') $current .= ';';
          $new = $current . ' ' . $styleToAdd;
          $attrs = preg_replace('/\sstyle\s*=\s*("|\')(.*?)\1/i', ' style=' . $q . $new . $q, $attrs, 1);
        } else {
          $attrs .= ' style="' . $styleToAdd . '"';
        }
        return '<' . $tag . $attrs . $self . '>';
      }, $html);
    }
  }

  // Forzar que imágenes/tablas/enlaces sean responsivos y limpios (inline styles)
  $sigHtml = kp_add_inline_style($sigHtml, 'img',   'max-width:100%; height:auto;');
  $sigHtml = kp_add_inline_style($sigHtml, 'table', 'width:100%; border-collapse:collapse;');
  $sigHtml = kp_add_inline_style($sigHtml, 'a',     'text-decoration:none;');

  // Render
  print '      <hr style="border:none; border-top:1px solid rgba(0,0,0,.08); margin:14px 0;">';
  print '      <div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">'
    . img_picto("", "signature", 'style="opacity:.8;"') . ' ' . $langs->trans("Signature") . '</div>';
  print '      <div style="font-size:14.5px; line-height:1.6; background:#ffffff; border-radius:12px; padding:14px 16px; box-shadow:0 6px 18px rgba(0,0,0,.06); overflow-wrap:anywhere;">'
    . $sigHtml .
    '</div>';
}


// Company
if (!getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object)) {
  $companysection = '';

  if ($mysoc->email) {
    $companysection .= '<span class="chip">' . img_picto('', 'email', 'class="pictofixedwidth"') . dol_print_email($mysoc->email, 0, 0, 1) . '</span>';
  }
  if ($mysoc->url) {
    $companysection .= '<span class="chip">' . img_picto('', 'globe', 'class="pictofixedwidth"') . dol_print_url($mysoc->url, '_blank', 0, 0, '') . '</span>';
  }
  if ($mysoc->phone) {
    $companysection .= '<span class="chip">' . img_picto('', 'phone', 'class="pictofixedwidth"') . dol_print_phone($mysoc->phone, $mysoc->country_code, 0, $mysoc->id, 'tel', ' ', '', '') . '</span>';
  }
  if ($mysoc->fax) {
    $companysection .= '<span class="chip">' . img_picto('', 'phoning_fax', 'class="pictofixedwidth"') . dol_print_phone($mysoc->fax, $mysoc->country_code, 0, $mysoc->id, 'fax', ' ', '', '') . '</span>';
  }

  // Company logo urls (no sobreescribir la foto de usuario)

?>
  <style>
    /* --- Card de empresa (desktop/tablet) --- */
    .company-card {
      display: grid;
      grid-template-columns: 72px 1fr;
      gap: 12px;
      align-items: center;
    }

    .company-card .company-logo img {
      display: block;
      width: 72px;
      height: 72px;
      object-fit: contain;
    }

    /* Chips (desktop/tablet): varias por fila con salto ordenado */
    .company-card .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .company-card .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      background: #f3f4f6;
      /* ajusta según tu tema */
      max-width: 100%;
      line-height: 1.2;
    }

    /* Evitar desbordes por textos/URLs largos dentro del chip */
    .company-card .chip,
    .company-card .chip * {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow-wrap: anywhere;
    }

    /* --- Móvil --- */
    @media (max-width: 600px) {

      /* Card en una sola columna (logo arriba) */
      .company-card {
        grid-template-columns: 1fr;
        align-items: flex-start;
      }

      .company-card .company-logo img {
        width: 96px;
        /* opcional */
        height: auto;
      }

      /* Chips en UNA sola columna, a lo ancho */
      .company-card .chips {
        display: grid;
        grid-template-columns: 1fr;
        gap: 6px;
        width: 100%;
      }

      .company-card .chip {
        display: flex;
        width: 100%;
        white-space: normal;
        /* permite salto de línea si hace falta */
      }

      .company-card .chip a {
        flex: 1 1 auto;
        min-width: 0;
      }
    }
  </style>
<?php

  $logosmall_c = $mysoc->logo_squarred_small ? $mysoc->logo_squarred_small : $mysoc->logo_small;
  $logo_c      = $mysoc->logo_squarred ? $mysoc->logo_squarred : $mysoc->logo;
  $paramlogo   = 'ONLINE_USER_LOGO_' . $suffix;
  if (getDolGlobalString($paramlogo))      $logosmall_c = getDolGlobalString($paramlogo);
  elseif (getDolGlobalString('ONLINE_USER_LOGO')) $logosmall_c = getDolGlobalString('ONLINE_USER_LOGO');

  $companyLogoUrlFull = '';
  if (!empty($logosmall_c) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $logosmall_c)) {
    $companyLogoUrlFull = $dolibarr_main_url_root . '/viewimage.php?modulepart=mycompany' . ($conf->entity > 1 ? '&entity=' . $conf->entity : '') . '&file=' . urlencode('logos/thumbs/' . $logosmall_c);
  } elseif (!empty($logo_c) && is_readable($conf->mycompany->dir_output . '/logos/' . $logo_c)) {
    $companyLogoUrlFull = $dolibarr_main_url_root . '/viewimage.php?modulepart=mycompany' . ($conf->entity > 1 ? '&entity=' . $conf->entity : '') . '&file=' . urlencode('logos/' . $logo_c);
  }

  // Social networks
  if (!empty($mysoc->socialnetworks) && is_array($mysoc->socialnetworks) && count($mysoc->socialnetworks) > 0) {
    if (!getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS_BUSINESS', 0, $object)) {
      foreach ($mysoc->socialnetworks as $key => $value) {
        if (!getDolUserInt('SOCIETE_PUBLIC_HIDE_SOCIALNETWORKS_' . strtoupper($key), 0, $object)) {
          $companysection .= '<span class="chip">' . dol_print_socialnetworks($value, 0, $mysoc->id, $key, $socialnetworksdict) . '</span>';
        }
      }
    }
  }

  print '  <div class="section" style="margin-top:24px">';
  print '    <div class="section-title">' . img_picto('', 'company', '') . $langs->trans("Company") . '</div>';
  print '    <div class="company-card">';
  print '      <div class="company-logo">';
  if ($companyLogoUrlFull) {
    $openA = !empty($mysoc->url) ? '<a href="' . $mysoc->url . '" target="_blank" rel="noopener">' : '';
    $closeA = !empty($mysoc->url) ? '</a>' : '';
    print $openA . '<img src="' . $companyLogoUrlFull . '" alt="company-logo">' . $closeA;
  }
  print '      </div>';
  print '      <div>';
  if ($mysoc->name) {
    print '<div class="user-company" style="font-size:18px">' . dol_escape_htmltag($mysoc->name) . '</div>';
  }
  if ($companysection) {
    print '<div class="chips" style="margin-top:10px">' . $companysection . '</div>';
  }
  print '      </div>';
  print '    </div>';
  print '  </div>';
}

// CTA vCard
print '  <div class="cta">';
print '    <a class="btn" href="' . $urlforqrcode . '">' . img_picto($langs->trans("Download") . ' VCF', 'add') . ' ' . $langs->trans("Download") . ' VCF</a>';
print '  </div>';

print '</div>'; // public-body

print '</form>';
print '</div>'; // public-wrapper

// --------------END Begin HTML --------------

// cerrar el contenedor de fondo
print '</div>';

$fullexternaleurltovirtualcard = $object->getOnlineVirtualCardUrl('', 'external');

print '<script>
jQuery(function(){
  jQuery(".virtualcardpreview").on("click", function(e){
    e.preventDefault();
    window.open("' . $fullexternaleurltovirtualcard . '");
  });
});
</script>';





llxFooter('', 'public');
$db->close();
