# The notify before should always come BEFORE all resources
# managed by the nginx class
# and the notify last should always come AFTER all resources
# managed by the nginx class.
node default {
  notify { 'before': }
  -> class { 'nginx': }
  -> notify { 'last': }
}
