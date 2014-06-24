# Wrapper class useful for hiera based deployments

class mongodb::replset(
  $sets = undef
) {

  if $sets {
    create_resources(mongodb_replset, $sets)
  }
}
