# private define
define apache::default_mods::load ($module = $title) {
  if defined("apache::mod::${module}") {
    include "::apache::mod::${module}"
  } else {
    ::apache::mod { $module: }
  }
}
