vcsrepo { '/tmp/vcstest-cvs-workspace-remote':
  ensure   => present,
  provider => cvs,
  source   => ':pserver:anonymous@cvs.sv.gnu.org:/sources/leetcvrt',
}
