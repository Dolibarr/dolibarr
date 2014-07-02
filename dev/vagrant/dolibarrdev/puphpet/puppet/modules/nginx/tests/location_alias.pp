include nginx

nginx::resource::location { 'www.test.com-alias':
    ensure         => present,
    location       => '/some/url',
    location_alias => '/new/url/',
    vhost          => 'www.test.com',
}
